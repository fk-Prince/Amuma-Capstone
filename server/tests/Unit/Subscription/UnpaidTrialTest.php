<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\SubscriptionController;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Service\SubscriptionReminderService;
use App\Service\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class UnpaidTrialTest extends TestCase
{
    use UsesTempDatabase;
    use SubscriptionFixtures;

    private SubscriptionController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSubscription();
        $this->controller = $this->app->make(SubscriptionController::class);
    }

    private function unpaidTrial(int $daysLeft = 11, string $code = 'A'): Subscription
    {
        $subscription = $this->subscribed($code, Plan::TYPE_SME, [
            'mode' => Subscription::MODE_TEST,
            'start_date' => now()->subDays(14 - $daysLeft)->toDateString(),
            'end_date' => now()->addDays($daysLeft)->toDateString(),
            'pending_plan_id' => $this->plan($code)->plan_id,
            'pending_plan_starts_at' => now()->addDays($daysLeft)->toDateString(),
        ]);

        $subscription->payments()->delete();

        return $subscription->fresh();
    }

    private function renew(array $extra = [])
    {
        return $this->controller->renew($this->request($this->card($extra)));
    }

    private function awaitingReview(int $daysSinceSubmitted = 0): Subscription
    {
        $submitted = now()->subDays($daysSinceSubmitted);

        $subscription = $this->unpaidTrial();
        $subscription->update([
            'status' => Subscription::STATUS_PENDING,
            'start_date' => $submitted->toDateString(),
            'end_date' => $submitted->copy()->addWeeks(2)->toDateString(),
            'pending_plan_starts_at' => $submitted->copy()->addWeeks(2)->toDateString(),
        ]);

        DB::table('agencies')->update(['status' => Agency::STATUS_PENDING]);
        DB::table('branches')->update(['status' => Branch::STATUS_PENDING]);
        BranchSubscription::query()->update(['status' => BranchSubscription::STATUS_PENDING]);

        return $subscription->fresh();
    }

    private function approve()
    {
        return $this->controller->action($this->request(
            ['action' => 'approve', 'branch_subscription_uuid' => BranchSubscription::firstOrFail()->uuid],
            $this->admin
        ));
    }

    private function reject()
    {
        return $this->controller->action($this->request(
            [
                'action' => 'reject',
                'branch_subscription_uuid' => BranchSubscription::firstOrFail()->uuid,
                'rejection_reason' => 'Business permit is expired.',
            ],
            $this->admin
        ));
    }

    public function test_rejecting_a_free_trial_request_has_nothing_to_refund(): void
    {
        $subscription = $this->awaitingReview();

        $this->reject();

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::STATUS_REJECTED, $fresh->status);
        $this->assertSame(BranchSubscription::STATUS_REJECTED, BranchSubscription::firstOrFail()->status);
        $this->assertSame(Branch::STATUS_REJECTED, $this->branch->fresh()->status);
        $this->assertTrue($fresh->isRejectedFreeTrial());
        Http::assertNotSent(fn($request) => str_contains($request->url(), 'refunds'));
    }

    public function test_a_rejected_free_trial_is_resubmitted_without_paying(): void
    {
        $subscription = $this->awaitingReview(5);
        $this->reject();
        $this->actingAs($this->owner);

        $this->app->make(SubscriptionService::class)->resubmitBranch([
            'target_branch_uuid' => $this->branch->uuid,
            'agency_id' => $this->agency->agency_id,
            'branch_name' => 'AMUMA Davao City',
            'branch_email' => 'davao@amuma.com',
            'branch_contact_number' => '09171234567',
            'branch_description' => 'Renewed business permit attached.',
            'branch_street' => 'Roxas Avenue',
            'branch_city' => 'Davao City',
            'branch_province' => 'Davao del Sur',
            'branch_country' => 'Philippines',
            'branch_latitude' => 7.0731,
            'branch_longitude' => 125.6128,
        ], $this->owner);

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::STATUS_PENDING, $fresh->status);
        $this->assertSame(Subscription::MODE_TEST, $fresh->mode);
        $this->assertSame(BranchSubscription::STATUS_PENDING, BranchSubscription::firstOrFail()->status);
        $this->assertSame(0, SubscriptionPayment::count());

        $this->approve();

        $this->assertSame(now()->addWeeks(2)->toDateString(), $this->date($subscription->fresh()->end_date));
        $this->assertTrue($subscription->fresh()->canPayTrial());
    }

    public function test_a_trial_waiting_for_review_cannot_be_paid_yet(): void
    {
        $this->awaitingReview();

        $this->assertFailsWith(
            'You can pay for your plan once an admin has verified your branch.',
            fn() => $this->renew(),
            422
        );
        $this->assertSame(0, SubscriptionPayment::count());
    }

    public function test_a_slow_review_never_locks_the_branch_before_approval(): void
    {
        $subscription = $this->awaitingReview(20);

        $this->assertFalse($subscription->trialHasEnded());
        $this->assertFalse($subscription->isLockedForPayment());

        $this->artisan('subscriptions:apply-pending-plans')->assertSuccessful();
        $this->artisan('subscriptions:mark-expired')->assertSuccessful();

        $this->assertSame(Subscription::STATUS_PENDING, $subscription->fresh()->status);
    }

    public function test_approving_the_trial_starts_its_two_weeks_from_today(): void
    {
        $subscription = $this->awaitingReview(20);

        $this->approve();

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $fresh->status);
        $this->assertSame(Subscription::MODE_TEST, $fresh->mode);
        $this->assertSame(now()->addWeeks(2)->toDateString(), $this->date($fresh->end_date));
        $this->assertTrue($fresh->canPayTrial());
        $this->assertFalse($fresh->isLockedForPayment());
    }

    public function test_a_first_time_agency_starts_its_free_trial_without_paying(): void
    {
        $user = $this->makeUser('Ana', 'Santos', true);
        $this->actingAs($user);

        $response = $this->app->make(SubscriptionService::class)->startTrial([
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
        ] + $this->branchFields(['branch_name' => 'AMUMA Davao City', 'branch_email' => 'davao@amuma.com']), $user);

        $this->assertSame(201, $response->getStatusCode());

        $subscription = Subscription::firstOrFail();
        $this->assertSame(Subscription::MODE_TEST, $subscription->mode);
        $this->assertSame(Subscription::STATUS_PENDING, $subscription->status);
        $this->assertSame(0, SubscriptionPayment::count());
        $this->assertTrue($subscription->awaitingTrialPayment());
        $this->assertSame(Branch::STATUS_PENDING, Branch::firstOrFail()->status);
        $this->assertTrue(Employee::where('user_id', $user->user_id)->exists());
        Http::assertNotSent(fn($request) => str_contains($request->url(), 'xendit'));
    }

    public function test_ending_an_unpaid_trial_early_asks_for_payment(): void
    {
        $this->unpaidTrial();

        $this->assertFailsWith(
            'Pay for your plan to start your paid year.',
            fn() => $this->controller->endTest($this->request([])),
            422
        );
    }

    public function test_paying_and_starting_now_goes_live_today(): void
    {
        $subscription = $this->unpaidTrial();

        $response = $this->renew(['start_now' => true]);

        $this->assertSame('Payment received. Your paid year starts today.', $this->data($response)['message']);
        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::MODE_LIVE, $fresh->mode);
        $this->assertSame(now()->addYear()->toDateString(), $this->date($fresh->end_date));
        $this->assertNull($fresh->pending_plan_id);

        $payment = SubscriptionPayment::firstOrFail();
        $this->assertSame(SubscriptionPayment::TYPE_SUBSCRIPTION, $payment->type);
        $this->assertEquals(12000, $payment->price);
    }

    public function test_paying_ahead_keeps_the_trial_until_it_ends(): void
    {
        $subscription = $this->unpaidTrial(5);

        $this->renew();

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::MODE_TEST, $fresh->mode);
        $this->assertTrue($fresh->isPaid());
        $this->assertFalse($fresh->awaitingTrialPayment());

        $this->travel(5)->days();
        $this->artisan('subscriptions:apply-pending-plans')->assertSuccessful();

        $live = $subscription->fresh();
        $this->assertSame(Subscription::MODE_LIVE, $live->mode);
        $this->assertSame(now()->addYear()->toDateString(), $this->date($live->end_date));
    }

    public function test_paying_ahead_can_pick_another_plan_of_the_same_type(): void
    {
        $subscription = $this->unpaidTrial();

        $this->renew(['plan_code' => 'C']);

        $fresh = $subscription->fresh();
        $this->assertSame($this->plan('C')->plan_id, $fresh->plan_id);
        $this->assertSame($this->plan('C')->plan_id, $fresh->pending_plan_id);
        $this->assertEquals($this->plan('C')->price, SubscriptionPayment::firstOrFail()->price);
    }

    public function test_the_nightly_job_does_not_start_an_unpaid_trial(): void
    {
        $subscription = $this->unpaidTrial(2);

        $this->travel(2)->days();
        $this->artisan('subscriptions:apply-pending-plans')->assertSuccessful();

        $this->assertSame(Subscription::MODE_TEST, $subscription->fresh()->mode);
        $this->assertTrue($subscription->fresh()->isLockedForPayment());

        $this->travel(1)->days();
        $this->artisan('subscriptions:mark-expired')->assertSuccessful();

        $this->assertSame(Subscription::STATUS_EXPIRED, $subscription->fresh()->status);
        $this->assertTrue($subscription->fresh()->isLockedForPayment());
    }

    public function test_paying_after_the_trial_ended_starts_the_year_today(): void
    {
        $subscription = $this->unpaidTrial(2);
        $this->travel(3)->days();
        $subscription->update(['status' => Subscription::STATUS_EXPIRED]);

        $this->renew();

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::MODE_LIVE, $fresh->mode);
        $this->assertSame(Subscription::STATUS_ACTIVE, $fresh->status);
        $this->assertSame(now()->addYear()->toDateString(), $this->date($fresh->end_date));
        $this->assertFalse($fresh->isLockedForPayment());
    }

    public function test_cancelling_an_unpaid_trial_needs_no_refund(): void
    {
        $subscription = $this->unpaidTrial();

        $response = $this->controller->cancelTest($this->request(['reason' => 'Not for us.']));

        $this->assertSame('Subscription cancelled.', $this->data($response)['message']);
        $this->assertSame(Subscription::STATUS_CANCELLED, $subscription->fresh()->status);
        Http::assertNotSent(fn($request) => str_contains($request->url(), 'refunds'));
    }

    public function test_an_unpaid_trial_cannot_buy_an_upgrade_on_its_own(): void
    {
        $subscription = $this->unpaidTrial();

        $this->assertFalse($subscription->canUpgrade());
        $this->assertTrue($subscription->renewalSummary()['awaiting_payment']);
    }

    public function test_a_paid_trial_is_not_awaiting_payment(): void
    {
        $subscription = $this->subscribed('A', Plan::TYPE_SME, [
            'mode' => Subscription::MODE_TEST,
            'end_date' => now()->addDays(5)->toDateString(),
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertFalse($subscription->awaitingTrialPayment());
        $this->assertFalse($subscription->isLockedForPayment());
    }

    public function test_unpaid_trials_are_reminded_to_pay_before_the_trial_ends(): void
    {
        $this->unpaidTrial(3);

        $this->app->make(SubscriptionReminderService::class)->sendDailyReminders();

        $this->assertDatabaseHas('notifications', [
            'to_user_id' => $this->owner->user_id,
            'message' => 'AMUMA Davao City: Your free trial ends in 3 days (' . now()->addDays(3)->toFormattedDateString() . '). Pay for your Homecare Services plan before then to keep your branch running.',
        ]);
    }
}
