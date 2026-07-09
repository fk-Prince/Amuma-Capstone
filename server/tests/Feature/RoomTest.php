<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RoomTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $user = User::firstOrFail();
        Auth::login($user);

        $response = $this->postJson('/api/rooms', [
            'branch_uuid' => '019f472a-7026-7327-9505-291119cf6fef',
            'room_no' => '502',
            'floor' => '1st',
            'room_type' => 'VIP',
            'capacity' => '1',
        ]);

        $response->assertStatus(201);
    }
}
