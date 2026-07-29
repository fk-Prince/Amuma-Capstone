<?php

namespace App\Repository;

use App\Models\Patient;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PatientRepository
{

    public function create(array $payload)
    {
        return Patient::create($payload);
    }

    public function findByFields(array $conditions)
    {
        return Patient::where($conditions)->first();
    }

    public function getPatient(int $branchId)
    {
        return Patient::with([
            'location',
            'admissions' => function ($query) {
                $query->where('status', 'admitted');
            },
            'admissions.bed.room',
            'admissions.admissionContracts.branchContract',
            'schedules.location',
            'schedules.scheduleServices.service',
        ])
            ->where('branch_id', $branchId)
            ->get();
    }

    public function showPatient(string $uuid)
    {
        return Patient::with([
            'location',
            'admissions',
            'admissions.bed.room',
            'admissions.admissionContracts.branchContract',
            'schedules.location',
            'schedules.scheduleServices.service',
        ])
            ->where('uuid', $uuid)
            ->get();
    }
}
