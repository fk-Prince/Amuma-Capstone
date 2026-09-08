<?php

namespace App\Repository;

use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

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
            if (!empty($payload['address'])) {
                $scheduledLocation = $this->locationRepository->create([
                    'full_address' => $payload['address'],
                ]);

                $payload['location_id'] = $scheduledLocation->location_id;
            }

            unset($payload['address']);

            $user = User::where('email', $payload['email'])->first();

            if (!$user) {
                $user = User::create([
                    'email' => $payload['email'],
                    'password' => Hash::make(
                        self::defaultPassword($payload['last_name'])
                    ),
                ]);
            }

            $initials = strtoupper(
                substr($payload['first_name'], 0, 1) . substr($payload['last_name'], 0, 1)
            );

            $user->client()->updateOrCreate(
                [
                    'user_id' => $user->user_id,
                ],
                [
                    'first_name' => $payload['first_name'],
                    'middle_name' => $payload['middle_name'] ?? null,
                    'last_name' => $payload['last_name'],
                    'location_id' => $payload['location_id'] ?? null,
                    'phone_number' => $payload['phone_number'] ?? null,
                    'occupation' => $payload['occupation'] ?? null,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . $initials,
                ]
            );

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

    public function update(string $user_id, array $payload)
    {
        return User::where('user_id', $user_id)->update($payload);
    }
}
