<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DiagnosisCase;
use Database\Seeders\Concerns\SeedsBranches;
use Illuminate\Database\Seeder;

class DiagnosisCaseSeeder extends Seeder
{
    use SeedsBranches;

    private const CASES = [
        [
            'title' => 'Pneumonia',
            'description' => 'Lung infection requiring antibiotics, oxygen support and close monitoring.',
            'price' => 30000.00,
        ],
        [
            'title' => 'Urinary Tract Infection',
            'description' => 'Infection of the urinary tract treated with antibiotics and hydration.',
            'price' => 6000.00,
        ],
        [
            'title' => 'Hypertension Management',
            'description' => 'Blood pressure stabilization, medication adjustment and monitoring.',
            'price' => 5000.00,
        ],
        [
            'title' => 'Diabetes Management',
            'description' => 'Blood sugar control, insulin adjustment and diet supervision.',
            'price' => 8000.00,
        ],
        [
            'title' => 'Stroke Recovery',
            'description' => 'Post-stroke care including rehabilitation and swallowing support.',
            'price' => 50000.00,
        ],
        [
            'title' => 'Hip Fracture Recovery',
            'description' => 'Post-operative care, pain management and mobility training.',
            'price' => 55000.00,
        ],
        [
            'title' => 'Dehydration',
            'description' => 'Fluid replacement and electrolyte monitoring.',
            'price' => 3000.00,
        ],
        [
            'title' => 'Pressure Ulcer Care',
            'description' => 'Wound dressing, repositioning and infection prevention.',
            'price' => 10000.00,
        ],
        [
            'title' => 'Dementia & Alzheimer\'s Care',
            'description' => 'Supervised daily care, wandering prevention and medication monitoring for memory loss.',
            'price' => 15000.00,
        ],
        [
            'title' => 'Congestive Heart Failure Care',
            'description' => 'Daily weight and fluid monitoring, medication management and symptom watch.',
            'price' => 25000.00,
        ],
        [
            'title' => 'Chronic Kidney Disease Care',
            'description' => 'Fluid and diet control, medication review and regular renal monitoring.',
            'price' => 20000.00,
        ],
        [
            'title' => 'COPD Management',
            'description' => 'Oxygen support, inhaler therapy and breathing monitoring.',
            'price' => 18000.00,
        ],
        [
            'title' => 'Parkinson\'s Disease Care',
            'description' => 'Medication timing, mobility support and fall prevention.',
            'price' => 22000.00,
        ],
        [
            'title' => 'Osteoarthritis & Pain Management',
            'description' => 'Joint pain control, mobility assistance and gentle exercise support.',
            'price' => 7000.00,
        ],
        [
            'title' => 'Dysphagia Management',
            'description' => 'Modified diet, thickened liquids and supervised, upright feeding.',
            'price' => 9000.00,
        ],
        [
            'title' => 'Post-Surgical Recovery',
            'description' => 'Wound checks, pain control and early mobility after an operation.',
            'price' => 35000.00,
        ],
        [
            'title' => 'Osteoporosis Management',
            'description' => 'Calcium and vitamin D supplementation, fall prevention and bone-health monitoring.',
            'price' => 6500.00,
        ],
        [
            'title' => 'Sepsis Recovery',
            'description' => 'Antibiotic follow-through, vital sign monitoring and infection surveillance.',
            'price' => 45000.00,
        ],
    ];

    public function run(): void
    {
        $branches = $this->seedBranches();

        if ($branches->isEmpty()) {
            $this->command?->warn('No branches found. Seed branches first.');
            return;
        }

        foreach ($branches as $branch) {
            if (!$branch->hasFacilitySubscription()) {
                continue;
            }

            foreach (self::CASES as $case) {
                DiagnosisCase::firstOrCreate(
                    [
                        'branch_id' => $branch->branch_id,
                        'title' => $case['title'],
                    ],
                    [
                        'description' => $case['description'],
                        'price' => $case['price'],
                    ]
                );
            }
        }
    }
}
