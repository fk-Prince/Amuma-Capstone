<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMailer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use UsesTempDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->user = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'user@amuma.com',
            'password' => bcrypt('old-password'),
        ]);
    }

    private function linkParams(): array
    {
        $url = Mail::sent(PasswordResetMailer::class)->last()->url;
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    public function test_unknown_email_gets_the_same_answer_but_no_email(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@amuma.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists for this email, we sent a link to reset your password.');

        Mail::assertNothingSent();
    }

    public function test_known_email_gets_a_reset_link(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'user@amuma.com'])->assertOk();

        Mail::assertSent(PasswordResetMailer::class, fn($mail) => $mail->hasTo('user@amuma.com'));

        $query = $this->linkParams();

        $this->assertSame($this->user->uuid, $query['user']);
        $this->postJson('/api/auth/reset-password/check', $query)->assertOk();
    }

    public function test_link_resets_the_password_once(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'user@amuma.com']);

        $payload = $this->linkParams() + [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->postJson('/api/auth/reset-password', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Your password has been reset.');

        $this->assertTrue(Hash::check('new-password', $this->user->fresh()->password));

        $this->postJson('/api/auth/reset-password', $payload)
            ->assertStatus(422)
            ->assertJsonPath('message', 'This link has expired or has already been used.');
    }

    public function test_invalid_link_is_rejected(): void
    {
        $this->postJson('/api/auth/reset-password/check', [
            'token' => 'not-a-real-token',
            'user' => $this->user->uuid,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This link has expired or has already been used.');
    }
}
