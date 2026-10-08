<?php

namespace App\Repository;

use App\Models\BranchContract;
use App\Models\PatientAdmission;
use Illuminate\Support\Carbon;

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

    public function findEndingOn(Carbon $date)
    {
        return PatientAdmission::with('patient.branch')
            ->where('status', PatientAdmission::STATUS_ADMITTED)
            ->whereDate('discharged_at', $date->toDateString())
            ->get();
    }

    public function getContracts(string $branchId)
    {
        return BranchContract::where('branch_id', $branchId)
            ->where('category', 'Facility')
            ->get();
    }
}
