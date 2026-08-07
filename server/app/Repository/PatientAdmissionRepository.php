<?php

namespace App\Repository;

use App\Models\BranchContract;
use App\Models\PatientAdmission;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    public function getContractsByRoom(string $admissionId)
    {
        $room = PatientAdmission::with('bed.room')
            ->find($admissionId)
            ?->bed
            ?->room;

        return BranchContract::where('branch_id', $room->branch_id)
            ->where('accommodation_type', $room->room_type)
            ->get();
    }
}
