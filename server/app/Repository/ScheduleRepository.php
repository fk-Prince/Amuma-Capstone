<?php

namespace App\Repository;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScheduleRepository
{
    // Same overlap condition used by getEmployeeAvailable()'s conflict
    // detection: a schedule's occupied window runs from scheduled_at to
    // scheduled_at + (its services' durations, or 60 min if unknown).
    private const CONFLICT_WINDOW_SQL = 'schedules.scheduled_at + (
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

    /**
     * The hard, server-side gate against double-booking an employee. Unlike
     * the pre-flight conflict check surfaced to the UI (which can be
     * bypassed by confirming past the warning, calling the endpoint
     * directly, or a race between check-time and submit-time), this is
     * re-verified at the moment of assignment and has no override — if it
     * returns true, the assignment must not be written.
     */
    public function employeeHasActiveConflict(
        int $employeeId,
        string $excludeScheduleId,
        Carbon $targetStart,
        Carbon $targetEnd
    ): bool {
        return ScheduleAssigned::query()
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->whereHas('scheduleService.schedule', function ($query) use ($targetStart, $targetEnd, $excludeScheduleId) {
                $query->where('schedules.schedule_id', '!=', $excludeScheduleId)
                    ->whereIn('schedules.status', [Schedule::STATUS_ONGOING, Schedule::STATUS_PENDING])
                    ->where('schedules.scheduled_at', '<', $targetEnd)
                    ->whereRaw(self::CONFLICT_WINDOW_SQL, [$targetStart]);
            })
            ->exists();
    }

    public function calculateScheduleDurationMinutes(Schedule $schedule): float
    {
        return (float) $schedule->scheduleServices->sum(function ($scheduleService) {
            if ($scheduleService->service_id && $scheduleService->service) {
                $duration = $scheduleService->service->maximum_duration;

                if ($duration) {
                    [$hours, $minutes, $seconds] = array_pad(explode(':', $duration), 3, 0);

                    return ((int) $hours * 60) + (int) $minutes + ((int) $seconds / 60);
                }
            }

            if ($scheduleService->hours_booked !== null) {
                return (float) $scheduleService->hours_booked * 60;
            }

            return 60;
        });
    }

    public function create(array $payload)
    {
        return Schedule::create($payload);
    }

    public function findByFields(array $conditions)
    {
        return Schedule::where($conditions)->first();
    }

    // public function retrievePaginate(array $payload)
    // {
    //     $query = Schedule::query()
    //         ->with([
    //             'patient.location',
    //             'patient.admissions.bed.room',
    //             'patient.currentAdmission.bed.room',
    //             'scheduleServices.service',
    //             'scheduleServices.assigned.employee.employees',
    //             'scheduleServices.assigned.onlineSchedules',
    //         ]);

    //     if (!empty($payload['patient_uuid'])) {
    //         $query->whereHas('patient', function ($q) use ($payload) {
    //             $q->where('uuid', $payload['patient_uuid']);
    //         });
    //     }

    //     if (!empty($payload['branch_uuid'])) {
    //         $query->whereHas('patient.branch', function ($q) use ($payload) {
    //             $q->where('uuid', $payload['branch_uuid']);
    //         });
    //     }

    //     if (!empty($payload['status'])) {
    //         $query->whereIn('status', (array) $payload['status']);
    //     }

    //     if (!empty($payload['category'])) {
    //         $query->whereIn('category', (array) $payload['category']);
    //     }

    //     if (!empty($payload['date'])) {
    //         $query->whereDate('scheduled_at', $payload['date']);
    //     }

    //     if (!empty($payload['type'])) {
    //         $types = array_map('strtolower', (array) $payload['type']);

    //         $query->whereHas('scheduleServices', function ($q) use ($types) {
    //             $q->where(function ($sub) use ($types) {
    //                 if (in_array('medical', $types, true)) {
    //                     $sub->orWhereNotNull('service_id');
    //                 }
    //                 if (in_array('adl', $types, true)) {
    //                     $sub->orWhereNotNull('hours_booked');
    //                 }
    //             });
    //         });
    //     }

    //     if (!empty($payload['date_from'])) {
    //         $query->whereDate('scheduled_at', '>=', $payload['date_from']);
    //     }

    //     if (!empty($payload['date_to'])) {
    //         $query->whereDate('scheduled_at', '<=', $payload['date_to']);
    //     }

    //     if (!empty($payload['search'])) {
    //         $search = $payload['search'];
    //         $query->whereHas('patient', function ($q) use ($search) {
    //             $q->where('first_name', 'like', "%{$search}%")
    //                 ->orWhere('last_name', 'like', "%{$search}%");
    //         });
    //     }

    //     if (!empty($payload['service_id'])) {
    //         $query->whereHas('scheduleServices', function ($q) use ($payload) {
    //             $q->where('service_id', $payload['service_id']);
    //         });
    //     }

    //     if (!empty($payload['employee_id'])) {
    //         $query->whereHas('scheduleServices.assigned', function ($q) use ($payload) {
    //             $q->where('employee_id', $payload['employee_id']);
    //         });
    //     }

    //     $sortBy = $payload['sort_by'] ?? 'scheduled_at';
    //     $sortDir = strtolower($payload['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
    //     $allowedSorts = ['scheduled_at', 'status', 'category', 'schedule_id'];
    //     if (!in_array($sortBy, $allowedSorts, true)) {
    //         $sortBy = 'scheduled_at';
    //     }

    //     $query->orderBy($sortBy, $sortDir);

    //     // $perPage = min((int) ($payload['per_page'] ?? 15), 100);
    //     if (isset($payload['per_page'])) {
    //         $perPage = min((int) $payload['per_page'], 100);
    //         return $query->paginate($perPage);
    //     }

    //     // return $query->paginate($perPage)->withQueryString();
    //     return $query->get();
    // }
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

        // if (!empty($payload['statuses'])) {
        //     $statuses = is_array($payload['statuses'])
        //         ? $payload['statuses']
        //         : explode(',', (string) $payload['statuses']);

        //     $query->whereIn('status', array_filter($statuses));
        // }
        if (!empty($payload['statuses'])) {
            $statuses = is_array($payload['statuses'])
                ? $payload['statuses']
                : explode(',', (string) $payload['statuses']);

            $query->whereIn('status', array_filter($statuses));
        }


        if (!empty($payload['category'])) {
            $query->whereIn('category', (array) $payload['category']);
        }

        $types = !empty($payload['type'])
            ? array_map('strtolower', (array) $payload['type'])
            : [];
        $wantsMedical = empty($types) || in_array('medical', $types, true);
        $wantsAdl = empty($types) || in_array('adl', $types, true);

        if (!empty($payload['type'])) {
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


        if (!empty($payload['date'])) {
            $date = $payload['date'];

            $query->where(function ($q) use ($date, $wantsMedical, $wantsAdl) {
                if ($wantsMedical) {
                    $q->orWhereDate('scheduled_at', $date);
                }

                if ($wantsAdl) {
                    $q->orWhereHas('scheduleServices', function ($sub) use ($date) {
                        $sub->whereNotNull('hours_booked')
                            ->whereRaw(
                                "?::date BETWEEN schedules.scheduled_at::date AND (schedules.scheduled_at + (schedule_services.hours_booked * interval '1 hour'))::date",
                                [$date]
                            );
                    });
                }
            });
        }

        if (!empty($payload['date_from'])) {
            $dateFrom = $payload['date_from'];

            $query->where(function ($q) use ($dateFrom, $wantsMedical, $wantsAdl) {
                if ($wantsMedical) {
                    $q->orWhereDate('scheduled_at', '>=', $dateFrom);
                }

                if ($wantsAdl) {

                    $q->orWhereHas('scheduleServices', function ($sub) use ($dateFrom) {
                        $sub->whereNotNull('hours_booked')
                            ->whereRaw(
                                "schedules.scheduled_at + (schedule_services.hours_booked * interval '1 hour') >= ?",
                                [$dateFrom]
                            );
                    });
                }
            });
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

        if (isset($payload['per_page'])) {
            $perPage = min((int) $payload['per_page'], 100);
            return $query->paginate($perPage);
        }

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

        // Only ACTIVE assignments should count toward "busy" — a deactivated
        // (reassigned-off) assignment no longer reflects the employee's real
        // schedule.
        $activeScheduleAssignments = function ($query) use ($targetStart, $targetEnd, $scheduleId) {
            $query->where('schedule_assigned.is_active', true)
                ->whereHas('scheduleService.schedule', function ($q) use ($targetStart, $targetEnd, $scheduleId) {
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
        };

        return Employee::with([
            'locations',
            'employeeBranch.scheduleAssignments' => function ($query) use ($activeScheduleAssignments) {
                $activeScheduleAssignments($query);
            },
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
            ->withExists(['employeeBranch as is_busy' => function ($query) use ($branchId, $allowedRoles, $activeScheduleAssignments) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles)
                    ->whereHas('scheduleAssignments', $activeScheduleAssignments);
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
        $conflict = 'schedules.scheduled_at + (
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

        $scheduleConflict = function ($q) use ($targetStart, $targetEnd, $conflict) {
            $q->whereIn('schedules.status', [Schedule::STATUS_ONGOING, Schedule::STATUS_PENDING])
                ->where('schedules.scheduled_at', '<', $targetEnd)
                ->whereRaw($conflict, [$targetStart]);
        };

        $activeScheduleAssignments = function ($query) use ($scheduleConflict) {
            $query->where('schedule_assigned.is_active', true)
                ->whereHas('scheduleService.schedule', $scheduleConflict);
        };

        $busy = function ($query) use ($branchId, $allowedRoles, $activeScheduleAssignments) {
            $query->where('branch_id', $branchId)
                ->whereIn('role_name', $allowedRoles)
                ->whereHas('scheduleAssignments', $activeScheduleAssignments);
        };

        $conflictCountClosure = function ($query) use ($branchId, $allowedRoles, $activeScheduleAssignments) {
            $query->where('branch_id', $branchId)
                ->whereIn('role_name', $allowedRoles)
                ->whereHas('scheduleAssignments', $activeScheduleAssignments);
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
            'employeeBranch.scheduleAssignments' => function ($query) use ($activeScheduleAssignments) {
                $activeScheduleAssignments($query);
            },
            'employeeBranch.scheduleAssignments.scheduleService.schedule' => function ($query) use ($scheduleConflict) {
                $scheduleConflict($query);
            },
            'employeeBranch.scheduleAssignments.scheduleService.schedule.patient',
        ])
            ->where('status', 'active')
            ->whereHas('employeeBranch', function ($query) use ($branchId, $allowedRoles) {
                $query->where('branch_id', $branchId)
                    ->whereIn('role_name', $allowedRoles);
            })
            ->withExists(['employeeBranch as is_busy' => $busy])
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

    public function getOverview(array $payload)
    {

        $branchId = $payload['branch_id'];
        $date = Carbon::parse($payload['date']) ?? Carbon::today();

        $baseQuery = Schedule::query()
            ->whereHas('patient', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereDate('scheduled_at', $date);

        $counts = (clone $baseQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = (int) $counts->sum();

        $upcomingList = (clone $baseQuery)
            ->with(['patient:patient_id,uuid,first_name,last_name'])
            ->where('status', Schedule::STATUS_PENDING)
            ->where('scheduled_at', '>=', Carbon::now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get([
                'schedule_id',
                'schedule_code',
                'patient_id',
                'category',
                'status',
                'scheduled_at',
            ]);

        $nextSlot = $upcomingList->first();

        $activeProviders = Employee::query()
            ->where('status', 'active')
            ->whereHas('employeeBranch', function ($q) use ($branchId) {
                $q->where('branch_id',  $branchId)
                    ->whereIn('role_name', ['nurse', 'caregiver']);
            })
            ->whereHas('employeeBranch.scheduleAssignments', function ($q) use ($date) {
                $q->where('schedule_assigned.is_active', true)
                    ->whereHas('scheduleService.schedule', function ($sub) use ($date) {
                        $sub->whereDate('scheduled_at', $date);
                    });
            })
            ->count();

        return [
            'schedule' => [
                'upcoming' => (int) ($counts[Schedule::STATUS_PENDING] ?? 0),
                'in_progress' => (int) ($counts[Schedule::STATUS_ONGOING] ?? 0),
                'waiting' => (int) ($counts['waiting'] ?? 0),
                'completed' => (int) ($counts[Schedule::STATUS_COMPLETED] ?? 0),
                'cancelled' => (int) ($counts[Schedule::STATUS_CANCELLED] ?? 0),
                'today' => $today,
                'next_slot_time' => $nextSlot?->scheduled_at?->format('h:i A'),
                'next_slot_patient' => $nextSlot?->patient
                    ? trim("{$nextSlot->patient->first_name} {$nextSlot->patient->last_name}")
                    : null,
                'upcoming_list' => $upcomingList->map(function ($schedule) {
                    return [
                        'schedule_id' => $schedule->schedule_id,
                        'reference_id' => $schedule->schedule_code,
                        'patient_name' => $schedule->patient
                            ? trim("{$schedule->patient->first_name} {$schedule->patient->last_name}")
                            : null,
                        'category' => $schedule->category,
                        'status' => $schedule->status,
                        'scheduled_at' => $schedule->scheduled_at,
                    ];
                })->values(),
            ],
            'providers' => [
                'active' => $activeProviders,
            ],
        ];
    }
}
