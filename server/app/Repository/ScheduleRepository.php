<?php

namespace App\Repository;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScheduleRepository
{

    public function create(array $payload)
    {
        return Schedule::create($payload);
    }

    public function findByFields(array $conditions)
    {
        return Schedule::where($conditions)->first();
    }

    public function retrievePaginate(array $payload)
    {
        $query = Schedule::query()
            ->with([
                'patient.location',
                'patient.admissions.bed.room',
                'patient.currentAdmission.bed.room',
                'scheduleServices.service',
                'scheduleServices.assigned.employee.employees',
                'scheduleServices.assigned.onlineSchedules',
            ]);

        if (!empty($payload['patient_uuid'])) {
            $query->whereHas('patient', function ($q) use ($payload) {
                $q->where('uuid', $payload['patient_uuid']);
            });
        }

        if (!empty($payload['branch_uuid'])) {
            $query->whereHas('patient.branch', function ($q) use ($payload) {
                $q->where('uuid', $payload['branch_uuid']);
            });
        }

        if (!empty($payload['status'])) {
            $query->whereIn('status', (array) $payload['status']);
        }

        if (!empty($payload['category'])) {
            $query->whereIn('category', (array) $payload['category']);
        }

        if (!empty($payload['date'])) {
            $query->whereDate('scheduled_at', $payload['date']);
        }

        if (!empty($payload['type'])) {
            $types = array_map('strtolower', (array) $payload['type']);

            $query->whereHas('scheduleServices', function ($q) use ($types) {
                $q->where(function ($sub) use ($types) {
                    if (in_array('medical', $types, true)) {
                        $sub->orWhereNotNull('service_id');
                    }
                    if (in_array('adl', $types, true)) {
                        $sub->orWhereNotNull('hours_booked');
                    }
                });
            });
        }

        if (!empty($payload['date_from'])) {
            $query->whereDate('scheduled_at', '>=', $payload['date_from']);
        }

        if (!empty($payload['date_to'])) {
            $query->whereDate('scheduled_at', '<=', $payload['date_to']);
        }

        if (!empty($payload['search'])) {
            $search = $payload['search'];
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if (!empty($payload['service_id'])) {
            $query->whereHas('scheduleServices', function ($q) use ($payload) {
                $q->where('service_id', $payload['service_id']);
            });
        }

        if (!empty($payload['employee_id'])) {
            $query->whereHas('scheduleServices.assigned', function ($q) use ($payload) {
                $q->where('employee_id', $payload['employee_id']);
            });
        }

        $sortBy = $payload['sort_by'] ?? 'scheduled_at';
        $sortDir = strtolower($payload['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['scheduled_at', 'status', 'category', 'schedule_id'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'scheduled_at';
        }

        $query->orderBy($sortBy, $sortDir);

        // $perPage = min((int) ($payload['per_page'] ?? 15), 100);
        if (isset($payload['per_page'])) {
            $perPage = min((int) $payload['per_page'], 100);
            return $query->paginate($perPage);
        }

        // return $query->paginate($perPage)->withQueryString();
        return $query->get();
    }



    public function getEmployeesForReassignment(
        string $scheduleId,
        string $branchId,
        string $date,
        string $preferredTime
    ) {
        $targetStart = Carbon::parse("{$date} {$preferredTime}");

        $targetSchedule = Schedule::with('scheduleServices.service')
            ->where('schedule_id', $scheduleId)
            ->firstOrFail();

        $targetDurationMinutes = $targetSchedule->scheduleServices
            ->sum(function ($ss) {
                $duration = $ss->service->maximum_duration ?? null;
                if (!$duration) {
                    return 60;
                }
                [$hours, $minutes, $seconds] = explode(':', $duration);
                return ((int) $hours * 60) + (int) $minutes + ((int) $seconds / 60);
            });

        $targetEnd = $targetStart->copy()->addMinutes($targetDurationMinutes);

        $scheduleServiceIds = $targetSchedule->scheduleServices
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->values();

        $allowedRoles = ['nurse', 'caregiver'];

        return Employee::with([
            'locations',
            'employeeBranch.scheduleAssignments.scheduleService.schedule',
            'employeeBranch' => function ($query) use ($branchId, $allowedRoles) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles)
                    ->with([
                        'branches',
                        'employeeServices' => function ($q) {
                            $q->where('is_active', true)->with('services');
                        },
                    ]);
            },
        ])
            ->where('status', 'active')
            ->whereHas('employeeBranch', function ($query) use ($branchId, $allowedRoles) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles);
            })
            ->withExists(['employeeBranch as is_busy' => function ($query) use ($targetStart, $targetEnd, $scheduleId, $branchId, $allowedRoles) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles)
                    ->whereHas('scheduleAssignments.scheduleService.schedule', function ($q) use ($targetStart, $targetEnd, $scheduleId) {
                        $q->where('schedules.schedule_id', '!=', $scheduleId)
                            ->whereIn('schedules.status', [Schedule::STATUS_ONGOING, Schedule::STATUS_PENDING])
                            ->where('schedules.scheduled_at', '<', $targetEnd)
                            ->whereRaw(
                                'schedules.scheduled_at + (
                                SELECT COALESCE(SUM(EXTRACT(EPOCH FROM sv.maximum_duration)), 3600) / 60
                                FROM schedule_services ss
                                INNER JOIN services sv ON sv.service_id = ss.service_id
                                WHERE ss.schedule_id = schedules.schedule_id
                            ) * INTERVAL \'1 minute\' > ?',
                                [$targetStart]
                            )->select([
                                'schedule_id',
                                'schedule_code',
                                'scheduled_at'
                            ]);
                    });
            }])
            ->withExists(['employeeBranch as is_assigned' => function ($query) use ($branchId, $scheduleServiceIds, $allowedRoles) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles)
                    ->whereHas('employeeServices', function ($q) use ($scheduleServiceIds) {
                        $q->where('is_active', true)
                            ->whereIn('service_id', $scheduleServiceIds);
                    });
            }])
            ->get();
    }

    public function getEmployeeAvailable(
        array $serviceIds,
        string $branchId,
        string $date,
        string $preferredTime,
        ?float $timeSpanHours = null
    ) {
        $targetStart = Carbon::parse("{$date} {$preferredTime}");
        $allowedRoles = ['nurse', 'caregiver'];
        if ($timeSpanHours !== null) {
            $targetDurationMinutes = $timeSpanHours * 60;
        } else {
            $targetDurationMinutes = Service::whereIn('service_id', $serviceIds)
                ->get()
                ->sum(function ($service) {
                    $duration = $service->maximum_duration ?? null;

                    if (!$duration) {
                        return 60;
                    }

                    [$hours, $minutes, $seconds] = array_pad(
                        explode(':', $duration),
                        3,
                        0
                    );

                    return ((int) $hours * 60)
                        + (int) $minutes
                        + ((int) $seconds / 60);
                });
        }
        $targetEnd = $targetStart->copy()->addMinutes($targetDurationMinutes);
        $conflictRawSql = 'schedules.scheduled_at + (
                    SELECT COALESCE(
                        SUM(
                            CASE
                                WHEN ss.service_id IS NOT NULL THEN EXTRACT(EPOCH FROM sv.maximum_duration) / 60
                                WHEN ss.hours_booked IS NOT NULL THEN ss.hours_booked * 60
                                ELSE 60
                            END
                        ),
                        60
                    )
                    FROM schedule_services ss
                    LEFT JOIN services sv ON sv.service_id = ss.service_id
                    WHERE ss.schedule_id = schedules.schedule_id
                ) * INTERVAL \'1 minute\' > ?';
        $conflictScheduleConstraint = function ($q) use ($targetStart, $targetEnd, $conflictRawSql) {
            $q->whereIn('schedules.status', [Schedule::STATUS_ONGOING, Schedule::STATUS_PENDING])
                ->where('schedules.scheduled_at', '<', $targetEnd)
                ->whereRaw($conflictRawSql, [$targetStart]);
        };

        $busyClosure = function ($query) use ($branchId, $allowedRoles, $conflictScheduleConstraint) {
            $query->where('branch_id', $branchId)
                ->whereIn('role_name', $allowedRoles)
                ->whereHas('scheduleAssignments.scheduleService.schedule', $conflictScheduleConstraint);
        };

        $conflictCountClosure = function ($query) use ($branchId, $allowedRoles, $conflictScheduleConstraint) {
            $query->where('branch_id', $branchId)
                ->whereIn('role_name', $allowedRoles)
                ->whereHas('scheduleAssignments.scheduleService.schedule', $conflictScheduleConstraint);
        };

        return Employee::with([
            'locations',
            'employeeBranch' => function ($query) use ($branchId, $allowedRoles) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles)
                    ->with([
                        'branches',
                        'employeeServices' => function ($q) {
                            $q->where('is_active', true)->with('services');
                        },
                    ]);
            },
            'employeeBranch.scheduleAssignments' => function ($query) use ($conflictScheduleConstraint) {
                $query->whereHas('scheduleService.schedule', $conflictScheduleConstraint);
            },
            'employeeBranch.scheduleAssignments.scheduleService.schedule' => function ($query) use ($conflictScheduleConstraint) {
                $conflictScheduleConstraint($query);
            },
            'employeeBranch.scheduleAssignments.scheduleService.schedule.patient',
        ])
            ->where('status', 'active')
            ->whereHas('employeeBranch', function ($query) use ($branchId, $allowedRoles) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles);
            })
            ->withExists(['employeeBranch as is_busy' => $busyClosure])
            ->withCount(['employeeBranch as conflict_count' => $conflictCountClosure])
            ->withExists(['employeeBranch as is_assigned' => function ($query) use ($branchId, $allowedRoles, $serviceIds) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles)
                    ->whereHas('employeeServices', function ($q) use ($serviceIds) {
                        $q->where('is_active', true)
                            ->whereIn('service_id', $serviceIds);
                    });
            }])
            ->get();
    }
}
