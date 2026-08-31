<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    private const SERVICES = [
        [
            'category_name' => 'Wound Care',
            'service_name' => 'Wound Care',
            'price' => 850.00,
            'maximum_duration' => '01:00:00',
        ],
        [
            'category_name' => 'Rehabilitation Services',
            'service_name' => 'Physical Therapy',
            'price' => 1200.00,
            'maximum_duration' => '01:30:00',
        ],
        [
            'category_name' => 'Medication Services',
            'service_name' => 'Medication Management',
            'price' => 500.00,
            'maximum_duration' => '00:30:00',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Vital Signs Monitoring',
            'price' => 400.00,
            'maximum_duration' => '00:30:00',
        ],
        [
            'category_name' => 'Medication Services',
            'service_name' => 'IV Therapy',
            'price' => 1100.00,
            'maximum_duration' => '01:00:00',
        ],
        [
            'category_name' => 'Post-Surgical Care',
            'service_name' => 'Post-Surgical Care',
            'price' => 1500.00,
            'maximum_duration' => '02:00:00',
        ],
    ];

    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        foreach ($branches as $branch) {
            $categories = [];

            foreach (self::SERVICES as $service) {
                $categoryName = $service['category_name'];

                if (!isset($categories[$categoryName])) {
                    $categories[$categoryName] = Category::firstOrCreate([
                        'branch_id' => $branch->branch_id,
                        'category_name' => $categoryName,
                    ]);
                }

                Service::firstOrCreate(
                    [
                        'branch_id' => $branch->branch_id,
                        'service_name' => $service['service_name'],
                    ],
                    [
                        'category_id' => $categories[$categoryName]->category_id,
                        'price' => $service['price'],
                        'maximum_duration' => $service['maximum_duration'],
                        'is_available' => true,
                        'type' => 'both',
                    ]
                );
            }
        }
    }
}
