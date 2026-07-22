<?php

namespace Database\Seeders;

use App\Enums\ModuleEnum;
use App\Models\EmployeePermission;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ModuleEnum::cases() as $module) {
            $permissions = [
                'has_create' => true,
                'has_read' => true,
                'has_update' => true,
                'has_approve' => true,
            ];

            if (in_array($module->value, [
                'Facility Management',
                'Rooms & Beds',
                'Services',
                'Employee Management',
                'Billing & Invoices',
            ])) {
                $permissions['has_approve'] = false;
            }

            Module::updateOrCreate(
                ['module_name' => $module->value],
                $permissions
            );
        }

        // foreach (ModuleEnum::cases() as $module) {

        //     $moduleModel = Module::where(
        //         'module_name',
        //         $module->value
        //     )->first();

        //     EmployeePermission::updateOrCreate(
        //         [
        //             'employee_id' => 1,
        //             'branch_id' => 2,
        //             'module_id' => $moduleModel->module_id,
        //         ],
        //         [
        //             'can_read' => true,
        //             'can_create' => true,
        //             'can_update' => true,
        //             'can_approve' => true
        //         ]
        //     );
        // }
    }
}
