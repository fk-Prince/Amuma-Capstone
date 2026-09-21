<?php

namespace App\Service;

use App\Http\Resources\PaymentReceiptResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PatientAccess;
use App\Models\Payment;
use App\Repository\PaymentRepository;
use App\Repository\RefundRepository;
use App\Utils\AccommodationHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private RefundRepository $refundRepository,
        private TransactionService $transactions,
        private InvoiceService $invoiceService
    ) {}

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

        $amount = round((float) ($payload['amount'] ?? 0), 2);
        $creditAmount = round((float) ($payload['credit_amount'] ?? 0), 2);

        if ($amount <= 0 && $creditAmount <= 0) {
            throw new Exception('Enter an amount greater than 0.', 422);
        }

        if ($amount > 0 && $creditAmount > 0) {
            throw new Exception('Pay with credit or with a card, not both.', 422);
        }

        if ($creditAmount > 0) {
            $available = $this->refundRepository->creditFor($access->patient_id);

            if ($creditAmount > $available + 0.01) {
                throw new Exception(
                    'Only ' . number_format($available, 2) . ' in credit is available on this account.',
                    422
                );
            }
        }

        $charge = $amount > 0 ? $this->chargeCard($client, $amount, $payload) : [];

        $method = 'CREDIT-CARD';
        $maskedAccountDetails = $charge['masked_card_number'] ?? null;
        $reference = $charge['id'] ?? null;

        $codes = array_filter((array) ($payload['invoice_codes'] ?? []));

        return DB::transaction(function () use ($access, $client, $amount, $creditAmount, $method, $maskedAccountDetails, $reference, $codes) {
            $invoiceIds = $access->patient->patient_invoices
                ->pluck('invoice_id');

            $invoices = Invoice::whereIn('invoice_id', $invoiceIds)
                ->whereIn('status', [
                    Invoice::STATUS_PENDING,
                    Invoice::STATUS_PARTIAL,
                ])
                ->when($codes, fn($q) => $q->whereIn('invoice_code', $codes))
                ->lockForUpdate()
                ->get()
                ->sortBy(fn($invoice) => $invoice->paymentOrder())
                ->values();

            if ($codes && $invoices->isEmpty()) {
                throw new Exception('The selected invoices are no longer payable.', 422);
            }

            $totalBalance = round((float) $invoices->sum('balance_due'), 2);

            if ($totalBalance <= 0) {
                throw new Exception('There is no outstanding balance to pay.', 422);
            }

            if ($amount + $creditAmount > $totalBalance + 0.01) {
                throw new Exception(
                    "Amount can't exceed the outstanding balance of {$totalBalance}.",
                    422
                );
            }

            $credit = $creditAmount > 0
                ? $this->invoiceService->applyCredit(
                    $access->patient,
                    $invoices,
                    $totalBalance,
                    null,
                    $creditAmount
                )
                : ['applied' => 0.0, 'payment' => null];

            if ($credit['applied'] + 0.01 < $creditAmount) {
                throw new Exception('The credit on this account has changed. Please try again.', 422);
            }

            $creditInvoiceIds = $credit['payment']
                ? $credit['payment']->allocations->pluck('invoice_id')->all()
                : [];

            $totalBalance = round($totalBalance - $credit['applied'], 2);

            if ($amount <= 0) {
                return [
                    'success' => true,
                    'message' => 'Credit applied to the outstanding balance.',
                    'credit_applied' => $credit['applied'],
                    'invoice_ids' => $creditInvoiceIds,
                    'remaining_balance' => max($totalBalance, 0),
                    'receipt' => $this->receiptResource($credit['payment']),
                    'credit_receipt' => null,
                ];
            }

            $invoices = $invoices
                ->filter(fn($invoice) => $invoice->refresh()->balance_due > 0)
                ->values();

            $transaction = $this->transactions->forPayment(
                $amount,
                $invoices->first()->branch_id,
                $access->patient_id,
                'Payment received through the family portal.',
                [
                    'client_id' => $client->client_id,
                    'method' => $method,
                    'party_name' => trim(
                        ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
                    ) ?: null,
                    'masked_account_number' => $maskedAccountDetails,
                    'transaction_reference_id' => $reference ?: null,
                ]
            );

            $receipt = $this->paymentRepository->create([
                'transaction_id'        => $transaction->transaction_id,
                'prior_balance'         => $totalBalance,
                'cash_tendered'         => $amount,
                'created_at'            => now(),
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
                'credit_applied' => $credit['applied'],
                'invoice_ids' => array_values(array_unique([...$creditInvoiceIds, ...$paidInvoiceIds])),
                'remaining_balance' => round(max($totalBalance - $applied, 0), 2),
                'receipt' => $this->receiptResource($receipt),
                'credit_receipt' => $this->receiptResource($credit['payment']),
            ];
        });
    }

    private function receiptResource(?Payment $payment): ?PaymentReceiptResource
    {
        if (!$payment) {
            return null;
        }

        return new PaymentReceiptResource(
            $payment->load([
                'allocations.invoice.invoiceServices.scheduleService.service',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.branchContract',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'transaction.branch.location',
                'transaction.patient',
                'transaction.client',
            ])
        );
    }

    public function receipt(Client $client, array $payload): PaymentReceiptResource
    {
        $receipt = $this->paymentRepository->findByCode(
            $payload['payment_code'],
            PaymentRepository::RECEIPT_RELATIONS
        );

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
