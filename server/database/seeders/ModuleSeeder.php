<?php

namespace Database\Seeders;

use App\Enums\ModuleEnum;
use App\Enums\RoleEnum;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::where('module_name', 'Manage Branches')
            ->update(['module_name' => ModuleEnum::ManageSubscription->value]);

        foreach (ModuleEnum::cases() as $module) {
            Module::updateOrCreate(['module_name' => $module->value]);
        }

        $this->grantAuditLogs();
    }

    private function grantAuditLogs(): void
    {
        $module = Module::where('module_name', ModuleEnum::AuditLogs->value)->first();

        foreach ([RoleEnum::AgencyOwner, RoleEnum::BranchManager] as $role) {
            $actions = $role->permissions()[ModuleEnum::AuditLogs->value] ?? [];

            EmployeeBranch::where('role_name', $role->value)
                ->get(['employee_id', 'branch_id'])
                ->each(fn($assignment) => EmployeePermission::firstOrCreate(
                    [
                        'employee_id' => $assignment->employee_id,
                        'branch_id' => $assignment->branch_id,
                        'module_id' => $module->module_id,
                    ],
                    EmployeePermission::grantColumns($actions)
                ));
        }
    }
}
