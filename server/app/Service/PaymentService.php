<?php

namespace App\Service;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PatientAccess;
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

        return DB::transaction(function () use ($access, $amount, $method, $maskedAccountDetails) {
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

            $remaining = $amount;
            $paidInvoiceIds = [];

            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }

                $balance = $invoice->balance_due;

                if ($balance <= 0) {
                    continue;
                }

                $paymentAmount = round(min($remaining, $balance), 2);

                $invoice->payments()->create([
                    'amount' => $paymentAmount,
                    'payment_method' => $method,
                    'masked_card_number' => $maskedAccountDetails,
                ]);

                $remaining = round($remaining - $paymentAmount, 2);

                $invoice->refresh();

                $invoice->update([
                    'status' => $invoice->balance_due <= 0
                        ? Invoice::STATUS_PAID
                        : Invoice::STATUS_PARTIAL,
                ]);

                $paidInvoiceIds[] = $invoice->invoice_id;
            }

            return [
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'invoice_ids' => $paidInvoiceIds,
                'remaining_balance' => round($totalBalance - $amount, 2),
            ];
        });
    }
}
