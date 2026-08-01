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

    public function getPatient(array $payload)
    {
        return Patient::with([
            'location',
            'admissions' => function ($query) {
                $query->where('status', 'admitted');
            },
            'admissions.bed.room',
            'admissions.invoiceAdmission.branchContract',
            'schedules.location',
            'schedules.scheduleServices.service',
        ])
            ->where('branch_id', $payload['branch_id'])
            ->when(!empty($payload['search']), function ($query) use ($payload) {
                $search = $payload['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->paginate(
                $payload['per_page'] ?? 10,
            );;
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
