<?php

namespace App\Repository;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Models\Employee;
use App\Models\Module;

class ModuleRepository
{
    public function getAllModules()
    {
        return Module::all();
    }

    public function getEmployeesModuleWithPermission(array $actions, ModuleEnum $module, string $branchId)
    {
        return Employee::with([
            'permissions.modules',
            'users'
        ])
            ->whereHas('permissions', function ($query) use ($branchId, $module, $actions) {
                $query->where('branch_id', $branchId)
                    ->whereHas('modules', function ($moduleQuery) use ($module) {
                        $moduleQuery->where('module_name', $module->value);
                    })
                    ->where(function ($permissionQuery) use ($actions) {

                        foreach ($actions as $action) {
                            $permissionQuery->orWhere($action->value, true);
                        }
                    });
            })
            ->get()
            ->map(function ($employee) {
                return [
                    'employee_id' => $employee->employee_id,
                    'user_id' => $employee->user_id,
                    'uuid' => $employee->users->uuid,
                ];
            });
    }
}
