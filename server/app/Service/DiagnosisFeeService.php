<?php

namespace App\Service;

use App\Models\AdmissionChargeDiagnosis;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Repository\AdmissionChargeDiagnosisRepository;
use App\Repository\DiagnosisCaseRepository;
use App\Repository\PatientRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DiagnosisFeeService
{
    public function __construct(
        private DiagnosisCaseRepository $diagnosisCaseRepository,
        private AdmissionChargeDiagnosisRepository $admissionChargeRepository,
        private PatientRepository $patientRepository,
    ) {}

    /*
        Diagnoses just recorded with the patient, each optionally carrying the
        diagnosis case the facility prices it under.
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
        [{patient_diagnosis_uuid, diagnosis_case_uuid}] when a new admission
        is created.
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

    // What the picked cases add to one period: their monthly prices, times
    // twelve when the period is billed yearly.
    public function total(Collection $fees, ?string $billingCycle = null): float
    {
        return round(
            (float) $fees->sum(fn(array $fee) => (float) $fee['amount'])
                * AdmissionChargeDiagnosis::multiplier($billingCycle),
            2
        );
    }

    /*
        Keeps what each diagnosis cost on the admission. The amount itself is
        billed inside the admission period's price on the invoice.
    */
    public function record(PatientAdmission $admission, Collection $fees): void
    {
        foreach ($fees as $fee) {
            $this->admissionChargeRepository->create([
                'patient_admission_id' => $admission->patient_admission_id,
                'patient_diagnosis_id' => $fee['diagnosis']->patient_diagnosis_id,
                'diagnosis_case_id' => $fee['case']->diagnosis_case_id,
                'amount' => round((float) $fee['amount'], 2),
                'description' => $fee['description'] ?? null,
            ]);
        }
    }

    public function replace(PatientAdmission $admission, Collection $fees): void
    {
        $this->admissionChargeRepository->deleteForAdmission($admission->patient_admission_id);

        $this->record($admission, $fees);
    }

    /*
        What an admission was billed for each diagnosis, keyed by diagnosis id.
    */
    public function admissionCharges(int $admissionId): Collection
    {
        return $this->admissionChargeRepository->forAdmission($admissionId)
            ->map(fn(AdmissionChargeDiagnosis $charge) => [
                'case_uuid' => $charge->diagnosisCase?->uuid,
                'amount' => (float) $charge->amount,
                'description' => $charge->description,
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
