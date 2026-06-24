<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Price;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $location = Location::create([
            'street' => 'Roxas Avenue',
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
            'country' => 'Philippines',
            'latitude' => '7.0731',
            'longitude' => '125.6128'
        ]);

        $user = User::factory()->create([
            'first_name' => 'Prince',
            'last_name' => 'Sestoso',
            'location_id' => $location->location_id,
            'phone_number' => '123',
            'email' => 'prince.sestoso@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $role = Role::create([
            'role_type' => 'owner',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Agency::create([
                'name' => "Agency {$i}",
                'description' => "Sample description for Agency {$i}",
                'location_id' => 1,
                'registered_by' =>  $user->user_id
            ]);
        }

        Role::insert([
            ['role_type' => 'branch_owner'],
            ['role_type' => 'administrator'],
            ['role_type' => 'accounting'],
            ['role_type' => 'admission'],
            ['role_type' => 'nurse'],
            ['role_type' => 'caregiver'],
        ]);

        $prices = [2500, 3500, 4500, 28000, 40000, 58000];

        foreach ($prices as $price) {
            Price::create([
                'price' => $price,
                'valid_from' => Carbon::now(),
            ]);
        }

        $plans = [
            [
                'plan_code' => 'A',
                'name' => 'Homecare Services',
                'description' => 'Receive professional care and support services from the comfort of your home through scheduled home visits and personalized assistance.',
                'monthly_price_id' => 1,
                'yearly_price_id' => 4,
            ],
            [
                'plan_code' => 'B',
                'name' => 'In-house Facility',
                'description' => 'Access comprehensive healthcare and wellness services within our facility, equipped with professional staff and modern amenities.',
                'monthly_price_id' => 2,
                'yearly_price_id' => 5,
            ],
            [
                'plan_code' => 'C',
                'name' => 'Hybrid',
                'description' => 'Enjoy a complete care package that combines personalized homecare services with full access to our in-house healthcare facility.',
                'monthly_price_id' => 3,
                'yearly_price_id' => 6,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
