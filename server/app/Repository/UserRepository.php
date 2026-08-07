<?php

namespace App\Repository;

use App\Models\Auth;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserRepository
{
    public function __construct(
        private LocationRepository $locationRepository,
    ) {}

    public function create(array $payload)
    {
        return User::create($payload);
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
                $password = strtolower($payload['last_name']) . rand(100000, 999999);
                $user = User::create([
                    'email' => $payload['email'],
                    'first_name' => $payload['first_name'],
                    'middle_name' => $payload['middle_name'] ?? null,
                    'last_name' => $payload['last_name'],
                    'location_id' => $payload['location_id'] ?? null,
                    'phone_number' => $payload['phone_number'] ?? null,
                    'occupation' => $payload['occupation'] ?? null,
                    'password' => Hash::make($password),
                ]);
            }
            return Client::updateOrCreate(
                [
                    'user_id' => $user->user_id,
                ],
                $payload
            );
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
