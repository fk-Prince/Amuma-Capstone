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
            'category_name' => 'Nursing Care',
            'service_name' => 'Wound Care & Dressing Management',
            'description' => 'A nurse checks the wound, gently cleans it, and applies a fresh dressing, keeping an eye out for signs of infection. Ideal for surgical wounds, pressure sores, and injuries that need regular attention while healing at home.',
            'price' => 850.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Medication Administration & IV Therapy',
            'description' => 'A nurse gives prescribed medications and IV therapy on time and exactly as ordered, then monitors how the patient responds. Spares the patient a trip to the hospital while keeping treatment safe and consistent.',
            'price' => 1100.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Post-Operative & Post-Hospital Recovery',
            'description' => 'Hands-on nursing support after surgery or a hospital stay, covering wound checks, medication follow-through, and close monitoring of recovery. Helps patients heal comfortably at home and lowers the risk of readmission.',
            'price' => 1500.00,
            'maximum_duration' => '02:00:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Chronic Disease Management & Vital Monitoring',
            'description' => 'Regular checks of blood pressure, heart rate, temperature, oxygen level, and blood sugar, with clear notes on any changes. Helps patients living with conditions such as hypertension, diabetes, or heart disease stay stable and catch problems early.',
            'price' => 400.00,
            'maximum_duration' => '00:30:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Palliative Care',
            'service_name' => 'Pain & Symptom Management',
            'description' => 'A nurse works with the patient\'s physicians to ease pain, nausea, breathlessness, and other difficult symptoms. The focus is on keeping the patient as comfortable and at ease as possible.',
            'price' => 900.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'both',
        ],
        [
            'category_name' => 'Palliative Care',
            'service_name' => 'Emotional, Spiritual & Psychological Support',
            'description' => 'A caring presence who listens, keeps the patient company, and supports their emotional, spiritual, and psychological needs. Offers comfort, reassurance, and a sense of peace during a difficult time.',
            'price' => 700.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'caregiver',
        ],
        [
            'category_name' => 'Palliative Care',
            'service_name' => 'End-of-Life Comfort Care',
            'description' => 'Gentle, round-the-patient care that puts comfort and dignity first during the final stage of life, provided by a nurse with a caregiver assisting. Lets the patient stay at home, surrounded by the people and things they love.',
            'price' => 1500.00,
            'maximum_duration' => '02:00:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Palliative Care',
            'service_name' => 'Family Grief & Bereavement Assistance',
            'description' => 'Compassionate support for family members before and after a loved one\'s passing, including someone to talk to and guidance on what to expect. Helps families cope with grief and loss at their own pace.',
            'price' => 600.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'caregiver',
        ],
        [
            'category_name' => 'Memory Care',
            'service_name' => 'Dementia & Alzheimer\'s Tailored Daily Care',
            'description' => 'Patient, routine-based help with bathing, dressing, meals, and daily activities, shaped around the needs of someone living with dementia or Alzheimer\'s. Familiar routines and calm, reassuring support make each day easier for the patient and family.',
            'price' => 1000.00,
            'maximum_duration' => '02:00:00',
            'performed_by' => 'caregiver',
        ],
        [
            'category_name' => 'Memory Care',
            'service_name' => 'Cognitive Enrichment & Safe Engagement',
            'description' => 'Simple, enjoyable activities such as looking through photos, music, puzzles, and conversation that keep the mind active and the patient calm. Always done in a safe setting, at a pace the patient is comfortable with.',
            'price' => 600.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'caregiver',
        ],
        [
            'category_name' => 'Memory Care',
            'service_name' => 'Wandering Prevention & Home Environment Safety',
            'description' => 'Attentive supervision plus practical safety checks around the home, such as secure doors, clear walkways, and hazard-proofing. Reduces the risk of wandering, falls, and accidents so families can rest easier.',
            'price' => 500.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'caregiver',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Catheter & Ostomy Care',
            'description' => 'A nurse handles catheter and ostomy care, including cleaning, changing supplies, and checking the skin for irritation or infection. Keeps the patient comfortable, clean, and protected from complications.',
            'price' => 750.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Blood Sugar Monitoring & Insulin Administration',
            'description' => 'A nurse checks blood sugar levels and gives insulin as prescribed, then teaches the patient and family what to watch for. Helps keep diabetes under control without frequent clinic visits.',
            'price' => 650.00,
            'maximum_duration' => '00:45:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Tube Feeding & Nutrition Support',
            'description' => 'A nurse prepares and administers tube feedings, cares for the feeding site, and monitors hydration and nutrition. Ensures patients who cannot eat normally get the nourishment they need, safely.',
            'price' => 900.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Nursing Care',
            'service_name' => 'Respiratory Care & Oxygen Support',
            'description' => 'A nurse monitors breathing and oxygen levels, sets up and checks oxygen equipment, and assists with nebulizer treatments. Helps patients with lung conditions breathe easier and stay safe at home.',
            'price' => 950.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'nurse',
        ],
        [
            'category_name' => 'Palliative Care',
            'service_name' => 'Comfort Positioning & Personal Care',
            'description' => 'Gentle repositioning, bathing, mouth care, and skin care that keep the patient clean, comfortable, and free from pressure sores. Delivered with patience and respect for the patient\'s dignity.',
            'price' => 800.00,
            'maximum_duration' => '01:30:00',
            'performed_by' => 'both',
        ],
        [
            'category_name' => 'Memory Care',
            'service_name' => 'Medication Reminders & Daily Routine Support',
            'description' => 'Friendly reminders and guidance to take medicines on time and follow a steady daily routine of meals, rest, and activities. Predictable days help the patient feel secure and reduce confusion.',
            'price' => 450.00,
            'maximum_duration' => '00:45:00',
            'performed_by' => 'both',
        ],
        [
            'category_name' => 'Memory Care',
            'service_name' => 'Safe Mobility & Fall Prevention',
            'description' => 'Careful help with walking, transfers, and moving around the home, plus simple checks that remove tripping hazards. Keeps the patient active and steady while lowering the risk of falls.',
            'price' => 650.00,
            'maximum_duration' => '01:00:00',
            'performed_by' => 'both',
        ],
        [
            'category_name' => 'Rehabilitation Services',
            'service_name' => 'Physical Therapy Exercises',
            'description' => 'Guided strengthening, balance, and range-of-motion exercises that follow the patient\'s therapy plan. Builds strength and independence after illness, injury, or surgery, with someone there to keep each session safe.',
            'price' => 1200.00,
            'maximum_duration' => '01:30:00',
            'performed_by' => 'both',
        ],
        [
            'category_name' => 'Rehabilitation Services',
            'service_name' => 'Post-Stroke Mobility & Recovery Support',
            'description' => 'Patient, step-by-step support with movement, speech practice, and daily tasks for patients recovering from a stroke. Encourages steady progress while keeping each activity safe and manageable.',
            'price' => 1300.00,
            'maximum_duration' => '02:00:00',
            'performed_by' => 'nurse',
        ],
    ];

    public function run(): void
    {
        $branches = Branch::orderBy('branch_id')->get();

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

                Service::updateOrCreate(
                    [
                        'branch_id' => $branch->branch_id,
                        'service_name' => $service['service_name'],
                    ],
                    [
                        'category_id' => $categories[$categoryName]->category_id,
                        'description' => $service['description'],
                        'price' => $service['price'],
                        'maximum_duration' => $service['maximum_duration'],
                        'performed_by' => $service['performed_by'],
                        'is_available' => true,
                        'type' => 'both',
                    ]
                );
            }
        }
    }
}
