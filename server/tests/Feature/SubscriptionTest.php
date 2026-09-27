<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use UsesTempDatabase;

    private int $agencyId;
    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'tester@amuma.com',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($user);

        $this->agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => $user->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->branchId = DB::table('branches')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'agency_id' => $this->agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function check(array $data)
    {
        return $this->postJson('/api/subscriptions-check-unique', $data);
    }

    public function test_new_agency_name_and_email_are_accepted(): void
    {
        $this->check([
            'agency_name' => 'Brand New Agency',
            'agency_email' => 'new@agency.com',
        ])->assertOk();
    }

    public function test_taken_agency_name_is_rejected(): void
    {
        $this->check(['agency_name' => 'AMUMA Incorporation'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('agency_name');
    }

    public function test_taken_agency_email_is_rejected(): void
    {
        $this->check(['agency_email' => 'info@amuma.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('agency_email');
    }

    public function test_existing_agency_can_keep_its_own_name_and_email(): void
    {
        $this->check([
            'agency_id' => $this->agencyId,
            'agency_name' => 'AMUMA Incorporation',
            'agency_email' => 'info@amuma.com',
        ])->assertOk();
    }

    public function test_taken_branch_email_is_rejected(): void
    {
        $this->check(['branch_email' => 'davao@amuma.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_email');
    }

    public function test_branch_name_is_rejected_within_the_same_agency(): void
    {
        $this->check([
            'agency_id' => $this->agencyId,
            'branch_name' => 'AMUMA Davao City',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_name');
    }

    public function test_branch_name_is_allowed_for_a_new_agency(): void
    {
        $this->check(['branch_name' => 'AMUMA Davao City'])->assertOk();
    }

    public function test_only_the_submitted_fields_are_checked(): void
    {
        $this->check(['branch_email' => 'free@amuma.com'])->assertOk();
    }
}
