<?php

namespace App\Http\Resources;

use App\Models\Refund;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return self::format($this->resource);
    }

    public static function format(?Transaction $withdrawal): ?array
    {
        if (!$withdrawal) {
            return null;
        }

        $withdrawal->loadMissing(
            'client',
            'refunds.allocations.allocation.invoice',
            'refunds.allocations.invoiceAdjustment'
        );

        $lines = $withdrawal->refunds->flatMap(
            fn(Refund $credit) => $credit->allocations
        );

        return [
            'refund_id' => $withdrawal->transaction_id,
            'refund_code' => $withdrawal->transaction_code,
            'amount' => (float) $withdrawal->amount,
            'refund_method' => $withdrawal->method,
            'masked_account_detail' => $withdrawal->masked_account_number,
            'account_name' => $withdrawal->party_name,
            'transaction_reference_id' => $withdrawal->transaction_reference_id,
            'status' => $withdrawal->status,
            'declined_reason' => $withdrawal->declined_reason,
            'requested_at' => $withdrawal->created_at?->toIso8601String(),
            'created_at' => $withdrawal->created_at?->toIso8601String(),
            'settled_at' => $withdrawal->status === Transaction::STATUS_REQUESTED
                ? null
                : $withdrawal->created_at?->toIso8601String(),
            'requested_by' => $withdrawal->client ? [
                'client_id' => $withdrawal->client->client_id,
                'name' => trim(
                    ($withdrawal->client->first_name ?? '') . ' '
                        . ($withdrawal->client->last_name ?? '')
                ),
            ] : null,
            'invoice_codes' => $lines
                ->map(fn($line) => $line->allocation?->invoice?->invoice_code)
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'reasons' => $lines
                ->map(fn($line) => $line->invoiceAdjustment?->reason)
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }
}
