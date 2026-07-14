<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            'Admissions',
            'Homecare Bookings',
            'Manage Branches',
            'Patients',
            'Schedules',
            'Facility Management',
            'Rooms & Beds',
            'Services',
            'Employee Management',
            'Billing & Invoices',
            'Reports',
            'Branch Settings',
        ];

        foreach ($modules as $module) {
            Module::firstOrCreate([
                'module_name' => $module,
            ]);
        }
    }
}
