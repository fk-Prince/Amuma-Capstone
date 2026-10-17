<?php

namespace App\Service;

use App\Models\AdmissionDiagnosis;
use App\Models\AdmissionPeriod;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Repository\AdmissionChargeDiagnosisRepository;
use App\Repository\DiagnosisCaseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PatientRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DiagnosisFeeService
{
    public function __construct(
        private DiagnosisCaseRepository $diagnosisCaseRepository,
        private AdmissionChargeDiagnosisRepository $admissionChargeRepository,
        private InvoiceRepository $invoiceRepository,
        private PatientRepository $patientRepository,
    ) {}

    /*
        Diagnoses just recorded with the patient, each optionally carrying the
        diagnosis case the facility prices it under. Every fee carries its
        monthly amount.
    */
    public function fromCreated(array $created, int $branchId): Collection
    {
        return collect($created)
            ->map(fn(array $entry) => $this->pair(
                $entry['diagnosis'],
                $entry['diagnosis_case_uuid'] ?? null,
                $branchId
            ))
            ->filter()
            ->values();
    }

    /*
        Diagnoses already on the patient's record, picked as
        [{patient_diagnosis_uuid, diagnosis_case_uuid, amount?, description?}].
    */
    public function fromRequest(mixed $entries, Patient $patient, int $branchId): Collection
    {
        $fees = collect();

        foreach (array_values((array) $entries) as $index => $entry) {
            if (!is_array($entry) || blank($entry['diagnosis_case_uuid'] ?? null)) {
                continue;
            }

            $diagnosis = $this->patientRepository->findDiagnosis(
                $patient,
                (string) ($entry['patient_diagnosis_uuid'] ?? '')
            );

            if (!$diagnosis) {
                throw ValidationException::withMessages([
                    "diagnoses.$index.patient_diagnosis_uuid" => 'Choose a valid diagnosis.',
                ]);
            }

            $fee = $this->pair($diagnosis, $entry['diagnosis_case_uuid'], $branchId, $index);

            $amount = $entry['amount'] ?? null;

            if (filled($amount)) {
                if (!is_numeric($amount) || (float) $amount < 0 || (float) $amount > 99999999.99) {
                    throw ValidationException::withMessages([
                        "diagnoses.$index.amount" => 'Enter a valid amount.',
                    ]);
                }

                $fee['amount'] = round((float) $amount, 2);
            }

            $description = trim((string) ($entry['description'] ?? ''));

            if (mb_strlen($description) > 255) {
                throw ValidationException::withMessages([
                    "diagnoses.$index.description" => 'The description may not be longer than 255 characters.',
                ]);
            }

            $fee['description'] = $description !== '' ? $description : null;

            $fees->push($fee);
        }

        return $fees->unique(fn(array $fee) => $fee['diagnosis']->patient_diagnosis_id)->values();
    }

    public function findDiagnosis(Patient $patient, string $uuid)
    {
        return $this->patientRepository->findDiagnosis($patient, $uuid);
    }

    /*
        Bills the fees on an invoice of their own, against the period the stay
        is in, and keeps one row per diagnosis under it. The amounts are billed
        as entered. Returns null when there is nothing to bill.
    */
    public function bill(PatientAdmission $admission, AdmissionPeriod $period, Collection $fees, int $branchId): ?Invoice
    {
        $billable = $fees->filter(fn(array $fee) => round((float) $fee['amount'], 2) >= 0.01);

        if ($billable->isEmpty()) {
            return null;
        }

        $total = round((float) $billable->sum(fn(array $fee) => (float) $fee['amount']), 2);

        $invoice = $this->invoiceRepository->create([
            'total_amount' => $total,
            'branch_id' => $branchId,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $charge = $this->admissionChargeRepository->createCharge([
            'admission_period_id' => $period->admission_period_id,
            'invoice_id' => $invoice->invoice_id,
            'amount' => $total,
        ]);

        foreach ($billable as $fee) {
            $this->admissionChargeRepository->createDiagnosis([
                'admission_charge_diagnosis_id' => $charge->admission_charge_diagnosis_id,
                'patient_diagnosis_id' => $fee['diagnosis']->patient_diagnosis_id,
                'diagnosis_case_id' => $fee['case']?->diagnosis_case_id,
                'description' => $fee['description'] ?? null,
                'amount' => round((float) $fee['amount'], 2),
            ]);
        }

        Invoice::forgetPatientInvoiceIds();

        return $invoice;
    }

    /*
        What an admission is billed for each diagnosis, keyed by diagnosis id,
        as it was last charged. Once the stay has been extended after that
        charge it has no invoice to show and needs charging again.
    */
    public function admissionCharges(int $admissionId): Collection
    {
        $editable = $this->admissionChargeRepository->editableDiagnosisIds($admissionId);
        $extendedAt = $this->admissionChargeRepository->lastExtensionAt($admissionId);

        return $this->admissionChargeRepository->forAdmission($admissionId)
            ->map(function (AdmissionDiagnosis $row, $diagnosisId) use ($editable, $extendedAt) {
                $needsCharge = $extendedAt && $row->charge?->created_at?->lt($extendedAt);
                $invoice = $needsCharge ? null : $row->charge?->invoice;

                return [
                    'editable' => !$needsCharge && $editable->contains($diagnosisId),
                    'needs_charge' => (bool) $needsCharge,
                    'case_uuid' => $row->diagnosisCase?->uuid,
                    'case_title' => $row->diagnosisCase?->title,
                    'amount' => (float) $row->amount,
                    'description' => $row->description,
                    'billed_amount' => (float) $row->billed_amount,
                    'invoice_code' => $invoice?->invoice_code,
                    'invoice_status' => $invoice?->status,
                    'balance_due' => $invoice ? (float) $invoice->balance_due : null,
                ];
            });
    }

    // A diagnosis already charged on the stay can only be charged again once
    // the stay has been extended since.
    public function hasCurrentCharge(int $admissionId, int $patientDiagnosisId): bool
    {
        $row = $this->admissionChargeRepository->forAdmission($admissionId)->get($patientDiagnosisId);

        if (!$row) {
            return false;
        }

        $extendedAt = $this->admissionChargeRepository->lastExtensionAt($admissionId);

        return !$extendedAt || !$row->charge?->created_at?->lt($extendedAt);
    }
    /*
        The diagnosis case last used to bill each diagnosis, keyed by the
        diagnosis id, so a new admission can start from the same fees.
    */
    public function lastCaseUuids(array $patientDiagnosisIds): Collection
    {
        $caseIds = $this->admissionChargeRepository->latestCaseIds($patientDiagnosisIds);

        if ($caseIds->isEmpty()) {
            return collect();
        }

        $cases = $this->diagnosisCaseRepository->findManyByIds($caseIds->unique()->values()->all());

        return $caseIds->map(fn($caseId) => $cases->get($caseId)?->uuid)->filter();
    }

    private function pair($diagnosis, ?string $caseUuid, int $branchId, ?int $index = null): ?array
    {
        if (blank($caseUuid)) {
            return null;
        }

        $case = $this->diagnosisCaseRepository->findInBranch($caseUuid, $branchId);

        if (!$case) {
            throw ValidationException::withMessages([
                ($index === null ? 'diagnoses' : "diagnoses.$index.diagnosis_case_uuid") => 'Choose a valid diagnosis case.',
            ]);
        }

        return [
            'diagnosis' => $diagnosis,
            'case' => $case,
            'amount' => round((float) $case->price, 2),
            'description' => null,
        ];
    }
}
