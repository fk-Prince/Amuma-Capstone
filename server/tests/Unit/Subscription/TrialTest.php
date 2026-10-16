<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\SubscriptionController;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class TrialTest extends TestCase
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

    private function onTrial(): Subscription
    {
        return $this->subscribed('A', Plan::TYPE_SME, [
            'mode' => Subscription::MODE_TEST,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->addDays(11)->toDateString(),
        ]);
    }

    private function withQueuedPlan(): Subscription
    {
        $subscription = $this->subscribed('C', Plan::TYPE_SME, [
            'end_date' => now()->addDays(20)->toDateString(),
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->addDays(20)->toDateString(),
        ]);

        $this->payment($this->plan('A'), 12000, ['type' => SubscriptionPayment::TYPE_RENEWAL]);

        return $subscription;
    }

    public function test_free_trial_ended_your_paid_year_starts_today(): void
    {
        $subscription = $this->onTrial();

        $response = $this->controller->endTest($this->request([]));

        $this->assertSame('Free trial ended. Your paid year starts today.', $this->data($response)['message']);
        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::MODE_LIVE, $fresh->mode);
        $this->assertSame(now()->addYear()->toDateString(), $this->date($fresh->end_date));
        $this->assertNull($fresh->pending_plan_id);
    }

    public function test_only_an_active_subscription_still_in_its_free_trial_can_be_ended_early(): void
    {
        $this->subscribed('A');

        $this->assertFailsWith(
            'Only an active subscription still in its free trial can be ended early.',
            fn() => $this->controller->endTest($this->request([])),
            422
        );
    }

    public function test_subscription_cancelled_your_payment_is_being_refunded(): void
    {
        $subscription = $this->onTrial();

        $response = $this->controller->cancelTest($this->request(['reason' => 'Too expensive for us.']));

        $this->assertSame('Subscription cancelled. Your payment is being refunded.', $this->data($response)['message']);
        $this->assertSame(Subscription::STATUS_CANCELLED, $subscription->fresh()->status);
        $this->assertSame('Too expensive for us.', $subscription->fresh()->cancel_reason);
        $this->assertSame(SubscriptionPayment::STATUS_REFUNDED, SubscriptionPayment::firstOrFail()->status);
    }

    public function test_a_cancel_reason_is_required(): void
    {
        $subscription = $this->onTrial();

        $this->assertFailsWith(
            'Please tell us why you are cancelling.',
            fn() => $this->controller->cancelTest($this->request([])),
            422
        );

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
    }

    public function test_the_subscription_could_not_be_cancelled_because_the_refund_failed(): void
    {
        $subscription = $this->onTrial();
        $this->failRefunds = true;

        $this->assertFailsWith(
            'The subscription could not be cancelled because the refund failed. Please try again.',
            fn() => $this->controller->cancelTest($this->request(['reason' => 'Too expensive for us.'])),
            502
        );

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
    }

    public function test_plan_change_cancelled_your_payment_is_being_refunded(): void
    {
        $subscription = $this->withQueuedPlan();

        $response = $this->controller->cancelPendingPlan($this->request([]));

        $this->assertSame('Plan change cancelled. Your payment is being refunded.', $this->data($response)['message']);
        $this->assertNull($subscription->fresh()->pending_plan_id);
        $this->assertSame(SubscriptionPayment::STATUS_REFUNDED, SubscriptionPayment::where('type', SubscriptionPayment::TYPE_RENEWAL)->firstOrFail()->status);
    }

    public function test_there_is_no_queued_plan_change_to_cancel(): void
    {
        $this->subscribed('A');

        $this->assertFailsWith(
            'There is no queued plan change to cancel.',
            fn() => $this->controller->cancelPendingPlan($this->request([])),
            422
        );
    }
}
