<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BedTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $user = User::firstOrFail();
        Auth::login($user);

        $response = $this->postJson('/api/beds', [
            'branch_uuid' => '019f46f6-3e1d-713f-b1eb-b6bdbf1e538b',
            'room_no' => '502',
            'bed_no' => 'A',
        ]);

        $response->assertStatus(201);
    }
}
