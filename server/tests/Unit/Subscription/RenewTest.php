<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\SubscriptionController;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class RenewTest extends TestCase
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

    private function renew(array $extra = [])
    {
        return $this->controller->renew($this->request($this->card($extra)));
    }

    private function endingIn(int $days, string $code = 'A'): Subscription
    {
        return $this->subscribed($code, Plan::TYPE_SME, ['end_date' => now()->addDays($days)->toDateString()]);
    }

    public function test_renewal_opens_7_days_before_this_subscription_ends(): void
    {
        $subscription = $this->endingIn(60);
        $opens = $subscription->end_date->copy()->subDays(7)->toFormattedDateString();

        $this->assertFailsWith("Renewal opens on {$opens}, 7 days before this subscription ends.", fn() => $this->renew(), 422);

        Http::assertNothingSent();
    }

    public function test_subscription_renewed_successfully(): void
    {
        $subscription = $this->endingIn(5);
        $newEnd = $subscription->end_date->copy()->addYear()->toDateString();

        $response = $this->renew();

        $this->assertSame('Subscription renewed successfully.', $this->data($response)['message']);
        $this->assertSame($newEnd, $this->date($subscription->fresh()->end_date));
    }

    public function test_upgraded_to_hybrid(): void
    {
        $subscription = $this->subscribed('A');

        $response = $this->renew(['plan_code' => 'C']);

        $this->assertSame('Upgraded to Hybrid.', $this->data($response)['message']);
        $this->assertSame($this->plan('C')->plan_id, $subscription->fresh()->plan_id);
        $this->assertEquals(4480, SubscriptionPayment::where('type', SubscriptionPayment::TYPE_UPGRADE)->firstOrFail()->price);
    }

    public function test_this_plan_can_only_be_upgraded_to_hybrid(): void
    {
        $this->subscribed('A');

        $this->assertFailsWith('This plan can only be upgraded to Hybrid.', fn() => $this->renew(['plan_code' => 'B']), 422);
    }
}
