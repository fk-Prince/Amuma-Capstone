<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoice_id'   => $this->invoice_id,
            'invoice_code' => $this->invoice_code,
            'total'        => (float) $this->total,
            'amount_paid'  => $this->amount_paid,
            'balance_due'  => $this->balance_due,
            'is_collected' => (bool) $this->is_collected,
            'status'       => $this->resolveStatus(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'patient'      => $this->resolvePatient(),

            'branch' => $this->whenLoaded('branch', fn() => [
                'branch_id' => $this->branch->branch_id,
                'name'      => $this->branch->name ?? null,
            ]),

            'services' => $this->whenLoaded(
                'invoiceServices',
                fn() =>
                $this->invoiceServices->map(fn($service) => [
                    'schedule_services_id' => $service->schedule_services_id,
                    'price'                => (float) $service->price,
                    'note'                 => $service->note,
                    'service_name'         => $service->scheduleService?->service_id === null
                        ? 'Activity of Daily Living (ADL)'
                        : ($service->scheduleService->service->service_name ?? null),
                ])
            ),

            'facilities' => $this->whenLoaded(
                'invoiceFacility',
                fn() =>
                $this->invoiceFacility->map(fn($facility) => [
                    'invoice_facility_id' => $facility->invoice_facility_id,
                    'branch_contract_id'  => $facility->branch_contract_id,
                    'price'               => (float) $facility->price,
                    'patient_admission_id' => $facility->patient_admission_id,
                    'patient_name' => trim(
                        ($facility->patientAdmission->patient->first_name ?? '') . ' ' .
                            ($facility->patientAdmission->patient->last_name ?? '')
                    ),
                ])
            ),

            'payments' => $this->whenLoaded(
                'payments',
                fn() =>
                $this->payments->map(fn($payment) => [
                    'payment_id'     => $payment->payment_id,
                    'reference_id'   => $payment->reference_id,
                    'amount'         => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'created_at'     => $payment->created_at?->toIso8601String(),
                ])
            ),
        ];
    }

    /**
     * Resolve display-oriented patient data from whichever relation chain
     * is loaded: an admitted facility charge, or a scheduled service.
     */
    protected function resolvePatient(): ?array
    {
        $patient = null;

        if ($this->resource->relationLoaded('invoiceFacility')) {
            $patient = $this->invoiceFacility->first()?->patientAdmission?->patient;
        }

        if (!$patient && $this->resource->relationLoaded('invoiceServices')) {
            $patient = $this->invoiceServices->first()?->scheduleService?->schedule?->patient;
        }

        if (!$patient) {
            return null;
        }

        return [
            'patient_id'    => $patient->patient_id,
            'full_name'     => trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) ?: null,
            'first_name'    => $patient->first_name,
            'middle_name'   => $patient->middle_name,
            'last_name'     => $patient->last_name,
            'gender'        => $patient->gender,
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'age'           => $patient->date_of_birth?->age,
            'blood_type'    => $patient->blood_type,
            'phone_number'  => $patient->phone_number,
            'citizenship'   => $patient->citizenship,
        ];
    }

    /**
     * Derive a human-readable status from amount paid vs total.
     */
    protected function resolveStatus(): string
    {
        if ($this->amount_paid <= 0) {
            return Invoice::STATUS_PENDING;
        }

        if ($this->balance_due > 0) {
            return Invoice::STATUS_PARTIAL;
        }

        return Invoice::STATUS_PAID;
    }
}
