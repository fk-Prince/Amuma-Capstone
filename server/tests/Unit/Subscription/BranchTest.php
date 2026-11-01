<?php

namespace Tests\Unit\Subscription;

use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Service\SubscriptionService;
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

    private function newBranch(): Branch
    {
        return Branch::where('name', 'AMUMA Tagum')->firstOrFail();
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
}
