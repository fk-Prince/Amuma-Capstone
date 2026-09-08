<?php

namespace App\Service;

use App\Http\Resources\PaymentReceiptResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PatientAccess;
use App\Models\Payment;
use App\Utils\AccommodationHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentService
{
    private function chargeCard(Client $client, float $amount, array $payload): array
    {
        if (empty($payload['token_id']) || empty($payload['authentication_id'])) {
            throw new Exception('Card details are required to pay online.', 422);
        }

        $response = Http::withOptions(['verify' => false])
            ->withBasicAuth(config('services.xendit.secret_key'), '')
            ->post('https://api.xendit.co/credit_card_charges', [
                'token_id' => $payload['token_id'],
                'authentication_id' => $payload['authentication_id'],
                'capture' => true,
                'descriptor' => 'balance',
                'currency' => 'PHP',
                'external_id' => (string) Str::uuid(),
                'amount' => $amount,
                'payer_email' => $client->user?->email,
                'payment_methods' => ['CREDIT-CARD'],
                'metadata' => [
                    'type' => 'patient_balance',
                    'patient_id' => $payload['patient_id'],
                    'client_id' => $client->client_id,
                ],
            ]);

        if ($response->failed()) {
            throw new Exception(
                $response->json('message') ?? 'The card was declined.',
                422
            );
        }

        return $response->json();
    }

    public function payBalance(Client $client, array $payload): array
    {
        $access = PatientAccess::where('patient_id', $payload['patient_id'])
            ->where('client_id', $client->client_id)
            ->where('have_access', true)
            ->first();

        if (!$access) {
            throw new Exception('You do not have access to this patient.', 403);
        }

        $amount = round((float) $payload['amount'], 2);

        if ($amount <= 0) {
            throw new Exception('Enter an amount greater than 0.', 422);
        }

        $charge = $this->chargeCard($client, $amount, $payload);

        $method = 'CREDIT-CARD';
        $maskedAccountDetails = $charge['masked_card_number'] ?? null;
        $reference = $charge['id'] ?? null;

        $codes = array_filter((array) ($payload['invoice_codes'] ?? []));

        return DB::transaction(function () use ($access, $client, $amount, $method, $maskedAccountDetails, $reference, $codes) {
            $invoiceIds = $access->patient->patient_invoices
                ->pluck('invoice_id');

            $invoices = Invoice::whereIn('invoice_id', $invoiceIds)
                ->whereIn('status', [
                    Invoice::STATUS_PENDING,
                    Invoice::STATUS_PARTIAL,
                ])
                ->when($codes, fn($q) => $q->whereIn('invoice_code', $codes))
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            if ($codes && $invoices->isEmpty()) {
                throw new Exception('The selected invoices are no longer payable.', 422);
            }

            $totalBalance = round((float) $invoices->sum('balance_due'), 2);

            if ($totalBalance <= 0) {
                throw new Exception('There is no outstanding balance to pay.', 422);
            }

            if ($amount > $totalBalance + 0.01) {
                throw new Exception(
                    "Amount can't exceed the outstanding balance of {$totalBalance}.",
                    422
                );
            }

            $receipt = Payment::create([
                'branch_id'          => $invoices->first()->branch_id,
                'patient_id'         => $access->patient_id,
                'client_id'          => $client->client_id,
                'payor_name'         => trim(
                    ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
                ) ?: null,
                'amount'             => $amount,
                'prior_balance'      => $totalBalance,
                'payment_method'     => $method,
                'reference_id'       => $reference,
                'masked_card_number' => $maskedAccountDetails,
                'created_at'         => now(),
            ]);

            $remaining = $amount;
            $paidInvoiceIds = [];

            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }

                $priorBalance = $invoice->balance_due;

                if ($priorBalance <= 0) {
                    continue;
                }

                $paymentAmount = round(min($remaining, $priorBalance), 2);

                $receipt->allocations()->create([
                    'invoice_id' => $invoice->invoice_id,
                    'amount' => $paymentAmount,
                    'description' => $invoice->paymentDescription(),
                    'created_at' => now(),
                ]);

                $remaining = round($remaining - $paymentAmount, 2);

                $invoice->refresh();

                $invoice->syncStatus();

                AccommodationHelper::activate($invoice);

                $paidInvoiceIds[] = $invoice->invoice_id;
            }

            $applied = round($amount - $remaining, 2);

            $receipt->update([
                'new_balance' => round(max($totalBalance - $applied, 0), 2),
            ]);

            return [
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'invoice_ids' => $paidInvoiceIds,
                'remaining_balance' => round(max($totalBalance - $applied, 0), 2),
                'receipt' => new PaymentReceiptResource(
                    $receipt->load([
                        'allocations.invoice.invoiceServices.scheduleService.service',
                        'allocations.invoice.invoiceAdmissionLines.admissionPeriod.branchContract',
                        'allocations.invoice.invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                        'branch.location',
                        'patient',
                        'client',
                    ])
                ),
            ];
        });
    }

    public function receipt(Client $client, array $payload): PaymentReceiptResource
    {
        $receipt = Payment::where('receipt_no', $payload['receipt_no'])
            ->with([
                'allocations.invoice.invoiceServices.scheduleService.service',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.branchContract',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'branch.location',
                'patient',
                'client',
                'issuedBy',
            ])
            ->first();

        if (!$receipt) {
            throw new Exception('Receipt not found.', 404);
        }

        $hasAccess = PatientAccess::where('patient_id', $receipt->patient_id)
            ->where('client_id', $client->client_id)
            ->where('have_access', true)
            ->exists();

        if (!$hasAccess) {
            throw new Exception('You do not have access to this receipt.', 403);
        }

        return new PaymentReceiptResource($receipt);
    }
}
