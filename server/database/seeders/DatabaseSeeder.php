<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlatformAdminSeeder::class,
            ModuleSeeder::class,
            PlanSeeder::class
        ]);
        // $location = Location::create([
        //     'street' => 'Roxas Avenue',
        //     'city' => 'Davao City',
        //     'province' => 'Davao del Sur',
        //     'country' => 'Philippines',
        //     'latitude' => '7.0731',
        //     'longitude' => '125.6128'
        // ]);

        // $user = User::factory()->create([
        //     'first_name' => 'Prince',
        //     'last_name' => 'Sestoso',
        //     'location_id' => $location->location_id,
        //     'phone_number' => '123',
        //     'email' => 'prince.sestoso@gmail.com',
        //     'password' => Hash::make('password'),
        //     'avatar' => 'https://ui-avatars.com/api/?name=' . $initials
        // ]);

    }
}
