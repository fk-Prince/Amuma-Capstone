<?php

namespace App\Repository;

use App\Models\AdmissionChargeDiagnosis;
use App\Models\AdmissionDiagnosis;

class AdmissionChargeDiagnosisRepository
{
    public function createCharge(array $payload): AdmissionChargeDiagnosis
    {
        return AdmissionChargeDiagnosis::create($payload);
    }

    public function createDiagnosis(array $payload): AdmissionDiagnosis
    {
        return AdmissionDiagnosis::create($payload);
    }

    public function forPeriod(int $periodId)
    {
        return AdmissionDiagnosis::with([
            'charge.invoice',
            'patientDiagnosis',
            'diagnosisCase',
            'admissionPeriod.branchContract',
        ])
            ->whereHas('charge', fn($query) => $query->where('admission_period_id', $periodId))
            ->get()
            ->keyBy('patient_diagnosis_id');
    }

    // Every diagnosis billed on the admission, whichever period, each as the
    // row it was first billed on.
    public function forAdmission(int $admissionId)
    {
        return AdmissionDiagnosis::with([
            'charge.invoice',
            'patientDiagnosis',
            'diagnosisCase',
            'admissionPeriod.branchContract',
        ])
            ->whereHas(
                'charge.admissionPeriod',
                fn($query) => $query->where('patient_admission_id', $admissionId)
            )
            ->orderBy('admission_diagnosis_id')
            ->get()
            ->unique('patient_diagnosis_id')
            ->keyBy('patient_diagnosis_id');
    }

    public function latestCaseIds(array $patientDiagnosisIds)
    {
        if (empty($patientDiagnosisIds)) {
            return collect();
        }

        return AdmissionDiagnosis::whereIn('patient_diagnosis_id', $patientDiagnosisIds)
            ->whereNotNull('diagnosis_case_id')
            ->orderByDesc('admission_diagnosis_id')
            ->get(['patient_diagnosis_id', 'diagnosis_case_id'])
            ->unique('patient_diagnosis_id')
            ->pluck('diagnosis_case_id', 'patient_diagnosis_id');
    }
}
