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
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiagnosisFeeService
{
    public function __construct(
        private DiagnosisCaseRepository $diagnosisCaseRepository,
        private AdmissionChargeDiagnosisRepository $admissionChargeRepository,
        private InvoiceRepository $invoiceRepository,
        private PatientRepository $patientRepository,
        private AdmissionPeriodService $periods,
    ) {}


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

            $billed = $entry['billed'] ?? AdmissionDiagnosis::BILLED_ONE_TIME;

            if (!in_array($billed, [AdmissionDiagnosis::BILLED_ONE_TIME, AdmissionDiagnosis::BILLED_MONTHLY], true)) {
                throw ValidationException::withMessages([
                    "diagnoses.$index.billed" => 'Choose one time or monthly.',
                ]);
            }

            $fee['billed'] = $billed;

            $fees->push($fee);
        }

        return $fees->unique(fn(array $fee) => $fee['diagnosis']->patient_diagnosis_id)->values();
    }

    public function findDiagnosis(Patient $patient, string $uuid)
    {
        return $this->patientRepository->findDiagnosis($patient, $uuid);
    }


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
            $row = $this->admissionChargeRepository->createDiagnosis([
                'admission_charge_diagnosis_id' => $charge->admission_charge_diagnosis_id,
                'patient_diagnosis_id' => $fee['diagnosis']->patient_diagnosis_id,
                'diagnosis_case_id' => $fee['case']?->diagnosis_case_id,
                'description' => $fee['description'] ?? null,
                'amount' => round((float) $fee['amount'], 2),
            ] + $this->recurrence($fee));

            // The newest charge carries the monthly billing from here on.
            $this->admissionChargeRepository->stopRecurring(
                $admission->patient_admission_id,
                $fee['diagnosis']->patient_diagnosis_id,
                $row->admission_diagnosis_id
            );
        }

        Invoice::forgetPatientInvoiceIds();

        return $invoice;
    }

    public function recurrence(array $fee, ?AdmissionDiagnosis $row = null): array
    {
        if (($fee['billed'] ?? null) !== AdmissionDiagnosis::BILLED_MONTHLY) {
            return ['billed' => AdmissionDiagnosis::BILLED_ONE_TIME, 'is_active' => false, 'date_billed' => null];
        }

        $next = $fee['date_billed']
            ?? ($row?->is_active ? $row->date_billed?->toDateString() : null)
            ?? now()->addMonthNoOverflow()->toDateString();

        return ['billed' => AdmissionDiagnosis::BILLED_MONTHLY, 'is_active' => true, 'date_billed' => $next];
    }


    public function billDue(?Carbon $today = null): int
    {
        $billed = 0;

        foreach ($this->admissionChargeRepository->dueForRecurring($today ?? now()) as $due) {
            DB::transaction(function () use ($due, &$billed) {
                $row = $this->admissionChargeRepository->lockRecurring($due->admission_diagnosis_id);

                $admission = $row?->charge?->admissionPeriod?->patientAdmission;
                $period = $admission ? $this->periods->current($admission) : null;

                if (!$row || !$period) {
                    return;
                }

                $invoice = $this->bill($admission, $period, collect([[
                    'diagnosis' => $row->patientDiagnosis,
                    'case' => $row->diagnosisCase,
                    'amount' => $row->amount,
                    'description' => $row->description,
                    'billed' => AdmissionDiagnosis::BILLED_MONTHLY,
                    'date_billed' => $row->date_billed->copy()->addMonthNoOverflow()->toDateString(),
                ]]), $admission->patient->branch_id);

                if ($invoice) {
                    $billed++;
                }
            });
        }

        return $billed;
    }

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
                    'billed' => $row->billed ?? AdmissionDiagnosis::BILLED_ONE_TIME,
                    'next_bill_date' => $row->is_active ? $row->date_billed?->toDateString() : null,
                ];
            });
    }


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
