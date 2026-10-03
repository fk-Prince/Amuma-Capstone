<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DiagnosisCase;
use Illuminate\Database\Seeder;

class DiagnosisCaseSeeder extends Seeder
{
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
    ];

    public function run(): void
    {
        $branch = Branch::orderBy('branch_id')->first();

        if (!$branch) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
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
