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
            'description' => 'Recovery care after a lung infection. Includes on-time antibiotics, breathing and temperature checks every shift, and help with deep breathing and coughing exercises. Caregivers watch for worsening signs such as fever, fast breathing or low oxygen and report them right away. Oxygen and nebulization supplies are billed separately.',
            'price' => 15000.00,
        ],
        [
            'title' => 'Urinary Tract Infection',
            'description' => 'Antibiotics given on schedule until the course is finished, extra fluids to flush the infection, and close attention to hygiene and toileting. Caregivers monitor temperature, pain and urine changes, and help prevent repeat infections.',
            'price' => 4500.00,
        ],
        [
            'title' => 'Hypertension Management',
            'description' => 'Regular blood pressure checks, maintenance medication given on time, and a low-salt diet. Readings are recorded so the doctor can adjust medication when needed. Caregivers watch for warning signs such as headache, dizziness or chest pain and report them right away.',
            'price' => 4000.00,
        ],
        [
            'title' => 'Diabetes Management',
            'description' => 'Blood sugar checks before meals and at bedtime, insulin or oral medication given on schedule, and meals planned to the patient\'s diet. Caregivers check the feet daily for wounds and watch for signs of low or high blood sugar.',
            'price' => 6500.00,
        ],
        [
            'title' => 'Stroke Recovery',
            'description' => 'Daily care for patients after a stroke. Includes help with movement and transfers, positioning to prevent bed sores, supervised meals for patients with swallowing difficulty, and support with rehabilitation exercises. Blood pressure and medication are monitored closely to lower the risk of another stroke.',
            'price' => 32500.00,
        ],
        [
            'title' => 'Hip Fracture Recovery',
            'description' => 'Care after a hip fracture or hip surgery. Includes pain management, wound checks, safe transfers, and gradual mobility training with a walker. Caregivers help with toileting and positioning and take steps to prevent falls, blood clots and bed sores during recovery.',
            'price' => 32500.00,
        ],
        [
            'title' => 'Dehydration',
            'description' => 'Short-term care to restore fluids and electrolytes. Caregivers encourage and track fluid intake, record urine output, and watch for signs such as dry mouth, weakness or confusion. Abnormal signs are reported to the nurse for follow-up.',
            'price' => 2500.00,
        ],
        [
            'title' => 'Pressure Ulcer Care',
            'description' => 'Wound cleaning and dressing as ordered, turning and repositioning every two hours, and pressure-relief cushions or mattresses. Caregivers keep the skin clean and dry, support good nutrition for healing, and watch the wound for signs of infection.',
            'price' => 11500.00,
        ],
        [
            'title' => 'Dementia & Alzheimer\'s Care',
            'description' => 'Supervised daily care for patients with memory loss. Includes a steady daily routine, help with bathing, dressing and eating, medication monitoring, and measures to prevent wandering and falls. Caregivers use calm, patient communication and simple activities to reduce confusion and agitation.',
            'price' => 9000.00,
        ],
        [
            'title' => 'Congestive Heart Failure Care',
            'description' => 'Daily weight checks, fluid and salt limits, and heart medication given on schedule. Caregivers monitor blood pressure, heart rate and swelling in the legs, and report any breathlessness or sudden weight gain right away.',
            'price' => 16000.00,
        ],
        [
            'title' => 'Chronic Kidney Disease Care',
            'description' => 'Fluid and diet control based on the doctor\'s orders, medication given on schedule, and daily tracking of weight, blood pressure and urine output. Caregivers watch for swelling, fatigue or breathing changes and coordinate lab follow-ups as needed.',
            'price' => 14000.00,
        ],
        [
            'title' => 'COPD Management',
            'description' => 'Care for patients with long-term breathing problems. Includes inhalers and breathing medication on schedule, oxygen saturation checks, positioning that makes breathing easier, and help with breathing exercises. Caregivers watch for shortness of breath or color changes and report them right away. Oxygen and nebulization supplies are billed separately.',
            'price' => 14000.00,
        ],
        [
            'title' => 'Parkinson\'s Disease Care',
            'description' => 'Medication given at exact times to control tremors and stiffness, help with walking, transfers and daily tasks, and close fall prevention. Caregivers support safe eating and swallowing and encourage gentle exercise to keep the patient moving.',
            'price' => 16000.00,
        ],
        [
            'title' => 'Osteoarthritis & Pain Management',
            'description' => 'Pain medication given on schedule, warm compresses, and gentle exercises to keep the joints moving. Caregivers help with walking, standing and daily tasks to reduce strain on painful joints and prevent falls.',
            'price' => 5000.00,
        ],
        [
            'title' => 'Dysphagia Management',
            'description' => 'Safe feeding for patients who have trouble swallowing. Meals are prepared in the right texture, with thickened liquids when needed. The patient is fed slowly, sitting upright, under supervision. Caregivers watch for coughing or choking during meals and keep the patient upright afterward to prevent aspiration.',
            'price' => 8000.00,
        ],
        [
            'title' => 'Post-Surgical Recovery',
            'description' => 'Care after an operation. Includes wound checks and dressing changes, pain medication on schedule, monitoring of vital signs, and early, safe movement to speed recovery. Any fever, bleeding or unusual pain is reported to the nurse right away.',
            'price' => 20000.00,
        ],
        [
            'title' => 'Osteoporosis Management',
            'description' => 'Calcium and vitamin D supplements given as prescribed, a bone-healthy diet, and gentle supervised movement. Caregivers help with walking and transfers and keep the room safe to prevent falls and fractures.',
            'price' => 4500.00,
        ],
        [
            'title' => 'Sepsis Recovery',
            'description' => 'Close follow-up care after a serious infection and hospital stay. Includes completing the antibiotic course, vital sign checks every shift, and careful monitoring for any return of infection. Caregivers help the patient regain strength with proper nutrition, rest and gradual movement.',
            'price' => 27500.00,
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
                DiagnosisCase::updateOrCreate(
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
