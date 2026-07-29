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
        $descriptions = [
            'Homecare' => 'Manage homecare services, requests, and patient care activities.',
            'Admissions' => 'Manage patient admissions, admission requests, and facility intake processes.',
            'Bookings' => 'Manage patient booking requests, reservations, and booking status.',
            'Patients' => 'Manage patient profiles, medical information, and patient records.',
            'Schedules' => 'Manage employee schedules, availability, and assigned tasks.',
            'Pricing' => 'Manage service pricing, rates, and cost configurations.',
            'Rooms & Beds' => 'Manage facility rooms, bed availability, and accommodation assignments.',
            'Services' => 'Manage available healthcare services and service configurations.',
            'Employee Management' => 'Manage employees, roles, and employee-related information.',
            'Billing & Invoices' => 'Manage billing records, invoices, and payment transactions.',
            'Reports' => 'View and generate system reports and analytics.',
            'Manage Branches' => 'Manage branch information, locations, and branch access.',
            'Branch Settings' => 'Configure branch-specific settings and preferences.',
        ];

        foreach (ModuleEnum::cases() as $module) {
            $permissions = [
                'has_create' => true,
                'has_read' => true,
                'has_update' => true,
                'has_approve' => true,
                'description' => $descriptions[$module->value] ?? null,
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
    }
}
