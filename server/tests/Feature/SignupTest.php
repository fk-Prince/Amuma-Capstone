<?php

namespace Tests\Feature;

use App\Mail\OtpMailer;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use UsesTempDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function createUser(string $email): User
    {
        return User::create([
            'uuid' => (string) Str::uuid(),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }

    private function sendCode(string $email): array
    {
        $key = $this->postJson('/api/auth/otp/send', ['email' => $email])->json('otp_key');
        $otp = Mail::sent(OtpMailer::class)->last()->otp;

        return [$key, (string) $otp];
    }

    private function register(string $key, string $otp, string $email)
    {
        return $this->postJson('/api/auth/otp/verify', [
            'otp_key' => $key,
            'otp_value' => $otp,
            'user' => [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => $email,
                'password' => 'secret123',
            ],
        ]);
    }

    public function test_existing_email_cannot_sign_up(): void
    {
        $this->createUser('taken@amuma.com');

        $this->postJson('/api/auth/otp/send', ['email' => 'taken@amuma.com'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'An account with this email already exists.');

        Mail::assertNothingSent();
    }

    public function test_new_email_can_proceed(): void
    {
        $this->postJson('/api/auth/otp/send', ['email' => 'new@amuma.com'])
            ->assertOk()
            ->assertJsonStructure(['otp_key', 'expires_in']);

        Mail::assertSent(OtpMailer::class, fn(OtpMailer $mail) => $mail->hasTo('new@amuma.com'));
    }

    public function test_new_email_can_register_with_the_code(): void
    {
        [$key, $otp] = $this->sendCode('new@amuma.com');

        $this->register($key, $otp, 'new@amuma.com')
            ->assertOk()
            ->assertJsonPath('message', 'Account created successfully!');

        $this->assertDatabaseHas('users', ['email' => 'new@amuma.com']);
    }

    public function test_email_taken_before_registering_is_rejected(): void
    {
        [$key, $otp] = $this->sendCode('new@amuma.com');

        $this->createUser('new@amuma.com');

        $this->register($key, $otp, 'new@amuma.com')
            ->assertStatus(409)
            ->assertJsonPath('message', 'An account with this email already exists.');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_wrong_code_does_not_register(): void
    {
        [$key, $otp] = $this->sendCode('new@amuma.com');

        $wrong = $otp === '111111' ? '222222' : '111111';

        $this->register($key, $wrong, 'new@amuma.com')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Incorrect code. Please try again.');

        $this->assertDatabaseMissing('users', ['email' => 'new@amuma.com']);
    }
}
