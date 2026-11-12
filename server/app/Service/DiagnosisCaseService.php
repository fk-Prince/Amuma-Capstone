<?php

namespace App\Service;

use App\Models\DiagnosisCase;
use App\Repository\DiagnosisCaseRepository;
use App\Repository\PatientRepository;
use Exception;
use Illuminate\Validation\ValidationException;

class DiagnosisCaseService
{
    public function __construct(
        private DiagnosisCaseRepository $diagnosisCaseRepository,
        private PatientRepository $patientRepository,
        private DiagnosisFeeService $diagnosisFees,
    ) {}

    public function listForBranch(array $payload)
    {
        return response()->json([
            'data' => $this->diagnosisCaseRepository
                ->listForBranch($payload['branch_id'])
                ->map(fn(DiagnosisCase $case) => $this->format($case))
                ->values(),
        ]);
    }

    /*
        The patient's recorded diagnoses, each with the diagnosis case it was
        last billed under so a new admission can start from the same fee.
    */
    public function patientDiagnoses(array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']],
            ['branch_id', '=', $payload['branch_id']],
        ]);

        if (!$patient) {
            throw new Exception('Patient not found.', 404);
        }

        $diagnoses = $patient->diagnoses()
            ->orderByDesc('diagnosis_date')
            ->orderByDesc('patient_diagnosis_id')
            ->get();

        $lastCases = $this->diagnosisFees->lastCaseUuids($diagnoses->pluck('patient_diagnosis_id')->all());
        $admissionCharges = !empty($payload['patient_admission_id'])
            ? $this->diagnosisFees->admissionCharges((int) $payload['patient_admission_id'])
            : collect();

        return response()->json([
            'data' => $diagnoses->map(fn($diagnosis) => [
                'uuid' => $diagnosis->uuid,
                'diagnosis' => $diagnosis->diagnosis,
                'diagnosis_date' => $diagnosis->diagnosis_date?->toDateString(),
                'last_case_uuid' => $lastCases->get($diagnosis->patient_diagnosis_id),
                'admission_charge' => $admissionCharges->get($diagnosis->patient_diagnosis_id),
            ])->values(),
        ]);
    }

    public function store(array $payload)
    {
        $title = $this->cleanTitle($payload['title']);
        $this->ensureUniqueTitle((int) $payload['branch_id'], $title);

        $case = $this->diagnosisCaseRepository->create([
            'branch_id' => $payload['branch_id'],
            'title' => $title,
            'description' => filled($payload['description'] ?? null) ? trim($payload['description']) : null,
            'price' => $payload['price'],
        ]);

        return response()->json([
            'message' => 'Diagnosis case added.',
            'data' => $this->format($case),
        ], 201);
    }

    public function update(array $payload, string $uuid)
    {
        $case = $this->diagnosisCaseRepository->findInBranch($uuid, $payload['branch_id']);

        if (!$case) {
            throw new Exception('Diagnosis case not found.', 404);
        }

        $title = $this->cleanTitle($payload['title']);
        $this->ensureUniqueTitle((int) $payload['branch_id'], $title, $case->diagnosis_case_id);

        $case = $this->diagnosisCaseRepository->update($case, [
            'title' => $title,
            'description' => filled($payload['description'] ?? null) ? trim($payload['description']) : null,
            'price' => $payload['price'],
        ]);

        return response()->json([
            'message' => 'Diagnosis case updated.',
            'data' => $this->format($case),
        ]);
    }

    private function cleanTitle(string $title): string
    {
        return preg_replace('/\s+/', ' ', trim($title));
    }

    private function ensureUniqueTitle(int $branchId, string $title, ?int $exceptId = null): void
    {
        if ($this->diagnosisCaseRepository->titleTaken($branchId, $title, $exceptId)) {
            throw ValidationException::withMessages([
                'title' => "This branch already has a diagnosis case named {$title}.",
            ]);
        }
    }

    private function format(DiagnosisCase $case): array
    {
        return [
            'uuid' => $case->uuid,
            'title' => $case->title,
            'description' => $case->description,
            'price' => (float) $case->price,
        ];
    }
}
