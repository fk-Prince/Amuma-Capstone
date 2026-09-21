<?php

namespace App\Repository;

use App\Models\Patient;
use Illuminate\Support\Str;
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
    public function update(Patient $patient, array $payload)
    {
        if (array_key_exists('address', $payload)) {
            $address = $payload['address'];
            unset($payload['address']);

            if ($patient->location) {
                $patient->location->update(['full_address' => $address]);
            } elseif ($address) {
                $payload['location_id'] = $this->locationRepository
                    ->create(['full_address' => $address])
                    ->location_id;
            }
        }

        $patient->update($payload);

        return $patient->load('location');
    }

    public function findByFields(array $conditions)
    {
        return Patient::where($conditions)->first();
    }


    private function requestedSections(array $payload): array
    {
        $sections = $payload['sections'] ?? null;

        if (is_string($sections)) {
            $sections = array_filter(explode(',', $sections));
        }

        return $sections ? array_map('trim', (array) $sections) : ['all'];
    }

    private function wants(array $sections, string $section): bool
    {
        return in_array('all', $sections, true)
            || in_array($section, $sections, true);
    }

    private function listRelations(array $sections, string $admissionRelation): array
    {
        $relations = ['location'];

        if ($this->wants($sections, 'care')) {
            $relations[] = 'currentAdmission';
            $relations[] = 'latestAdmission';
            $relations[] = 'schedules';
        }

        if ($this->wants($sections, 'assessment')) {
            $relations[] = 'assessments';
            $relations[] = 'diagnoses';
        }

        if ($this->wants($sections, 'schedules')) {
            $relations[] = 'schedules.location';
            $relations[] = 'schedules.scheduleServices.service';
        }

        if ($this->wants($sections, 'family')) {
            $relations[] = 'patientAccess.client.user';
        }

        if ($this->wants($sections, 'admissions')) {
            $relations[] = 'currentAdmission';
            $relations[] = 'latestAdmission';
            $relations[] = "{$admissionRelation}.bed.room";
            $relations[] = "{$admissionRelation}.currentPeriod.branchContract";
            $relations[] = "{$admissionRelation}.currentInvoiceAdmission.invoice";
        }

        return array_values(array_unique($relations));
    }

    public function getPatient(array $payload)
    {
        $sections = $this->requestedSections($payload);

        if (!empty($payload['type']) && $payload['type'] === 'admission') {
            return Patient::with($this->listRelations($sections, 'latestAdmission'))
                ->where('branch_id', $payload['branch_id'])
                ->whereHas('latestAdmission')
                ->when(!empty($payload['assigned_employee_id']), function ($query) use ($payload) {
                    $query->whereHas('schedules.scheduleServices.assigned', function ($q) use ($payload) {
                        $q->where('employee_id', $payload['assigned_employee_id'])
                            ->where('is_active', true);
                    });
                })
                ->when(!empty($payload['search']), function ($query) use ($payload) {
                    $search = $payload['search'];

                    $query->where(function ($q) use ($search) {
                        $q->where('patient_code', 'ilike', "%{$search}%")
                            ->orWhere('first_name', 'ilike', "%{$search}%")
                            ->orWhere('last_name', 'ilike', "%{$search}%")
                            ->orWhere('middle_name', 'ilike', "%{$search}%")
                            ->orWhereRaw(
                                "LOWER(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?",
                                ['%' . strtolower($search) . '%']
                            );

                        if (Str::isUuid($search)) {
                            $q->orWhere('uuid', '=', $search);
                        }
                    });
                })
                ->paginate($payload['per_page'] ?? 10);
        }


        $relations = $this->listRelations($sections, 'admissions');

        if ($this->wants($sections, 'admissions')) {
            $relations['admissions'] = function ($query) {
                $query->where('status', 'admitted');
            };
        }

        return Patient::with($relations)
            ->where('branch_id', $payload['branch_id'])
            ->when(!empty($payload['assigned_employee_id']), function ($query) use ($payload) {
                $query->whereHas('schedules.scheduleServices.assigned', function ($q) use ($payload) {
                    $q->where('employee_id', $payload['assigned_employee_id'])
                        ->where('is_active', true);
                });
            })
            ->when(!empty($payload['search']), function ($query) use ($payload) {
                $search = $payload['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('patient_code', 'ilike', "{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
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
            'assessments',
            'diagnoses',
            'admissions.bed.room',
            'admissions.invoiceAdmission.admissionPeriod.branchContract',
            'admissions.invoiceAdmission.invoice',
            'admissions.invoiceAdmission.invoice.allocations.refundAllocations',

            'admissions.currentPeriod.branchContract',
            'admissions.currentPeriod.invoiceAdmissionLines.invoice.allocations.refundAllocations',
            'admissions.currentPeriod.invoiceAdmissionLines.invoice.invoiceAdjustments',
            'admissions.latestPeriod.branchContract',

            // formatFuturePeriods() reads periods, not futurePeriods.
            'admissions.periods.branchContract',
            'admissions.periods.invoiceAdmissionLines.invoice',
            'admissions.periods.invoiceAdmissionLines.admissionPeriod.branchContract',

            'admissions.futurePeriods.invoiceAdmissionLines.invoice.allocations.refundAllocations',
            'admissions.futurePeriods.invoiceAdmissionLines.invoice.invoiceAdjustments',
            'admissions.currentInvoiceAdmission.invoice',
            'admissions.currentInvoiceAdmission.invoice.allocations.refundAllocations',
            'currentAdmission',
            'latestAdmission',

            'patientAccess.client.user',

            'schedules.location',
            'schedules.scheduleServices.service',
        ])
            ->withCount(['medications', 'vitals'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findForReport(string $uuid)
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return null;
        }

        return Patient::with([
            'branch.location',
            'branch.agencies',
            'location',
            'assessments',
            'diagnoses',

            'admissions.bed.room',

            'schedules.location',
            'schedules.scheduleServices.service',

            'medications',
            'vitals',
            'activities',
        ])
            ->where('uuid', $uuid)
            ->first();
    }
}
