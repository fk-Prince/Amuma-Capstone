<?php

namespace App\Service;

use App\Models\AdmissionChargeDiagnosis;
use App\Models\AdmissionPeriod;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Repository\AdmissionChargeDiagnosisRepository;
use App\Repository\DiagnosisCaseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PatientAdmissionRepository;
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
        private PatientAdmissionRepository $patientAdmissionRepository,
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

    // What the fees come to on one period: the monthly amounts, times twelve
    // when the period is billed yearly.
    public function total(Collection $fees, ?string $billingCycle = null): float
    {
        return round(
            (float) $fees->sum(fn(array $fee) => (float) $fee['amount'])
                * AdmissionChargeDiagnosis::multiplier($billingCycle),
            2
        );
    }

    /*
        Bills the fees for one admission period on an invoice of their own and
        keeps one row per diagnosis against it. Returns null when there is
        nothing to bill.
    */
    public function bill(PatientAdmission $admission, AdmissionPeriod $period, Collection $fees, int $branchId): ?Invoice
    {
        $period->loadMissing('branchContract');

        $multiplier = AdmissionChargeDiagnosis::multiplier($period->branchContract?->billing_cycle);

        $billable = $fees->filter(fn(array $fee) => round((float) $fee['amount'] * $multiplier, 2) >= 0.01);

        if ($billable->isEmpty()) {
            return null;
        }

        $invoice = $this->invoiceRepository->create([
            'total_amount' => round((float) $billable->sum(fn(array $fee) => (float) $fee['amount']) * $multiplier, 2),
            'branch_id' => $branchId,
            'status' => Invoice::STATUS_PENDING,
        ]);

        foreach ($billable as $fee) {
            $this->admissionChargeRepository->create([
                'patient_admission_id' => $admission->patient_admission_id,
                'admission_period_id' => $period->admission_period_id,
                'invoice_id' => $invoice->invoice_id,
                'patient_diagnosis_id' => $fee['diagnosis']->patient_diagnosis_id,
                'diagnosis_case_id' => $fee['case']?->diagnosis_case_id,
                'amount' => round((float) $fee['amount'] * $multiplier, 2),
                'description' => $fee['description'] ?? null,
            ]);
        }

        Invoice::forgetPatientInvoiceIds();

        return $invoice;
    }

    // The fees set when the patient was admitted, as monthly amounts.
    public function baseFees(PatientAdmission $admission): Collection
    {
        $first = $admission->periods()
            ->where('reason', AdmissionPeriod::REASON_ADMITTED)
            ->orderBy('admission_period_id')
            ->first();

        if (!$first) {
            return collect();
        }

        return $this->admissionChargeRepository->forPeriod($first->admission_period_id)
            ->map(fn(AdmissionChargeDiagnosis $row) => [
                'diagnosis' => $row->patientDiagnosis,
                'case' => $row->diagnosisCase,
                'amount' => $row->monthlyAmount(),
                'description' => $row->description,
                'billed' => (float) $row->amount,
                'invoice' => $row->invoice,
            ])
            ->values();
    }

    // An extension bills the same fees again, by the extension's own cycle.
    public function billExtension(PatientAdmission $admission, AdmissionPeriod $next, int $branchId): ?Invoice
    {
        return $this->bill($admission, $next, $this->baseFees($admission), $branchId);
    }

    /*
        What an admission is billed for each diagnosis, keyed by diagnosis id,
        as monthly amounts.
    */
    public function admissionCharges(int $admissionId): Collection
    {
        $admission = $this->patientAdmissionRepository->findByFields([
            ['patient_admission_id', '=', $admissionId],
        ]);

        if (!$admission) {
            return collect();
        }

        return $this->baseFees($admission)
            ->keyBy(fn(array $fee) => $fee['diagnosis']->patient_diagnosis_id)
            ->map(fn(array $fee) => [
                'case_uuid' => $fee['case']?->uuid,
                'case_title' => $fee['case']?->title,
                'amount' => (float) $fee['amount'],
                'description' => $fee['description'],
                'billed_amount' => (float) $fee['billed'],
                'invoice_code' => $fee['invoice']?->invoice_code,
                'invoice_status' => $fee['invoice']?->status,
                'balance_due' => $fee['invoice'] ? (float) $fee['invoice']->balance_due : null,
            ]);
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
