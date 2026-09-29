<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Service\Security\PasswordResetService;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function __construct(private PasswordResetService $passwordResetService) {}

    public function forgot(ForgotPasswordRequest $request)
    {
        return $this->passwordResetService->sendLink($request->validated());
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'user' => ['required', 'string'],
        ], [
            'token.required' => __('This link has expired or has already been used.'),
            'user.required' => __('This link has expired or has already been used.'),
        ]);

        return $this->passwordResetService->checkLink($validated);
    }

    public function reset(ResetPasswordRequest $request)
    {
        return $this->passwordResetService->reset($request->validated());
    }
}
