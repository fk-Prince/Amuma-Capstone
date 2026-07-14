<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
