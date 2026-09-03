<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'receipt_no'   => $this->receipt_no,
            'channel'      => $this->client_id ? 'portal' : 'counter',
            'issued_at'    => $this->created_at?->toIso8601String(),
            'is_voided'    => $this->is_voided,
            'voided_at'    => $this->voided_at?->toIso8601String(),
            'void_reason'  => $this->void_reason,

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
                'balance_before' => (float) $this->balance_before,
                'balance_after'  => (float) $this->balance_after,
            ],

            'lines' => $this->payments->values()->map(fn($payment, $index) => [
                'line_no'           => $index + 1,
                'invoice_id'        => $payment->invoice_id,
                'payment_id'        => $payment->payment_id,
                'payment_reference' => $payment->reference_id,
                'invoice_code'      => $payment->invoice?->invoice_code,
                'description'       => $payment->description
                    ?: $payment->invoice?->paymentDescription()
                    ?: 'Payment for balance',
                'invoice_date'      => $payment->invoice?->created_at?->toIso8601String(),
                'prior_balance'     => (float) $payment->prior_balance,
                'amount_applied'    => (float) $payment->amount,
                'new_balance'       => (float) $payment->new_balance,
            ]),
        ];
    }

    protected function resolveIssuerName(): ?string
    {
        if (!$this->issued_by) {
            return null;
        }

        $issuer = $this->issuer;

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
