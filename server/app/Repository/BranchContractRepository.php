<?php

namespace App\Repository;

use App\Models\BranchContract;

class BranchContractRepository
{

    public function create(array $payload)
    {
        return BranchContract::create($payload);
    }

    public function findByField(array $conditions)
    {
        return BranchContract::where($conditions)->first();
    }

    public function findAllByConditions(array $conditions)
    {
        return BranchContract::where($conditions)->get();
    }

    public function all(string $branchId)
    {
        return BranchContract::where('branch_id', $branchId)->get();
    }

    public function overview(array $payload, string $branchId)
    {
        return BranchContract::where('branch_id', $branchId)
            ->where('is_active', true)
            ->count();
    }
}
