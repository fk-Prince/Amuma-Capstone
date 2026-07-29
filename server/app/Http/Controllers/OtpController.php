<?php

namespace App\Http\Controllers;

use App\Http\Requests\OtpRequest;
use App\Service\Security\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);
        return $this->otpService->send($validated);
    }

    public function verify(OtpRequest $request)
    {
        return $this->otpService->verify($request->all());
    }
}
