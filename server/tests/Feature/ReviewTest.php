<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }


    public function test_example(): void
    {
        $user = User::firstOrFail();
        Auth::login($user);


        $response = $this->postJson('/api/reviews', [
            // 'branch_uuid' =>  $branch->uuid,
            'branch_uuid' =>  '019f275e-c434-7210-a16d-92288c25b8f8',
            'rate' => '4.00',
            'description' => 'Excellent service',
        ]);

        $response->assertStatus(201);
    }
}
