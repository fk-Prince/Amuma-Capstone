<?php

namespace App\Repository;

use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeeRepository
{

    public function createEmployee(array $payload)
    {
        return Employee::create($payload);
    }

    public function findEmployeeByFields(array $conditioins)
    {
        return Employee::where($conditioins)->first();
    }

    public function getPaginateEmployee(array $payload, string $branchId)
    {
        $perPage = $payload['per_page'] ?? 10;
        $search = $payload['search'] ?? null;
        $status = $payload['status'] ?? null;

        $query = User::query()
            ->with([
                'employee.employeeBranch' => function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                },
                'employee.permissions' => function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                },
                'employee.permissions.modules',
                'client',
            ])->whereHas('employee');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->whereHas('employee', function ($q) use ($status) {
                $q->where('assignment_type', $status);
            });
        }

        $users = $query->paginate($perPage);

        $users->getCollection()->each(function ($user) {
            $user->makeVisible([
                'employee',
                // 'client',
                // 'systemOwner',   
            ]);
        });


        $statusCounts = User::query()
            ->whereHas('employee')
            ->whereHas('employee.employeeBranch', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->with('employee:user_id,status')
            ->get()
            ->groupBy(fn($user) => $user->employee?->status ?? 'inactive')
            ->map(fn($users) => $users->count());
        $totalEmployees = $statusCounts->sum();

        return [
            'users' => $users,
            'total_employee' => $totalEmployees,
            'status_counts' => $statusCounts,
        ];
    }


    public function getEmployeesWithBusyLabel(string $scheduleId, string $branchId)
    {
        $targetSchedule = Schedule::with('scheduleServices.service')
            ->where('schedule_id', $scheduleId)
            ->firstOrFail();

        $targetStart = Carbon::parse($targetSchedule->scheduled_at);
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

        $activeScheduleAssignments = function ($query) use ($targetStart, $targetEnd, $scheduleId) {
            $query->where('schedule_assigned.is_active', true)
                ->whereHas('scheduleService.schedule', function ($q) use ($targetStart, $targetEnd, $scheduleId) {
                    $q->whereIn('schedules.status', ['ongoing', 'pending'])
                        ->where('schedules.schedule_id', '!=', $scheduleId)
                        ->where('schedules.scheduled_at', '<', $targetEnd)
                        ->whereRaw(
                            'schedules.scheduled_at + (
                    SELECT COALESCE(SUM(EXTRACT(EPOCH FROM sv.maximum_duration)), 3600) / 60
                    FROM schedule_services ss
                    INNER JOIN services sv ON sv.service_id = ss.service_id
                    WHERE ss.schedule_id = schedules.schedule_id
                ) * INTERVAL \'1 minute\' > ?',
                            [$targetStart]
                        );
                });
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
    // public function getEmployeesWithBusyLabel(string $scheduleId, string $branchId)
    // {
    //     $targetSchedule = Schedule::with('scheduleServices.service')
    //         ->where('schedule_id', $scheduleId)
    //         ->firstOrFail();

    //     $targetStart = Carbon::parse($targetSchedule->scheduled_at);
    //     $targetDurationMinutes = $targetSchedule->scheduleServices
    //         ->sum(function ($ss) {
    //             $duration = $ss->service->maximum_duration ?? null;
    //             if (!$duration) {
    //                 return 60;
    //             }
    //             [$hours, $minutes, $seconds] = explode(':', $duration);
    //             return ((int) $hours * 60) + (int) $minutes + ((int) $seconds / 60);
    //         });
    //     $targetEnd = $targetStart->copy()->addMinutes($targetDurationMinutes);

    //     $scheduleServiceIds = $targetSchedule->scheduleServices
    //         ->pluck('service_id')
    //         ->filter()
    //         ->unique()
    //         ->values();

    //     $allowedRoles = ['nurse', 'caregiver'];

    //     return Employee::with([
    //         'locations',
    //         'employeeBranch' => function ($query) use ($branchId, $allowedRoles) {
    //             $query->where('branch_id', $branchId)
    //                 ->whereIn('role_name', $allowedRoles)
    //                 ->with([
    //                     'branches',
    //                     'employeeServices' => function ($q) {
    //                         $q->where('is_active', true)->with('services');
    //                     },
    //                 ]);
    //         },
    //     ])
    //         ->where('status', 'active')
    //         ->whereHas('employeeBranch', function ($query) use ($branchId, $allowedRoles) {
    //             $query->where('branch_id', $branchId)
    //                 ->whereIn('role_name', $allowedRoles);
    //         })
    //         ->withExists(['employeeBranch as is_busy' => function ($query) use ($targetStart, $targetEnd, $scheduleId, $branchId, $allowedRoles) {
    //             $query->where('branch_id', $branchId)
    //                 ->whereIn('role_name', $allowedRoles)
    //                 ->whereHas('scheduleAssignments.scheduleService.schedule', function ($q) use ($targetStart, $targetEnd, $scheduleId) {
    //                     $q->whereIn('schedules.status', ['ongoing', 'pending'])
    //                         ->where('schedules.schedule_id', '!=', $scheduleId)
    //                         ->where('schedules.scheduled_at', '<', $targetEnd)
    //                         ->whereRaw(
    //                             'schedules.scheduled_at + (
    //                     SELECT COALESCE(SUM(EXTRACT(EPOCH FROM sv.maximum_duration)), 3600) / 60
    //                     FROM schedule_services ss
    //                     INNER JOIN services sv ON sv.service_id = ss.service_id
    //                     WHERE ss.schedule_id = schedules.schedule_id
    //                 ) * INTERVAL \'1 minute\' > ?',
    //                             [$targetStart]
    //                         );
    //                 });
    //         }])
    //         ->withExists(['employeeBranch as is_assigned' => function ($query) use ($branchId, $scheduleServiceIds, $allowedRoles) {
    //             $query->where('branch_id', $branchId)
    //                 ->whereIn('role_name', $allowedRoles)
    //                 ->whereHas('employeeServices', function ($q) use ($scheduleServiceIds) {
    //                     $q->where('is_active', true)
    //                         ->whereIn('service_id', $scheduleServiceIds);
    //                 });
    //         }])
    //         ->get();
    // }


    // public function getEmployeesWithBusyLabel(string $scheduleId, string $branchId)
    // {
    //     $targetSchedule = Schedule::with('scheduleServices.service')
    //         ->where('schedule_id', $scheduleId)
    //         ->firstOrFail();

    //     $targetStart = Carbon::parse($targetSchedule->scheduled_at);
    //     $targetDurationMinutes = $targetSchedule->scheduleServices
    //         ->sum(function ($ss) {
    //             $duration = $ss->service->maximum_duration ?? null;
    //             if (!$duration) {
    //                 return 60;
    //             }
    //             [$hours, $minutes, $seconds] = explode(':', $duration);
    //             return ((int) $hours * 60) + (int) $minutes + ((int) $seconds / 60);
    //         });
    //     $targetEnd = $targetStart->copy()->addMinutes($targetDurationMinutes);

    //     return Employee::with([
    //         'locations',
    //         'employeeBranch' => function ($query) use ($branchId) {
    //             $query->where('branch_id', $branchId)->with('branches');
    //         },
    //     ])
    //         ->whereHas('employeeBranch', function ($query) use ($branchId) {
    //             $query->where('branch_id', $branchId);
    //         })
    //         ->withExists(['employeeBranch as is_busy' => function ($query) use ($targetStart, $targetEnd, $scheduleId, $branchId) {
    //             $query->where('branch_id', $branchId)
    //                 ->whereHas('scheduleAssignments.scheduleService.schedule', function ($q) use ($targetStart, $targetEnd, $scheduleId) {
    //                     $q->where('schedules.schedule_id', '!=', $scheduleId)
    //                         ->where('schedules.scheduled_at', '<', $targetEnd)
    //                         ->whereRaw(
    //                             'schedules.scheduled_at + (
    //                             SELECT COALESCE(SUM(EXTRACT(EPOCH FROM sv.maximum_duration)), 3600) / 60
    //                             FROM schedule_services ss
    //                             INNER JOIN services sv ON sv.service_id = ss.service_id
    //                             WHERE ss.schedule_id = schedules.schedule_id
    //                         ) * INTERVAL \'1 minute\' > ?',
    //                             [$targetStart]
    //                         );
    //                 });
    //         }])
    //         ->get();
    // }

    private function eligibleAssignmentTypes(string $serviceType): array
    {
        return match (strtolower($serviceType)) {
            'online' => [
                'online',
                'Online',
                'Homecare',
                'Homecare + Inhouse Facility',
                'both',
                'Both',
            ],

            'facility' => [
                'facility',
                'Facility',
                'Inhouse Facility',
                'Homecare + Inhouse Facility',
                'both',
                'Both',
            ],

            'both' => [
                'online',
                'Online',
                'facility',
                'Facility',
                'Homecare',
                'Inhouse Facility',
                'Homecare + Inhouse Facility',
                'both',
                'Both',
            ],

            default => [],
        };
    }

    public function getEmployeeServices(string $branchId, array $payload)
    {
        $service = Service::findOrFail($payload['service_id']);

        $employees = EmployeeBranch::query()
            ->where('branch_id', $branchId)
            ->whereIn('assignment_type', $this->eligibleAssignmentTypes($service->type))
            ->whereIn('role_name', ['nurse'])
            ->with([
                'employees',
                'employeeServices' => function ($query) use ($payload) {
                    $query->where('service_id', $payload['service_id'])
                        ->where('is_active', true);
                },
            ])
            ->get();

        $x =  $employees->map(function ($employeeBranch) use ($payload) {
            $employee = $employeeBranch->employees;

            $employee->formatted_assignment_type = match (strtolower($employeeBranch->assignment_type)) {
                'both' => 'Homecare + Inhouse Facility',
                'homecare', 'online' => 'Homecare',
                'facility', 'inhouse facility' => 'Inhouse Facility',
                default => 'Not yet Assigned',
            };

            $employee->role_name = ucwords(str_replace('_', ' ', $employeeBranch->role_name));
            $employee->assignment_type = $employeeBranch->assignment_type;

            $employee->assigned = [
                [
                    'service_id' => $payload['service_id'],
                    'is_assigned' => $employeeBranch->employeeServices->isNotEmpty(),
                ],
            ];

            return $employee;
        });

        Log::info($x);
        return $x;
    }
}
