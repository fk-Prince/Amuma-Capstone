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

    public function findOneByFields(array $conditions)
    {
        return Service::where($conditions)->first();
    }

    public function findByFields(array $conditions)
    {
        $query = Service::query();
        foreach ($conditions as $condition) {
            [$field, $operator, $value] = $condition;
            if (strtoupper($operator) === 'IN') {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $operator, $value);
            }
        }
        return $query->get();
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
        return EmployeeService::updateOrCreate(
            [
                'employee_branch_id' => $payload['employee_branch_id'],
                'service_id' => $payload['service_id'],
            ],
            [
                'is_active' => true,
            ]
        );
    }

    public function nurseServicesFor(EmployeeBranch $nurse)
    {
        $types = match (strtolower((string) $nurse->assignment_type)) {
            'online', 'homecare' => ['online', 'both'],
            'facility', 'inhouse facility' => ['facility', 'both'],
            default => ['online', 'facility', 'both'],
        };

        return Service::where('branch_id', $nurse->branch_id)
            ->whereIn('type', $types)
            ->where(fn($query) => $query
                ->whereNull('performed_by')
                ->orWhere('performed_by', '!=', Service::STAFF_ROLE_CAREGIVER))
            ->get();
    }

    public function assignIfNew(int $employeeBranchId, int $serviceId)
    {
        return EmployeeService::firstOrCreate(
            [
                'employee_branch_id' => $employeeBranchId,
                'service_id' => $serviceId,
            ],
            [
                'is_active' => true,
            ]
        );
    }

    public function unassignEmployee(int $employeeBranchId, int $serviceId)
    {
        return EmployeeService::updateOrCreate(
            [
                'employee_branch_id' => $employeeBranchId,
                'service_id' => $serviceId,
            ],
            [
                'is_active' => false,
            ]
        );
    }
}
