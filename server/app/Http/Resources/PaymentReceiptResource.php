<?php

namespace App\Http\Resources;

use App\Utils\Vat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payment_code' => $this->payment_code,
            'channel'      => $this->client_id ? 'portal' : 'counter',
            'issued_at'    => $this->created_at?->toIso8601String(),

            'issuer' => [
                'branch_name' => $this->branch?->name,
                'logo'        => $this->branch?->image,
                'address'     => $this->branch?->location?->full_address,
                'contact'     => $this->branch?->contact_number,
                'email'       => $this->branch?->email,
                'tin'         => data_get($this->branch?->settings, 'tin'),
                'permit_no'   => data_get($this->branch?->settings, 'bir_permit_no'),            ],

            'tax' => $this->taxSummary(),

            'payor' => [
                'name' => $this->resolvePayorName(),
            ],

            'issued_by' => $this->resolveIssuerName(),

            'patient' => [
                'patient_uuid' => $this->patient?->uuid,
                'patient_code' => $this->patient?->patient_code,
                'full_name'    => $this->patient?->display_name ?: null,
            ],

            'payment' => [
                'method'          => $this->payment_method,
                'masked_account'  => $this->masked_account,
                'amount_tendered' => (float) $this->amount_tendered,
                'amount_applied'  => (float) $this->amount_applied,
                'change_due'      => (float) $this->change_due,
                'amount_in_words' => $this->amount_in_words,
            ],

            'account' => [
                'balance_before' => (float) $this->prior_balance,
                'balance_after'  => (float) $this->balance_after,
            ],

            // One line per invoice this payment was split across, each with its
            // own description — the reference belongs to the payment as a whole.
            'lines' => $this->allocations->values()->map(fn($allocation, $index) => [
                'line_no'           => $index + 1,
                'allocation_id'     => $allocation->allocation_id,
                'invoice_id'        => $allocation->invoice_id,
                'payment_id'        => $allocation->payment_id,
                'payment_reference' => $this->reference_id,
                'invoice_code'      => $allocation->invoice?->invoice_code,
                'description'       => $allocation->description
                    ?: $allocation->invoice?->paymentDescription()
                    ?: 'Payment for balance',
                'rows'              => $this->lineRows($allocation),
                'invoice_date'      => $allocation->invoice?->created_at?->toIso8601String(),
                'amount_applied'    => (float) $allocation->amount,
                'duration'      => $allocation->invoice?->adlDuration(),
                'months_billed'     => $allocation->invoice?->adlMonthsBilled(),
            ]),
        ];
    }

    protected function taxSummary(): array
    {
        [$taxed, $exempt] = $this->allocations
            ->filter(fn($allocation) => (float) $allocation->amount > 0)
            ->partition(fn($allocation) => (float) $allocation->invoice?->vat_rate > 0);

        $vat = round($taxed->sum(fn($allocation) => Vat::portion(
            (float) $allocation->amount,
            (float) $allocation->invoice->vat_rate
        )), 2);

        $rates = $taxed->map(fn($allocation) => (float) $allocation->invoice->vat_rate)->unique();

        return [
            'vat_rate'      => $rates->count() === 1 ? $rates->first() : null,
            'vatable_sales' => round((float) $taxed->sum('amount') - $vat, 2),
            'vat_exempt_sales' => round((float) $exempt->sum('amount'), 2),
            'vat_amount'    => $vat,
        ];
    }

    protected function lineRows($allocation): array
    {
        $amount = round((float) $allocation->amount, 2);
        $groups = $allocation->invoice?->paymentLines() ?? collect();

        if ($groups->count() <= 1 || $amount <= 0) {
            return [[
                'description' => $allocation->description
                    ?: ($groups->first()['description'] ?? 'Payment for balance'),
                'amount' => $amount,
            ]];
        }

        $skip = round((float) $allocation->invoice->allocations()
            ->where('allocation_id', '<', $allocation->allocation_id)
            ->sum('amount'), 2);

        $rows = [];
        $remaining = $amount;
        $last = $groups->count() - 1;

        foreach ($groups->values() as $index => $group) {
            $weight = round((float) $group['weight'], 2);
            $available = max(0, round($weight - $skip, 2));
            $skip = max(0, round($skip - $weight, 2));

            $share = $index === $last ? $remaining : min($remaining, $available);

            if ($share > 0) {
                $rows[] = ['description' => $group['description'], 'amount' => round($share, 2)];
                $remaining = round($remaining - $share, 2);
            }
        }

        return $rows ?: [['description' => $groups->first()['description'], 'amount' => $amount]];
    }

    protected function resolveIssuerName(): ?string
    {
        if (!$this->issued_by) {
            return null;
        }

        $issuer = $this->issuedBy;

        if (!$issuer) {
            return null;
        }

        $name = trim(
            ($issuer->first_name ?? '') . ' ' . ($issuer->last_name ?? '')
        );

        return $name !== '' ? $name : $issuer->email;
    }

    protected function resolvePayorName(): ?string
    {
        if ($this->payor_name) {
            return $this->payor_name;
        }

        $name = trim(
            ($this->client?->first_name ?? '') . ' ' .
                ($this->client?->last_name ?? '')
        );

        return $name !== '' ? $name : null;
    }
}
