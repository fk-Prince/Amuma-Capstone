<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Price;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
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

        Role::insert([
            ['role_type' => 'branch_owner'],
            ['role_type' => 'administrator'],
            ['role_type' => 'accounting'],
            ['role_type' => 'admission'],
            ['role_type' => 'nurse'],
            ['role_type' => 'caregiver'],
        ]);

        UserRole::create([
            'user_id' =>  $user->user_id,
            'role_id' =>  $role->role_id,
        ]);


        $plans = [
            [
                'plan_code' => 'A',
                'name' => 'Homecare Services',
                'description' => 'Receive professional care and support services from the comfort of your home through scheduled home visits and personalized assistance.',
                'monthly_price' => 2500,
                'yearly_price' => 28000,
            ],
            [
                'plan_code' => 'B',
                'name' => 'In-house Facility',
                'description' => 'Access comprehensive healthcare and wellness services within our facility, equipped with professional staff and modern amenities.',
                'monthly_price' => 3500,
                'yearly_price' => 40000,
            ],
            [
                'plan_code' => 'C',
                'name' => 'Hybrid',
                'description' => 'Enjoy a complete care package that combines personalized homecare services with full access to our in-house healthcare facility.',
                'monthly_price' => 4500,
                'yearly_price' => 51000,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
