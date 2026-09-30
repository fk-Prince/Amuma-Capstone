<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return self::format($this->resource);
    }

    public static function format(?Transaction $deposit): ?array
    {
        if (!$deposit) {
            return null;
        }

        return [
            'deposit_id' => $deposit->transaction_id,
            'deposit_code' => $deposit->transaction_code,
            'reference_id' => $deposit->transaction_reference_id,
            'amount' => (float) $deposit->amount,
            'method' => $deposit->method,
            'masked_account_detail' => $deposit->masked_account_number,
            'deposited_by' => $deposit->party_name,
            'note' => $deposit->description,
            'status' => $deposit->status,
            'created_at' => $deposit->created_at?->toIso8601String(),
        ];
    }
}
