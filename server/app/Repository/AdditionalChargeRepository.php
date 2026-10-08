<?php

namespace App\Repository;

use App\Models\AdditionalCharge;
use App\Models\Invoice;
use App\Models\PatientDiagnosis;

class AdditionalChargeRepository
{
    public function create(array $payload)
    {
        return AdditionalCharge::create($payload);
    }

    public function attachDiagnosis(AdditionalCharge $charge, PatientDiagnosis $diagnosis, ?int $diagnosisCaseId): void
    {
        $diagnosis->additionalCharge()->detach(
            $diagnosis->additionalCharge()
                ->whereHas('invoice', fn($query) => $query->where('status', Invoice::STATUS_VOID))
                ->pluck('additional_charges.additional_charge_id')
                ->all()
        );

        $charge->patientDiagnosis()->attach($diagnosis->patient_diagnosis_id, [
            'diagnosis_case_id' => $diagnosisCaseId,
        ]);
    }

    public function createDiagnosis(array $payload): PatientDiagnosis
    {
        return PatientDiagnosis::create($payload);
    }

    public function listDiagnoses(int $patientId)
    {
        return PatientDiagnosis::with(['additionalCharge' => fn($query) => $query
            ->whereHas('invoice', fn($invoice) => $invoice->where('status', '!=', Invoice::STATUS_VOID))
            ->with('invoice')])
            ->where('patient_id', $patientId)
            ->orderByDesc('diagnosis_date')
            ->orderByDesc('patient_diagnosis_id')
            ->get();
    }

    public function findPatientDiagnosis(string $uuid, int $patientId): ?PatientDiagnosis
    {
        return PatientDiagnosis::where('uuid', $uuid)
            ->where('patient_id', $patientId)
            ->first();
    }

    public function paginateForPatient(int $patientId, int $perPage)
    {
        return AdditionalCharge::with(['invoice', 'patientAdmission', 'patientDiagnosis'])
            ->whereHas('patientAdmission', fn($query) => $query->where('patient_id', $patientId))
            ->latest('additional_charge_id')
            ->paginate($perPage);
    }
}
