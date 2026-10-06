<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Enums\RoleEnum;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeScheduleResource;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\EmployeeRepository;
use App\Repository\LocationRepository;
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

    public function __construct(
        EmployeeRepository $employeeRepository,
        UserRepository $userRepository,
        LocationRepository $locationRepository
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->userRepository = $userRepository;
        $this->locationRepository = $locationRepository;
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

    public function updateStatus(array $payload, string $uuid, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Update);

        $target = $this->userRepository->findByField('uuid', $uuid);

        $employee = $target
            ? $this->employeeRepository->findEmployeeByFields([['user_id', '=', $target->user_id]])
            : null;

        $employeeBranch = $employee
            ? $employee->employeeBranch()->where('branch_id', $branch->branch_id)->first()
            : null;

        if (!$employeeBranch) {
            throw new Exception('Employee not found.', 404);
        }

        if ($employeeBranch->role_name === RoleEnum::AgencyOwner->value) {
            throw new Exception('The agency owner cannot be put on leave.', 403);
        }

        if ($employeeBranch->status === EmployeeBranch::STATUS_INACTIVE) {
            throw new Exception('Inactive employees cannot be put on leave.', 422);
        }

        $employeeBranch->update(['status' => $payload['status']]);

        return response()->json([
            'message' => $payload['status'] === EmployeeBranch::STATUS_ONLEAVE
                ? 'Employee is now on leave.'
                : 'Employee is back from leave.',
            'employee' => $this->employeeRow($target, $branch->branch_id),
        ], 200);
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
                'employee.permissions' => fn($q) => $q->where('branch_id', $branchId),
                'employee.permissions.modules',
            ])
        );
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
            ]);

            if (!$employee) {
                throw new Exception('Failed to create employee.', 500);
            }

            $employee->employeeBranch()->create([
                'role_name' => RoleEnum::slug($payload['role_name']),
                'assignment_type' => $this->assignmentTypeFor($payload['role_name'], $payload['assignment_type'] ?? null),
                'branch_id' => $branch->branch_id,
                'employee_id' => $employee->employee_id,
                'status' => $payload['status'] ?? EmployeeBranch::STATUS_ACTIVE,
            ]);

            //INSERT PERMISSION
            $this->savePermissions($employee, $branch->branch_id, $payload['permissions'] ?? []);

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

            $user = $this->userRepository->update($employee->user_id, $userChanges);


            // UPDATE LOCATION
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
            ]);


            // UPDATE EMPLOYEE BRANCH
            $employeeBranch = $employee->employeeBranch()
                ->where('branch_id', $branch->branch_id)
                ->first();

            $employeeBranch?->update([
                'role_name' => RoleEnum::slug($payload['role_name']),
                'assignment_type' => $this->assignmentTypeFor($payload['role_name'], $payload['assignment_type'] ?? null),
                'status' => $payload['status'] ?? $employeeBranch->status,
            ]);


            // UPDATE PERMISSIONS
            if (isset($payload['permissions'])) {
                $employee->permissions()->delete();
                $this->savePermissions($employee, $branch->branch_id, $payload['permissions']);
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
