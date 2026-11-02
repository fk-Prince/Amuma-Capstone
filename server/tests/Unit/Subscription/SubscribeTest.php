<?php

namespace Tests\Unit\Subscription;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Service\SubscriptionService;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SubscribeTest extends TestCase
{
    use UsesTempDatabase;
    use SubscriptionFixtures;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSubscription();
        $this->user = $this->makeUser('Ana', 'Santos', true);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge($this->card(), [
            'plan_code' => 'A',
            'plan_type' => Plan::TYPE_SME,
            'agency_name' => 'Santos Care',
            'agency_email' => 'santos@care.ph',
            'agency_street' => 'Roxas Avenue',
            'agency_city' => 'Davao City',
            'agency_province' => 'Davao del Sur',
            'agency_country' => 'Philippines',
            'agency_latitude' => 7.0731,
            'agency_longitude' => 125.6128,
        ], $this->branchFields(['branch_name' => 'AMUMA Davao City', 'branch_email' => 'davao@amuma.com']), $overrides);
    }

    private function subscribe(array $overrides = [])
    {
        $this->actingAs($this->user);

        return $this->app->make(SubscriptionService::class)->startTrial($this->payload($overrides), $this->user);
    }

    private function validateDetails(array $overrides)
    {
        $details = array_merge($this->payload(), $overrides);
        unset($details['agency_name'], $details['agency_email']);

        return $this->actingAs($this->user, 'sanctum')->postJson('/api/subscriptions-validate', $details);
    }

    public function test_almost_there_we_ll_notify_you_once_your_branch_is_verified(): void
    {
        $response = $this->subscribe();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame("Almost there! We'll notify you once your branch is verified.", $this->data($response)['message']);

        $subscription = Subscription::firstOrFail();
        $this->assertSame(Subscription::STATUS_PENDING, $subscription->status);
        $this->assertSame(Branch::STATUS_PENDING, Branch::firstOrFail()->status);
        $this->assertSame(Agency::STATUS_PENDING, Agency::firstOrFail()->status);
        $this->assertSame(BranchSubscription::STATUS_PENDING, BranchSubscription::firstOrFail()->status);
    }

    public function test_add_a_card_to_start_your_free_trial(): void
    {
        $this->assertFailsWith('Add a card to start your free trial.', fn() => $this->subscribe(['token_id' => null]), 422);

        $this->assertSame(0, Subscription::count());
    }

    public function test_the_branch_name_field_is_required(): void
    {
        $this->validateDetails(['branch_name' => ''])
            ->assertJsonValidationErrors(['branch_name' => 'The branch name field is required.']);
    }

    public function test_the_branch_email_has_already_been_taken(): void
    {
        $this->subscribe();

        $this->validateDetails(['branch_email' => 'davao@amuma.com'])
            ->assertJsonValidationErrors(['branch_email' => 'The branch email has already been taken.']);
    }

    public function test_the_tin_must_be_a_valid_tin(): void
    {
        foreach (['12345', '004-512-873', '004-512-873-0000', '004512873000'] as $tin) {
            $this->validateDetails(['branch_settings' => ['tin' => $tin]])->assertJsonValidationErrors([
                'branch_settings.tin' => 'The TIN must be a valid TIN, e.g. 004-512-873-000 or 004-512-873-00000.',
            ]);
        }
    }

    public function test_invalid_plan_type(): void
    {
        $this->assertFailsWith('Invalid plan type.', fn() => $this->subscribe(['plan_type' => 'monthly']), 422);

        Http::assertNothingSent();
    }

    public function test_plan_not_found(): void
    {
        $this->assertFailsWith('Plan not found.', fn() => $this->subscribe(['plan_code' => 'Z']), 404);

        Http::assertNothingSent();
    }
}
