<?php

namespace App\Service;

use App\Http\Resources\PaymentReceiptResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PatientAccess;
use App\Models\PaymentReceipt;
use App\Utils\MaskUtil;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{

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

        $method = trim((string) $payload['method']);
        $maskedAccountDetails = MaskUtil::accountDetails(
            $method,
            trim((string) $payload['account_details'])
        );

        return DB::transaction(function () use ($access, $client, $amount, $method, $maskedAccountDetails) {
            $invoiceIds = $access->patient->patient_invoices
                ->pluck('invoice_id');

            $invoices = Invoice::whereIn('invoice_id', $invoiceIds)
                ->whereIn('status', [
                    Invoice::STATUS_PENDING,
                    Invoice::STATUS_PARTIAL,
                ])
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

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

            $receipt = PaymentReceipt::create([
                'branch_id'       => $invoices->first()->branch_id,
                'patient_id'      => $access->patient_id,
                'client_id'       => $client->client_id,
                'payor_name'      => trim(
                    ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
                ) ?: null,
                'amount_tendered' => $amount,
                'balance_before'  => $totalBalance,
                'created_at'      => now(),
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

                $payment = $invoice->payments()->create([
                    'receipt_id' => $receipt->receipt_id,
                    'amount' => $paymentAmount,
                    'payment_method' => $method,
                    'description' => $invoice->paymentDescription(),
                    'masked_card_number' => $maskedAccountDetails,
                    'prior_balance' => $priorBalance,
                ]);

                $remaining = round($remaining - $paymentAmount, 2);

                $invoice->refresh();

                $status = $invoice->balance_due <= 0
                    ? Invoice::STATUS_PAID
                    : Invoice::STATUS_PARTIAL;

                $invoice->update([
                    'status' => $status,
                ]);

                $payment->update([
                    'new_balance' => $invoice->balance_due,
                ]);

                $paidInvoiceIds[] = $invoice->invoice_id;
            }

            $applied = round($amount - $remaining, 2);

            return [
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'invoice_ids' => $paidInvoiceIds,
                'remaining_balance' => round($totalBalance - $applied, 2),
                'receipt' => new PaymentReceiptResource(
                    $receipt->load([
                        'payments.invoice.invoiceServices.scheduleService.service',
                'payments.invoice.invoiceAccommodation.branchContract',
                'payments.invoice.invoiceAccommodation.patientAdmission.bed.room',
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
        $receipt = PaymentReceipt::where('receipt_no', $payload['receipt_no'])
            ->with([
                'payments.invoice.invoiceServices.scheduleService.service',
                'payments.invoice.invoiceAccommodation.branchContract',
                'payments.invoice.invoiceAccommodation.patientAdmission.bed.room',
                'branch.location',
                'patient',
                'client',
                'issuer',
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
