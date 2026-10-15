<?php

namespace Tests\Unit\Subscription;

use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Service\SubscriptionService;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use UsesTempDatabase;
    use SubscriptionFixtures;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSubscription();
        $this->service = $this->app->make(SubscriptionService::class);
    }

    private function addFree()
    {
        $this->actingAs($this->owner);

        return $this->service->createBranchWithinCapacity(
            $this->branchFields(['agency_id' => $this->agency->agency_id]),
            $this->owner
        );
    }

    private function buyExtra()
    {
        $this->actingAs($this->owner);

        return $this->service->makeAdditionalBranch(
            $this->branchFields($this->card(['agency_id' => $this->agency->agency_id, 'subscription_uuid' => $this->subscription->uuid])),
            $this->owner
        );
    }

    private function resubmit(Branch $branch)
    {
        $this->actingAs($this->owner);

        return $this->service->resubmitBranch([
            'target_branch_uuid' => $branch->uuid,
            'agency_id' => $this->agency->agency_id,
            'branch_name' => 'AMUMA Tagum Riverside',
            'branch_email' => 'tagum@amuma.com',
            'branch_contact_number' => '09171234567',
            'branch_description' => 'Moved to a licensed building.',
            'branch_street' => 'Rizal Street',
            'branch_city' => 'Tagum',
            'branch_province' => 'Davao del Norte',
            'branch_country' => 'Philippines',
            'branch_latitude' => 7.4478,
            'branch_longitude' => 125.8078,
        ], $this->owner);
    }

    private function newBranch(): Branch
    {
        return Branch::where('name', 'AMUMA Tagum')->firstOrFail();
    }

    private function moreBranches(int $count): void
    {
        for ($i = 2; $i < $count + 2; $i++) {
            $this->link($this->makeBranch("AMUMA Branch {$i}", "branch{$i}@amuma.com"), BranchSubscription::STATUS_APPROVED);
        }
    }

    public function test_tenth_branch_on_an_enterprise_plan_is_valid(): void
    {
        $this->subscribed('A', Plan::TYPE_ENTERPRISE);
        $this->moreBranches(8);

        $this->assertSame('Branch added and sent for review.', $this->data($this->addFree())['message']);
        $this->assertSame(10, BranchSubscription::count());
    }

    public function test_eleventh_branch_on_an_enterprise_plan_is_invalid(): void
    {
        $this->subscribed('A', Plan::TYPE_ENTERPRISE);
        $this->moreBranches(9);

        $this->assertFailsWith(
            'That subscription has no free branch slots. Pick another or purchase a new subscription.',
            fn() => $this->addFree(),
            409
        );

        $this->assertSame(10, BranchSubscription::count());
    }

    public function test_extra_branch_with_1_month_left_costs_one_twelfth(): void
    {
        $this->subscribed('A', Plan::TYPE_SME, ['end_date' => now()->startOfDay()->addMonth()->toDateString()]);

        $this->buyExtra();

        $this->assertEquals(1000, SubscriptionPayment::where('type', SubscriptionPayment::TYPE_ADDITIONAL_BRANCH)->firstOrFail()->price);
    }

    public function test_this_subscription_cant_take_an_additional_branch(): void
    {
        $this->subscribed('A', Plan::TYPE_SME, ['end_date' => now()->toDateString()]);

        $this->assertFailsWith("This subscription can't take an additional branch.", fn() => $this->buyExtra(), 409);

        Http::assertNothingSent();
    }

    public function test_branch_added_and_sent_for_review(): void
    {
        $this->subscribed('A', Plan::TYPE_ENTERPRISE);

        $response = $this->addFree();

        $this->assertSame('Branch added and sent for review.', $this->data($response)['message']);
        $link = BranchSubscription::where('branch_id', $this->newBranch()->branch_id)->firstOrFail();
        $this->assertSame(BranchSubscription::STATUS_PENDING, $link->status);
        $this->assertSame(BranchSubscription::TYPE_INCLUDED, $link->type);
        $this->assertSame(1, SubscriptionPayment::count());
    }

    public function test_new_branch_request_from_amuma_tagum_is_awaiting_your_review(): void
    {
        $this->subscribed('A', Plan::TYPE_ENTERPRISE);

        $this->addFree();

        $this->assertSame(
            'New branch request from AMUMA Tagum (included in an existing subscription) is awaiting your review.',
            Notification::where('to_user_id', $this->admin->user_id)->firstOrFail()->message
        );
    }

    public function test_that_subscription_has_no_free_branch_slots(): void
    {
        $this->subscribed('A', Plan::TYPE_SME);

        $this->assertFailsWith(
            'That subscription has no free branch slots. Pick another or purchase a new subscription.',
            fn() => $this->addFree(),
            409
        );

        $this->assertSame(1, Branch::count());
    }

    public function test_branches_can_be_added_once_your_free_trial_ends(): void
    {
        $this->subscribed('A', Plan::TYPE_SME, ['mode' => Subscription::MODE_TEST]);

        $this->assertFailsWith('Branches can be added once your free trial ends.', fn() => $this->buyExtra(), 422);

        Http::assertNothingSent();
    }

    public function test_payment_received_branch_sent_for_review(): void
    {
        $this->subscribed('A', Plan::TYPE_SME);

        $response = $this->buyExtra();

        $this->assertSame('Payment received. Branch sent for review.', $this->data($response)['message']);
        $payment = SubscriptionPayment::where('type', SubscriptionPayment::TYPE_ADDITIONAL_BRANCH)->firstOrFail();
        $this->assertEquals(6000, $payment->price);
        $this->assertSame($this->newBranch()->branch_id, (int) $payment->branch_id);
    }

    public function test_extra_branch_is_saved_as_an_additional_pending_request(): void
    {
        $this->subscribed('A', Plan::TYPE_SME);

        $this->buyExtra();

        $link = BranchSubscription::where('branch_id', $this->newBranch()->branch_id)->firstOrFail();
        $this->assertSame(BranchSubscription::TYPE_ADDITIONAL, $link->type);
        $this->assertSame(BranchSubscription::STATUS_PENDING, $link->status);
    }

    public function test_branch_resubmitted_for_review(): void
    {
        $this->subscribed('A', Plan::TYPE_ENTERPRISE);
        $rejected = $this->makeBranch('AMUMA Tagum', 'tagum@amuma.com', Branch::STATUS_REJECTED);
        $link = $this->link($rejected, BranchSubscription::STATUS_REJECTED);

        $response = $this->resubmit($rejected);

        $this->assertSame('Branch resubmitted for review.', $this->data($response)['message']);
        $this->assertSame(BranchSubscription::STATUS_PENDING, $link->fresh()->status);
        $this->assertSame('AMUMA Tagum Riverside', $rejected->fresh()->name);
    }

    public function test_only_rejected_branches_can_be_resubmitted(): void
    {
        $this->subscribed('A', Plan::TYPE_ENTERPRISE);

        $this->assertFailsWith('Only rejected branches can be resubmitted.', fn() => $this->resubmit($this->branch), 422);
    }
}
