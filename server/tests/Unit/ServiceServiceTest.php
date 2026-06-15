<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Service\ServiceService;
use Illuminate\Support\Facades\Auth;

class ServiceServiceTest extends TestCase
{


    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_create()
    {
        $user = User::firstOrFail();
        Auth::login($user);
        $payload = [
            'branch_uuid' => '019ec61e-c549-72d6-952c-b69b82ba5376',
            'category_name' => 'Test Category',
            'price' => '50.00',
            'service_name' => 'Physical Therapy',
            'maximum_duration' => '00:30:00',
            'is_available' => true,
            'type' => 'facility',
        ];

        $response = $this->postJson('/api/services', $payload);

        if ($response->status() !== 201) {
            dd([
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }

        $this->assertEquals(201, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Service created successfully', $data['message']);
    }
}
