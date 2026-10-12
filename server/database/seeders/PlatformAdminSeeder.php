<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    private const ADMINS = [
        [
            'email' => 'prince.sestoso@gmail.com',
            'first_name' => 'Prince',
            'last_name' => 'Sestoso',
            'phone_number' => '9771171913',
        ],
        [
            'email' => 'nicollette.libunao@gmail.com',
            'first_name' => 'Nicollette',
            'last_name' => 'Libunao',
            'phone_number' => '9602602240',
        ],
        [
            'email' => 'gem.mamale@gmail.com',
            'first_name' => 'Gem',
            'last_name' => 'Mamale',
            'phone_number' => '9811400436',
        ],
        [
            'email' => 'hannah.magsipoc@gmail.com',
            'first_name' => 'Hannah',
            'last_name' => 'Magsipoc',
            'phone_number' => '9093324911',
        ],
    ];

    public function run(): void
    {
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

        foreach (self::ADMINS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'password' => Hash::make('password'),
                    'provider' => 'local',
                ]
            );

            $initials = strtoupper(
                substr($data['first_name'], 0, 1) . substr($data['last_name'], 0, 1)
            );

            $admin = PlatformAdmin::firstOrCreate(
                ['user_id' => $user->user_id],
                [
                    'location_id' => $location->location_id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone_number' => $data['phone_number'],
                    'avatar' => 'https://ui-avatars.com/api/?name=' . $initials,
                ]
            );

            if (! $admin->location_id) {
                $admin->update(['location_id' => $location->location_id]);
            }
        }
    }
}
