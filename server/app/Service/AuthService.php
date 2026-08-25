<?php

namespace App\Service;

use App\Models\User;
use App\Repository\UserRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(array $payload)
    {
        $user = $this->userRepository->findByField('email', $payload['email']);

        if (!$user) {
            throw new Exception(__('Incorrect credentials'), 404);
        }

        if (!Hash::check($payload['password'], $user->password)) {
            throw new Exception(__('Incorrect credentials'), 401);
        }

        Auth::login($user);
        request()->session()->regenerate();


        return response()->json([
            'user' => $user,
            'message' => __('Successfully logged-in.'),
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => __('Logged out successfully'),
        ], 200);
    }

    public function signup(array $payload)
    {
        $exists = $this->userRepository->findByField('email', $payload['email']);

        if ($exists) {
            throw new Exception(__('Email already exists.'), 409);
        }

        $user = $this->userRepository->create([
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);

        $initials = strtoupper(
            substr($payload['first_name'], 0, 1) . substr($payload['last_name'], 0, 1)
        );

        $user->client()->create([
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
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
        $googleUser = $driver->stateless()->user();

        $user = $this->userRepository->findByField('email', $googleUser->getEmail());

        if (!$user) {
            $nameParts = explode(' ', trim($googleUser->getName()), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            $user = User::create([
                'email' => $googleUser->getEmail(),
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'is_verified' => true,
            ]);

            $user->client()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'avatar' => $googleUser->getAvatar(),
            ]);
        } elseif ($user->provider !== 'google') {
            $user->update([
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
            ]);

            $user->client()->updateOrCreate(
                ['user_id' => $user->user_id],
                ['avatar' => $googleUser->getAvatar()],
            );
        }

        Auth::login($user);
        request()->session()->regenerate();

        $token = $user->createToken('auth-token')->plainTextToken;

        return redirect()->away(
            config('app.client_url') . '/auth/success?token=' . urlencode($token)
        );
    }
}
