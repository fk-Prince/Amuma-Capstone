<?php

namespace App\Service;

use App\Models\DiagnosisCase;
use App\Repository\DiagnosisCaseRepository;
use Exception;

class DiagnosisCaseService
{
    public function __construct(private DiagnosisCaseRepository $diagnosisCaseRepository) {}

    public function listForBranch(array $payload)
    {
        return response()->json([
            'data' => $this->diagnosisCaseRepository
                ->listForBranch($payload['branch_id'])
                ->map(fn(DiagnosisCase $case) => $this->format($case))
                ->values(),
        ]);
    }

    public function store(array $payload)
    {
        $case = $this->diagnosisCaseRepository->create([
            'branch_id' => $payload['branch_id'],
            'title' => trim($payload['title']),
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

        $case = $this->diagnosisCaseRepository->update($case, [
            'title' => trim($payload['title']),
            'description' => filled($payload['description'] ?? null) ? trim($payload['description']) : null,
            'price' => $payload['price'],
        ]);

        return response()->json([
            'message' => 'Diagnosis case updated.',
            'data' => $this->format($case),
        ]);
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
