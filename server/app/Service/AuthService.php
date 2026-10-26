<?php

namespace App\Service;

use App\Models\AuditLog;
use App\Models\User;
use App\Repository\UserRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    private UserRepository $userRepository;

    private AuditService $auditService;

    public function __construct(UserRepository $userRepository, AuditService $auditService)
    {
        $this->userRepository = $userRepository;
        $this->auditService = $auditService;
    }

    public function login(array $payload)
    {
        $identifier = filled($payload['employee_code'] ?? null)
            ? $payload['employee_code']
            : $payload['email'];

        $user = filled($payload['employee_code'] ?? null)
            ? $this->userRepository->findByEmployeeCode($payload['employee_code'])
            : $this->userRepository->findByField('email', $payload['email']);

        if (!$user) {
            $this->auditService->log(
                AuditLog::ACTION_LOGIN_FAILED,
                'auth',
                description: "Failed login for {$identifier}: account not found."
            );

            throw new Exception(__('Incorrect credentials'), 404);
        }

        if (empty($user->password)) {
            throw new Exception(
                __('This email is registered with :provider. Please sign in with :provider instead.', [
                    'provider' => Str::title($user->provider),
                ]),
                409
            );
        }

        if (!Hash::check($payload['password'], $user->password)) {
            $this->auditService->log(
                AuditLog::ACTION_LOGIN_FAILED,
                'auth',
                $user,
                'Failed login: incorrect password.',
                $user
            );

            throw new Exception(__('Incorrect credentials'), 401);
        }

        Auth::login($user);
        request()->session()->regenerate();

        $this->auditService->log(AuditLog::ACTION_LOGIN, 'auth', $user, actor: $user);

        return response()->json([
            'user' => $user,
            'message' => __('Successfully logged-in.'),
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $this->auditService->log(AuditLog::ACTION_LOGOUT, 'auth', $request->user());
        }

        $request->user()?->currentAccessToken()?->delete();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = response()->json([
            'message' => __('Logged out successfully'),
        ], 200);

        foreach ($this->sessionCookieNames() as $name) {
            $response->withoutCookie($name, config('session.path'), config('session.domain'));
        }

        return $response;
    }

    private function sessionCookieNames(): array
    {
        $session = Str::slug((string) config('app.name')) . '-session';

        return array_unique([
            config('session.cookie'),
            config('session.xsrf_cookie'),
            $session,
            $session . '-sandbox',
            'XSRF-TOKEN',
            'XSRF-TOKEN-SANDBOX',
        ]);
    }

    public function signup(array $payload)
    {
        $exists = $this->userRepository->findByField('email', $payload['email']);

        if ($exists) {
            throw new Exception(__('An account with this email already exists.'), 409);
        }

        $user = $this->userRepository->create([
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);

        $initials = strtoupper(
            substr($payload['first_name'], 0, 1) . substr($payload['last_name'], 0, 1)
        );

        $user->client()->create([
            'first_name' => Str::title($payload['first_name']),
            'last_name' => Str::title($payload['last_name']),
            'avatar' => 'https://ui-avatars.com/api/?name=' . $initials,
        ]);

        $user->load('client');

        return response()->json([
            'user' => $user,
            'message' => __('Registration successful.'),
        ], 201);
    }

    public function google()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        $driver->setHttpClient(new \GuzzleHttp\Client([
            'verify' => false
        ]));

        return response()->json([
            'url' => $driver->stateless()->redirect()->getTargetUrl(),
        ], 200);
    }

    public function googleCallback()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        $driver->setHttpClient(new \GuzzleHttp\Client([
            'verify' => false
        ]));

        try {
            $googleUser = $driver->stateless()->user();
        } catch (Exception $e) {
            Log::error('Google sign-in failed: ' . $e->getMessage());

            return redirect()->away(
                config('app.client_url') . '/auth/signin?error=google_failed'
            );
        }

        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();

        if (!$user) {
            $user = $this->userRepository->findByField('email', $googleUser->getEmail());
        }

        if (!$user) {
            $nameParts = explode(' ', trim($googleUser->getName()), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            $user = User::create([
                'email' => $googleUser->getEmail(),
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
            ]);

            $user->client()->create([
                'first_name' => Str::title($firstName),
                'last_name' => Str::title($lastName),
                'avatar' => $googleUser->getAvatar()
                    ?: 'https://ui-avatars.com/api/?name=' . strtoupper(
                        substr($firstName, 0, 1) . substr($lastName, 0, 1)
                    ),
            ]);
        } elseif ($user->provider !== 'google') {
            return redirect()->away(
                config('app.client_url') . '/auth/signin?error=provider_mismatch'
            );
        }

        Auth::login($user);
        request()->session()->regenerate();

        $this->auditService->log(AuditLog::ACTION_LOGIN, 'auth', $user, 'Signed in with Google.', $user);

        $token =$user->createToken('auth-token')->plainTextToken;

        return redirect()->away(
            config('app.client_url') . '/auth/success?token=' . urlencode($token)
        );
    }
}
