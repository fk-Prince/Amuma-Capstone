<?php

namespace App\Repository;

use App\Models\Employee;
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
}
