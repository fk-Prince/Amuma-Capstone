<?php

namespace Tests\Feature;

use App\Enums\ModuleEnum;
use App\Enums\RoleEnum;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use UsesTempDatabase;

    private User $owner;
    private int $branchId;
    private string $branchUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ModuleSeeder::class);

        $this->owner = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'owner@amuma.com',
            'password' => 'password',
        ]);

        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => $this->owner->user_id,
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

        $this->giveEmployeeAccess($this->owner, 'branch_manager', true);

        Sanctum::actingAs($this->owner);
    }

    private function giveEmployeeAccess(User $user, string $role, bool $withEmployeeManagement): Employee
    {
        $employee = $user->employee()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '9171234567',
            'birth_date' => '1990-01-01',
        ]);

        $employee->employeeBranch()->create([
            'branch_id' => $this->branchId,
            'role_name' => $role,
            'status' => EmployeeBranch::STATUS_ACTIVE,
        ]);

        if ($withEmployeeManagement) {
            $module = Module::where('module_name', ModuleEnum::EmployeeManagement->value)->first();

            $employee->permissions()->create([
                'module_id' => $module->module_id,
                'branch_id' => $this->branchId,
                'employee_id' => $employee->employee_id,
            ] + EmployeePermission::grantColumns($module->actionColumns()));
        }

        return $employee;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'nurse1@amuma.com',
            'first_name' => 'Nora',
            'middle_name' => '',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1995-05-20',
            'phone_number' => '9171112222',
            'location' => [
                'street' => 'Rizal Street',
                'city' => 'Davao City',
                'province' => 'Davao del Sur',
                'country' => 'Philippines',
            ],
            'role_name' => 'nurse',
            'assignment_type' => 'both',
            'status' => 'active',
            'branch_uuid' => $this->branchUuid,
            'permissions' => [],
        ], $overrides);
    }

    private function createEmployee(array $overrides = []): User
    {
        $this->postJson('/api/employees', $this->payload($overrides))->assertOk();

        return User::where('email', $overrides['email'] ?? 'nurse1@amuma.com')->firstOrFail();
    }

    private function employeeBranch(User $user)
    {
        return $user->employee->employeeBranch()->where('branch_id', $this->branchId)->first();
    }

    public function test_creating_a_nurse_stores_the_details_and_default_password(): void
    {
        $readModule = Module::first();

        $response = $this->postJson('/api/employees', $this->payload([
            'permissions' => [
                ['module_id' => $readModule->module_id, 'actions' => ['can_read']],
            ],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.access.email', 'nurse1@amuma.com')
            ->assertJsonPath('data.access.default_password', 'delacruz1995')
            ->assertJsonPath('employee.role_name', 'Nurse');

        $user = User::where('email', 'nurse1@amuma.com')->firstOrFail();

        $this->assertTrue(Hash::check('delacruz1995', $user->password));
        $this->assertSame('Nora', $user->employee->first_name);
        $this->assertSame('nurse', $this->employeeBranch($user)->role_name);
        $this->assertSame('both', $this->employeeBranch($user)->assignment_type);
        $this->assertSame(1, $user->employee->permissions()->count());
    }

    public function test_assignment_type_is_null_for_positions_other_than_nurse_and_caregiver(): void
    {
        $user = $this->createEmployee([
            'email' => 'admin1@amuma.com',
            'role_name' => 'cashier',
            'assignment_type' => 'both',
        ]);

        $this->assertNull($this->employeeBranch($user)->assignment_type);
    }

    public function test_nurse_and_caregiver_require_an_assignment_type(): void
    {
        foreach (['nurse', 'caregiver'] as $role) {
            $this->postJson('/api/employees', $this->payload([
                'email' => "{$role}@amuma.com",
                'role_name' => $role,
                'assignment_type' => null,
            ]))->assertStatus(422)->assertJsonValidationErrors('assignment_type');
        }
    }

    public function test_a_cashier_employee_does_not_need_an_assignment_type(): void
    {
        $this->postJson('/api/employees', $this->payload([
            'email' => 'admin2@amuma.com',
            'role_name' => 'cashier',
            'assignment_type' => null,
        ]))->assertOk();
    }

    public function test_creating_with_a_taken_email_is_rejected(): void
    {
        $this->postJson('/api/employees', $this->payload(['email' => 'owner@amuma.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_creating_requires_the_address_and_valid_phone(): void
    {
        $this->postJson('/api/employees', $this->payload([
            'location' => ['street' => '', 'city' => '', 'province' => '', 'country' => ''],
            'phone_number' => '12345',
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'location.street',
            'location.city',
            'phone_number',
        ]);
    }

    public function test_creating_without_employee_permission_is_refused(): void
    {
        $staff = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'staff@amuma.com',
            'password' => 'password',
        ]);

        $this->giveEmployeeAccess($staff, 'nurse', false);

        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/employees', $this->payload());

        $this->assertFalse($response->isSuccessful());
        $this->assertNull(User::where('email', 'nurse1@amuma.com')->first());
    }

    public function test_updating_changes_the_employee_details(): void
    {
        $user = $this->createEmployee();

        $this->putJson("/api/employees/{$user->uuid}", $this->payload([
            'first_name' => 'Noreen',
            'phone_number' => '9173334444',
            'location' => [
                'street' => 'Mabini Street',
                'city' => 'Tagum City',
                'province' => 'Davao del Norte',
                'country' => 'Philippines',
            ],
            'assignment_type' => 'facility',
        ]))->assertOk()->assertJsonPath('message', 'Successfully Updated Employee Information.');

        $employee = $user->employee->fresh();

        $this->assertSame('Noreen', $employee->first_name);
        $this->assertSame('9173334444', $employee->phone_number);
        $this->assertSame('Tagum City', $employee->locations->city);
        $this->assertSame('facility', $this->employeeBranch($user)->assignment_type);
    }

    public function test_changing_the_position_to_a_non_clinical_role_clears_the_assignment(): void
    {
        $user = $this->createEmployee();

        $this->putJson("/api/employees/{$user->uuid}", $this->payload([
            'role_name' => 'cashier',
            'assignment_type' => 'both',
        ]))->assertOk();

        $this->assertSame('cashier', $this->employeeBranch($user)->role_name);
        $this->assertNull($this->employeeBranch($user)->assignment_type);
    }

    public function test_updating_can_set_a_new_password(): void
    {
        $user = $this->createEmployee();

        $this->putJson("/api/employees/{$user->uuid}", $this->payload([
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ]))->assertOk();

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_updating_without_a_password_keeps_the_current_one(): void
    {
        $user = $this->createEmployee();
        $before = $user->fresh()->password;

        $this->putJson("/api/employees/{$user->uuid}", $this->payload())->assertOk();

        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_an_invalid_new_password_is_rejected(): void
    {
        $user = $this->createEmployee();

        $this->putJson("/api/employees/{$user->uuid}", $this->payload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))->assertStatus(422)->assertJsonValidationErrors('password');

        $this->putJson("/api/employees/{$user->uuid}", $this->payload([
            'password' => 'long-enough-1',
            'password_confirmation' => 'does-not-match',
        ]))->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_updating_can_keep_its_own_email_but_not_take_another(): void
    {
        $user = $this->createEmployee();

        $this->putJson("/api/employees/{$user->uuid}", $this->payload())->assertOk();

        $this->putJson("/api/employees/{$user->uuid}", $this->payload(['email' => 'owner@amuma.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_an_employee_can_be_put_on_leave_and_brought_back(): void
    {
        $user = $this->createEmployee();

        $this->patchJson("/api/employees/{$user->uuid}/status", [
            'status' => 'on_leave',
            'branch_uuid' => $this->branchUuid,
        ])->assertOk()->assertJsonPath('message', 'Employee is now on leave.');

        $this->assertSame('on_leave', $this->employeeBranch($user)->fresh()->status);

        $this->patchJson("/api/employees/{$user->uuid}/status", [
            'status' => 'active',
            'branch_uuid' => $this->branchUuid,
        ])->assertOk();

        $this->assertSame('active', $this->employeeBranch($user)->fresh()->status);
    }

    public function test_status_only_accepts_active_or_on_leave(): void
    {
        $user = $this->createEmployee();

        $this->patchJson("/api/employees/{$user->uuid}/status", [
            'status' => 'inactive',
            'branch_uuid' => $this->branchUuid,
        ])->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_an_inactive_employee_cannot_be_put_on_leave(): void
    {
        $user = $this->createEmployee(['status' => 'inactive']);

        $response = $this->patchJson("/api/employees/{$user->uuid}/status", [
            'status' => 'on_leave',
            'branch_uuid' => $this->branchUuid,
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('inactive', $this->employeeBranch($user)->fresh()->status);
    }

    public function test_the_employee_list_only_contains_this_branchs_staff_in_rank_order(): void
    {
        $this->createEmployee(['email' => 'nurse1@amuma.com', 'role_name' => 'nurse']);
        $this->createEmployee(['email' => 'admin1@amuma.com', 'role_name' => 'cashier']);
        $this->createEmployee(['email' => 'caregiver1@amuma.com', 'role_name' => 'caregiver']);

        $otherBranchId = DB::table('branches')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Other Branch',
            'email' => 'other@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outsider = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'outsider@amuma.com',
            'password' => 'password',
        ]);

        $outsider->employee()->create([
            'first_name' => 'Out',
            'last_name' => 'Sider',
            'phone_number' => '9170000000',
            'birth_date' => '1990-01-01',
        ])->employeeBranch()->create([
            'branch_id' => $otherBranchId,
            'role_name' => 'nurse',
            'status' => EmployeeBranch::STATUS_ACTIVE,
        ]);

        $response = $this->getJson('/api/employees?' . http_build_query([
            'branch_uuid' => $this->branchUuid,
            'per_page' => 15,
        ]))->assertOk();

        $emails = collect($response->json('data'))->pluck('email')->all();

        $this->assertNotContains('outsider@amuma.com', $emails);
        $this->assertSame(
            ['owner@amuma.com', 'admin1@amuma.com', 'nurse1@amuma.com', 'caregiver1@amuma.com'],
            $emails
        );
        $this->assertSame(4, $response->json('total_employee'));
    }
}
