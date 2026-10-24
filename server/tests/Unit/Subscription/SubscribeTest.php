<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\PlanController;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Employee;
use App\Models\EmployeePermission;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Service\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SubscribeTest extends TestCase
{
    use UsesTempDatabase;
    use SubscriptionFixtures;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSubscription();
        $this->user = $this->makeUser('Ana', 'Santos', true);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge($this->card(), [
            'plan_code' => 'A',
            'plan_type' => Plan::TYPE_SME,
            'agency_name' => 'Santos Care',
            'agency_email' => 'santos@care.ph',
            'agency_street' => 'Roxas Avenue',
            'agency_city' => 'Davao City',
            'agency_province' => 'Davao del Sur',
            'agency_country' => 'Philippines',
            'agency_latitude' => 7.0731,
            'agency_longitude' => 125.6128,
        ], $this->branchFields(['branch_name' => 'AMUMA Davao City', 'branch_email' => 'davao@amuma.com']), $overrides);
    }

    private function subscribe(array $overrides = [])
    {
        $this->actingAs($this->user);

        return $this->app->make(SubscriptionService::class)->startTrial($this->payload($overrides), $this->user);
    }

    private function payUpFront(array $overrides = [])
    {
        $this->actingAs($this->user);

        return $this->app->make(SubscriptionService::class)->makeSubscription($this->payload($overrides), $this->user);
    }

    private function validateDetails(array $overrides)
    {
        $details = array_merge($this->payload(), $overrides);
        unset($details['agency_name'], $details['agency_email']);

        return $this->actingAs($this->user, 'sanctum')->postJson('/api/subscriptions-validate', $details);
    }

    public function test_plans_are_listed_with_their_prices(): void
    {
        $plans = $this->app->make(PlanController::class)->index(Request::create('/api/plans'));

        $this->assertCount(6, $plans);
        $this->assertEquals(12000, $plans->firstWhere('plan_code', 'A')->price);
        $this->assertSame(10, $plans->where('type', Plan::TYPE_ENTERPRISE)->first()->branch_limit);
    }

    public function test_plan_updated_successfully(): void
    {
        $plan = $this->plan('A');

        $response = $this->app->make(PlanController::class)->update(Request::create('/api/plans', 'PUT', ['price' => 13500]), $plan);

        $this->assertSame('Plan updated successfully.', $this->data($response)['message']);
        $this->assertEquals(13500, $plan->fresh()->price);
        $this->assertSame($plan->description, $plan->fresh()->description);
    }

    public function test_almost_there_we_ll_notify_you_once_your_branch_is_verified(): void
    {
        $response = $this->subscribe();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame("Almost there! We'll notify you once your branch is verified.", $this->data($response)['message']);

        $subscription = Subscription::firstOrFail();
        $this->assertSame(Subscription::STATUS_PENDING, $subscription->status);
        $this->assertSame(Branch::STATUS_PENDING, Branch::firstOrFail()->status);
        $this->assertSame(Agency::STATUS_PENDING, Agency::firstOrFail()->status);
        $this->assertSame(BranchSubscription::STATUS_PENDING, BranchSubscription::firstOrFail()->status);
    }

    public function test_first_time_agency_gets_a_two_week_free_trial(): void
    {
        $this->subscribe();

        $subscription = Subscription::firstOrFail();

        $this->assertSame(Subscription::MODE_TEST, $subscription->mode);
        $this->assertSame(now()->addWeeks(2)->toDateString(), $this->date($subscription->end_date));
        $this->assertSame($subscription->plan_id, $subscription->pending_plan_id);
    }

    public function test_the_free_trial_request_takes_no_payment(): void
    {
        $this->subscribe();

        $subscription = Subscription::firstOrFail();

        $this->assertSame(0, SubscriptionPayment::count());
        $this->assertTrue($subscription->awaitingTrialPayment());
        $this->assertFalse($subscription->canPayTrial());
        Http::assertNotSent(fn($request) => str_contains($request->url(), 'xendit'));
    }

    public function test_paying_up_front_records_the_payment(): void
    {
        $this->payUpFront();

        $payment = SubscriptionPayment::firstOrFail();

        $this->assertSame(SubscriptionPayment::STATUS_PAID, $payment->status);
        $this->assertSame(SubscriptionPayment::TYPE_SUBSCRIPTION, $payment->type);
        $this->assertEquals(12000, $payment->price);
        $this->assertSame('400000XXXXXX0002', $payment->masked_card_number);
    }

    public function test_user_becomes_the_agency_owner_with_every_permission(): void
    {
        $this->subscribe();

        $employee = Employee::where('user_id', $this->user->user_id)->firstOrFail();

        $this->assertSame('agency_owner', $employee->employeeBranch()->firstOrFail()->role_name);
        $this->assertSame(\App\Models\Module::count(), EmployeePermission::where('employee_id', $employee->employee_id)->count());
    }

    public function test_new_subscription_request_from_amuma_davao_city_is_awaiting_your_review(): void
    {
        $this->subscribe();

        $this->assertSame(
            'New subscription request from AMUMA Davao City is awaiting your review.',
            Notification::where('to_user_id', $this->admin->user_id)->firstOrFail()->message
        );
    }

    public function test_this_agency_already_has_a_subscription_add_a_branch_to_it_instead(): void
    {
        $this->subscribe();

        $this->assertFailsWith(
            'This agency already has a subscription. Add a branch to it instead.',
            fn() => $this->subscribe(['agency_id' => Agency::firstOrFail()->agency_id]),
            422
        );

        $this->assertSame(1, Subscription::count());
    }

    public function test_the_branch_name_field_is_required(): void
    {
        $this->validateDetails(['branch_name' => ''])
            ->assertJsonValidationErrors(['branch_name' => 'The branch name field is required.']);
    }

    public function test_the_branch_email_has_already_been_taken(): void
    {
        $this->subscribe();

        $this->validateDetails(['branch_email' => 'davao@amuma.com'])
            ->assertJsonValidationErrors(['branch_email' => 'The branch email has already been taken.']);
    }

    public function test_the_tin_must_be_a_valid_tin(): void
    {
        foreach (['12345', '004-512-873', '004-512-873-0000', '004512873000'] as $tin) {
            $this->validateDetails(['branch_settings' => ['tin' => $tin]])->assertJsonValidationErrors([
                'branch_settings.tin' => 'The TIN must be a valid TIN, e.g. 004-512-873-000 or 004-512-873-00000.',
            ]);
        }
    }

    public function test_tin_ending_in_3_or_5_digits_is_valid(): void
    {
        foreach (['004-512-873-000', '004-512-873-00000'] as $tin) {
            $this->validateDetails(['branch_settings' => ['tin' => $tin]])
                ->assertJsonMissingValidationErrors(['branch_settings.tin']);
        }
    }

    public function test_invalid_plan_type(): void
    {
        $this->assertFailsWith('Invalid plan type.', fn() => $this->subscribe(['plan_type' => 'monthly']), 422);

        Http::assertNothingSent();
    }

    public function test_plan_not_found(): void
    {
        $this->assertFailsWith('Plan not found.', fn() => $this->subscribe(['plan_code' => 'Z']), 404);

        Http::assertNothingSent();
    }

    public function test_payment_failed(): void
    {
        $this->declineCard = true;

        $response = $this->payUpFront();

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame('Payment failed.', $this->data($response)['message']);
        $this->assertSame(0, Subscription::count());
        $this->assertSame(0, Agency::count());
    }

    public function test_card_is_charged_the_plan_price(): void
    {
        $this->payUpFront(['plan_code' => 'C', 'plan_type' => Plan::TYPE_ENTERPRISE]);

        Http::assertSent(fn($request) => str_ends_with($request->url(), '/credit_card_charges') && (float) $request['amount'] === 100000.0);
    }
}
