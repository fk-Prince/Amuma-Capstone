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
        $homecare = 'Unlock the homecare module of AMUMA — book and schedule home visits, assign caregivers with QR attendance, and keep every patient record in one system.';
        $facility = 'Unlock the facility module of AMUMA — manage admissions, rooms and beds, room contracts, and in-house patient care from a single dashboard.';
        $hybrid = 'Unlock both modules of AMUMA — run home visits and in-house admissions side by side, on one subscription and one set of patient records.';

        $plans = [
            ['plan_code' => 'A', 'type' => Plan::TYPE_SME, 'name' => 'Homecare Services', 'description' => $homecare, 'price' => 12000, 'additional_branch_price' => 12000],
            ['plan_code' => 'A', 'type' => Plan::TYPE_ENTERPRISE, 'name' => 'Homecare Services', 'description' => $homecare, 'price' => 56000, 'additional_branch_price' => 12000],
            ['plan_code' => 'B', 'type' => Plan::TYPE_SME, 'name' => 'In-house Facility', 'description' => $facility, 'price' => 15000, 'additional_branch_price' => 15000],
            ['plan_code' => 'B', 'type' => Plan::TYPE_ENTERPRISE, 'name' => 'In-house Facility', 'description' => $facility, 'price' => 66000, 'additional_branch_price' => 15000],
            ['plan_code' => 'C', 'type' => Plan::TYPE_SME, 'name' => 'Hybrid', 'description' => $hybrid, 'price' => 20000, 'additional_branch_price' => 20000],
            ['plan_code' => 'C', 'type' => Plan::TYPE_ENTERPRISE, 'name' => 'Hybrid', 'description' => $hybrid, 'price' => 100000, 'additional_branch_price' => 20000],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['plan_code' => $plan['plan_code'], 'type' => $plan['type']],
                $plan
            );
        }
    }
}
