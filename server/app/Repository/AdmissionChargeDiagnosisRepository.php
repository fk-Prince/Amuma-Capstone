<?php

namespace App\Repository;

use App\Models\AdmissionChargeDiagnosis;
use App\Models\AdmissionDiagnosis;
use App\Models\Invoice;

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
            ->whereDoesntHave(
                'charge.invoice',
                fn($query) => $query->whereIn('status', Invoice::CLOSED_STATUSES)
            )
            ->orderBy('admission_diagnosis_id')
            ->get()
            ->unique('patient_diagnosis_id')
            ->keyBy('patient_diagnosis_id');
    }

    // Every live row a diagnosis has on the admission, one per billed period.
    public function rowsFor(int $admissionId, int $patientDiagnosisId, bool $lock = false)
    {
        $query = AdmissionDiagnosis::with([
            'charge.invoice.allocations',
            'charge.admissionPeriod.branchContract',
        ])
            ->where('patient_diagnosis_id', $patientDiagnosisId)
            ->whereHas(
                'charge.admissionPeriod',
                fn($q) => $q->where('patient_admission_id', $admissionId)
            )
            ->whereDoesntHave(
                'charge.invoice',
                fn($q) => $q->whereIn('status', Invoice::CLOSED_STATUSES)
            );

        return $lock ? $query->lockForUpdate()->get() : $query->get();
    }

    // Diagnoses nobody has paid toward yet, so they can still be changed or
    // taken off, as patient diagnosis ids.
    public function editableDiagnosisIds(int $admissionId)
    {
        return AdmissionDiagnosis::with('charge.invoice.allocations')
            ->whereHas(
                'charge.admissionPeriod',
                fn($q) => $q->where('patient_admission_id', $admissionId)
            )
            ->whereDoesntHave(
                'charge.invoice',
                fn($q) => $q->whereIn('status', Invoice::CLOSED_STATUSES)
            )
            ->get()
            ->groupBy('patient_diagnosis_id')
            ->filter(fn($rows) => $rows->every(
                fn(AdmissionDiagnosis $row) => !$row->charge?->invoice
                    || $row->charge->invoice->allocations->isEmpty()
            ))
            ->keys();
    }

    public function updateDiagnosis(AdmissionDiagnosis $row, array $payload): void
    {
        $row->update($payload);
    }

    public function deleteDiagnosis(AdmissionDiagnosis $row): void
    {
        $row->delete();
    }

    public function deleteCharge(AdmissionChargeDiagnosis $charge): void
    {
        $charge->delete();
    }

    // Brings the charge, and the invoice behind it, back to what its rows come
    // to over the months it bills.
    public function syncCharge(AdmissionChargeDiagnosis $charge, int $months): void
    {
        $amount = round((float) $charge->diagnoses()->sum('amount') * $months, 2);

        $charge->update(['amount' => $amount]);
        $charge->invoice?->update(['total_amount' => $amount]);
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
