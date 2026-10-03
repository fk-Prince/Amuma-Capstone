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
                'middle_name' => 'Two',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234502',
            ],
            [
                'email' => 'princesestoso3@gmail.com',
                'first_name' => 'Prince',
                'middle_name' => 'Three',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234503',
            ],
            [
                'email' => 'princesestoso4@gmail.com',
                'first_name' => 'Prince',
                'middle_name' => 'Four',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234504',
            ],
            [
                'email' => 'princesestoso5@gmail.com',
                'first_name' => 'Prince',
                'middle_name' => 'Five',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234505',
            ],
            [
                'email' => 'princesestoso6@gmail.com',
                'first_name' => 'Prince',
                'middle_name' => 'Six',

                'last_name' => 'Sestoso',
                'phone_number' => '9171234506',
            ],
            [
                'email' => 'princesestoso7@gmail.com',
                'first_name' => 'Prince',
                'middle_name' => 'Seven',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234506',
            ],
            [
                'email' => 'princesestoso8@gmail.com',
                'first_name' => 'Prince',
                'middle_name' => 'Eight',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234506',
            ],
            [
                'email' => 'princesestoso9@gmail.com',
                'first_name' => 'Prince',
                'middle_name' => 'Nine',
                'last_name' => 'Sestoso',
                'phone_number' => '9171234506',
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
                ]
            );
        }
    }
}
