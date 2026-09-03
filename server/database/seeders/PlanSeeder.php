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
                'description' => 'Unlock the homecare module of AMUMA — book and schedule home visits, assign caregivers with QR attendance, and keep every patient record in one system.',
                'monthly_price' => 2500,
                'yearly_price' => 28000,
            ],
            [
                'plan_code' => 'B',
                'name' => 'In-house Facility',
                'description' => 'Unlock the facility module of AMUMA — manage admissions, rooms and beds, room contracts, and in-house patient care from a single dashboard.',
                'monthly_price' => 3500,
                'yearly_price' => 40000,
            ],
            [
                'plan_code' => 'C',
                'name' => 'Hybrid',
                'description' => 'Unlock both modules of AMUMA — run home visits and in-house admissions side by side, on one subscription and one set of patient records.',
                'monthly_price' => 4500,
                'yearly_price' => 51000,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['plan_code' => $plan['plan_code']],
                $plan
            );
        }
    }
}
