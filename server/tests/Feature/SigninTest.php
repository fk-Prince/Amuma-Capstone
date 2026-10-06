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

    private function signin(string $email, string $password)
    {
        return $this->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/auth/login', ['email' => $email, 'password' => $password]);
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
}
