<?php

namespace App\Http\Resources;

use App\Utils\MoneyWords;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// The receipt for a booking's downpayment, built from its transaction. A
// booking payment is taken before any invoice exists, so there is nothing to
// allocate it to yet; the receipt shows what it was for.
class BookingReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->booking?->booking_data ?? [];
        $payment = $data['payment'] ?? [];
        $patient = $data['patient'] ?? [];

        $amount = round((float) $this->amount, 2);
        $total = round((float) ($payment['total_amount'] ?? $amount), 2);
        $balance = round((float) ($payment['balance_amount'] ?? max($total - $amount, 0)), 2);

        $description = $this->description ?: 'Booking downpayment';

        $patientName = trim(implode(' ', array_filter([
            $patient['first_name'] ?? null,
            $patient['middle_name'] ?? null,
            $patient['last_name'] ?? null,
            $patient['suffix'] ?? null,
        ])));

        return [
            'payment_code' => $this->transaction_code,
            'channel'      => 'portal',
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
                'name' => $this->party_name ?: null,
            ],

            'issued_by' => null,

            'patient' => [
                'patient_uuid' => $this->patient?->uuid,
                'patient_code' => $this->patient?->patient_code,
                'full_name'    => $patientName !== '' ? $patientName : null,
            ],

            'payment' => [
                'method'          => $this->method,
                'masked_account'  => $this->masked_account_number,
                'amount_tendered' => $amount,
                'amount_applied'  => $amount,
                'change_due'      => 0.0,
                'amount_in_words' => MoneyWords::pesos($amount),
            ],

            'account' => [
                'balance_before' => $total,
                'balance_after'  => $balance,
            ],

            'lines' => [[
                'line_no'           => 1,
                'allocation_id'     => null,
                'invoice_id'        => null,
                'payment_id'        => null,
                'payment_reference' => $this->transaction_reference_id,
                'invoice_code'      => null,
                'description'       => $description,
                'rows'              => [['description' => $description, 'amount' => $amount]],
                'invoice_date'      => null,
                'amount_applied'    => $amount,
                'hours_booked'      => null,
            ]],
        ];
    }
}
