<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Enums\RoleEnum;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\Concerns\AssignsRandomServices;
use Database\Seeders\Concerns\SeedsBranches;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NurseSeeder extends Seeder
{
    use AssignsRandomServices;
    use SeedsBranches;

    private const NURSE_COUNT = 5;


    private const ASSIGNMENT_TYPES = ['both', 'online', 'facility'];

    public function run(): void
    {
        $branches = $this->seedBranches();

        if ($branches->isEmpty()) {
            $this->command?->warn('No branches found. Seed branches first.');
            return;
        }

        $modulesByName = Module::all()->keyBy('module_name');

        foreach ($branches->values() as $position => $branch) {
            $branchTag = $position === 0 ? '' : '.b' . ($position + 1);
            $branchLabel = $position === 0 ? '' : ' B' . ($position + 1);

            for ($i = 1; $i <= self::NURSE_COUNT; $i++) {
                $user = User::firstOrCreate(
                    ['email' => $this->staffEmail("nurse{$i}", $branchTag)],
                    [
                        'password' => Hash::make('password'),
                        'provider' => 'local',
                    ]
                );

                $employee = Employee::updateOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'first_name' => "Nurse{$i}{$branchLabel}",
                        'last_name' => 'Account',
                        'avatar' => 'https://ui-avatars.com/api/?name=N' . $i,
                        'birth_date' => now()->subYears(25 + $i)->subDays($i * 30)->toDateString(),
                        'phone_number' => '918' . str_pad((string) (1000000 + $position * 1000 + $i), 7, '0', STR_PAD_LEFT),
                    ]
                );

                $assignmentType = self::ASSIGNMENT_TYPES[($i - 1) % count(self::ASSIGNMENT_TYPES)];

                $employeeBranch = EmployeeBranch::updateOrCreate(
                    [
                        'employee_id' => $employee->employee_id,
                        'branch_id' => $branch->branch_id,
                    ],
                    [
                        'role_name' => 'nurse',
                        'assignment_type' => $assignmentType,
                        'status' => EmployeeBranch::STATUS_ACTIVE,
                    ]
                );

                foreach (RoleEnum::Nurse->permissions() as $moduleName => $actions) {
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

                $this->assignRandomServices($employeeBranch, 'nurse');
            }
        }
    }
}
