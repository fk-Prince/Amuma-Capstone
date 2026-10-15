<?php

namespace Tests\Unit\Booking;

use App\Enums\ModuleEnum;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait BookingFixtures
{
    protected Branch $branch;
    protected int $bookingsModuleId;
    protected int $billingModuleId;
    protected User $admission;
    protected User $relative;
    protected Client $client;
    protected int $roomId;
    protected int $bedId;
    protected int $commonContractId;
    protected array $services = [];
    private int $userCount = 0;

    protected function setUpBooking(): void
    {
        Invoice::forgetPatientInvoiceIds();

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
            'settings' => json_encode([
                'homecare_downpayment_percent' => 50,
                'complete_admission_booking_percent' => 100,
                'minimum_adl_hours' => 4,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->branch = Branch::findOrFail($branchId);

        $this->bookingsModuleId = Module::create(['module_name' => ModuleEnum::Bookings->value])->module_id;
        $this->billingModuleId = Module::create(['module_name' => ModuleEnum::BillingAndInvoices->value])->module_id;

        $this->admission = $this->staff(ModuleEnum::Bookings->actionColumns(), 'admission');
        $this->grant($this->admission, $this->billingModuleId, ModuleEnum::BillingAndInvoices->actionColumns());

        $this->relative = $this->makeUser('gina.cruz@gmail.com');
        $this->client = Client::create([
            'user_id' => $this->relative->user_id,
            'first_name' => 'Gina',
            'last_name' => 'Cruz',
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'branch_id' => $branchId,
            'category_name' => 'Nursing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['Wound Care' => 800, 'Blood Pressure Check' => 300] as $name => $price) {
            $serviceId = DB::table('services')->insertGetId([
                'service_uuid' => (string) Str::uuid(),
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'service_name' => $name,
                'price' => $price,
                'maximum_duration' => '01:00:00',
                'type' => 'online',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->services[] = ['service_id' => $serviceId, 'service_name' => $name, 'price' => $price];
        }

        $this->contract('Homecare', 'ADL', 'HOURLY', 300);
        $this->commonContractId = $this->contract('Facility', 'COMMON', 'MONTHLY', 15000);

        $this->roomId = DB::table('rooms')->insertGetId([
            'room_no' => '101',
            'floor' => '1',
            'branch_id' => $branchId,
            'room_type' => 'Common',
            'capacity' => '2',
            'status' => 'Available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bedId = DB::table('beds')->insertGetId([
            'room_id' => $this->roomId,
            'bed_no' => 'A',
            'status' => 'Available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function contract(string $category, string $type, string $cycle, float $price): int
    {
        return DB::table('branch_contracts')->insertGetId([
            'branch_id' => $this->branch->branch_id,
            'category' => $category,
            'accommodation_type' => $type,
            'billing_cycle' => $cycle,
            'price' => $price,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function giveHomecareSubscription(): void
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

    protected function makeUser(?string $email = null): User
    {
        $this->userCount++;

        return User::create([
            'uuid' => (string) Str::uuid(),
            'email' => $email ?? "user{$this->userCount}@amuma.com",
            'password' => 'password',
        ]);
    }

    protected function staff(array $actions, string $role): User
    {
        $user = $this->makeUser();

        $employee = Employee::create([
            'user_id' => $user->user_id,
            'first_name' => ucfirst($role),
            'last_name' => 'Staff',
        ]);

        EmployeeBranch::create([
            'branch_id' => $this->branch->branch_id,
            'employee_id' => $employee->employee_id,
            'role_name' => $role,
            'status' => 'active',
        ]);

        $this->grant($user, $this->bookingsModuleId, $actions);

        return $user;
    }

    protected function grant(User $user, int $moduleId, array $actions): void
    {
        EmployeePermission::create([
            'employee_id' => $user->employee->employee_id,
            'branch_id' => $this->branch->branch_id,
            'module_id' => $moduleId,
        ] + EmployeePermission::grantColumns($actions));
    }

    protected function patientData(): array
    {
        return [
            'first_name' => 'Lola',
            'middle_name' => null,
            'last_name' => 'Cruz',
            'gender' => 'female',
            'date_of_birth' => '1950-03-14',
            'address' => 'Bajada, Davao City',
        ];
    }

    protected function guardianData(): array
    {
        return [
            'first_name' => 'Gina',
            'middle_name' => null,
            'last_name' => 'Cruz',
            'email' => 'gina.cruz@gmail.com',
            'phone_number' => '09171234567',
            'relationship' => 'daughter',
        ];
    }

    protected function visitDate(): string
    {
        return now()->addDays(2)->toDateString();
    }

    protected function medicalVisit(array $overrides = []): array
    {
        return array_merge([
            'type' => Booking::TYPE_MEDICAL,
            'date' => $this->visitDate(),
            'prefered_time' => '09:00',
            'address' => 'Matina, Davao City',
            'latitude' => 7.06,
            'longitude' => 125.59,
            'services' => $this->services,
            'time_span' => '',
        ], $overrides);
    }

    protected function adlVisit(array $overrides = []): array
    {
        return $this->medicalVisit(array_merge([
            'type' => Booking::TYPE_ADL,
            'services' => [],
            'time_span' => 4,
        ], $overrides));
    }

    protected function completeAdmission(array $overrides = []): array
    {
        return array_merge([
            'type' => Booking::TYPE_COMPLETEADMISSION,
            'plan' => 'Common',
            'billing_cycle' => 'Monthly',
            'admission_date' => now()->addDays(5)->toDateString(),
        ], $overrides);
    }

    protected function bookingRequest(string $category, array $bookingData): array
    {
        return [
            'branch_uuid' => $this->branch->uuid,
            'category' => $category,
            'booking_data' => $bookingData + [
                'patient' => $this->patientData(),
                'guardian' => $this->guardianData(),
                'assessment' => [],
                'diagnoses' => [],
            ],
        ];
    }

    protected function booking(array $data, array $overrides = []): Booking
    {
        $category = isset($data['homecare']) ? Booking::CATEGORY_ONLINE : Booking::CATEGORY_FACILITY;

        return Booking::create(array_merge([
            'user_id' => $this->relative->user_id,
            'branch_id' => $this->branch->branch_id,
            'category' => $category,
            'booking_data' => $data + [
                'patient' => $this->patientData(),
                'guardian' => $this->guardianData(),
                'assessment' => [],
                'diagnoses' => [],
            ],
            'status' => Booking::STATUS_PENDING,
            'booking_type' => Booking::BOOKINGTYPE_ONLINE,
            'valid_until' => Carbon::parse($this->visitDate() . ' 09:00'),
        ], $overrides));
    }

    protected function makeRequest(array $data, ?User $user = null): Request
    {
        $user ??= $this->admission;

        return Request::create('/api/bookings', 'POST', $data + ['branch_uuid' => $this->branch->uuid])
            ->setUserResolver(fn() => $user);
    }

    protected function assertFailsWith(string $message, callable $action, ?int $code = null): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            $this->assertContains($message, collect($e->errors())->flatten()->all());

            return;
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
