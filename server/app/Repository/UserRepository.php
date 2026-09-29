<?php

namespace App\Repository;

use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository
{
    public function __construct(
        private LocationRepository $locationRepository,
    ) {}

    public function create(array $payload)
    {
        return User::create($payload);
    }

    public static function defaultPassword(string $lastName, mixed $createdAt = null): string
    {
        $name = strtolower(preg_replace('/[^A-Za-z]/', '', $lastName));

        return ($name !== '' ? $name : 'client')
            . ($createdAt ? Carbon::parse($createdAt)->year : now()->year);
    }

    public function createUpdateTypeUser(array $payload, string $type)
    {
        if ($type === 'client') {
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [Str::lower(trim($payload['email']))])->first();

            if (!$user) {
                $user = User::create([
                    'email' => $payload['email'],
                    'password' => Hash::make(
                        self::defaultPassword($payload['last_name'])
                    ),
                ]);
            }

            $client = $user->client;

            if (!empty($payload['address']) && empty($client?->location_id)) {
                $payload['location_id'] = $this->locationRepository->create([
                    'full_address' => $payload['address'],
                ])->location_id;
            }

            $initials = strtoupper(
                substr($payload['first_name'], 0, 1) . substr($payload['last_name'], 0, 1)
            );

            $values = [
                'first_name' => $payload['first_name'],
                'middle_name' => $payload['middle_name'] ?? null,
                'last_name' => $payload['last_name'],
                'location_id' => $payload['location_id'] ?? null,
                'phone_number' => $payload['phone_number'] ?? null,
                'occupation' => $payload['occupation'] ?? null,
                'avatar' => 'https://ui-avatars.com/api/?name=' . $initials,
            ];

            if (!$client) {
                $user->client()->create($values);
            } else {
                $missing = collect($values)
                    ->filter(fn($value, $column) => blank($client->{$column}) && !blank($value))
                    ->all();

                if ($missing) {
                    $client->update($missing);
                }
            }

            return $user->load('client');
        }
    }

    public function findByField(string $column, string $value)
    {
        return User::where($column, $value)->first();
    }



    public function updateOrCreate(object $payload)
    {
        return User::updateOrCreate(
            ['email' => $payload->getEmail()],
            [
                'first_name'      => explode(' ', $payload->getName())[0],
                'last_name'       => explode(' ', $payload->getName())[1] ?? '',
                'uuid'            => $payload->getId(),
                'provider'        => 'google',
                'password'        => null,
                'profile_picture' => $payload->getAvatar(),
            ]
        );
    }

    public function completeOnboarding(User $user, string $area, string $tour): User
    {
        $user->forceFill(["onboarding->{$area}->{$tour}" => now()->toDateTimeString()])->save();

        return $user;
    }

    public function update(string $user_id, array $payload)
    {
        return User::where('user_id', $user_id)->update($payload);
    }

    public function resetPassword(User $user, string $password): User
    {
        $user->forceFill(['password' => Hash::make($password)])->save();
        $user->tokens()->delete();

        return $user;
    }
}
