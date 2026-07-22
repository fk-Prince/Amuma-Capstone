<?php

namespace App\Repository;

use App\Models\EmployeeBranch;
use App\Models\EmployeeService;
use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServiceRepository
{
    public function create(array $payload)
    {
        return Service::create($payload);
    }

    public function existsInBranch(int $branchId, string $col,  string $value): bool
    {
        return Service::where('branch_id', $branchId)
            ->where($col, $value)
            ->exists();
    }

    public function findByFields(array $conditions)
    {
        return Service::where($conditions)->first();
    }


    public function existsEmployeeService(string $serviceId, string $employeeId, string $branchId)
    {
        return EmployeeService::where('service_id', $serviceId)
            ->whereHas('employeeBranch', function ($query) use ($employeeId, $branchId) {
                $query->where('employee_id', $employeeId)
                    ->where('branch_id', $branchId);
            })
            ->exists();
    }

    public function assignEmployee(array $payload)
    {
        return EmployeeService::create($payload);
    }
}
