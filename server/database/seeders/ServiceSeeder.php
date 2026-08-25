<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    private const CATEGORY_NAME = 'Medical Services';

    // Medical-type schedule services select from this catalog (see
    // ScheduleService::TYPE_MEDICAL) — ADL schedule services have no
    // catalog Service at all (schedule_services.service_id is nullable).
    private const SERVICES = [
        [
            'service_name' => 'Wound Care',
            'price' => 850.00,
            'maximum_duration' => '01:00:00',
        ],
        [
            'service_name' => 'Physical Therapy',
            'price' => 1200.00,
            'maximum_duration' => '01:30:00',
        ],
        [
            'service_name' => 'Nursing Care',
            'price' => 950.00,
            'maximum_duration' => '02:00:00',
        ],
        [
            'service_name' => 'Medication Management',
            'price' => 500.00,
            'maximum_duration' => '00:30:00',
        ],
        [
            'service_name' => 'Vital Signs Monitoring',
            'price' => 400.00,
            'maximum_duration' => '00:30:00',
        ],
        [
            'service_name' => 'IV Therapy',
            'price' => 1100.00,
            'maximum_duration' => '01:00:00',
        ],
        [
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
            $category = Category::firstOrCreate([
                'branch_id' => $branch->branch_id,
                'category_name' => self::CATEGORY_NAME,
            ]);

            foreach (self::SERVICES as $service) {
                Service::firstOrCreate(
                    [
                        'branch_id' => $branch->branch_id,
                        'category_id' => $category->category_id,
                        'service_name' => $service['service_name'],
                    ],
                    [
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
