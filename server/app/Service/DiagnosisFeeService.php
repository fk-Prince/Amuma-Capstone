<?php

namespace App\Service;

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

            $fees->push($this->pair($diagnosis, $entry['diagnosis_case_uuid'], $branchId, $index));
        }

        return $fees->unique(fn(array $fee) => $fee['diagnosis']->patient_diagnosis_id)->values();
    }

    public function total(Collection $fees): float
    {
        return round((float) $fees->sum(fn(array $fee) => (float) $fee['case']->price), 2);
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
                'amount' => round((float) $fee['case']->price, 2),
            ]);
        }
    }

    public function replace(PatientAdmission $admission, Collection $fees): void
    {
        $this->admissionChargeRepository->deleteForAdmission($admission->patient_admission_id);

        $this->record($admission, $fees);
    }

    /*
        The diagnosis case an admission was billed under, keyed by diagnosis id.
    */
    public function admissionCaseUuids(int $admissionId): Collection
    {
        $caseIds = $this->admissionChargeRepository->caseIdsForAdmission($admissionId);

        if ($caseIds->isEmpty()) {
            return collect();
        }

        $cases = $this->diagnosisCaseRepository->findManyByIds($caseIds->unique()->values()->all());

        return $caseIds->map(fn($caseId) => $cases->get($caseId)?->uuid)->filter();
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

        return ['diagnosis' => $diagnosis, 'case' => $case];
    }
}
