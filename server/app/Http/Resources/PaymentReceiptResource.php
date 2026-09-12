<?php

namespace App\Http\Resources;

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
                'permit_no'   => data_get($this->branch?->settings, 'bir_permit_no'),
            ],

            'payor' => [
                'name' => $this->resolvePayorName(),
            ],

            'issued_by' => $this->resolveIssuerName(),

            'patient' => [
                'patient_uuid' => $this->patient?->uuid,
                'patient_code' => $this->patient?->patient_code,
                'full_name'    => trim(
                    ($this->patient?->first_name ?? '') . ' ' .
                        ($this->patient?->last_name ?? '')
                ) ?: null,
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
                'invoice_date'      => $allocation->invoice?->created_at?->toIso8601String(),
                'amount_applied'    => (float) $allocation->amount,
            ]),
        ];
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
