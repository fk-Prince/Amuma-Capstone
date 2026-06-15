<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $user = User::firstOrFail();
        Auth::login($user);

        $response = $this->postJson('/api/reviews', [
            'branch_uuid' => '23432423423',
            'rate' => '5.00',
            'description' => 'Excellent service',
        ]);

        $response->assertStatus(201);
    }
}
