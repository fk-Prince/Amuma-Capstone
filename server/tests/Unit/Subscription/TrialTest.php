<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\SubscriptionController;
use App\Models\Plan;
use App\Models\Subscription;
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
}
