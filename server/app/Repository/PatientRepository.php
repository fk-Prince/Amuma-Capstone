<?php

namespace App\Repository;

use App\Models\Patient;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PatientRepository
{

    public function __construct(
        private LocationRepository $locationRepository,
    ) {}

    public function create(array $payload)
    {
        if (!empty($payload['address'])) {
            $scheduledLocation = $this->locationRepository->create([
                'full_address' => $payload['address'],
            ]);
            $payload['location_id'] = $scheduledLocation->location_id;
        }
        return Patient::create($payload);
    }
    public function findByFields(array $conditions)
    {
        return Patient::where($conditions)->first();
    }

    public function getPatient(array $payload)
    {
        if (!empty($payload['type']) && $payload['type'] === 'admission') {
            return Patient::with([
                'location',
                'latestAdmission.bed.room',
                'latestAdmission.admissionContract',
                'latestAdmission.invoiceAdmission.branchContract',
                'schedules.location',
            ])
                ->where('branch_id', $payload['branch_id'])
                ->whereHas('latestAdmission')
                ->when(!empty($payload['search']), function ($query) use ($payload) {
                    $search = $payload['search'];

                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('uuid', '=', $search);
                    });
                })
                ->paginate($payload['per_page'] ?? 10);
        }


        return Patient::with([
            'location',
            'admissions' => function ($query) {
                $query->where('status', 'admitted');
            },
            'admissions.bed.room',
            'admissions.admissionContract',
            'schedules.location',
            'schedules.scheduleServices.service',
        ])
            ->where('branch_id', $payload['branch_id'])
            ->when(!empty($payload['search']), function ($query) use ($payload) {
                $search = $payload['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhereRaw(
                            "LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?",
                            ['%' . strtolower($search) . '%']
                        );
                });
            })

            ->when(!empty($payload['category']) && $payload['category'] !== 'all', function ($query) use ($payload) {
                if ($payload['category'] === 'Homecare') {
                    $query->whereHas('schedules', function ($subQuery) use ($payload) {
                        $subQuery->where('category', ucfirst($payload['category']));
                    });
                } elseif ($payload['category'] === 'Facility') {
                    $query->whereHas('admissions');
                }
            })
            // ->when(!empty($payload['category']) && $payload['category'] !== 'all', function ($query) use ($payload) {
            //     $query->whereHas('admissions.admissionContract', function ($q) use ($payload) {
            //         $q->where('category', $payload['category']);
            //     });
            // })
            ->when(!empty($payload['date_from']), function ($query) use ($payload) {
                $query->where('created_at', '>=', $payload['date_from'] . ' 00:00:00');
            })
            ->when(!empty($payload['date_to']), function ($query) use ($payload) {
                $query->where('created_at', '<=', $payload['date_to'] . ' 23:59:59');
            })
            ->paginate($payload['per_page'] ?? 10);
    }

    public function showPatient(string $uuid)
    {
        return Patient::with([
            'location',
            'admissions',
            'admissions.bed.room',
            'admissions.invoiceAdmission.branchContract',
            'admissions.invoiceAdmission.invoice',
            'latestAdmission.admissionContract',
            'latestAdmission.invoiceAdmission.branchContract',
            'latestAdmission.invoiceAdmission.invoice',
            'latestAdmission.bed.room',
            'schedules.location',
            'schedules.scheduleServices.service',
        ])
            ->where('uuid', $uuid)
            ->first();
        // return Patient::with([
        //     'location',
        //     'admissions',
        //     'admissions.bed.room',
        //     'admissions.invoiceAdmission.branchContract',
        //     'admissions.invoiceAdmission.invoice',
        //     'latestAdmission.admissionContract',
        //     'latestAdmission.admissionContract.invoiceAdmission.invoice',
        //     'latestAdmission.bed.room',
        //     'schedules.location',
        //     'schedules.scheduleServices.service',
        // ])
        //     ->where('uuid', $uuid)
        //     ->first();
    }
}
