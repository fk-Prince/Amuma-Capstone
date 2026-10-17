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
        private NotificationService $notificationService,
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

            $fee['billed'] = ($entry['billed'] ?? null) === AdmissionDiagnosis::BILLED_MONTHLY
                ? AdmissionDiagnosis::BILLED_MONTHLY
                : null;
            $fee['is_active'] = $fee['billed'] !== null
                && filter_var($entry['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $fee['start_next'] = $fee['billed'] !== null && ($entry['start'] ?? null) === 'next';

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

        $now = $billable->reject(fn(array $fee) => !empty($fee['start_next']));
        $later = $billable->filter(fn(array $fee) => !empty($fee['start_next']));

        $invoice = null;

        if ($now->isNotEmpty()) {
            $total = round((float) $now->sum(fn(array $fee) => (float) $fee['amount']), 2);

            $invoice = $this->invoiceRepository->create([
                'total_amount' => $total,
                'branch_id' => $branchId,
                'status' => Invoice::STATUS_PENDING,
            ]);

            $this->recordCharge($admission, $period, $now, $invoice, $total);
        }

        if ($later->isNotEmpty()) {
            $this->recordCharge($admission, $period, $later, null, 0);
        }

        Invoice::forgetPatientInvoiceIds();

        return $invoice;
    }

    private function recordCharge(PatientAdmission $admission, AdmissionPeriod $period, Collection $fees, ?Invoice $invoice, float $total): void
    {
        $charge = $this->admissionChargeRepository->createCharge([
            'admission_period_id' => $period->admission_period_id,
            'invoice_id' => $invoice?->invoice_id,
            'amount' => $total,
        ]);

        foreach ($fees as $fee) {
            $row = $this->admissionChargeRepository->createDiagnosis([
                'admission_charge_diagnosis_id' => $charge->admission_charge_diagnosis_id,
                'patient_diagnosis_id' => $fee['diagnosis']->patient_diagnosis_id,
                'diagnosis_case_id' => $fee['case']?->diagnosis_case_id,
                'description' => $fee['description'] ?? null,
                'amount' => round((float) $fee['amount'], 2),
            ] + $this->recurrence($fee, $admission));

            $this->admissionChargeRepository->stopRecurring(
                $admission->patient_admission_id,
                $fee['diagnosis']->patient_diagnosis_id,
                $row->admission_diagnosis_id
            );
        }
    }
    public function recurrence(array $fee, PatientAdmission $admission, ?AdmissionDiagnosis $row = null): array
    {
        if (($fee['billed'] ?? null) !== AdmissionDiagnosis::BILLED_MONTHLY) {
            return ['billed' => null, 'is_active' => false, 'date_billed' => null];
        }

        $date = $fee['date_billed'] ?? null;

        if (!$date && $row?->billed === AdmissionDiagnosis::BILLED_MONTHLY && $row->date_billed?->gte(today())) {
            $date = $row->date_billed->toDateString();
        }

        return [
            'billed' => AdmissionDiagnosis::BILLED_MONTHLY,
            'is_active' => (bool) ($fee['is_active'] ?? true),
            'date_billed' => $date ?? $this->nextAnniversary($admission, today()),
        ];
    }

    public function nextAnniversary(PatientAdmission $admission, Carbon $after): string
    {
        $start = Carbon::parse($admission->admitted_at)->startOfDay();
        $months = 1;

        do {
            $next = $start->copy()->addMonthsNoOverflow($months++);
        } while ($next->lte($after));

        return $next->toDateString();
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
                    'is_active' => true,
                    'date_billed' => $this->nextAnniversary($admission, $row->date_billed),
                ]]), $admission->patient->branch_id);

                if ($invoice) {
                    $billed++;

                    $this->notificationService->notifyPatientAccess(
                        $admission->patient,
                        sprintf(
                            '%s was billed again for %s (%s): ₱%s on invoice %s. Monthly billing continues on %s.',
                            $admission->patient->display_name,
                            $row->diagnosisCase?->title ?? 'a diagnosis case',
                            $row->patientDiagnosis?->diagnosis ?? 'diagnosis',
                            number_format((float) $row->amount, 2),
                            $invoice->invoice_code,
                            Carbon::parse($this->nextAnniversary($admission, $row->date_billed))->format('M j, Y')
                        ),
                        'Diagnosis Case Charge',
                        null,
                        null,
                        $invoice->invoice_code
                    );
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
                    'billed' => $row->billed,
                    'is_active' => (bool) $row->is_active,
                    'next_bill_date' => $row->billed ? $row->date_billed?->toDateString() : null,
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
