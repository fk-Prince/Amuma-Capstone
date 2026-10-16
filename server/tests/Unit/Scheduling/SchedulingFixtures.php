<?php

namespace Tests\Unit\Scheduling;

use App\Enums\ModuleEnum;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\ScheduleService;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait SchedulingFixtures
{
    protected Branch $branch;
    protected int $schedulesModuleId;
    protected User $coordinator;
    protected Patient $patient;
    protected array $serviceIds = [];
    private int $userCount = 0;

    protected function setUpScheduling(): void
    {
        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'agency_id' => $agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'status' => Branch::STATUS_VERIFIED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->branch = Branch::findOrFail($branchId);
        $this->schedulesModuleId = Module::create(['module_name' => ModuleEnum::Schedules->value])->module_id;

        $this->coordinator = $this->staff(ModuleEnum::Schedules->actionColumns(), 'admission');

        $categoryId = DB::table('categories')->insertGetId([
            'branch_id' => $branchId,
            'category_name' => 'Nursing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            'Wound Care' => 'both',
            'Insulin Injection' => 'nurse',
            'Bathing Assistance' => 'caregiver',
        ] as $name => $performedBy) {
            $this->serviceIds[$name] = DB::table('services')->insertGetId([
                'service_uuid' => (string) Str::uuid(),
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'service_name' => $name,
                'price' => 500,
                'maximum_duration' => '01:00:00',
                'type' => 'online',
                'performed_by' => $performedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->patient = $this->patient('Lola', 'Cruz');
    }

    protected function patient(string $first, string $last): Patient
    {
        $patientId = DB::table('patients')->insertGetId([
            'branch_id' => $this->branch->branch_id,
            'uuid' => (string) Str::uuid(),
            'patient_code' => 'PT-' . Str::random(6),
            'first_name' => $first,
            'last_name' => $last,
            'gender' => 'female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Patient::findOrFail($patientId);
    }

    protected function makeUser(?string $email = null): User
    {
        $this->userCount++;

        return User::create([
            'uuid' => (string) Str::uuid(),
            'email' => $email ?? "user{$this->userCount}@amuma.com",
            'password' => 'password',
        ]);
    }

    protected function staff(array $actions, string $role, string $assignmentType = 'online', ?string $name = null): User
    {
        $user = $this->makeUser();

        $employee = Employee::create([
            'user_id' => $user->user_id,
            'first_name' => $name ?? ucfirst($role),
            'last_name' => 'Staff',
        ]);

        EmployeeBranch::create([
            'branch_id' => $this->branch->branch_id,
            'employee_id' => $employee->employee_id,
            'role_name' => $role,
            'assignment_type' => $assignmentType,
            'status' => EmployeeBranch::STATUS_ACTIVE,
        ]);

        EmployeePermission::create([
            'employee_id' => $employee->employee_id,
            'branch_id' => $this->branch->branch_id,
            'module_id' => $this->schedulesModuleId,
        ] + EmployeePermission::grantColumns($actions));

        return $user;
    }

    protected function employee(string $role, string $assignmentType = 'online', ?string $name = null): Employee
    {
        return $this->staff([], $role, $assignmentType, $name)->employee;
    }

    protected function schedule(string $category = Schedule::CATEGORYHOMECARE, string $status = Schedule::STATUS_PENDING, ?Patient $patient = null, string $at = '+2 days 09:00'): Schedule
    {
        return Schedule::create([
            'patient_id' => ($patient ?? $this->patient)->patient_id,
            'scheduled_at' => now()->modify($at),
            'status' => $status,
            'category' => $category,
        ]);
    }

    protected function medicalService(Schedule $schedule, string $serviceName = 'Wound Care'): ScheduleService
    {
        return $schedule->scheduleServices()->create([
            'service_id' => $this->serviceIds[$serviceName],
            'type' => ScheduleService::TYPE_MEDICAL,
        ]);
    }

    protected function adlService(Schedule $schedule, float $hours = 4): ScheduleService
    {
        return $schedule->scheduleServices()->create([
            'hours_booked' => $hours,
            'type' => ScheduleService::TYPE_ADL,
        ]);
    }

    protected function assign(ScheduleService $service, Employee $employee, bool $active = true): ScheduleAssigned
    {
        return $service->assigned()->create([
            'employee_id' => $employee->employee_id,
            'is_active' => $active,
        ]);
    }

    protected function makeRequest(array $data, ?User $user = null): Request
    {
        $user ??= $this->coordinator;

        return Request::create('/api/schedules', 'POST', $data + ['branch_uuid' => $this->branch->uuid])
            ->setUserResolver(fn() => $user);
    }

    protected function assertFailsWith(string $message, callable $action, ?int $code = null): void
    {
        try {
            $action();
        } catch (Exception $e) {
            $this->assertSame($message, $e->getMessage());

            if ($code !== null) {
                $this->assertSame($code, $e->getCode());
            }

            return;
        }

        $this->fail("Expected the error \"{$message}\".");
    }
}
