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

    public function dischargingBy(int $branchId, Carbon $until)
    {
        return PatientAdmission::query()
            ->select(['patient_admission_id', 'patient_id', 'bed_id', 'discharged_at'])
            ->with([
                'patient:patient_id,uuid,first_name,last_name,suffix',
                'bed:bed_id,bed_no,room_id',
                'bed.room:room_id,room_no',
            ])
            ->where('status', PatientAdmission::STATUS_ADMITTED)
            ->whereNotNull('discharged_at')
            ->where('discharged_at', '<=', $until)
            ->whereHas('patient', fn($query) => $query->where('branch_id', $branchId))
            ->orderBy('discharged_at')
            ->get();
    }

    public function getContracts(string $branchId)
    {
        return BranchContract::where('branch_id', $branchId)
            ->where('category', 'Facility')
            ->get();
    }
}
