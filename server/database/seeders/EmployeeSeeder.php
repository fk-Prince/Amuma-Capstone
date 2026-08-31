<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    private const ROLES = [
        'administrator',
        'admission',
        'accounting',
        'nurse',
        'caregiver',
    ];


    private const ROLE_DEFAULT_PERMISSIONS = [
        'administrator' => [
            'Patients' => ['can_read'],
            'Rooms & Beds' => ['can_read', 'can_create', 'can_update'],
            'Contracts' => ['can_read', 'can_create', 'can_update'],
            'Services' => ['can_read', 'can_create', 'can_update', 'can_assign'],
            'Employee Management' => ['can_read', 'can_create', 'can_update'],
            'Bookings' => ['can_read'],
            'Admissions' => ['can_read'],
            'Billing & Invoices' => ['can_read'],
            'Manage Branches' =>  ['can_read', 'can_create', 'can_update'],
            'Branch Settings' =>  ['can_read', 'can_create', 'can_update'],
            'Schedules' => ['can_read']
        ],
        'admission' => [
            'Patients' => ['can_read', 'can_create', 'can_update', 'can_approve', 'can_assign'],
            'Admissions' => ['can_read', 'can_create', 'can_update', 'can_approve', 'can_assign'],
            'Bookings' => ['can_read', 'can_create', 'can_update', 'can_approve', 'can_assign'],
            'Schedules' => ['can_read', 'can_create', 'can_update', 'can_approve', 'can_assign'],
            'Services' => ['can_read', 'can_create', 'can_update', 'can_assign'],
            'Contracts' => ['can_read'],
            'Rooms & Beds' => ['can_read'],
            'Employee Management' => ['can_read'],
        ],
        'accounting' => [
            'Billing & Invoices' => ['can_read', 'can_create', 'can_update', 'can_approve', 'can_assign'],
            'Patients' => ['can_read'],
        ],
        'nurse' => [
            'Patients' => ['can_read', 'can_create', 'can_update'],
            'Schedules' => ['can_read'],
        ],
        'caregiver' => [
            'Patients' => ['can_read', 'can_create', 'can_update'],
            'Schedules' => ['can_read'],
        ],
    ];

    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        $modulesByName = Module::all()->keyBy('module_name');

        foreach (self::ROLES as $index => $role) {
            $user = User::firstOrCreate(
                ['email' => "{$role}@gmail.com"],
                [
                    'password' => Hash::make('password'),
                    'provider' => 'local',
                ]
            );

            $employee = Employee::updateOrCreate(
                ['user_id' => $user->user_id],
                [
                    'first_name' => Str::title($role),
                    'last_name' => 'Account',
                    'status' => Employee::STATUS_ACTIVE,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . strtoupper(substr($role, 0, 2)),
                    'birth_date' => now()->subYears(25 + $index)->subDays($index * 30)->toDateString(),
                    'phone_number' => '0917' . str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                ]
            );

            foreach ($branches as $branch) {
                EmployeeBranch::firstOrCreate(
                    [
                        'employee_id' => $employee->employee_id,
                        'branch_id' => $branch->branch_id,
                    ],
                    [
                        'role_name' => $role,
                        'assignment_type' => 'both',
                    ]
                );

                foreach (self::ROLE_DEFAULT_PERMISSIONS[$role] ?? [] as $moduleName => $actions) {
                    $module = $modulesByName->get($moduleName);

                    if (!$module) {
                        continue;
                    }

                    EmployeePermission::updateOrCreate(
                        [
                            'employee_id' => $employee->employee_id,
                            'branch_id' => $branch->branch_id,
                            'module_id' => $module->module_id,
                        ],
                        [
                            'can_read' => in_array('can_read', $actions, true),
                            'can_create' => in_array('can_create', $actions, true),
                            'can_update' => in_array('can_update', $actions, true),
                            'can_approve' => in_array('can_approve', $actions, true),
                            'can_assign' => in_array('can_assign', $actions, true),
                        ]
                    );
                }
            }
        }
    }
}
