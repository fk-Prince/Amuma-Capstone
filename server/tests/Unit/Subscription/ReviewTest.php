<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\SubscriptionController;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\VerificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use UsesTempDatabase;
    use SubscriptionFixtures;

    private SubscriptionController $controller;
    private BranchSubscription $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSubscription();
        $this->controller = $this->app->make(SubscriptionController::class);

        $this->subscribed('A', Plan::TYPE_SME, [
            'status' => Subscription::STATUS_PENDING,
            'mode' => Subscription::MODE_TEST,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'pending_plan_id' => $this->plan('A')->plan_id,
            'pending_plan_starts_at' => now()->addWeeks(2)->toDateString(),
        ]);

        DB::table('agencies')->update(['status' => Agency::STATUS_PENDING]);
        DB::table('branches')->update(['status' => Branch::STATUS_PENDING]);
        BranchSubscription::query()->update(['status' => BranchSubscription::STATUS_PENDING]);
        $this->request = BranchSubscription::firstOrFail();
    }

    private function decide(string $action, array $extra = [], ?BranchSubscription $link = null)
    {
        return $this->controller->action($this->request(
            ['action' => $action, 'branch_subscription_uuid' => ($link ?? $this->request)->uuid] + $extra,
            $this->admin
        ));
    }

    private function ownerAlert(): string
    {
        return Notification::where('to_user_id', $this->owner->user_id)->latest('notification_id')->firstOrFail()->message;
    }

    public function test_branch_and_agency_are_verified_on_approval(): void
    {
        $this->decide('approve');

        $this->assertSame(BranchSubscription::STATUS_APPROVED, $this->request->fresh()->status);
        $this->assertSame(Branch::STATUS_VERIFIED, $this->branch->fresh()->status);
        $this->assertSame(Agency::STATUS_VERIFIED, $this->agency->fresh()->status);
    }

    public function test_approval_starts_the_free_trial(): void
    {
        $this->decide('approve');

        $subscription = $this->subscription->fresh();

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame(now()->addWeeks(2)->toDateString(), $this->date($subscription->end_date));
        $this->assertSame($this->plan('A')->plan_id, $subscription->pending_plan_id);
    }

    public function test_amuma_davao_city_was_approved_and_is_now_active(): void
    {
        $this->decide('approve');

        $log = VerificationLog::firstOrFail();
        $this->assertSame(VerificationLog::ACTION_APPROVED, $log->action);
        $this->assertSame($this->admin->user_id, (int) $log->action_by);
        $this->assertSame('AMUMA Davao City was approved and is now active.', $this->ownerAlert());
    }

    public function test_only_pending_branches_can_be_approved(): void
    {
        $this->decide('approve');

        $this->assertFailsWith(
            'Only pending branches can be approved (current status: approved).',
            fn() => $this->decide('approve'),
            404
        );

        $this->assertSame(1, VerificationLog::count());
    }

    public function test_branch_request_rejected(): void
    {
        $response = $this->decide('reject', ['rejection_reason' => 'Business permit is expired']);

        $this->assertSame('Branch request rejected.', $this->data($response)['message']);
        $this->assertSame(BranchSubscription::STATUS_REJECTED, $this->request->fresh()->status);
        $this->assertSame(Subscription::STATUS_REJECTED, $this->subscription->fresh()->status);
        $this->assertSame(SubscriptionPayment::STATUS_REFUNDED, SubscriptionPayment::firstOrFail()->status);
        $this->assertSame(Agency::STATUS_REJECTED, $this->agency->fresh()->status);
    }

    public function test_amuma_davao_city_was_not_approved_reason_business_permit_is_expired(): void
    {
        $this->decide('reject', ['rejection_reason' => 'Business permit is expired']);

        $this->assertSame('Business permit is expired', VerificationLog::firstOrFail()->reason);
        $this->assertSame('AMUMA Davao City was not approved. Reason: Business permit is expired', $this->ownerAlert());
    }

    public function test_rejecting_an_additional_branch_refunds_only_its_payment(): void
    {
        $this->decide('approve');
        $extra = $this->makeBranch('AMUMA Tagum', 'tagum@amuma.com', Branch::STATUS_PENDING);
        $link = $this->link($extra, BranchSubscription::STATUS_PENDING, BranchSubscription::TYPE_ADDITIONAL);
        $extraPayment = $this->payment($this->plan('A'), 6000, [
            'branch_id' => $extra->branch_id,
            'type' => SubscriptionPayment::TYPE_ADDITIONAL_BRANCH,
        ]);

        $this->decide('reject', ['rejection_reason' => 'Wrong address'], $link);

        $this->assertSame(SubscriptionPayment::STATUS_REFUNDED, $extraPayment->fresh()->status);
        $this->assertSame(1, SubscriptionPayment::where('status', SubscriptionPayment::STATUS_PAID)->count());
        $this->assertSame(Subscription::STATUS_ACTIVE, $this->subscription->fresh()->status);
    }

    public function test_subscription_cannot_be_rejected_because_the_payment_refund_failed(): void
    {
        $this->failRefunds = true;

        $this->assertFailsWith('Subscription cannot be rejected because the payment refund failed.', fn() => $this->decide('reject'));

        $this->assertSame(BranchSubscription::STATUS_PENDING, $this->request->fresh()->status);
        $this->assertSame(SubscriptionPayment::STATUS_PAID, SubscriptionPayment::firstOrFail()->status);
        Http::assertSent(fn($request) => str_contains($request->url(), 'refunds'));
    }

    public function test_history_shows_the_decision_and_who_made_it(): void
    {
        $this->decide('approve');

        $logs = $this->data($this->decide('logs'))['data'];

        $this->assertCount(1, $logs);
        $this->assertSame('approved', $logs[0]['action']);
        $this->assertSame('Mara Reyes', $logs[0]['reviewed_by']);
    }
}
