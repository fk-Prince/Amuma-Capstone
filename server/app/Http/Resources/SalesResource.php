<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'invoice_code' => $this->invoice_code,
            'created_at'   => $this->created_at?->toIso8601String(),
            'patient_name' => $this->patientName(),
            'description'  => $this->paymentDescription(),
            'gross_amount' => $this->sales_amount,
            'status'       => $this->status,
        ];
    }

    private function patientName(): ?string
    {
        $patient = $this->invoiceAdmissionLines->first()?->admissionPeriod?->patientAdmission?->patient
            ?? $this->invoiceServices->first()?->scheduleService?->schedule?->patient
            ?? $this->additionalCharges->first()?->patient
            ?? $this->diagnosisLines->first()?->admissionPeriod?->patientAdmission?->patient;

        return $patient?->display_name ?: null;
    }
}
