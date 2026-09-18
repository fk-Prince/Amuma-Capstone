<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Enums\RoleEnum;
use App\Models\EmployeePermission;
use App\Models\EmployeeService;
use App\Models\Module;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NurseSeeder extends Seeder
{
    private const NURSE_COUNT = 5;


    // Cycled across the nurses so test data covers a homecare-only nurse, a
    // facility-only nurse, and one that can be assigned to both, instead of
    // every seeded nurse being interchangeable.
    private const ASSIGNMENT_TYPES = ['both', 'online', 'facility'];

    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        $modulesByName = Module::all()->keyBy('module_name');

        for ($i = 1; $i <= self::NURSE_COUNT; $i++) {
            $user = User::firstOrCreate(
                ['email' => "nurse{$i}@gmail.com"],
                [
                    'password' => Hash::make('password'),
                    'provider' => 'local',
                ]
            );

            $employee = Employee::updateOrCreate(
                ['user_id' => $user->user_id],
                [
                    'first_name' => "Nurse{$i}",
                    'last_name' => 'Account',
                    'status' => Employee::STATUS_ACTIVE,
                    'avatar' => 'https://ui-avatars.com/api/?name=N' . $i,
                    'birth_date' => now()->subYears(25 + $i)->subDays($i * 30)->toDateString(),
                    'phone_number' => '918' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT),
                ]
            );

            $assignmentType = self::ASSIGNMENT_TYPES[($i - 1) % count(self::ASSIGNMENT_TYPES)];

            foreach ($branches as $branch) {
                $employeeBranch = EmployeeBranch::updateOrCreate(
                    [
                        'employee_id' => $employee->employee_id,
                        'branch_id' => $branch->branch_id,
                    ],
                    [
                        'role_name' => 'nurse',
                        'assignment_type' => $assignmentType,
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

                $services = Service::where('branch_id', $branch->branch_id)->get();

                foreach ($services as $service) {
                    EmployeeService::firstOrCreate(
                        [
                            'employee_branch_id' => $employeeBranch->employee_branch_id,
                            'service_id' => $service->service_id,
                        ],
                        [
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
