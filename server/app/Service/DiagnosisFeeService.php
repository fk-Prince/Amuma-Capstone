<?php

namespace App\Service;

use App\Models\AdmissionChargeDiagnosis;
use App\Models\AdmissionDiagnosis;
use App\Models\AdmissionPeriod;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Repository\AdmissionChargeDiagnosisRepository;
use App\Repository\DiagnosisCaseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PatientAdmissionRepository;
use App\Repository\PatientRepository;
use Carbon\Carbon;
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
        private AdmissionPeriodService $periods,
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
        The months of a period a diagnosis case still bills when it is added.
        A waiting admission, or an extension, bills the whole period: one month,
        or twelve when yearly. Once the stay is running, every month that has
        passed is dropped, and a month more than a week in counts as passed.
    */
    public function billableMonths(PatientAdmission $admission, AdmissionPeriod $period, ?Carbon $asOf = null): int
    {
        $period->loadMissing('branchContract');

        $full = AdmissionChargeDiagnosis::multiplier($period->branchContract?->billing_cycle);

        if ($admission->status !== PatientAdmission::STATUS_ADMITTED || !$period->start_date) {
            return $full;
        }

        $start = Carbon::parse($period->start_date);
        $now = $asOf ?? now();

        if ($now->lte($start)) {
            return $full;
        }

        $passed = (int) floor($start->diffInMonths($now, true));

        if ($start->copy()->addMonths($passed)->diffInDays($now, true) > 7) {
            $passed++;
        }

        return max(0, $full - $passed);
    }

    // What adding a diagnosis case to the admission right now would bill, as
    // months of the current period against the whole period.
    public function monthsForAdmission(int $admissionId): ?array
    {
        $admission = $this->patientAdmissionRepository->findByFields([
            ['patient_admission_id', '=', $admissionId],
        ]);

        $period = $admission ? $this->periods->current($admission) : null;

        if (!$period) {
            return null;
        }

        $period->loadMissing('branchContract');

        return [
            'billable' => $this->billableMonths($admission, $period),
            'full' => AdmissionChargeDiagnosis::multiplier($period->branchContract?->billing_cycle),
        ];
    }

    /*
        Bills the fees for one admission period on an invoice of their own and
        keeps one row per diagnosis against it, for the months given or the
        whole period. Returns null when there is nothing to bill. With no
        months left the diagnoses are still recorded on the period, without an
        invoice, so the next period bills them.
    */
    public function bill(PatientAdmission $admission, AdmissionPeriod $period, Collection $fees, int $branchId, ?int $months = null): ?Invoice
    {
        $period->loadMissing('branchContract');

        $months ??= AdmissionChargeDiagnosis::multiplier($period->branchContract?->billing_cycle);

        $billable = $fees->filter(fn(array $fee) => round((float) $fee['amount'], 2) >= 0.01);

        if ($billable->isEmpty()) {
            return null;
        }

        $total = round((float) $billable->sum(fn(array $fee) => (float) $fee['amount']) * $months, 2);

        $invoice = $total >= 0.01
            ? $this->invoiceRepository->create([
                'total_amount' => $total,
                'branch_id' => $branchId,
                'status' => Invoice::STATUS_PENDING,
            ])
            : null;

        $charge = $this->admissionChargeRepository->createCharge([
            'admission_period_id' => $period->admission_period_id,
            'invoice_id' => $invoice?->invoice_id,
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

    // The fees on the admission so far, as monthly amounts.
    public function baseFees(PatientAdmission $admission): Collection
    {
        return $this->admissionChargeRepository->forAdmission($admission->patient_admission_id)
            ->map(fn(AdmissionDiagnosis $row) => [
                'diagnosis' => $row->patientDiagnosis,
                'case' => $row->diagnosisCase,
                'amount' => $row->monthlyAmount(),
                'description' => $row->description,
                'billed' => $row->billed_amount,
                'invoice' => $row->charge?->invoice,
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

        $editable = $this->admissionChargeRepository->editableDiagnosisIds($admissionId);

        return $this->baseFees($admission)
            ->keyBy(fn(array $fee) => $fee['diagnosis']->patient_diagnosis_id)
            ->map(fn(array $fee, $diagnosisId) => [
                'editable' => $editable->contains($diagnosisId),
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
