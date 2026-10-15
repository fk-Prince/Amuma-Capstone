<?php

namespace Tests\Unit\Subscription;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Http\Controllers\SubscriptionController;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class DailyJobsTest extends TestCase
{
    use UsesTempDatabase;
    use SubscriptionFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSubscription();
    }

    public function test_trial_turns_into_the_paid_year_when_it_ends(): void
    {
        $subscription = $this->subscribed('A', Plan::TYPE_SME, [
            'mode' => Subscription::MODE_TEST,
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->toDateString(),
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->toDateString(),
        ]);

        Artisan::call('subscriptions:apply-pending-plans');

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::MODE_LIVE, $fresh->mode);
        $this->assertNull($fresh->pending_plan_id);
        $this->assertSame(now()->toDateString(), $this->date($fresh->start_date));
    }

    public function test_plan_waiting_for_a_later_date_is_left_alone(): void
    {
        $subscription = $this->subscribed('C', Plan::TYPE_SME, [
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->addMonth()->toDateString(),
        ]);

        Artisan::call('subscriptions:apply-pending-plans');

        $this->assertSame($this->plan('C')->plan_id, $subscription->fresh()->plan_id);
    }

    public function test_subscription_past_its_end_date_is_marked_expired(): void
    {
        $subscription = $this->subscribed('A', Plan::TYPE_SME, ['end_date' => now()->subDay()->toDateString()]);

        Artisan::call('subscriptions:mark-expired');

        $this->assertSame(Subscription::STATUS_EXPIRED, $subscription->fresh()->status);
    }

    public function test_this_branchs_subscription_has_ended(): void
    {
        $this->subscribed('A', Plan::TYPE_SME, ['end_date' => now()->subDay()->toDateString()]);
        Artisan::call('subscriptions:mark-expired');

        AuthGuard::requireModule($this->owner, $this->branch->branch_id, ModuleEnum::Bookings, PermissionAction::Read);

        $this->assertFailsWith(
            "This branch's subscription has ended. Records can still be viewed, but changes are blocked until it is renewed.",
            fn() => AuthGuard::requireModule($this->owner, $this->branch->branch_id, ModuleEnum::Bookings, PermissionAction::Create),
            402
        );
    }

    public function test_amuma_davao_city_your_homecare_services_subscription_ends_in_3_days(): void
    {
        $subscription = $this->subscribed('A', Plan::TYPE_SME, ['end_date' => now()->addDays(3)->toDateString()]);
        $date = $subscription->end_date->toFormattedDateString();

        Artisan::call('subscriptions:remind-renewals');
        Artisan::call('subscriptions:remind-renewals');

        $reminders = Notification::where('to_user_id', $this->owner->user_id)->get();
        $this->assertCount(1, $reminders);
        $this->assertSame(
            "AMUMA Davao City: Your Homecare Services subscription ends in 3 days ({$date}). Renew it before then to avoid interruption.",
            $reminders->first()->message
        );
    }

    public function test_no_renewal_reminder_far_from_the_end_date(): void
    {
        $this->subscribed('A');

        Artisan::call('subscriptions:remind-renewals');

        $this->assertSame(0, Notification::count());
    }

    public function test_insufficient_permissions(): void
    {
        $this->subscribed('A', Plan::TYPE_SME, ['end_date' => now()->addDays(5)->toDateString()]);
        $manager = $this->branchManager();

        $this->assertFailsWith(
            'Insufficient permissions',
            fn() => $this->app->make(SubscriptionController::class)->renew($this->request($this->card(), $manager)),
            403
        );
    }

    public function test_branch_manager_can_view_the_subscription(): void
    {
        $this->subscribed('A');
        $manager = $this->branchManager();

        $result = $this->app->make(SubscriptionController::class)->index($this->request([], $manager));

        $this->assertCount(1, $result->resolve());
    }
}
