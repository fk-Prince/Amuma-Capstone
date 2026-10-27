<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesVatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $gross = $this->sales_amount;
        $vat = $this->sales_vat;
        $taxed = (float) $this->vat_rate > 0;

        return [
            'invoice_code'     => $this->invoice_code,
            'created_at'       => $this->created_at?->toIso8601String(),
            'patient_name'     => $this->patientName(),
            'description'      => $this->paymentDescription(),
            'gross_amount'     => $gross,
            'vatable_sales'    => $taxed ? round($gross - $vat, 2) : 0.0,
            'vat_exempt_sales' => $taxed ? 0.0 : $gross,
            'vat_amount'       => $vat,
            'vat_rate'         => (float) $this->vat_rate,
            'status'           => $this->status,
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
