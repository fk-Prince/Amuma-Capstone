<?php

namespace Database\Seeders;

use App\Enums\ModuleEnum;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{

    public function run(): void
    {
        $descriptions = [
            'Bookings' => 'Manage patient booking requests, reservations, appointments, and booking status.',
            'Schedules' => 'Manage employee schedules, availability, shifts, and assigned tasks.',
            'Admissions' => 'Manage patient admissions, admission requests, and facility intake processes.',
            'Patients' => 'Manage patient profiles, manage patient schedules, medical information, records, and related details.',
            'Contracts' => 'Manage service contracts, pricing, rates, and cost configurations.',
            'Rooms & Beds' => 'Manage facility rooms, bed availability, and patient accommodation assignments.',
            'Services' => 'Manage healthcare services, service details, pricing, and configurations.',
            'Employee Management' => 'Manage employees, roles, permissions, and employee information.',
            'Billing & Invoices' => 'Manage billing records, invoices, payments, and financial transactions.',
            'Manage Branches' => 'Manage branch information, locations, operations, and branch access.',
            'Branch Settings' => 'Configure branch-specific settings, preferences, and operational options.',
        ];

        $approveDisabledModules = [
            'Schedules',
            'Admissions',
            'Contracts',
            'Rooms & Beds',
            'Services',
            'Employee Management',
            'Billing & Invoices',
            'Branch Settings',
        ];

        $assignDisabledModules = [
            'Bookings',
            'Admissions',
            'Contracts',
            'Rooms & Beds',
            'Services',
            'Employee Management',
            'Billing & Invoices',
            'Branch Settings',
        ];

        foreach (ModuleEnum::cases() as $module) {
            $moduleName = $module->value;

            $permissions = [
                'has_create' => true,
                'has_read' => true,
                'has_update' => true,

                'has_approve' => !in_array(
                    $moduleName,
                    $approveDisabledModules,
                    true
                ),

                'has_assign' => !in_array(
                    $moduleName,
                    $assignDisabledModules,
                    true
                ),

                'description' => $descriptions[$moduleName] ?? null,
            ];

            Module::updateOrCreate(
                ['module_name' => $moduleName],
                $permissions
            );
        }
    }
}
