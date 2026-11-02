<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\SubscriptionController;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SavedCardTest extends TestCase
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

    private function savedCard(bool $autoRenew = false): array
    {
        return [
            'card_token_id' => 'tok_saved',
            'masked_card_number' => '400000XXXXXX0002',
            'card_brand' => 'VISA',
            'auto_renew' => $autoRenew,
        ];
    }

    private function trialWithCard(int $daysLeft = 0, bool $autoRenew = false): Subscription
    {
        $subscription = $this->subscribed('A', Plan::TYPE_SME, [
            'mode' => Subscription::MODE_TEST,
            'start_date' => now()->subDays(14 - $daysLeft)->toDateString(),
            'end_date' => now()->addDays($daysLeft)->toDateString(),
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->addDays($daysLeft)->toDateString(),
        ] + $this->savedCard($autoRenew));

        $subscription->payments()->delete();

        return $subscription->fresh();
    }

    private function chargeSavedCards(): void
    {
        $this->artisan('subscriptions:charge-saved-cards')->assertSuccessful();
    }

    private function ownerAlert(): ?string
    {
        return Notification::where('to_user_id', $this->owner->user_id)->latest('notification_id')->value('message');
    }

    public function test_your_free_trial_ended_and_the_plan_was_charged_to_your_card(): void
    {
        $subscription = $this->trialWithCard();

        $this->chargeSavedCards();

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::MODE_LIVE, $fresh->mode);
        $this->assertSame(now()->addYear()->toDateString(), $this->date($fresh->end_date));
        $this->assertEquals(13440, SubscriptionPayment::firstOrFail()->price);
        $this->assertSame(
            'AMUMA Davao City: Your free trial ended and ₱13,440.00 was charged to your card ending in 0002. Your paid year of Homecare Services starts today.',
            $this->ownerAlert()
        );
    }

    public function test_save_a_card_first_to_turn_on_auto_renew(): void
    {
        $this->subscribed('A');

        $this->assertFailsWith(
            'Save a card first to turn on auto-renew.',
            fn() => $this->controller->setAutoRenew($this->request(['auto_renew' => true])),
            422
        );
    }
}
