<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
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

    private const ROLE_COUNTS = [
        'caregiver' => 8,
        'nurse' => 8
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
            $count = self::ROLE_COUNTS[$role] ?? 1;

            for ($n = 1; $n <= $count; $n++) {
                $email = $n === 1 ? "{$role}@gmail.com" : "{$role}{$n}@gmail.com";
                $suffix = $n === 1 ? '' : " {$n}";

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'password' => Hash::make('password'),
                        'provider' => 'local',
                    ]
                );

                $employee = Employee::updateOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'first_name' => Str::title($role) . $suffix,
                        'last_name' => 'Account',
                        'status' => Employee::STATUS_ACTIVE,
                        'avatar' => 'https://ui-avatars.com/api/?name=' . strtoupper(substr($role, 0, 2)),
                        'birth_date' => now()->subYears(25 + $index)->subDays(($index * 10 + $n) * 30)->toDateString(),
                        'phone_number' => '917' . str_pad((string) (1000000 + $index * 10 + $n), 7, '0', STR_PAD_LEFT),
                    ]
                );


                $assignmentType = 'both';

                if ($role === 'caregiver') {
                    $rotation = ['both', 'online', 'facility', 'both', 'online'];
                    $assignmentType = $rotation[($n - 1) % count($rotation)];
                }

                foreach ($branches as $branch) {
                    EmployeeBranch::firstOrCreate(
                        [
                            'employee_id' => $employee->employee_id,
                            'branch_id' => $branch->branch_id,
                        ],
                        [
                            'role_name' => $role,
                            'assignment_type' => $assignmentType,
                        ]
                    );

                    foreach (RoleEnum::permissionsFor($role) as $moduleName => $actions) {
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
                            EmployeePermission::grantColumns($actions)
                        );
                    }
                }
            }
        }
    }
}
