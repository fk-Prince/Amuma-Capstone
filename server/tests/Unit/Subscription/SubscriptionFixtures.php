<?php

namespace Tests\Unit\Subscription;

use App\Enums\ModuleEnum;
use App\Enums\RoleEnum;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlanSeeder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait SubscriptionFixtures
{
    protected bool $declineCard = false;
    protected bool $failRefunds = false;
    protected User $admin;
    protected User $owner;
    protected Agency $agency;
    protected Branch $branch;
    protected Subscription $subscription;
    private int $userCount = 0;

    protected function setUpSubscription(): void
    {
        $this->seed(PlanSeeder::class);

        foreach (ModuleEnum::cases() as $module) {
            Module::create(['module_name' => $module->value]);
        }

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'refunds')) {
                return $this->failRefunds
                    ? Http::response(['error_code' => 'API_ERROR'], 500)
                    : Http::response(['status' => 'SUCCEEDED']);
            }

            if (str_ends_with($request->url(), '/credit_card_charges')) {
                if ($this->declineCard) {
                    return Http::response(['error_code' => 'CARD_DECLINED_ERROR'], 400);
                }

                return Http::response([
                    'id' => 'chg_' . Str::random(8),
                    'external_id' => $request['external_id'],
                    'masked_card_number' => '400000XXXXXX0002',
                    'metadata' => $request['metadata'],
                    'status' => 'CAPTURED',
                ]);
            }

            return Http::response([]);
        });

        $this->admin = $this->makeUser('Mara', 'Reyes');
        PlatformAdmin::create(['user_id' => $this->admin->user_id, 'first_name' => 'Mara', 'last_name' => 'Reyes']);
    }

    protected function makeUser(string $first, string $last, bool $client = false): User
    {
        $this->userCount++;

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => strtolower($first) . $this->userCount . '@amuma.com',
            'password' => 'password',
        ]);

        if ($client) {
            Client::create(['user_id' => $user->user_id, 'first_name' => $first, 'last_name' => $last]);
        }

        return $user;
    }

    protected function plan(string $code, string $type = Plan::TYPE_SME): Plan
    {
        return Plan::where('plan_code', $code)->where('type', $type)->firstOrFail();
    }

    protected function subscribed(string $code = 'A', string $type = Plan::TYPE_SME, array $overrides = []): Subscription
    {
        $this->owner = $this->makeUser('Ana', 'Santos', true);

        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Santos Care',
            'email' => 'santos@care.ph',
            'registered_by' => $this->owner->user_id,
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->agency = Agency::findOrFail($agencyId);

        $this->branch = $this->makeBranch('AMUMA Davao City', 'davao@amuma.com');

        $plan = $this->plan($code, $type);

        $this->subscription = Subscription::create(array_merge([
            'agency_id' => $agencyId,
            'plan_id' => $plan->plan_id,
            'status' => Subscription::STATUS_ACTIVE,
            'mode' => Subscription::MODE_LIVE,
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->startOfDay()->addMonths(6)->toDateString(),
        ], $overrides));

        $this->link($this->branch, BranchSubscription::STATUS_APPROVED);
        $this->payment($plan, (float) $plan->price);

        $employee = Employee::create([
            'user_id' => $this->owner->user_id,
            'employee_code' => 'EMP-0001',
            'first_name' => 'Ana',
            'last_name' => 'Santos',
        ]);

        $this->assign($employee, $this->branch, RoleEnum::AgencyOwner->value, RoleEnum::AgencyOwner->permissions());

        return $this->subscription;
    }

    protected function makeBranch(string $name, string $email, string $status = Branch::STATUS_VERIFIED): Branch
    {
        $branchId = DB::table('branches')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'agency_id' => $this->agency->agency_id,
            'name' => $name,
            'email' => $email,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Branch::findOrFail($branchId);
    }

    protected function link(Branch $branch, string $status, string $type = BranchSubscription::TYPE_INCLUDED): BranchSubscription
    {
        return BranchSubscription::create([
            'subscription_id' => $this->subscription->subscription_id,
            'branch_id' => $branch->branch_id,
            'status' => $status,
            'type' => $type,
        ]);
    }

    protected function payment(Plan $plan, float $price, array $overrides = []): SubscriptionPayment
    {
        return $this->subscription->payments()->create(array_merge([
            'user_id' => $this->owner->user_id ?? null,
            'plan_id' => $plan->plan_id,
            'xendit_invoice_id' => 'chg_' . Str::random(8),
            'payment_reference_id' => (string) Str::uuid(),
            'masked_card_number' => '400000XXXXXX0002',
            'price' => $price,
            'status' => SubscriptionPayment::STATUS_PAID,
            'type' => SubscriptionPayment::TYPE_SUBSCRIPTION,
            'payment_method' => 'CREDIT-CARD',
        ], $overrides));
    }

    protected function assign(Employee $employee, Branch $branch, string $role, array $permissions): void
    {
        EmployeeBranch::create([
            'branch_id' => $branch->branch_id,
            'employee_id' => $employee->employee_id,
            'role_name' => $role,
            'status' => 'active',
        ]);

        foreach (Module::all() as $module) {
            EmployeePermission::create([
                'employee_id' => $employee->employee_id,
                'branch_id' => $branch->branch_id,
                'module_id' => $module->module_id,
            ] + EmployeePermission::grantColumns($permissions[$module->module_name] ?? []));
        }
    }

    protected function branchManager(): User
    {
        $user = $this->makeUser('Leo', 'Cruz');
        $employee = Employee::create([
            'user_id' => $user->user_id,
            'employee_code' => 'EMP-0002',
            'first_name' => 'Leo',
            'last_name' => 'Cruz',
        ]);

        $this->assign($employee, $this->branch, RoleEnum::BranchManager->value, RoleEnum::BranchManager->permissions());

        return $user;
    }

    protected function branchFields(array $overrides = []): array
    {
        return array_merge([
            'branch_name' => 'AMUMA Tagum',
            'branch_email' => 'tagum@amuma.com',
            'branch_contact_number' => '09171234567',
            'branch_description' => 'Second branch in Tagum City.',
            'branch_street' => 'Rizal Street',
            'branch_city' => 'Tagum',
            'branch_province' => 'Davao del Norte',
            'branch_country' => 'Philippines',
            'branch_latitude' => 7.4478,
            'branch_longitude' => 125.8078,
            'branch_settings' => ['currency' => 'PHP', 'time_zone' => 'Asia/Manila', 'tin' => '004-512-873-000'],
        ], $overrides);
    }

    protected function card(array $overrides = []): array
    {
        return array_merge([
            'payment_method' => 'CREDIT-CARD',
            'token_id' => 'tok_test',
            'authentication_id' => 'auth_test',
        ], $overrides);
    }

    protected function request(array $data, ?User $user = null): Request
    {
        $user ??= $this->owner;
        $this->actingAs($user);

        return Request::create('/api/subscriptions', 'POST', $data + ['branch_uuid' => $this->branch->uuid])
            ->setUserResolver(fn() => $user);
    }

    protected function data($response): array
    {
        return $response->getData(true);
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

    protected function date(mixed $value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
