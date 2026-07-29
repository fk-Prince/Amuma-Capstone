<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeScheduleResource;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private BranchRepository $branchRepository;
    private UserRepository $userRepository;
    private LocationRepository $locationRepository;

    public function __construct(
        EmployeeRepository $employeeRepository,
        BranchRepository $branchRepository,
        UserRepository $userRepository,
        LocationRepository $locationRepository
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->branchRepository = $branchRepository;
        $this->userRepository = $userRepository;
        $this->locationRepository = $locationRepository;
    }

    public function createEmployee(array $payload, User $user)
    {

        return DB::transaction(function () use ($payload, $user) {

            $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Create);

            //INSERT USER
            $user = $this->userRepository->create([
                'email' => $payload['email'],
                'password' => Hash::make(
                    $payload['last_name'] . Carbon::parse($payload['birth_date'])->year
                ),
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
                $image = SupabaseService::store($payload['avatar']);
            } else {
                $image = 'https://ui-avatars.com/api/?name=' . $initials;
            };

            // INSERT EMPLOYEE
            $employee = $user->employee()->create([
                'first_name' => Str::title($payload['first_name']),
                'last_name' => Str::title($payload['last_name']),
                'location_id' => $location->location_id,
                'phone_number' => $payload['phone_number'],
                'birth_date' => $payload['birth_date'],
                'avatar' => $image,
            ]);

            if (!$employee) {
                throw new Exception('Failed to create employee.', 500);
            }

            $employee->employeeBranch()->create([
                'role_name' => $payload['role_name'],
                'assignment_type' => $payload['assignment_type'],
                'branch_id' => $branch->branch_id,
                'employee_id' => $employee->employee_id,
            ]);

            //INSERT PERMISSION
            foreach ($payload['permissions'] as $permission) {
                $employee->permissions()->create([
                    'module_id'   => $permission['module_id'],
                    'branch_id'   => $branch->branch_id,
                    'employee_id' => $employee->employee_id,
                    'can_read'    => $permission['can_read'],
                    'can_create'  => $permission['can_create'],
                    'can_update'  => $permission['can_update'],
                ]);
            }

            return response()->json([
                'message' => 'Successfully Created Employee.'
            ], 200);
        });
    }

    public function updateEmployee(array $payload, string $uuid, User $user)
    {

        return DB::transaction(function () use ($payload, $uuid, $user) {

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

            $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
            AuthGuard::requireModule($user,   $branch->branch_id, ModuleEnum::EmployeeManagement,  PermissionAction::Update);

            $user = $this->userRepository->update($employee->user_id, ['email' => $payload['email']]);


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
                $image = SupabaseService::store($payload['avatar']);
            }

            // UPDATE EMPLOYEE
            $employee->update([
                'first_name' => Str::title($payload['first_name']),
                'last_name' => Str::title($payload['last_name']),
                'phone_number' => $payload['phone_number'],
                'birth_date' => $payload['birth_date'],
                'avatar' => $image,
                'location_id' => $employee->location_id
            ]);


            // UPDATE EMPLOYEE BRANCH
            $employee->employeeBranch()
                ->where('branch_id', $branch->branch_id)
                ->update([
                    'role_name' => $payload['role_name'],
                    'assignment_type' => $payload['assignment_type'],
                ]);


            // UPDATE PERMISSIONS
            if (isset($payload['permissions'])) {
                $employee->permissions()->delete();
                foreach ($payload['permissions'] as $permission) {
                    $employee->permissions()->create([
                        'module_id'   => $permission['module_id'],
                        'branch_id'   => $branch->branch_id,
                        'employee_id' => $employee->employee_id,
                        'can_read'    => $permission['can_read'],
                        'can_create'  => $permission['can_create'],
                        'can_update'  => $permission['can_update'],
                    ]);
                }
            }

            return response()->json([
                'message' => 'Successfully Updated Employee Information.'
            ], 200);
        });
    }

    public function getEmployees(array $payload, User $user, string $type)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
        if ($type === 'regular') {
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Read);
            $result =  $this->employeeRepository->getPaginateEmployee($payload, $branch->branch_id);
            request()->merge([
                'branch_id' => $branch->branch_id
            ]);
            return EmployeeResource::collection($result['users'])
                ->additional([
                    'total_employee' => $result['total_employee'],
                    'status_counts' => $result['status_counts'],
                ]);
        } else if ($type === 'schedule') {
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Create);
            $result = $this->employeeRepository->getEmployeesWithBusyLabel($payload['schedule_id'], $branch->branch_id);
            return EmployeeScheduleResource::collection($result);
        } else if ($type === 'service') {
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Create);
            $result = $this->employeeRepository->getEmployeeServices($branch->branch_id, $payload);
            return $result;
        }
    }
}
