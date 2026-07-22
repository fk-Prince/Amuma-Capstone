<?php

namespace App\Repository;

use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


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

    public function getPaginateEmployeeSchedule(array $payload, string $branchId)
    {
        return EmployeeBranch::with('employees')
            ->where('branch_id', $branchId)
            ->paginate($payload['per_page']);
    }


    public function getEmployeeServices(string $branchId, array $payload)
    {
        $query = EmployeeBranch::query()
            ->where('branch_id', $branchId)
            ->whereDoesntHave('employeeServices', function ($query) use ($payload) {
                $query->where('service_id', $payload['service_id']);
            })
            ->with('employees');

        if (isset($payload['per_page'])) {
            return $query->paginate((int) $payload['per_page']);
        }

        return $query->get();
        // $service = Service::where('service_id', $payload['service_id'])
        //     ->first();

        // if (!$service) {
        //     return collect();
        // }

        // $query = EmployeeBranch::query()
        //     ->where('branch_id', $branchId)
        //     ->where('assignment_type', $service->type)
        //     ->whereDoesntHave('employeeServices', function ($query) use ($payload) {
        //         $query->where('service_id', $payload['service_id']);
        //     })
        //     ->with('employees');

        // if (isset($payload['per_page'])) {
        //     return $query->paginate((int) $payload['per_page']);
        // }

        // return $query->get();
    }
}
