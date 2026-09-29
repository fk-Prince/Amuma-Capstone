<?php

namespace App\Service\Security;

use App\Mail\OtpMailer;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OtpService
{
    private AuthService $authService;
    private UserRepository $userRepository;

    public function __construct(AuthService $authService, UserRepository $userRepository)
    {
        $this->authService = $authService;
        $this->userRepository = $userRepository;
    }

    private const OTP_TTL_MINUTES = 5;

    public function send(array $payload)
    {
        if ($this->userRepository->findByField('email', $payload['email'])) {
            throw ValidationException::withMessages([
                'email' => __('An account with this email already exists.'),
            ]);
        }

        $otp = rand(100000, 999999);

        $key = Str::random(32);

        Cache::put(
            "otp:{$key}",
            [
                'otp' => $otp,
                'email' => $payload['email'],
            ],
            now()->addMinutes(self::OTP_TTL_MINUTES)
        );

        try {
            Mail::to($payload['email'])->send(
                new OtpMailer(
                    $otp,
                    self::OTP_TTL_MINUTES
                )
            );
        } catch (Throwable) {
            Cache::forget("otp:{$key}");

            throw new Exception(
                __("We couldn't send the code to this email right now. Please try again in a moment."),
                503
            );
        }

        return response()->json([
            'status' => true,
            'message' => __('We sent a 6-digit code to your email. It expires in :minutes minutes.', [
                'minutes' => self::OTP_TTL_MINUTES,
            ]),
            'otp_key' => $key,
            'expires_in' => self::OTP_TTL_MINUTES * 60,
        ]);
    }

    public function verify(array $payload)
    {
        $data = Cache::get("otp:{$payload['otp_key']}");

        if (!$data) {
            throw new Exception(
                __('Your code has expired. Click Resend Code to get a new one.'),
                422
            );
        }

        if (strcasecmp($data['email'], $payload['user']['email']) !== 0) {
            throw new Exception(
                __('This code was sent to a different email address. Request a new code.'),
                422
            );
        }

        if ($data['otp'] != $payload['otp_value']) {
            throw new Exception(
                __('Incorrect code. Please try again.'),
                422
            );
        }

        Cache::forget("otp:{$payload['otp_key']}");

        $user = $this->authService->signup($payload['user']);

        return response()->json([
            'status' => true,
            'message' => __('Account created successfully!'),
            'user' => $user,
        ]);
    }
}
