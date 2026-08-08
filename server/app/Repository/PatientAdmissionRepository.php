<?php

namespace App\Repository;

use App\Models\BranchContract;
use App\Models\PatientAdmission;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class PatientAdmissionRepository
{
    public function findByFields(array $conditions)
    {
        return PatientAdmission::where($conditions)->first();
    }

    public function create(array $payload)
    {
        return PatientAdmission::create($payload);
    }

    public function getContracts(string $branchId)
    {
        return BranchContract::where('branch_id', $branchId)
            ->where('category', 'Facility')
            ->get();
    }
}
