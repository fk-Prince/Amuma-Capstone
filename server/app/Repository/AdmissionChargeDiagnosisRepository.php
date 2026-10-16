<?php

namespace App\Repository;

use App\Models\AdmissionChargeDiagnosis;

class AdmissionChargeDiagnosisRepository
{
    public function create(array $payload): AdmissionChargeDiagnosis
    {
        return AdmissionChargeDiagnosis::create($payload);
    }

    public function forPeriod(int $periodId)
    {
        return AdmissionChargeDiagnosis::with(['diagnosisCase', 'patientDiagnosis', 'invoice', 'admissionPeriod.branchContract'])
            ->where('admission_period_id', $periodId)
            ->get()
            ->keyBy('patient_diagnosis_id');
    }

    public function latestCaseIds(array $patientDiagnosisIds)
    {
        if (empty($patientDiagnosisIds)) {
            return collect();
        }

        return AdmissionChargeDiagnosis::whereIn('patient_diagnosis_id', $patientDiagnosisIds)
            ->whereNotNull('diagnosis_case_id')
            ->orderByDesc('admission_charge_diagnosis_id')
            ->get(['patient_diagnosis_id', 'diagnosis_case_id'])
            ->unique('patient_diagnosis_id')
            ->pluck('diagnosis_case_id', 'patient_diagnosis_id');
    }
}
