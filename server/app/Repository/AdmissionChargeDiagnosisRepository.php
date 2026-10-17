<?php

namespace App\Repository;

use App\Models\AdmissionChargeDiagnosis;
use App\Models\AdmissionDiagnosis;
use App\Models\AdmissionPeriod;
use App\Models\PatientAdmission;
use App\Models\Invoice;
use Carbon\Carbon;

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
    // row it was last billed on.
    public function forAdmission(int $admissionId)
    {
        return AdmissionDiagnosis::with([
            'charge.invoice',
            'patientDiagnosis',
            'diagnosisCase',
        ])
            ->whereHas(
                'charge.admissionPeriod',
                fn($query) => $query->where('patient_admission_id', $admissionId)
            )
            ->whereDoesntHave(
                'charge.invoice',
                fn($query) => $query->whereIn('status', Invoice::CLOSED_STATUSES)
            )
            ->orderByDesc('admission_diagnosis_id')
            ->get()
            ->unique('patient_diagnosis_id')
            ->keyBy('patient_diagnosis_id');
    }

    // The live row a diagnosis was last billed on, which is the one that can
    // still be changed.
    public function rowsFor(int $admissionId, int $patientDiagnosisId, bool $lock = false)
    {
        $query = AdmissionDiagnosis::with('charge.invoice.allocations')
            ->where('patient_diagnosis_id', $patientDiagnosisId)
            ->whereHas(
                'charge.admissionPeriod',
                fn($q) => $q->where('patient_admission_id', $admissionId)
            )
            ->whereDoesntHave(
                'charge.invoice',
                fn($q) => $q->whereIn('status', Invoice::CLOSED_STATUSES)
            )
            ->orderByDesc('admission_diagnosis_id')
            ->limit(1);

        return $lock ? $query->lockForUpdate()->get() : $query->get();
    }

    // Diagnoses that can still be changed or taken off, as patient diagnosis ids.
    public function editableDiagnosisIds(int $admissionId)
    {
        return AdmissionDiagnosis::query()
            ->whereHas(
                'charge.admissionPeriod',
                fn($q) => $q->where('patient_admission_id', $admissionId)
            )
            ->whereDoesntHave(
                'charge.invoice',
                fn($q) => $q->whereIn('status', Invoice::CLOSED_STATUSES)
            )
            ->orderByDesc('admission_diagnosis_id')
            ->get()
            ->unique('patient_diagnosis_id')
            ->pluck('patient_diagnosis_id');
    }

    // The monthly diagnosis cases due to be billed again as of the date.
    public function dueForRecurring(Carbon $date)
    {
        return AdmissionDiagnosis::where('is_active', true)
            ->where('billed', AdmissionDiagnosis::BILLED_MONTHLY)
            ->whereDate('date_billed', '<=', $date->toDateString())
            ->whereHas(
                'charge.admissionPeriod.patientAdmission',
                fn($query) => $query->where('status', PatientAdmission::STATUS_ADMITTED)
            )
            ->orderBy('admission_diagnosis_id')
            ->get();
    }

    // Re-reads a monthly case under lock, and only while it is still active.
    public function lockRecurring(int $id): ?AdmissionDiagnosis
    {
        return AdmissionDiagnosis::with('charge.admissionPeriod.patientAdmission.patient', 'patientDiagnosis', 'diagnosisCase')
            ->where('admission_diagnosis_id', $id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();
    }

    public function stopRecurringForAdmission(int $admissionId): void
    {
        AdmissionDiagnosis::where('is_active', true)
            ->whereHas(
                'charge.admissionPeriod',
                fn($query) => $query->where('patient_admission_id', $admissionId)
            )
            ->update(['is_active' => false]);
    }

    public function stopRecurring(int $admissionId, int $patientDiagnosisId, int $exceptId): void
    {
        AdmissionDiagnosis::where('patient_diagnosis_id', $patientDiagnosisId)
            ->where('admission_diagnosis_id', '!=', $exceptId)
            ->where('is_active', true)
            ->whereHas(
                'charge.admissionPeriod',
                fn($query) => $query->where('patient_admission_id', $admissionId)
            )
            ->update(['is_active' => false]);
    }

    // When the stay was last extended, which leaves earlier diagnosis charges
    // behind.
    public function lastExtensionAt(int $admissionId): ?Carbon
    {
        $at = AdmissionPeriod::where('patient_admission_id', $admissionId)
            ->where('reason', AdmissionPeriod::REASON_EXTENDED)
            ->where('status', '!=', AdmissionPeriod::STATUS_CANCELLED)
            ->max('created_at');

        return $at ? Carbon::parse($at) : null;
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

    // Brings the charge, and the invoice behind it, back to what its rows come to.
    public function syncCharge(AdmissionChargeDiagnosis $charge): void
    {
        $amount = round((float) $charge->diagnoses()->sum('amount'), 2);

        $charge->update(['amount' => $amount]);
        $charge->invoice?->update(['total_amount' => $amount]);
        $charge->invoice?->syncStatus();
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
