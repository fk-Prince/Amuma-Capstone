<?php

namespace Tests\Feature;

use App\Enums\ModuleEnum;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\EmployeePermission;
use App\Models\Module;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ModuleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SubscriptionRenewalTest extends TestCase
{
    use UsesTempDatabase;

    private User $owner;
    private int $agencyId;
    private int $branchId;
    private string $branchUuid;
    private int $homecarePlanId;
    private int $facilityPlanId;
    private int $bothPlanId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ModuleSeeder::class);

        $this->owner = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'owner@amuma.com',
            'password' => 'password',
        ]);

        $this->agencyId = DB::table('agencies')->insertGetId([
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
            'agency_id' => $this->agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->homecarePlanId = $this->makePlan('Homecare', 'A', 1000, 10000);
        $this->facilityPlanId = $this->makePlan('Facility', 'B', 2000, 20000);
        $this->bothPlanId = $this->makePlan('Complete', 'C', 3000, 30000);

        $this->giveAccess(renew: true, createBranches: true);

        Sanctum::actingAs($this->owner);

        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), 'nominatim') || str_contains($request->url(), 'openstreetmap')) {
                return Http::response([['lat' => '7.0731', 'lon' => '125.6128']]);
            }

            if ($request->method() === 'POST') {
                return Http::response(['id' => 'inv_1', 'invoice_url' => 'https://checkout.xendit.co/web/inv_1']);
            }

            return Http::response([[
                'id' => 'inv_1',
                'status' => 'PAID',
                'metadata' => ['payment_type' => 'RENEWAL'],
            ]]);
        });
    }

    private function makePlan(string $name, string $code, float $monthly, float $yearly): int
    {
        return DB::table('plans')->insertGetId([
            'name' => $name,
            'description' => "{$name} plan",
            'plan_code' => $code,
            'monthly_price' => $monthly,
            'yearly_price' => $yearly,
        ]);
    }

    private function giveAccess(bool $renew, bool $createBranches): void
    {
        $employee = $this->owner->employee()->create([
            'first_name' => 'Olivia',
            'last_name' => 'Owner',
            'phone_number' => '9171234567',
            'birth_date' => '1985-01-01',
            'status' => 'active',
        ]);

        $employee->employeeBranch()->create([
            'branch_id' => $this->branchId,
            'role_name' => 'branch_manager',
        ]);

        $grants = [
            ModuleEnum::BranchSettings->value => $renew ? ['can_read', 'can_update', 'can_renew'] : ['can_read', 'can_update'],
            ModuleEnum::ManageBranches->value => $createBranches ? ['can_read', 'can_create'] : ['can_read'],
        ];

        foreach ($grants as $moduleName => $actions) {
            $module = Module::where('module_name', $moduleName)->first();

            $employee->permissions()->create([
                'module_id' => $module->module_id,
                'branch_id' => $this->branchId,
                'employee_id' => $employee->employee_id,
            ] + EmployeePermission::grantColumns($actions));
        }
    }

    private function makeSubscription(array $overrides = [], ?int $branchId = null): Subscription
    {
        $subscription = Subscription::create(array_merge([
            'plan_id' => $this->homecarePlanId,
            'agency_id' => $this->agencyId,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_interval' => 'MONTHLY',
            'start_date' => now()->subDays(24)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
        ], $overrides));

        $subscription->payments()->create([
            'xendit_invoice_id' => 'inv_seed',
            'payment_reference_id' => (string) Str::uuid(),
            'price' => 1000,
            'status' => 'paid',
            'plan_id' => $subscription->plan_id,
            'type' => 'subscription',
            'billing_interval' => $subscription->billing_interval,
            'payment_method' => 'GCASH',
        ]);

        BranchSubscription::create([
            'subscription_id' => $subscription->subscription_id,
            'branch_id' => $branchId ?? $this->branchId,
            'status' => BranchSubscription::STATUS_APPROVED,
        ]);

        return $subscription;
    }

    private function renew(array $overrides = [])
    {
        return $this->postJson('/api/subscriptions-renew', array_merge([
            'branch_uuid' => $this->branchUuid,
            'payment_method' => 'GCASH',
        ], $overrides));
    }

    private function startedInvoice(): array
    {
        $request = Http::recorded(fn(HttpRequest $r) => $r->method() === 'POST')->first()[0];
        $reference = $request['external_id'];

        return [
            'reference' => $reference,
            'amount' => $request['amount'],
            'pending' => Cache::get("xendit_payment_{$reference}"),
        ];
    }

    private function settle(string $reference)
    {
        return $this->getJson("/api/auth/payments/status/{$reference}");
    }

    // ---- renewal window

    public function test_renewal_is_refused_until_seven_days_are_left(): void
    {
        $this->makeSubscription(['end_date' => now()->addDays(8)->toDateString()]);

        $response = $this->renew();

        $this->assertFalse($response->isSuccessful());
        $this->assertStringContainsString('Renewal opens on', $response->json('message'));
        Http::assertNothingSent();
    }

    public function test_renewal_opens_exactly_seven_days_before_the_end(): void
    {
        $this->makeSubscription(['end_date' => now()->addDays(7)->toDateString()]);

        $this->renew()->assertOk()->assertJsonPath('invoice_url', 'https://checkout.xendit.co/web/inv_1');

        $this->assertNotNull($this->startedInvoice()['pending']);
    }

    public function test_an_expired_subscription_can_still_be_renewed(): void
    {
        $this->makeSubscription([
            'status' => Subscription::STATUS_EXPIRED,
            'end_date' => now()->subDays(3)->toDateString(),
        ]);

        $this->renew()->assertOk();

        $pending = $this->startedInvoice()['pending'];

        $this->assertSame(
            now()->addMonth()->toDateString(),
            Carbon::parse($pending['endDate'])->toDateString()
        );
    }

    public function test_renewal_extends_from_the_current_end_date(): void
    {
        $end = now()->addDays(5)->startOfDay();
        $this->makeSubscription(['end_date' => $end->toDateString()]);

        $this->renew()->assertOk();

        $pending = $this->startedInvoice()['pending'];

        $this->assertSame($end->copy()->addMonth()->toDateString(), Carbon::parse($pending['endDate'])->toDateString());
        $this->assertFalse($pending['is_upgrade']);
        $this->assertSame('renewal', $pending['type']);
    }

    public function test_renewal_charges_the_price_of_the_chosen_billing_interval(): void
    {
        $this->makeSubscription();

        $this->renew()->assertOk();
        $this->assertEquals(1000, $this->startedInvoice()['amount']);
    }

    public function test_a_monthly_subscription_can_renew_yearly(): void
    {
        $end = now()->addDays(5)->startOfDay();
        $this->makeSubscription(['end_date' => $end->toDateString()]);

        $this->renew(['billing_interval' => 'yearly'])->assertOk();

        $invoice = $this->startedInvoice();

        $this->assertEquals(10000, $invoice['amount']);
        $this->assertSame('YEARLY', $invoice['pending']['billing_interval']);
        $this->assertSame($end->copy()->addYear()->toDateString(), Carbon::parse($invoice['pending']['endDate'])->toDateString());
    }

    public function test_an_unknown_billing_interval_is_refused(): void
    {
        $this->makeSubscription();

        $response = $this->renew(['billing_interval' => 'weekly']);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Invalid billing interval.', $response->json('message'));
    }

    public function test_a_branch_without_a_subscription_cannot_renew(): void
    {
        $response = $this->renew();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('This branch has no subscription to renew.', $response->json('message'));
    }

    public function test_renewing_needs_the_renew_permission(): void
    {
        DB::table('employee_permissions')->update(['can_renew' => false]);
        $this->makeSubscription();

        $response = $this->renew();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Insufficient permissions', $response->json('message'));
        Http::assertNothingSent();
    }

    public function test_an_unsupported_payment_method_is_refused(): void
    {
        $this->makeSubscription();

        $this->assertFalse($this->renew(['payment_method' => 'CASH'])->isSuccessful());
        Http::assertNothingSent();
    }


    public function test_paying_a_renewal_extends_the_subscription_and_records_the_payment(): void
    {
        $end = now()->addDays(5)->startOfDay();
        $subscription = $this->makeSubscription(['end_date' => $end->toDateString()]);

        $this->renew()->assertOk();

        $this->settle($this->startedInvoice()['reference'])
            ->assertOk()
            ->assertJsonPath('status', 'submitted');

        $subscription->refresh();

        $this->assertSame($end->copy()->addMonth()->toDateString(), $subscription->end_date->toDateString());
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame(2, $subscription->payments()->count());
        $this->assertSame('renewal', $subscription->payments()->latest('subscription_payment_id')->first()->type);
    }

    public function test_a_payment_reference_only_renews_once(): void
    {
        $subscription = $this->makeSubscription();

        $this->renew()->assertOk();
        $reference = $this->startedInvoice()['reference'];

        $this->settle($reference)->assertOk();
        $this->settle($reference)->assertOk();

        $this->assertSame(2, $subscription->payments()->count());
    }

    public function test_a_yearly_renewal_switches_the_subscription_billing_interval(): void
    {
        $subscription = $this->makeSubscription();

        $this->renew(['billing_interval' => 'YEARLY'])->assertOk();
        $this->settle($this->startedInvoice()['reference'])->assertOk();

        $this->assertSame('YEARLY', $subscription->fresh()->billing_interval);
    }


    public function test_an_upgrade_is_allowed_before_the_renewal_window(): void
    {
        $this->makeSubscription(['end_date' => now()->addDays(20)->toDateString()]);

        $this->renew(['plan_code' => 'C'])->assertOk();

        $pending = $this->startedInvoice()['pending'];

        $this->assertTrue($pending['is_upgrade']);
        $this->assertEquals(3000, $this->startedInvoice()['amount']);
    }

    public function test_an_upgrade_queues_until_the_current_period_ends(): void
    {
        $end = now()->addDays(20)->startOfDay();
        $subscription = $this->makeSubscription(['end_date' => $end->toDateString()]);

        $this->renew(['plan_code' => 'C'])->assertOk();
        $this->settle($this->startedInvoice()['reference'])->assertOk();

        $subscription->refresh();

        $this->assertSame($this->homecarePlanId, $subscription->plan_id);
        $this->assertSame($this->bothPlanId, $subscription->pending_plan_id);
        $this->assertSame($end->toDateString(), $subscription->pending_plan_starts_at->toDateString());
        $this->assertSame($end->toDateString(), $subscription->end_date->toDateString());
    }

    public function test_an_upgrade_can_start_right_away(): void
    {
        $subscription = $this->makeSubscription(['end_date' => now()->addDays(20)->toDateString()]);

        $this->renew(['plan_code' => 'C', 'upgrade_timing' => 'now'])->assertOk();
        $this->settle($this->startedInvoice()['reference'])->assertOk();

        $subscription->refresh();

        $this->assertSame($this->bothPlanId, $subscription->plan_id);
        $this->assertNull($subscription->pending_plan_id);
        $this->assertSame(now()->toDateString(), $subscription->start_date->toDateString());
        $this->assertSame(now()->addMonth()->toDateString(), $subscription->end_date->toDateString());
    }

    public function test_a_queued_upgrade_blocks_another_renewal(): void
    {
        $this->makeSubscription([
            'end_date' => now()->addDays(3)->toDateString(),
            'pending_plan_id' => $this->bothPlanId,
            'pending_plan_starts_at' => now()->addDays(3)->toDateString(),
            'pending_billing_interval' => 'MONTHLY',
        ]);

        $response = $this->renew();

        $this->assertFalse($response->isSuccessful());
        $this->assertStringContainsString('already paid for', $response->json('message'));
        Http::assertNothingSent();
    }

    public function test_a_queued_upgrade_that_is_due_takes_over_before_renewing(): void
    {
        $subscription = $this->makeSubscription([
            'end_date' => now()->subDays(25)->toDateString(),
            'pending_plan_id' => $this->bothPlanId,
            'pending_plan_starts_at' => now()->subDays(25)->toDateString(),
            'pending_billing_interval' => 'MONTHLY',
        ]);

        $this->renew()->assertOk();

        $subscription->refresh();

        $this->assertSame($this->bothPlanId, $subscription->plan_id);
        $this->assertNull($subscription->pending_plan_id);
        $this->assertEquals(3000, $this->startedInvoice()['amount']);
    }

    public function test_applying_a_queued_upgrade_activates_it_now(): void
    {
        $subscription = $this->makeSubscription([
            'end_date' => now()->addDays(10)->toDateString(),
            'pending_plan_id' => $this->bothPlanId,
            'pending_plan_starts_at' => now()->addDays(10)->toDateString(),
            'pending_billing_interval' => 'MONTHLY',
        ]);

        $this->postJson('/api/subscriptions-apply-upgrade', ['branch_uuid' => $this->branchUuid])
            ->assertOk()
            ->assertJsonPath('forfeited_days', 10)
            ->assertJsonPath('subscription.plan.plan_code', 'C')
            ->assertJsonPath('subscription.pending_plan', null);

        $subscription->refresh();

        $this->assertSame($this->bothPlanId, $subscription->plan_id);
        $this->assertNull($subscription->pending_plan_id);
        $this->assertSame(now()->addMonth()->toDateString(), $subscription->end_date->toDateString());
    }

    public function test_there_is_nothing_to_apply_without_a_queued_upgrade(): void
    {
        $this->makeSubscription();

        $response = $this->postJson('/api/subscriptions-apply-upgrade', ['branch_uuid' => $this->branchUuid]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('There is no queued upgrade to apply.', $response->json('message'));
    }


    private function branchPayload(string $suffix = '1', array $overrides = []): array
    {
        return array_merge([
            'branch_uuid' => $this->branchUuid,
            'plan_code' => 'C',
            'billing_interval' => 'MONTHLY',
            'branch_name' => "AMUMA Branch {$suffix}",
            'branch_street' => 'Rizal Street',
            'branch_city' => 'Davao City',
            'branch_province' => 'Davao del Sur',
            'branch_country' => 'Philippines',
            'branch_description' => 'A new branch',
            'branch_email' => "branch{$suffix}@amuma.com",
            'branch_contact_number' => '9171234567',
            'branch_document' => UploadedFile::fake()->create('permit.pdf', 10, 'application/pdf'),
            'branch_settings' => [
                'currency' => 'PHP',
                'opening' => '08:00',
                'closing' => '17:00',
                'time_zone' => 'Asia/Manila',
                'reserved_walkin_slots' => 2,
                'enable_booking_pre_admission' => true,
                'enable_booking_complete_admission' => true,
                'minimum_adl_hours' => 4,
                'tin' => '004-512-873-000',
                'is_open' => true,
            ],
        ], $overrides);
    }

    private function addBranch(string $suffix = '1', array $overrides = [])
    {
        return $this->post('/api/subscriptions-branch', $this->branchPayload($suffix, $overrides), ['Accept' => 'application/json']);
    }

    private function fillSubscription(Subscription $subscription, int $count, string $status = BranchSubscription::STATUS_APPROVED): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $id = DB::table('branches')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'agency_id' => $this->agencyId,
                'name' => "Filler {$i} {$status}",
                'email' => "filler{$i}{$status}@amuma.com",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            BranchSubscription::create([
                'subscription_id' => $subscription->subscription_id,
                'branch_id' => $id,
                'status' => $status,
            ]);
        }
    }

    private function facilitySubscription(): Subscription
    {
        return $this->makeSubscription([
            'plan_id' => $this->bothPlanId,
            'end_date' => now()->addDays(20)->toDateString(),
        ]);
    }

    public function test_a_branch_can_be_added_while_the_subscription_has_room(): void
    {
        $subscription = $this->facilitySubscription();

        $this->addBranch()
            ->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('branch.name', 'AMUMA Branch 1')
            ->assertJsonPath('branch.agency.agency_id', $this->agencyId);

        $link = BranchSubscription::where('subscription_id', $subscription->subscription_id)
            ->where('branch_id', Branch::where('name', 'AMUMA Branch 1')->value('branch_id'))
            ->first();

        $this->assertNotNull($link);
        $this->assertSame(BranchSubscription::STATUS_PENDING, $link->status);
    }

    public function test_the_creator_becomes_the_owner_of_the_new_branch(): void
    {
        $this->facilitySubscription();

        $this->addBranch()->assertStatus(201);

        $branchId = Branch::where('name', 'AMUMA Branch 1')->value('branch_id');

        $this->assertDatabaseHas('employee_branches', [
            'branch_id' => $branchId,
            'role_name' => 'branch_manager',
        ]);
    }

    public function test_the_fifth_branch_fits_and_the_sixth_is_refused(): void
    {
        $subscription = $this->facilitySubscription();

        $this->fillSubscription($subscription, Subscription::BRANCH_LIMIT - 2);

        $this->addBranch('5')->assertStatus(201);

        $response = $this->addBranch('6');

        $this->assertFalse($response->isSuccessful());
        $this->assertStringContainsString('no free branch slots', $response->json('message'));
        $this->assertSame(0, Branch::where('name', 'AMUMA Branch 6')->count());
        $this->assertSame(
            Subscription::BRANCH_LIMIT,
            BranchSubscription::where('subscription_id', $subscription->subscription_id)->count()
        );
    }

    public function test_a_full_subscription_refuses_a_new_branch(): void
    {
        $subscription = $this->facilitySubscription();

        $this->fillSubscription($subscription, Subscription::BRANCH_LIMIT - 1);

        $response = $this->addBranch();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(0, Branch::where('name', 'AMUMA Branch 1')->count());
    }

    public function test_rejected_branches_do_not_count_against_the_limit(): void
    {
        $subscription = $this->facilitySubscription();

        $this->fillSubscription($subscription, 3, BranchSubscription::STATUS_REJECTED);
        $this->fillSubscription($subscription, Subscription::BRANCH_LIMIT - 2, BranchSubscription::STATUS_APPROVED);

        $this->addBranch()->assertStatus(201);
    }

    public function test_pending_branches_count_against_the_limit(): void
    {
        $subscription = $this->facilitySubscription();

        $this->fillSubscription($subscription, Subscription::BRANCH_LIMIT - 1, BranchSubscription::STATUS_PENDING);

        $this->assertFalse($this->addBranch()->isSuccessful());
    }

    public function test_another_subscription_with_room_can_take_the_branch(): void
    {
        $full = $this->facilitySubscription();
        $this->fillSubscription($full, Subscription::BRANCH_LIMIT - 1);

        $second = $this->makeSubscription([
            'plan_id' => $this->bothPlanId,
            'end_date' => now()->addDays(25)->toDateString(),
        ], $this->branchId);

        $this->addBranch('7', ['subscription_uuid' => $second->uuid])->assertStatus(201);

        $this->assertSame(
            2,
            BranchSubscription::where('subscription_id', $second->subscription_id)->count()
        );
    }

    public function test_a_subscription_that_was_never_paid_has_no_room(): void
    {
        $subscription = $this->facilitySubscription();
        $subscription->payments()->update(['status' => 'refunded']);

        $this->assertFalse($this->addBranch()->isSuccessful());
    }

    public function test_adding_a_branch_needs_the_manage_branches_create_permission(): void
    {
        DB::table('employee_permissions')->update(['can_create' => false]);
        $this->facilitySubscription();

        $response = $this->addBranch();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Insufficient permissions', $response->json('message'));
        $this->assertSame(0, Branch::where('name', 'AMUMA Branch 1')->count());
    }

    public function test_adding_a_branch_needs_an_active_facility_subscription(): void
    {
        $this->makeSubscription(['plan_id' => $this->homecarePlanId]);

        $response = $this->addBranch();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('No active facility subscription.', $response->json('message'));
    }

    public function test_a_new_branch_cannot_reuse_a_taken_email_or_name(): void
    {
        $this->facilitySubscription();

        $this->addBranch('1', ['branch_email' => 'davao@amuma.com', 'branch_name' => 'AMUMA Davao City'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch_email', 'branch_name']);
    }
}
