<?php

namespace Tests\Unit\Subscription;

use App\Http\Controllers\SubscriptionController;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
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

    public function test_amuma_davao_city_was_approved_and_is_now_active(): void
    {
        $this->decide('approve');

        $log = VerificationLog::firstOrFail();
        $this->assertSame(VerificationLog::ACTION_APPROVED, $log->action);
        $this->assertSame($this->admin->user_id, (int) $log->action_by);
        $this->assertSame('AMUMA Davao City was approved and is now active.', $this->ownerAlert());
    }

    public function test_amuma_davao_city_was_not_approved_reason_business_permit_is_expired(): void
    {
        $this->decide('reject', ['rejection_reason' => 'Business permit is expired']);

        $this->assertSame('Business permit is expired', VerificationLog::firstOrFail()->reason);
        $this->assertSame('AMUMA Davao City was not approved. Reason: Business permit is expired', $this->ownerAlert());
    }
}
