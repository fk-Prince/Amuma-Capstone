<?php

namespace Tests\Unit\Scheduling;

use App\Enums\ModuleEnum;
use App\Http\Controllers\PatientAdmissionController;
use App\Models\Bed;
use App\Models\EmployeePermission;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\PatientAdmission;
use App\Models\User;
use App\Service\PatientService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    use UsesTempDatabase;
    use SchedulingFixtures;

    private PatientAdmissionController $controller;
    private User $admissionStaff;
    private int $admissionsModuleId;
    private int $roomId;
    private int $bedId;
    private int $contractId;

    protected function setUp(): void
    {
        parent::setUp();

        Invoice::forgetPatientInvoiceIds();
        $this->setUpScheduling();
        $this->giveFacilitySubscription();

        $this->admissionsModuleId = Module::create(['module_name' => ModuleEnum::Admissions->value])->module_id;
        $this->admissionStaff = $this->admissionStaff(ModuleEnum::Admissions->actionColumns());

        $this->contractId = DB::table('branch_contracts')->insertGetId([
            'branch_id' => $this->branch->branch_id,
            'category' => 'Facility',
            'accommodation_type' => 'COMMON',
            'billing_cycle' => 'MONTHLY',
            'price' => 15000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'room_no' => '101',
            'floor' => '1',
            'branch_id' => $this->branch->branch_id,
            'room_type' => 'Common',
            'capacity' => '2',
            'status' => 'Available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bedId = DB::table('beds')->insertGetId([
            'room_id' => $this->roomId,
            'bed_no' => 'A',
            'status' => Bed::STATUS_AVAILABLE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patients = $this->createStub(PatientService::class);
        $patients->method('showPatient')->willReturn(['uuid' => $this->patient->uuid]);
        $this->app->instance(PatientService::class, $patients);

        $this->controller = $this->app->make(PatientAdmissionController::class);
    }

    private function giveFacilitySubscription(): void
    {
        $planId = DB::table('plans')->insertGetId([
            'description' => 'Homecare and facility',
            'name' => 'Complete',
            'price' => 1000,
            'plan_code' => 'C',
            'type' => 'sme',
        ]);

        $subscriptionId = DB::table('subscriptions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'plan_id' => $planId,
            'agency_id' => $this->branch->agency_id,
            'status' => 'active',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('branch_subscription')->insert([
            'uuid' => (string) Str::uuid(),
            'subscription_id' => $subscriptionId,
            'branch_id' => $this->branch->branch_id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function admissionStaff(array $actions): User
    {
        $user = $this->staff([], 'admission');

        EmployeePermission::create([
            'employee_id' => $user->employee->employee_id,
            'branch_id' => $this->branch->branch_id,
            'module_id' => $this->admissionsModuleId,
        ] + EmployeePermission::grantColumns($actions));

        return $user;
    }

    private function newAdmission(array $overrides = [], ?User $user = null)
    {
        return $this->controller->action($this->makeRequest($overrides + [
            'action' => 'new_admission',
            'p_uuid' => $this->patient->uuid,
            'room_id' => $this->roomId,
            'bed_id' => $this->bedId,
            'contract_id' => $this->contractId,
            'admitted_at' => now()->addDays(3)->toDateString(),
        ], $user ?? $this->admissionStaff));
    }

    private function admission(string $status, ?int $bedId = null): PatientAdmission
    {
        return PatientAdmission::create([
            'patient_id' => $this->patient->patient_id,
            'bed_id' => $bedId ?? $this->bedId,
            'status' => $status,
            'admitted_at' => now(),
            'discharged_at' => now()->addMonth(),
        ]);
    }

    public function test_please_select_an_admission_date(): void
    {
        $this->assertFailsWith(
            'Please select an admission date.',
            fn() => $this->newAdmission(['admitted_at' => '']),
            400
        );
    }

    public function test_patient_already_has_an_active_admission(): void
    {
        $this->admission(PatientAdmission::STATUS_ADMITTED);

        $this->assertFailsWith(
            'Patient already has an active admission.',
            fn() => $this->newAdmission(),
            400
        );

        $this->assertSame(1, PatientAdmission::count());
    }

    public function test_the_selected_bed_is_no_longer_available(): void
    {
        Bed::whereKey($this->bedId)->update(['status' => Bed::STATUS_OCCUPIED]);

        $this->assertFailsWith(
            'The selected bed is no longer available.',
            fn() => $this->newAdmission(),
            400
        );

        $this->assertSame(0, PatientAdmission::count());
    }
}
