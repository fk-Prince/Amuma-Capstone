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

    public function test_renewal_with_exactly_7_days_left_is_valid(): void
    {
        $this->endingIn(7);

        $this->assertSame('Subscription renewed successfully.', $this->data($this->renew())['message']);
    }

    public function test_renewal_with_8_days_left_is_invalid(): void
    {
        $subscription = $this->endingIn(8);
        $opens = $subscription->end_date->copy()->subDays(7)->toFormattedDateString();

        $this->assertFailsWith("Renewal opens on {$opens}, 7 days before this subscription ends.", fn() => $this->renew(), 422);
    }

    public function test_subscription_renewed_successfully(): void
    {
        $subscription = $this->endingIn(5);
        $newEnd = $subscription->end_date->copy()->addYear()->toDateString();

        $response = $this->renew();

        $this->assertSame('Subscription renewed successfully.', $this->data($response)['message']);
        $this->assertSame($newEnd, $this->date($subscription->fresh()->end_date));
    }

    public function test_renewal_payment_is_the_plan_price(): void
    {
        $this->endingIn(5);

        $this->renew();

        $payment = SubscriptionPayment::where('type', SubscriptionPayment::TYPE_RENEWAL)->firstOrFail();
        $this->assertEquals(12000, $payment->price);
    }

    public function test_renewed_homecare_services_starts_on_the_end_date(): void
    {
        $subscription = $this->endingIn(5, 'C');
        $end = $subscription->end_date->copy();

        $response = $this->renew(['plan_code' => 'A']);

        $this->assertSame('Renewed. Homecare Services starts on ' . $end->toFormattedDateString() . '.', $this->data($response)['message']);
        $fresh = $subscription->fresh();
        $this->assertSame($this->plan('C')->plan_id, $fresh->plan_id);
        $this->assertSame($this->plan('A')->plan_id, $fresh->pending_plan_id);
        $this->assertSame($end->toDateString(), $this->date($fresh->pending_plan_starts_at));
    }

    public function test_upgraded_to_hybrid(): void
    {
        $subscription = $this->subscribed('A');

        $response = $this->renew(['plan_code' => 'C']);

        $this->assertSame('Upgraded to Hybrid.', $this->data($response)['message']);
        $this->assertSame($this->plan('C')->plan_id, $subscription->fresh()->plan_id);
        $this->assertEquals(4000, SubscriptionPayment::where('type', SubscriptionPayment::TYPE_UPGRADE)->firstOrFail()->price);
    }

    public function test_this_plan_can_only_be_upgraded_to_hybrid(): void
    {
        $this->subscribed('A');

        $this->assertFailsWith('This plan can only be upgraded to Hybrid.', fn() => $this->renew(['plan_code' => 'B']), 422);
    }

    public function test_the_plan_type_of_a_subscription_cant_be_changed(): void
    {
        $this->endingIn(5);

        $this->assertFailsWith("The plan type of a subscription can't be changed.", fn() => $this->renew(['plan_type' => Plan::TYPE_ENTERPRISE]), 422);
    }

    public function test_your_paid_year_is_already_paid_for_and_starts_when_your_free_trial_ends(): void
    {
        $this->subscribed('A', Plan::TYPE_SME, [
            'mode' => Subscription::MODE_TEST,
            'end_date' => now()->addDays(5)->toDateString(),
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertFailsWith(
            'Your paid year is already paid for and starts when your free trial ends.',
            fn() => $this->renew(),
            422
        );
    }

    public function test_payment_failed_leaves_the_subscription_unchanged(): void
    {
        $subscription = $this->endingIn(5);
        $this->declineCard = true;

        $response = $this->renew();

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame($this->date($subscription->end_date), $this->date($subscription->fresh()->end_date));
        $this->assertSame(0, SubscriptionPayment::where('type', SubscriptionPayment::TYPE_RENEWAL)->count());
    }
}
