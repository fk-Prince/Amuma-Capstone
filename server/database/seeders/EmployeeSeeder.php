<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\EmployeeService;
use App\Models\Module;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    private const ROLES = [
        'branch_manager',
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
        $branches = Branch::orderBy('branch_id')->limit(1)->get();

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
                        'first_name' => Str::title(str_replace('_', ' ', $role)) . $suffix,
                        'last_name' => 'Account',
                        'avatar' => 'https://ui-avatars.com/api/?name=' . strtoupper(substr($role, 0, 2)),
                        'birth_date' => now()->subYears(25 + $index)->subDays(($index * 10 + $n) * 30)->toDateString(),
                        'phone_number' => '917' . str_pad((string) (1000000 + $index * 10 + $n), 7, '0', STR_PAD_LEFT),
                    ]
                );


                $assignmentType = in_array($role, ['nurse', 'caregiver'], true) ? 'both' : null;

                if ($role === 'caregiver') {
                    $rotation = ['online', 'facility'];
                    $assignmentType = $rotation[($n - 1) % count($rotation)];
                }

                foreach ($branches as $branch) {
                    $employeeBranch = EmployeeBranch::firstOrCreate(
                        [
                            'employee_id' => $employee->employee_id,
                            'branch_id' => $branch->branch_id,
                        ],
                        [
                            'role_name' => $role,
                            'assignment_type' => $assignmentType,
                            'status' => EmployeeBranch::STATUS_ACTIVE,
                        ]
                    );

                    if ($role === 'nurse') {
                        $service = Service::where('branch_id', $branch->branch_id)->inRandomOrder()->first();

                        if ($service) {
                            EmployeeService::firstOrCreate(
                                ['employee_branch_id' => $employeeBranch->employee_branch_id],
                                [
                                    'service_id' => $service->service_id,
                                    'is_active' => true,
                                ]
                            );
                        }
                    }

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
