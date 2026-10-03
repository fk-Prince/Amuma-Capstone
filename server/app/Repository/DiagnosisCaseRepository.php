<?php

namespace App\Repository;

use App\Models\DiagnosisCase;

class DiagnosisCaseRepository
{
    public function listForBranch(int $branchId)
    {
        return DiagnosisCase::where('branch_id', $branchId)
            ->orderBy('title')
            ->get();
    }

    public function findManyByIds(array $ids)
    {
        return DiagnosisCase::whereIn('diagnosis_case_id', $ids)->get()->keyBy('diagnosis_case_id');
    }

    public function findInBranch(string $uuid, int $branchId): ?DiagnosisCase
    {
        return DiagnosisCase::where('uuid', $uuid)
            ->where('branch_id', $branchId)
            ->first();
    }

    public function create(array $payload): DiagnosisCase
    {
        return DiagnosisCase::create($payload);
    }

    public function update(DiagnosisCase $case, array $payload): DiagnosisCase
    {
        $case->update($payload);

        return $case;
    }
}
