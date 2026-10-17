<?php

namespace Database\Seeders\Concerns;

use App\Models\EmployeeBranch;
use App\Models\EmployeeService;
use App\Models\Service;

trait AssignsRandomServices
{
    protected function assignRandomServices(EmployeeBranch $employeeBranch, string $role): void
    {
        if (!in_array($role, [Service::STAFF_ROLE_NURSE, Service::STAFF_ROLE_CAREGIVER], true)) {
            return;
        }

        if ($employeeBranch->employeeServices()->exists()) {
            return;
        }

        $assignment = $employeeBranch->assignment_type;

        $eligible = Service::where('branch_id', $employeeBranch->branch_id)
            ->get()
            ->filter(fn(Service $service) => in_array($role, $service->allowedStaffRoles(), true)
                && (!$assignment || $assignment === 'both' || in_array($service->type, [$assignment, 'both'], true)))
            ->values();

        if ($eligible->isEmpty()) {
            return;
        }

        $count = random_int(min(2, $eligible->count()), min(5, $eligible->count()));

        foreach ($eligible->shuffle()->take($count) as $service) {
            EmployeeService::firstOrCreate(
                [
                    'employee_branch_id' => $employeeBranch->employee_branch_id,
                    'service_id' => $service->service_id,
                ],
                ['is_active' => true]
            );
        }
    }
}
