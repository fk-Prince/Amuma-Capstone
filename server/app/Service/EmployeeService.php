<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Enums\RoleEnum;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\EmployeeLeaveResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeScheduleResource;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\EmployeeLeaveRepository;
use App\Repository\EmployeeRepository;
use App\Repository\EmployeeWorkloadRepository;
use App\Repository\LocationRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\External\SupabaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private UserRepository $userRepository;
    private LocationRepository $locationRepository;
    private AuditService $auditService;
    private ServiceRepository $serviceRepository;
    private EmployeeLeaveRepository $employeeLeaveRepository;
    private EmployeeWorkloadRepository $employeeWorkloadRepository;
    private NotificationService $notificationService;

    public function __construct(
        EmployeeRepository $employeeRepository,
        UserRepository $userRepository,
        LocationRepository $locationRepository,
        AuditService $auditService,
        ServiceRepository $serviceRepository,
        EmployeeLeaveRepository $employeeLeaveRepository,
        EmployeeWorkloadRepository $employeeWorkloadRepository,
        NotificationService $notificationService
    ) {
        $this->auditService = $auditService;
        $this->employeeRepository = $employeeRepository;
        $this->userRepository = $userRepository;
        $this->locationRepository = $locationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->employeeLeaveRepository = $employeeLeaveRepository;
        $this->employeeWorkloadRepository = $employeeWorkloadRepository;
        $this->notificationService = $notificationService;
    }

    private function autoAssignServices(array $payload, ?EmployeeBranch $employeeBranch): void
    {
        if (
            !$employeeBranch
            || $employeeBranch->role_name !== RoleEnum::Nurse->value
            || !filter_var($payload['auto_assign_services'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ) {
            return;
        }

        $this->serviceRepository->nurseServicesFor($employeeBranch)
            ->each(fn($service) => $this->serviceRepository->assignIfNew(
                $employeeBranch->employee_branch_id,
                $service->service_id
            ));
    }

    private function resolveDocuments(array $payload): array
    {
        $documents = [];

        foreach ($payload['documents'] ?? [] as $document) {
            if (!empty($document['file']) && $document['file'] instanceof UploadedFile) {
                $uploaded = SupabaseService::store($document['file']);

                $documents[] = [
                    'label' => $document['label'],
                    'url' => $uploaded['url'],
                ];
            } elseif (!empty($document['url'])) {
                $documents[] = [
                    'label' => $document['label'],
                    'url' => $document['url'],
                ];
            }
        }

        return $documents;
    }

    private function resolveAchievements(array $payload): array
    {
        $achievements = [];

        foreach ($payload['achievements'] ?? [] as $achievement) {
            $url = null;

            if (!empty($achievement['file']) && $achievement['file'] instanceof UploadedFile) {
                $url = SupabaseService::store($achievement['file'])['url'];
            } elseif (!empty($achievement['url'])) {
                $url = $achievement['url'];
            }

            if (blank($achievement['label'] ?? null)) {
                continue;
            }

            $achievements[] = [
                'label' => $achievement['label'],
                'url' => $url,
                'date' => $achievement['date'] ?? null,
            ];
        }

        return $achievements;
    }

    public function updateStatus(array $payload, string $uuid, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Update);

        [$target, $employeeBranch] = $this->resolveEmployeeBranch($uuid, $branch->branch_id);

        $current = $employeeBranch->status;
        $next = $payload['status'];

        if ($employeeBranch->role_name === RoleEnum::AgencyOwner->value) {
            throw new Exception(
                $next === EmployeeBranch::STATUS_INACTIVE
                    ? 'The agency owner cannot be marked inactive.'
                    : 'The agency owner cannot be put on leave.',
                403
            );
        }

        if ($current === $next) {
            throw new Exception(match ($next) {
                EmployeeBranch::STATUS_ONLEAVE => 'This employee is already on leave.',
                EmployeeBranch::STATUS_INACTIVE => 'This employee is already inactive.',
                default => 'This employee is already active.',
            }, 422);
        }

        if ($current === EmployeeBranch::STATUS_INACTIVE && $next === EmployeeBranch::STATUS_ONLEAVE) {
            throw new Exception('Inactive employees cannot be put on leave.', 422);
        }

        $assignments = collect();
        $shifts = collect();
        $stepsAway = in_array($next, [EmployeeBranch::STATUS_INACTIVE, EmployeeBranch::STATUS_ONLEAVE], true);

        if ($stepsAway) {
            $before = $next === EmployeeBranch::STATUS_ONLEAVE && !empty($payload['ends_on'])
                ? Carbon::parse($payload['ends_on'])->startOfDay()
                : null;

            $assignments = $this->employeeWorkloadRepository->assignments($employeeBranch, $before);

            if ($assignments->contains('clocked_in', true)) {
                throw new Exception(
                    'This employee is checked in to a visit right now. Finish or hand over that visit first.',
                    422
                );
            }

            $releases = $next === EmployeeBranch::STATUS_INACTIVE
                || filter_var($payload['unassign_work'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($releases) {
                $shifts = $this->employeeWorkloadRepository->shifts($employeeBranch);
            } else {
                $assignments = collect();
            }
        }

        DB::transaction(function () use ($employeeBranch, $payload, $user, $next, $assignments, $shifts) {
            $this->employeeLeaveRepository->closeOpen($employeeBranch, $user->user_id);

            if ($next === EmployeeBranch::STATUS_ONLEAVE) {
                $this->employeeLeaveRepository->start($employeeBranch, $payload, $user->user_id);
            }

            $this->employeeWorkloadRepository->release($assignments, $shifts);

            $employeeBranch->update(['status' => $next]);
        });

        if ($assignments->isNotEmpty() || $shifts->isNotEmpty()) {
            $this->notifyReassignment($branch, $user, $target, $next, $payload['ends_on'] ?? null, $assignments->count(), $shifts->count());
        }

        return response()->json([
            'message' => match (true) {
                $next === EmployeeBranch::STATUS_ONLEAVE => 'Employee is now on leave.',
                $next === EmployeeBranch::STATUS_INACTIVE => 'Employee is now inactive.',
                $current === EmployeeBranch::STATUS_INACTIVE => 'Employee is active again.',
                default => 'Employee is back from leave.',
            },
            'employee' => $this->employeeRow($target, $branch->branch_id),
        ], 200);
    }

    public function getWorkload(array $payload, string $uuid, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Update);

        [, $employeeBranch] = $this->resolveEmployeeBranch($uuid, $branch->branch_id);

        $before = !empty($payload['until'])
            ? Carbon::parse($payload['until'])->startOfDay()
            : null;

        $assignments = $this->employeeWorkloadRepository->assignments($employeeBranch, $before)
            ->map(fn($assigned) => [
                'kind' => 'schedule',
                'title' => $assigned->scheduleService?->service?->service_name ?? 'ADL care',
                'patient' => $this->personName($assigned->scheduleService?->schedule?->patient),
                'scheduled_at' => $assigned->scheduleService?->schedule?->scheduled_at?->toIso8601String(),
                'in_progress' => (bool) $assigned->clocked_in,
            ]);

        $timeZone = data_get($branch->settings, 'time_zone') ?: config('app.timezone');

        $shifts = $this->employeeWorkloadRepository->shifts($employeeBranch)
            ->map(fn($shift) => [
                'kind' => 'shift',
                'title' => 'Caregiver shift',
                'patient' => $this->personName($shift->admission?->patient),
                'room' => $shift->admission?->bed?->room?->room_no,
                'in_progress' => false,
                'on_duty' => $this->onDutyNow($shift, $timeZone),
            ])
            ->sortByDesc('on_duty')
            ->values();

        return response()->json([
            'data' => [
                'in_progress' => $assignments->where('in_progress', true)->values(),
                'upcoming' => $assignments->where('in_progress', false)->values()->concat($shifts)->values(),
            ],
        ]);
    }

    private function resolveEmployeeBranch(string $uuid, int $branchId): array
    {
        $target = $this->userRepository->findByField('uuid', $uuid);

        $employeeBranch = $target?->employee
            ?->employeeBranch()
            ->where('branch_id', $branchId)
            ->first();

        if (!$employeeBranch) {
            throw new Exception('Employee not found.', 404);
        }

        return [$target, $employeeBranch];
    }

    private function onDutyNow(object $shift, string $timeZone): bool
    {
        $now = now($timeZone)->format('H:i');
        $start = substr((string) $shift->start_time, 0, 5);
        $end = substr((string) $shift->end_time, 0, 5);

        if ($start === $end) {
            return true;
        }

        return $start < $end
            ? $now >= $start && $now < $end
            : $now >= $start || $now < $end;
    }

    private function personName(?object $person): ?string
    {
        return $person ? trim("{$person->first_name} {$person->last_name}") : null;
    }

    private function notifyReassignment(
        object $branch,
        User $actor,
        User $target,
        string $status,
        ?string $endsOn,
        int $scheduleCount,
        int $shiftCount,
    ): void {
        $name = $this->personName($target->employee) ?: 'An employee';

        $parts = array_filter([
            $scheduleCount ? $scheduleCount . ' ' . Str::plural('schedule', $scheduleCount) : null,
            $shiftCount ? $shiftCount . ' caregiver ' . Str::plural('shift', $shiftCount) : null,
        ]);

        $reason = $status === EmployeeBranch::STATUS_INACTIVE
            ? 'was marked inactive'
            : 'is on leave' . ($endsOn ? ' until ' . Carbon::parse($endsOn)->format('M j, Y') : '');

        $message = "{$name} {$reason}, so " . implode(' and ', $parts) . ' no longer have staff. Assign someone else.';

        $this->notificationService->notifyBranchStaff(
            $branch,
            $message,
            'Schedule',
            $actor,
            null,
            null,
            [RoleEnum::Admission->value],
        );
    }

    public function getLeaveHistory(array $payload, string $uuid, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Read);

        $target = $this->userRepository->findByField('uuid', $uuid);

        $employeeBranch = $target?->employee
            ?->employeeBranch()
            ->where('branch_id', $branch->branch_id)
            ->first();

        if (!$employeeBranch) {
            throw new Exception('Employee not found.', 404);
        }

        return EmployeeLeaveResource::collection(
            $this->employeeLeaveRepository->history($employeeBranch)
        );
    }

    public function endDueLeaves(): int
    {
        $leaves = $this->employeeLeaveRepository->due(now()->startOfDay());

        $leaves->each(fn($leave) => DB::transaction(function () use ($leave) {
            $this->employeeLeaveRepository->closeOpen(
                $leave->employeeBranch,
                null,
                $leave->ends_on->copy()->startOfDay()
            );

            if ($leave->employeeBranch->status === EmployeeBranch::STATUS_ONLEAVE) {
                $leave->employeeBranch->update(['status' => EmployeeBranch::STATUS_ACTIVE]);
            }
        }));

        return $leaves->count();
    }

    private function assignmentTypeFor(string $role, ?string $assignment): ?string
    {
        $assignable = [RoleEnum::Nurse->value, RoleEnum::Caregiver->value];

        return in_array(RoleEnum::slug($role), $assignable, true) ? $assignment : null;
    }

    private function employeeRow(User $user, int $branchId): EmployeeResource
    {
        request()->merge(['branch_id' => $branchId]);

        return new EmployeeResource(
            $user->fresh([
                'employee.locations',
                'employee.employeeBranch' => fn($q) => $q->where('branch_id', $branchId),
                'employee.employeeBranch.currentLeave',
                'employee.permissions' => fn($q) => $q->where('branch_id', $branchId),
                'employee.permissions.modules',
            ])
        );
    }

    private function permissionSnapshot(Employee $employee, int $branchId): array
    {
        return $employee->permissions()
            ->where('branch_id', $branchId)
            ->with('modules:module_id,module_name')
            ->get()
            ->mapWithKeys(fn(EmployeePermission $permission) => [
                $permission->modules->module_name => implode(', ', array_map(
                    fn(string $action) => str_replace('can_', '', $action),
                    $permission->grantedActions()
                )) ?: 'No access',
            ])
            ->all();
    }

    private function permissionChanges(array $other, array $current): array
    {
        $changes = [];

        foreach ($current as $module => $actions) {
            if (($other[$module] ?? null) !== $actions) {
                $changes["permissions.{$module}"] = $actions;
            }
        }

        foreach (array_diff_key($other, $current) as $module => $actions) {
            $changes["permissions.{$module}"] = 'No access';
        }

        return $changes;
    }

    private function savePermissions(object $employee, int $branchId, array $permissions): void
    {
        $modules = Module::whereIn(
            'module_id',
            collect($permissions)->pluck('module_id')
        )->get()->keyBy('module_id');

        foreach ($permissions as $permission) {
            $module = $modules->get($permission['module_id']);

            if (!$module) {
                continue;
            }

            $actions = array_values(array_intersect(
                (array) ($permission['actions'] ?? []),
                $module->actionColumns()
            ));

            if (!$actions) {
                continue;
            }

            $employee->permissions()->create([
                'module_id'   => $module->module_id,
                'branch_id'   => $branchId,
                'employee_id' => $employee->employee_id,
            ] + EmployeePermission::grantColumns($actions));
        }
    }

    private function employeeSlip(
        object $employee,
        User $user,
        object $branch,
        array $payload,
        string $password
    ): array {
        $granted = collect($payload['permissions'] ?? [])
            ->filter(fn($permission) => in_array(
                PermissionAction::Read->value,
                (array) ($permission['actions'] ?? []),
                true
            ))
            ->count();

        return [
            'employee' => [
                'employee_id' => $employee->employee_id,
                'employee_code' => $employee->employee_code,
                'uuid' => $user->uuid,
                'full_name' => trim($employee->first_name . ' ' . $employee->last_name),
                'birth_date' => Carbon::parse($employee->birth_date)->toDateString(),
                'phone_number' => $employee->phone_number,
                'role_name' => $payload['role_name'],
                'assignment_type' => $this->assignmentTypeFor($payload['role_name'], $payload['assignment_type'] ?? null),
            ],
            'branch' => [
                'name' => $branch->name,
            ],
            'access' => [
                'email' => $user->email,
                'default_password' => $password,
                'module_count' => $granted,
            ],
        ];
    }

    public function createEmployee(array $payload, User $user)
    {

        return DB::transaction(function () use ($payload, $user) {

            $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Create);

            $password = UserRepository::defaultPassword(
                $payload['last_name'],
                $payload['birth_date']
            );

            //INSERT USER
            $user = $this->userRepository->create([
                'email' => $payload['email'],
                'password' => Hash::make($password),
                'provider' => 'local',
            ]);

            if (!$user) {
                throw new Exception('Failed to create user.', 500);
            }

            // INSERT LOCATION
            $location = $this->locationRepository->create([
                'street' => $payload['location']['street'],
                'city' => $payload['location']['city'],
                'province' => $payload['location']['province'],
                'country' => $payload['location']['country'],
            ]);


            $initials = strtoupper(
                substr($payload['first_name'], 0, 1) . substr($payload['last_name'], 0, 1)
            );

            $image = null;
            if (!empty($payload['avatar']) && $payload['avatar'] instanceof UploadedFile) {
                $image = SupabaseService::store($payload['avatar'])['url'];
            } else {
                $image = 'https://ui-avatars.com/api/?name=' . $initials;
            };

            // INSERT EMPLOYEE
            $employee = $user->employee()->create([
                'employee_code' => Employee::generateCode($branch->branch_id),
                'first_name' => Str::title($payload['first_name']),
                'last_name' => Str::title($payload['last_name']),
                'location_id' => $location->location_id,
                'phone_number' => $payload['phone_number'],
                'birth_date' => $payload['birth_date'],
                'avatar' => $image,
                'documents' => $this->resolveDocuments($payload),
                'achievements' => $this->resolveAchievements($payload),
            ]);

            if (!$employee) {
                throw new Exception('Failed to create employee.', 500);
            }

            $employeeBranch = $employee->employeeBranch()->create([
                'role_name' => RoleEnum::slug($payload['role_name']),
                'assignment_type' => $this->assignmentTypeFor($payload['role_name'], $payload['assignment_type'] ?? null),
                'branch_id' => $branch->branch_id,
                'employee_id' => $employee->employee_id,
                'status' => $payload['status'] ?? EmployeeBranch::STATUS_ACTIVE,
            ]);

            $this->autoAssignServices($payload, $employeeBranch);

            //INSERT PERMISSION
            $this->savePermissions($employee, $branch->branch_id, $payload['permissions'] ?? []);

            $this->auditService->log(
                AuditLog::ACTION_CREATED,
                'employees',
                $employee,
                'Granted module permissions.',
                new: $this->permissionChanges([], $this->permissionSnapshot($employee, $branch->branch_id))
            );

            return response()->json([
                'message' => 'Successfully Created Employee.',
                'data' => $this->employeeSlip($employee, $user, $branch, $payload, $password),
                'employee' => $this->employeeRow($user, $branch->branch_id),
            ], 200);
        });
    }

    public function updateEmployee(array $payload, string $uuid, User $user)
    {
        return DB::transaction(function () use ($payload, $uuid, $user) {
            $actor = $user;
            $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
            AuthGuard::requireModule($actor,  $branch->branch_id, ModuleEnum::EmployeeManagement,  PermissionAction::Update);

            $user = $this->userRepository->findByField('uuid', $uuid);

            if (!$user) {
                throw new Exception('Employee not found.', 404);
            }

            $employee = $this->employeeRepository->findEmployeeByFields(
                [['user_id', '=', $user->user_id]]
            );

            if (!$employee) {
                throw new Exception('Employee not found.', 404);
            }

            $employeeBranch = $employee->employeeBranch()
                ->where('branch_id', $branch->branch_id)
                ->first();

            if (
                $employeeBranch?->role_name === RoleEnum::AgencyOwner->value
                && $actor->user_id !== $user->user_id
            ) {
                throw new Exception('Only the agency owner can update this profile.', 403);
            }

            $userChanges = ['email' => $payload['email']];

            if (!empty($payload['password'])) {
                $userChanges['password'] = Hash::make($payload['password']);
            }

            $this->userRepository->update($employee->user_id, $userChanges);

            if (!empty($payload['password'])) {
                if ($actor->user_id === $employee->user_id) {
                    $actor->forceFill(['password' => $userChanges['password']])
                        ->syncOriginalAttribute('password');
                }

                $this->auditService->log(
                    AuditLog::ACTION_UPDATED,
                    'employees',
                    $employee,
                    'Changed the account password.'
                );
            }


            // UPDATE LOCATION
            $oldAddress = $employee->locations?->only(AuditService::ADDRESS_FIELDS) ?? [];

            if ($employee->locations) {
                $employee->locations()->update([
                    'street' => $payload['location']['street'],
                    'city' => $payload['location']['city'],
                    'province' => $payload['location']['province'],
                    'country' => $payload['location']['country'],
                ]);
            } else {
                $location = $this->locationRepository->create([
                    'street' => $payload['location']['street'],
                    'city' => $payload['location']['city'],
                    'province' => $payload['location']['province'],
                    'country' => $payload['location']['country'],
                ]);
                $employee->location_id = $location->location_id;
                $employee->save();
            }

            $this->auditService->addressChange(
                $employee,
                'employees',
                $oldAddress,
                $payload['location'],
                $branch->branch_id
            );


            $image = $employee->avatar;

            if (!empty($payload['avatar']) && $payload['avatar'] instanceof UploadedFile) {
                $image = SupabaseService::store($payload['avatar'])['url'];
            }

            // UPDATE EMPLOYEE
            $employee->update([
                'first_name' => Str::title($payload['first_name']),
                'middle_name' => Str::title($payload['middle_name']),
                'last_name' => Str::title($payload['last_name']),
                'phone_number' => $payload['phone_number'],
                'birth_date' => $payload['birth_date'],
                'avatar' => $image,
                'location_id' => $employee->location_id,
                'documents' => $this->resolveDocuments($payload),
                'achievements' => $this->resolveAchievements($payload),
            ]);


            // UPDATE EMPLOYEE BRANCH
            $employeeBranch = $employee->employeeBranch()
                ->where('branch_id', $branch->branch_id)
                ->first();

            $employeeBranch?->update([
                'role_name' => RoleEnum::slug($payload['role_name']),
                'assignment_type' => $this->assignmentTypeFor($payload['role_name'], $payload['assignment_type'] ?? null),
            ]);

            $this->autoAssignServices($payload, $employeeBranch);


            // UPDATE PERMISSIONS
            if (isset($payload['permissions'])) {
                $before = $this->permissionSnapshot($employee, $branch->branch_id);

                $employee->permissions()->delete();
                $this->savePermissions($employee, $branch->branch_id, $payload['permissions']);

                $after = $this->permissionSnapshot($employee, $branch->branch_id);

                if ($before !== $after) {
                    $this->auditService->log(
                        AuditLog::ACTION_UPDATED,
                        'employees',
                        $employee,
                        'Changed module permissions.',
                        old: $this->permissionChanges($after, $before),
                        new: $this->permissionChanges($before, $after)
                    );
                }
            }

            return response()->json([
                'message' => 'Successfully Updated Employee Information.',
                'employee' => $this->employeeRow($employee->users, $branch->branch_id),
            ], 200);
        });
    }

    public function getEmployees(array $payload, User $user, string $type)
    {
        $branchId = $payload['branch_id'];

        if ($type === 'regular') {
            $result = $this->employeeRepository->getPaginateEmployee($payload, $branchId);
            request()->merge([
                'branch_id' => $branchId
            ]);
            return EmployeeResource::collection($result['users'])->additional(['total_employee' => $result['total_employee'],   'status_counts' => $result['status_counts'],]);
        } else if ($type === 'schedule') {
            $result = $this->employeeRepository->getEmployeesWithBusyLabel(
                $payload['schedule_id'],
                $branchId,
                $payload['date'] ?? null,
                $payload['preferred_time'] ?? null
            );
            return EmployeeScheduleResource::collection($result);
        } else if ($type === 'service') {
            return $this->employeeRepository->getEmployeeServices($branchId, $payload);
        }

        return null;
    }
}
