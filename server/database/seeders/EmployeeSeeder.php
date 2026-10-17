<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\Concerns\AssignsRandomServices;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    use AssignsRandomServices;

    private const ROLES = [
        'branch_manager',
        'admission',
        'cashier',
        'nurse',
        'caregiver',
    ];

    private const ROLE_COUNTS = [
        'caregiver' => 8,
        'nurse' => 8
    ];


    public function run(): void
    {
        $branches = Branch::orderBy('branch_id')->get();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        $modulesByName = Module::all()->keyBy('module_name');

        foreach ($branches->values() as $position => $branch) {
            $branchTag = $position === 0 ? '' : '.b' . ($position + 1);
            $branchLabel = $position === 0 ? '' : ' B' . ($position + 1);

            foreach (self::ROLES as $index => $role) {
                $count = self::ROLE_COUNTS[$role] ?? 1;

                for ($n = 1; $n <= $count; $n++) {
                    $email = ($n === 1 ? $role : "{$role}{$n}") . "{$branchTag}@gmail.com";
                    $suffix = ($n === 1 ? '' : " {$n}") . $branchLabel;

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
                            'phone_number' => '917' . str_pad((string) (1000000 + $position * 1000 + $index * 10 + $n), 7, '0', STR_PAD_LEFT),
                        ]
                    );

                    $assignmentType = in_array($role, ['nurse', 'caregiver'], true) ? 'both' : null;

                    if ($role === 'caregiver') {
                        $rotation = array_values(array_filter([
                            $branch->hasHomecareSubscription() ? 'online' : null,
                            $branch->hasFacilitySubscription() ? 'facility' : null,
                        ])) ?: ['online', 'facility'];

                        $assignmentType = $rotation[($n - 1) % count($rotation)];
                    }

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

                    $this->assignRandomServices($employeeBranch, $role);

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
