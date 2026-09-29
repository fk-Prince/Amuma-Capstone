<?php

namespace App\Service\Security;

use App\Mail\PasswordResetMailer;
use App\Models\User;
use App\Repository\UserRepository;
use Exception;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Throwable;

class PasswordResetService
{
    public function __construct(private UserRepository $userRepository) {}

    private function broker(): PasswordBroker
    {
        return Password::broker();
    }

    private function minutes(): int
    {
        return (int) config('auth.passwords.users.expire', 60);
    }

    public function sendLink(array $payload)
    {
        $user = $this->userRepository->findByField('email', $payload['email']);

        if ($user && !$this->broker()->getRepository()->recentlyCreatedToken($user)) {
            $token = $this->broker()->createToken($user);

            $url = config('app.client_url') . '/auth/reset-password?' . http_build_query([
                'token' => $token,
                'user' => $user->uuid,
            ]);

            try {
                Mail::to($user->email)->send(new PasswordResetMailer($url, $this->minutes()));
            } catch (Throwable) {
                $this->broker()->deleteToken($user);

                throw new Exception(
                    __("We couldn't send the email right now. Please try again in a moment."),
                    503
                );
            }
        }

        return response()->json([
            'message' => __('If an account exists for this email, we sent a link to reset your password.'),
            'expires_in' => $this->minutes() * 60,
        ]);
    }

    public function checkLink(array $payload)
    {
        $this->userForToken($payload['user'], $payload['token']);

        return response()->json([
            'message' => __('This link is valid.'),
        ]);
    }

    public function reset(array $payload)
    {
        $user = $this->userForToken($payload['user'], $payload['token']);

        $this->userRepository->resetPassword($user, $payload['password']);
        $this->broker()->deleteToken($user);

        return response()->json([
            'message' => __('Your password has been reset.'),
        ]);
    }

    private function userForToken(string $uuid, string $token): User
    {
        $user = $this->userRepository->findByField('uuid', $uuid);

        if (!$user || !$this->broker()->tokenExists($user, $token)) {
            throw new Exception(
                __('This link has expired or has already been used.'),
                422
            );
        }

        return $user;
    }
}
