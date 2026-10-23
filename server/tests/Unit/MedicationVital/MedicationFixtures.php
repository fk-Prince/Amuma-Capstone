<?php

namespace Tests\Unit\MedicationVital;

use App\Enums\ModuleEnum;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait MedicationFixtures
{
    private int $branchId;
    private string $branchUuid;

    private function setUpMedicationStaff(User $user): void
    {
        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => $user->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->branchUuid = (string) Str::uuid();

        $this->branchId = DB::table('branches')->insertGetId([
            'uuid' => $this->branchUuid,
            'agency_id' => $agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employee = Employee::create([
            'user_id' => $user->user_id,
            'first_name' => 'Nina',
            'last_name' => 'Nurse',
        ]);

        EmployeeBranch::create([
            'branch_id' => $this->branchId,
            'employee_id' => $employee->employee_id,
            'role_name' => 'nurse',
            'status' => EmployeeBranch::STATUS_ACTIVE,
        ]);

        EmployeePermission::create([
            'employee_id' => $employee->employee_id,
            'branch_id' => $this->branchId,
            'module_id' => Module::create(['module_name' => ModuleEnum::Medications->value])->module_id,
        ] + EmployeePermission::grantColumns(ModuleEnum::Medications->actionColumns()));
    }
}
