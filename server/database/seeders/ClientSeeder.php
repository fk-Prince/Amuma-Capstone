<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'email' => 'princesestoso2@gmail.com',
                'first_name' => 'Prince',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234502',
            ],
            [
                'email' => 'princesestoso3@gmail.com',
                'first_name' => 'Prince',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234503',
            ],
        ];

        $location = Location::firstOrCreate(
            [
                'street'   => 'J.P. Laurel Avenue',
                'city'     => 'Davao City',
                'province' => 'Davao del Sur',
                'country'  => 'Philippines',
            ],
            [
                'latitude'  => 7.1907,
                'longitude' => 125.4553,
            ]
        );

        foreach ($clients as $client) {
            $user = User::firstOrCreate(
                ['email' => $client['email']],
                [
                    'password' => Hash::make('password'),
                    'provider' => 'local',
                ]
            );

            $initials = strtoupper(
                substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)
            );

            $user->client()->updateOrCreate(
                ['user_id' => $user->user_id],
                [
                    'first_name' => $client['first_name'],
                    'last_name' => $client['last_name'],
                    'phone_number' => $client['phone_number'],
                    'location_id' => $location->location_id,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . $initials,
                    'is_verified' => true,
                ]
            );
        }
    }
}
