<?php

namespace App\Service\Security;

use App\Mail\OtpMailer;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
            throw new Exception(__('Email already exists.'), 409);
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

        Mail::to($payload['email'])->send(
            new OtpMailer(
                $otp,
                $payload['email']
            )
        );

        return response()->json([
            'status' => true,
            'message' => __('OTP sent to your email.'),
            'otp_key' => $key,
            'expires_in' => self::OTP_TTL_MINUTES * 60,
        ]);
    }

    public function verify(array $payload)
    {
        $data = Cache::get("otp:{$payload['otp_key']}");

        if (!$data) {
            throw new Exception(
                __('OTP expired or invalid.'),
                422
            );
        }

        if ($data['otp'] != $payload['otp_value']) {
            throw new Exception(
                __('Invalid OTP.'),
                422
            );
        }

        Cache::forget("otp:{$payload['otp_key']}");

        $user = $this->authService->signup($payload['user']);

        return response()->json([
            'status' => true,
            'message' => __('Registration successful.'),
            'user' => $user,
        ]);
    }
}
