<?php

namespace App\Service\Utils;

use App\Mail\OtpMailer;
use App\Service\AuthService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService =  $authService;
    }

    public function send(array $payload)
    {
        $otp = rand(100000, 999999);
        $key = Str::random(32);
        Cache::put("otp:{$key}", [
            'otp' => $otp,
            'email' => $payload['email'],
        ], now()->addMinutes(3));

        Mail::to('prince.sestoso@gmail.com')->send(new OtpMailer($otp, 'prince.sestoso@gmail.com'));
        // Mail::to($payload['email'])->send(new OtpMailer($otp, $payload['email']));

        return response()->json([
            'status' => true,
            'message' => __('OTP sent to your email.'),
            'otp_key' => $key,
        ]);
    }

    public function verify(array $payload)
    {
        $data = Cache::get("otp:{$payload['otp_key']}");

        if (!$data) {
            throw new Exception(__('OTP expired or invalid.'), 422);
        }

        if ($data['otp'] != $payload['otp_value']) {
            throw new Exception(__('Invalid OTP.'), 422);
        }

        Cache::forget("otp:{$payload['otp_key']}");

        $user = $this->authService->register($payload['user']);

        return response()->json([
            'status'  => true,
            'message' => __('Registration successful.'),
            'user' => $user
        ]);
    }
}
