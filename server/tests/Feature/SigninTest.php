<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SigninTest extends TestCase
{
    use UsesTempDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:3000']]);

        User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'user@amuma.com',
            'password' => bcrypt('secret123'),
        ]);
    }

    private function signin(string $email, string $password, string $portal = 'checkout')
    {
        return $this->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/auth/login', ['email' => $email, 'password' => $password, 'portal' => $portal]);
    }

    public function test_user_can_sign_in(): void
    {
        $this->signin('user@amuma.com', 'secret123')
            ->assertOk()
            ->assertJsonPath('user.email', 'user@amuma.com');

        $this->assertAuthenticated();
    }

    public function test_wrong_password_cannot_sign_in(): void
    {
        $this->signin('user@amuma.com', 'wrong-password')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Incorrect credentials');

        $this->assertGuest();
    }

    public function test_unknown_email_cannot_sign_in(): void
    {
        $this->signin('nobody@amuma.com', 'secret123')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Incorrect credentials');

        $this->assertGuest();
    }

    public function test_portal_is_required(): void
    {
        $this->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/auth/login', ['email' => 'user@amuma.com', 'password' => 'secret123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('portal');

        $this->assertGuest();
    }

    public function test_account_that_is_not_staff_cannot_use_the_staff_portal(): void
    {
        $this->signin('user@amuma.com', 'secret123', 'staff')
            ->assertStatus(403);

        $this->assertGuest();
    }

    public function test_account_that_is_not_a_client_cannot_use_the_client_portal(): void
    {
        $this->signin('user@amuma.com', 'secret123', 'client')
            ->assertStatus(403);

        $this->assertGuest();
    }
}
