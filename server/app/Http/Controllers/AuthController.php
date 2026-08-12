<?php

namespace App\Http\Controllers;

use App\Http\Requests\SigninRequest;
use App\Http\Requests\SignupRequest;
use App\Service\AuthService;
use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function validate(SignupRequest $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Validation passed',
            'data' => $request->validated(),
        ]);
    }

    public function login(SigninRequest $request)
    {
        return $this->authService->login($request->all());
    }

    public function logout(Request $request)
    {
        return $this->authService->logout($request);
    }

    public function google(Request $request)
    {
        return $this->authService->google();
    }

    public function googleCallback(Request $request)
    {
        return $this->authService->googleCallback();
    }

    public function signup(Request $request)
    {
        return $this->authService->signup($request->all());
    }
}
