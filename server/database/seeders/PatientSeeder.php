<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\AdmissionPeriod;
use App\Models\Bed;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\Client;
use App\Models\EmployeeBranch;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Patient;
use App\Models\PatientAccess;
use App\Models\PatientActivity;
use App\Models\PatientAdmission;
use App\Models\PatientAssessment;
use App\Models\PatientDiagnosis;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Models\Vital;
use App\Utils\AdmissionHelper;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PatientSeeder extends Seeder
{
    private const BRANCH_NAME = 'AMUMA Davao City';

    public function run(): void
    {
        $branch = Branch::where('name', self::BRANCH_NAME)->first();

        if (!$branch) {
            $this->command->warn('Branch "' . self::BRANCH_NAME . '" not found. Seed branches first.');
            return;
        }

        foreach ($this->patients() as $data) {
            $exists = Patient::where('branch_id', $branch->branch_id)
                ->where('first_name', $data['profile']['first_name'])
                ->where('last_name', $data['profile']['last_name'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::transaction(function () use ($branch, $data) {
                $patient = $this->createPatient($branch, $data);

                $this->createAssessment($patient, $data['assessment']);
                $this->createDiagnoses($patient, $data['diagnoses']);
                $this->createMedications($patient, $data['medications']);
                $this->createVitals($patient, $data['vitals']);
                $this->createActivities($patient, $data['activities']);
                $this->createGuardian($patient, $data['guardian']);

                if (isset($data['admission'])) {
                    $this->createAdmission($branch, $patient, $data['admission']);
                }

                if (isset($data['homecare'])) {
                    $this->createHomecare($branch, $patient, $data);
                }
            });
        }
    }

    private function createPatient(Branch $branch, array $data): Patient
    {
        $location = Location::create([
            'street' => $data['address']['street'],
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
            'country' => 'Philippines',
            'full_address' => "{$data['address']['street']}, {$data['address']['barangay']}, Davao City, Davao del Sur",
        ]);

        return Patient::create($data['profile'] + [
            'branch_id' => $branch->branch_id,
            'location_id' => $location->location_id,
            'citizenship' => 'Filipino',
        ]);
    }

    private function createAssessment(Patient $patient, array $assessment): void
    {
        PatientAssessment::create($assessment + ['patient_id' => $patient->patient_id]);
    }

    private function createDiagnoses(Patient $patient, array $diagnoses): void
    {
        foreach ($diagnoses as [$diagnosis, $monthsAgo, $notes]) {
            PatientDiagnosis::create([
                'patient_id' => $patient->patient_id,
                'diagnosis' => $diagnosis,
                'diagnosis_date' => now()->subMonths($monthsAgo)->toDateString(),
                'diagnosis_notes' => $notes,
            ]);
        }
    }

    private function createMedications(Patient $patient, array $medications): void
    {
        $markedBy = $this->medicationMarker($patient->branch_id);

        foreach ($medications as $medication) {
            $record = Medication::create($medication + [
                'patient_id' => $patient->patient_id,
                'duration' => '30',
                'frequency' => 'everyday',
                'start_date' => now()->subDays(14)->toDateString(),
                'recorded_at' => now()->subDays(14),
            ]);

            $this->createTakenDoses($record, $markedBy);
        }
    }

    private function createTakenDoses(Medication $medication, ?int $markedBy): void
    {
        if ($medication->kind !== 'Scheduled' || empty($medication->times)) {
            return;
        }

        $interval = [
            'every_2_days' => 2,
            'every_3_days' => 3,
            'every_week' => 7,
        ][$medication->frequency] ?? 1;

        $doses = [];

        for ($day = 0; $day < (int) $medication->duration; $day += $interval) {
            $date = $medication->start_date->copy()->addDays($day)->toDateString();

            foreach ($medication->times as $time) {
                $dueAt = Carbon::parse("{$date} {$time}");

                if ($dueAt->isFuture()) {
                    continue;
                }

                $doses[] = [
                    'date' => $date,
                    'time' => $time,
                    'status' => MedicationSchedule::STATUS_TAKEN,
                    'marked_by' => $markedBy,
                    'recorded_at' => $dueAt->copy()->addMinutes(10),
                ];
            }
        }

        $medication->schedules()->createMany($doses);
    }

    private function medicationMarker(int $branchId): ?int
    {
        return EmployeeBranch::query()
            ->join('employees', 'employees.employee_id', '=', 'employee_branches.employee_id')
            ->where('employee_branches.branch_id', $branchId)
            ->whereIn('employee_branches.role_name', [RoleEnum::Nurse->value, RoleEnum::Caregiver->value])
            ->orderByRaw('employee_branches.role_name = ? desc', [RoleEnum::Nurse->value])
            ->value('employees.user_id');
    }

    private function createVitals(Patient $patient, array $baseline): void
    {
        [$systolic, $diastolic, $heartRate, $glucose] = $baseline;

        foreach ([6, 4, 2, 0] as $step => $daysAgo) {
            Vital::create([
                'patient_id' => $patient->patient_id,
                'blood_pressure_systolic' => $systolic + [4, -2, 6, 0][$step],
                'blood_pressure_diastolic' => $diastolic + [2, 0, 3, -1][$step],
                'heart_rate' => $heartRate + [3, -1, 5, 0][$step],
                'respiratory_rate' => [18, 17, 19, 18][$step],
                'temperature' => [36.6, 36.8, 37.1, 36.7][$step],
                'oxygen_saturation' => [97, 98, 96, 98][$step],
                'blood_glucose' => $glucose + [12, -6, 18, 0][$step],
                'pain_level' => [2, 1, 3, 1][$step],
                'recorded_date' => now()->subDays($daysAgo)->toDateString(),
                'recorded_time' => ['08:00', '08:30', '09:00', '08:15'][$step],
                'notes' => [
                    'Morning check before breakfast.',
                    'Rested well overnight.',
                    'Slightly elevated after morning walk; rechecked and stable.',
                    'Routine check, no complaints.',
                ][$step],
            ]);
        }
    }

    private function createActivities(Patient $patient, array $activities): void
    {
        foreach ($activities as [$type, $title, $subtitle, $description, $daysAgo, $time]) {
            PatientActivity::create([
                'patient_id' => $patient->patient_id,
                'type' => $type,
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'occurred_at' => Carbon::parse(now()->subDays($daysAgo)->toDateString() . " {$time}"),
            ]);
        }
    }

    private function createGuardian(Patient $patient, array $guardian): void
    {
        $client = User::where('email', $guardian['email'])->first()?->client;

        if (!$client instanceof Client) {
            return;
        }

        PatientAccess::firstOrCreate(
            [
                'client_id' => $client->client_id,
                'patient_id' => $patient->patient_id,
            ],
            [
                'have_access' => true,
                'relationship_type' => $guardian['relationship'],
            ]
        );
    }

    private function createAdmission(Branch $branch, Patient $patient, array $admission): void
    {
        $contract = BranchContract::where('branch_id', $branch->branch_id)
            ->where('category', BranchContract::CAREGORY_FACILITY)
            ->where('accommodation_type', $admission['accommodation'])
            ->where('billing_cycle', $admission['billing_cycle'])
            ->where('is_active', true)
            ->first();

        $bed = Bed::query()
            ->where('status', Bed::STATUS_AVAILABLE)
            ->whereHas('room', function ($query) use ($branch, $admission) {
                $query->where('branch_id', $branch->branch_id)
                    ->whereRaw('LOWER(room_type) = ?', [strtolower($admission['accommodation'])]);
            })
            ->orderBy('bed_id')
            ->first();

        if (!$contract || !$bed) {
            $this->command->warn("Skipped admission for {$patient->first_name} {$patient->last_name}: no matching contract or available bed.");
            return;
        }

        $admittedAt = now()->subDays($admission['days_ago'])->setTime(9, 0);
        $endDate = AdmissionHelper::calculateEndDate($admittedAt->copy(), $contract->billing_cycle);

        $patientAdmission = PatientAdmission::create([
            'patient_id' => $patient->patient_id,
            'bed_id' => $bed->bed_id,
            'status' => PatientAdmission::STATUS_ADMITTED,
            'note' => $admission['note'],
            'admitted_at' => $admittedAt,
            'discharged_at' => $endDate,
        ]);

        $period = AdmissionPeriod::create([
            'patient_admission_id' => $patientAdmission->patient_admission_id,
            'branch_contract_id' => $contract->branch_contract_id,
            'start_date' => $admittedAt,
            'end_date' => $endDate,
            'status' => AdmissionPeriod::STATUS_ACTIVE,
            'reason' => AdmissionPeriod::REASON_ADMITTED,
        ]);

        $bed->update(['status' => Bed::STATUS_OCCUPIED]);

        $invoice = Invoice::create([
            'branch_id' => $branch->branch_id,
            'total_amount' => $contract->price,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceAdmissionLines()->create([
            'admission_period_id' => $period->admission_period_id,
            'price' => $contract->price,
        ]);

        DB::table('invoices')
            ->where('invoice_id', $invoice->invoice_id)
            ->update(['created_at' => $admittedAt]);

        DB::table('patient_admissions')
            ->where('patient_admission_id', $patientAdmission->patient_admission_id)
            ->update(['created_at' => $admittedAt, 'updated_at' => $admittedAt]);

        DB::table('patients')
            ->where('patient_id', $patient->patient_id)
            ->update(['created_at' => $admittedAt->copy()->subDay(), 'updated_at' => $admittedAt]);
    }

    private function createHomecare(Branch $branch, Patient $patient, array $data): void
    {
        $homecare = $data['homecare'];

        $location = Location::create([
            'street' => $homecare['street'],
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
            'country' => 'Philippines',
            'full_address' => "{$homecare['street']}, Davao City, Davao del Sur",
            'latitude' => $homecare['latitude'],
            'longitude' => $homecare['longitude'],
        ]);

        $schedule = Schedule::create([
            'patient_id' => $patient->patient_id,
            'location_id' => $location->location_id,
            'scheduled_at' => now()->addDays($homecare['in_days'])->setTime(...$homecare['time']),
            'status' => Schedule::STATUS_PENDING,
            'category' => 'Homecare',
            'note' => $homecare['note'],
        ]);

        $invoice = Invoice::create([
            'branch_id' => $branch->branch_id,
            'total_amount' => 0,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $total = 0;
        $bookedServices = [];
        $adlRate = null;

        if ($homecare['type'] === 'Medical') {
            $findService = fn() => Service::where('branch_id', $branch->branch_id)
                ->whereIn('type', ['online', 'both'])
                ->where('is_available', true)
                ->orderBy('service_id')
                ->first();

            $service = $findService();

            if (!$service) {
                $this->call(ServiceSeeder::class);
                $service = $findService();
            }


            if (!$service) {
                throw new RuntimeException(
                    "No available homecare service for branch {$branch->branch_id}; a medical visit can't be seeded without one."
                );
            }

            $scheduleService = $schedule->scheduleServices()->create([
                'service_id' => $service->service_id,
                'hours_booked' => null,
                'type' => 'Medical',
            ]);

            $scheduleService->invoiceServices()->create([
                'invoice_id' => $invoice->invoice_id,
                'price' => $service->price,
            ]);

            $total = (float) $service->price;
            $bookedServices[] = [
                'service_id' => $service->service_id,
                'service_name' => $service->service_name,
                'price' => (float) $service->price,
            ];
        } else {
            $adlRate = (float) (BranchContract::where('branch_id', $branch->branch_id)
                ->where('category', BranchContract::CAREGORY_HOMECARE)
                ->where('accommodation_type', BranchContract::ACCOMMODATION_TYPE_ADL)
                ->value('price') ?? 350);

            $scheduleService = $schedule->scheduleServices()->create([
                'service_id' => null,
                'hours_booked' => $homecare['hours'],
                'type' => 'ADL',
            ]);

            $total = $adlRate * $homecare['hours'];

            $scheduleService->invoiceServices()->create([
                'invoice_id' => $invoice->invoice_id,
                'price' => $total,
            ]);
        }

        $invoice->update(['total_amount' => $total]);

        $this->createBooking($branch, $patient, $data, $schedule, $invoice, [
            'type' => $homecare['type'],
            'date' => $schedule->scheduled_at->toDateString(),
            'prefered_time' => $schedule->scheduled_at->format('H:i'),
            'time_span' => $homecare['type'] === 'ADL' ? (string) $homecare['hours'] : null,
            'address' => $location->full_address,
            'latitude' => $homecare['latitude'],
            'longitude' => $homecare['longitude'],
            'services' => $bookedServices,
            'price' => $adlRate,
        ]);
    }

    private function createBooking(
        Branch $branch,
        Patient $patient,
        array $data,
        Schedule $schedule,
        Invoice $invoice,
        array $homecare
    ): void {
        $guardianUser = User::where('email', $data['guardian']['email'])->first();
        $client = $guardianUser?->client;
        $bookedAt = now()->subDays($data['homecare']['booked_days_ago'])->setTime(14, 30);

        $booking = Booking::create([
            'user_id' => $guardianUser?->user_id,
            'branch_id' => $branch->branch_id,
            'category' => Booking::CATEGORY_ONLINE,
            'booking_type' => Booking::BOOKINGTYPE_ONLINE,
            'status' => Booking::STATUS_APPROVED,
            'reviewed_by' => EmployeeBranch::where('branch_id', $branch->branch_id)
                ->where('role_name', RoleEnum::AgencyOwner->value)
                ->value('employee_id'),
            'valid_until' => $schedule->scheduled_at,
            'booking_data' => [
                'patient' => [
                    'first_name' => $patient->first_name,
                    'middle_name' => $patient->middle_name,
                    'last_name' => $patient->last_name,
                    'gender' => $patient->gender,
                    'citizenship' => $patient->citizenship,
                    'date_of_birth' => $patient->date_of_birth?->toDateString(),
                    'phone_number' => $patient->phone_number,
                    'height' => $patient->height,
                    'weight' => $patient->weight,
                    'blood_type' => $patient->blood_type,
                    'address' => $patient->location?->full_address,
                    'allergies' => $patient->allergies,
                ],
                'guardian' => [
                    'first_name' => $client?->first_name,
                    'middle_name' => $client?->middle_name,
                    'last_name' => $client?->last_name,
                    'phone_number' => $client?->phone_number,
                    'email' => $data['guardian']['email'],
                    'relationship' => $data['guardian']['relationship'],
                    'occupation' => $client?->occupation,
                    'address' => $client?->location?->full_address,
                ],
                'facility' => [],
                'homecare' => $homecare,
                'assessment' => [$data['assessment']],
                'diagnoses' => array_map(fn($diagnosis) => [
                    'diagnosis' => $diagnosis[0],
                    'diagnosis_date' => now()->subMonths($diagnosis[1])->toDateString(),
                    'diagnosis_notes' => $diagnosis[2],
                ], $data['diagnoses']),
                'reserved' => null,
                'payment' => [
                    'total_amount' => (float) $invoice->total_amount,
                    'payment_status' => 'pending',
                ],
            ],
        ]);

        $booking->patientBookings()->create([
            'patient_id' => $patient->patient_id,
            'invoice_id' => $invoice->invoice_id,
        ]);

        DB::table('bookings')
            ->where('booking_id', $booking->booking_id)
            ->update(['created_at' => $bookedAt, 'updated_at' => $bookedAt->copy()->addHours(3)]);

        DB::table('invoices')
            ->where('invoice_id', $invoice->invoice_id)
            ->update(['created_at' => $bookedAt->copy()->addHours(3)]);

        DB::table('patients')
            ->where('patient_id', $patient->patient_id)
            ->update(['created_at' => $bookedAt->copy()->addHours(3), 'updated_at' => $bookedAt->copy()->addHours(3)]);
    }

    private function patients(): array
    {
        return [
            [
                'profile' => [
                    'first_name' => 'Lourdes',
                    'middle_name' => 'Abellana',
                    'last_name' => 'Villarin',
                    'gender' => 'Female',
                    'height' => 152,
                    'weight' => 58.4,
                    'blood_type' => 'O+',
                    'date_of_birth' => '1946-03-14',
                    'phone_number' => '9171100001',
                    'allergies' => ['Penicillin', 'Shellfish'],
                ],
                'address' => ['street' => '123 Jacinto Street', 'barangay' => 'Poblacion District'],
                'assessment' => [
                    'condition' => 'wheelchair',
                    'mental_state' => 'forgetfulness',
                    'affect' => 'flat',
                    'behavior' => 'cooperative',
                    'communication' => 'Coherent & Logical',
                    'speech' => 'clear',
                    'bathing' => 2,
                    'transferring' => 2,
                    'toileting' => 3,
                    'grooming' => 3,
                    'eating' => 4,
                    'locomotion' => 2,
                    'dressing' => 3,
                ],
                'diagnoses' => [
                    ['Early-stage Alzheimer\'s disease', 18, 'Mild short-term memory loss; oriented to person and place. Family reports repeated questions.'],
                    ['Hypertension, Stage 1', 60, 'Controlled with medication. Target BP below 140/90.'],
                    ['Osteoarthritis of both knees', 36, 'Uses wheelchair for longer distances; short assisted walks encouraged.'],
                ],
                'medications' => [
                    ['name' => 'Donepezil', 'strength' => '5 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take at bedtime.', 'taken_for' => 'Memory', 'kind' => 'Scheduled', 'times' => ['21:00']],
                    ['name' => 'Amlodipine', 'strength' => '5 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take after breakfast.', 'taken_for' => 'Blood pressure', 'kind' => 'Scheduled', 'times' => ['08:00']],
                    ['name' => 'Paracetamol', 'strength' => '500 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Only for knee pain; at least 6 hours apart.', 'taken_for' => 'Joint pain', 'kind' => 'PRN', 'times' => null],
                ],
                'vitals' => [134, 84, 76, 108],
                'activities' => [
                    ['therapy', 'Memory stimulation session', 'Occupational therapy', 'Photo recall and sorting exercises; engaged for 30 minutes.', 5, '10:00'],
                    ['meal', 'Lunch', 'Low-sodium diet', 'Finished most of the meal; needed help cutting food.', 3, '12:00'],
                    ['activity', 'Garden stroll', 'Wheelchair assisted', 'Enjoyed 20 minutes outside with a caregiver.', 1, '16:00'],
                    ['appointment', 'Neurology follow-up', 'Dr. Reyes, SPMC', 'Cognitive screening repeated; no change in plan.', 0, '09:30'],
                ],
                'guardian' => ['email' => 'princesestoso6@gmail.com', 'relationship' => 'Daughter'],
                'admission' => ['accommodation' => 'COMMON', 'billing_cycle' => 'MONTHLY', 'days_ago' => 12, 'note' => 'Needs supervision at night due to wandering.'],
            ],
            [
                'profile' => [
                    'first_name' => 'Ernesto',
                    'middle_name' => 'Pacheco',
                    'last_name' => 'Galvez',
                    'gender' => 'Male',
                    'height' => 168,
                    'weight' => 71.2,
                    'blood_type' => 'A+',
                    'date_of_birth' => '1942-11-02',
                    'phone_number' => '9171100002',
                    'allergies' => ['Sulfa drugs'],
                ],
                'address' => ['street' => '45 Quimpo Boulevard', 'barangay' => 'Matina Crossing'],
                'assessment' => [
                    'condition' => 'stretcher',
                    'mental_state' => 'drowsy',
                    'affect' => 'flat',
                    'behavior' => 'lack_of_interaction',
                    'communication' => 'Impaired',
                    'speech' => 'aphasic',
                    'bathing' => 1,
                    'transferring' => 1,
                    'toileting' => 1,
                    'grooming' => 1,
                    'eating' => 2,
                    'locomotion' => 1,
                    'dressing' => 1,
                ],
                'diagnoses' => [
                    ['Post-stroke right hemiparesis', 4, 'Ischemic stroke four months ago. Right-sided weakness; bed-bound.'],
                    ['Type 2 diabetes mellitus', 120, 'On insulin. Monitor glucose before meals.'],
                    ['Dysphagia', 4, 'Soft diet with thickened liquids. Upright position during meals.'],
                ],
                'medications' => [
                    ['name' => 'Clopidogrel', 'strength' => '75 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Crush and mix with puree.', 'taken_for' => 'Stroke prevention', 'kind' => 'Scheduled', 'times' => ['08:00']],
                    ['name' => 'Insulin Glargine', 'strength' => '100 U/mL', 'dosage_amount' => 12, 'dosage_unit' => 'ml', 'route' => 'subcutaneous', 'instructions' => 'Inject in the abdomen, rotate sites.', 'taken_for' => 'Diabetes', 'kind' => 'Scheduled', 'times' => ['21:00']],
                    ['name' => 'Atorvastatin', 'strength' => '40 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take at night.', 'taken_for' => 'Cholesterol', 'kind' => 'Scheduled', 'times' => ['20:00']],
                ],
                'vitals' => [142, 88, 82, 156],
                'activities' => [
                    ['therapy', 'Passive range of motion', 'Physical therapy', 'Right arm and leg exercises; tolerated well.', 6, '09:00'],
                    ['meal', 'Breakfast', 'Soft diet, thickened liquids', 'Fed by caregiver; no coughing noted.', 2, '07:30'],
                    ['therapy', 'Speech therapy', 'Swallow training', 'Practised chin-tuck swallow with the therapist.', 1, '14:00'],
                    ['appointment', 'Diabetes review', 'Dr. Lim, Davao Doctors Hospital', 'Insulin dose kept the same; HbA1c due next month.', 0, '10:00'],
                ],
                'guardian' => ['email' => 'princesestoso3@gmail.com', 'relationship' => 'Son'],
                'admission' => ['accommodation' => 'VIP', 'billing_cycle' => 'MONTHLY', 'days_ago' => 5, 'note' => 'Turn every 2 hours to prevent pressure sores.'],
            ],
            [
                'profile' => [
                    'first_name' => 'Rosalinda',
                    'middle_name' => 'Tan',
                    'last_name' => 'Maglangit',
                    'gender' => 'Female',
                    'height' => 149,
                    'weight' => 49.8,
                    'blood_type' => 'B+',
                    'date_of_birth' => '1950-07-21',
                    'phone_number' => '9171100003',
                    'allergies' => ['Aspirin', 'Latex'],
                ],
                'address' => ['street' => '78 Bajada Road', 'barangay' => 'San Antonio'],
                'assessment' => [
                    'condition' => 'ambulatory',
                    'mental_state' => 'alert',
                    'affect' => 'tearful',
                    'behavior' => 'cooperative',
                    'communication' => 'Coherent & Logical',
                    'speech' => 'clear',
                    'bathing' => 4,
                    'transferring' => 4,
                    'toileting' => 5,
                    'grooming' => 4,
                    'eating' => 5,
                    'locomotion' => 3,
                    'dressing' => 4,
                ],
                'diagnoses' => [
                    ['Chronic obstructive pulmonary disease (COPD)', 48, 'Moderate. Short of breath on exertion; uses inhaler.'],
                    ['Major depressive disorder, mild', 8, 'Low mood since spouse passed. Seen by psychiatry monthly.'],
                ],
                'medications' => [
                    ['name' => 'Salbutamol', 'strength' => '100 mcg', 'dosage_amount' => 2, 'dosage_unit' => 'puff', 'route' => 'inhalation', 'instructions' => 'When short of breath; max 8 puffs a day.', 'taken_for' => 'Breathing', 'kind' => 'PRN', 'times' => null],
                    ['name' => 'Tiotropium', 'strength' => '18 mcg', 'dosage_amount' => 1, 'dosage_unit' => 'capsule', 'route' => 'inhalation', 'instructions' => 'Inhale once each morning.', 'taken_for' => 'COPD', 'kind' => 'Scheduled', 'times' => ['07:00']],
                    ['name' => 'Sertraline', 'strength' => '50 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take with breakfast.', 'taken_for' => 'Mood', 'kind' => 'Scheduled', 'times' => ['08:00']],
                ],
                'vitals' => [124, 78, 88, 98],
                'activities' => [
                    ['activity', 'Group bingo', 'Recreation hall', 'Joined with other residents; smiled and chatted.', 4, '15:00'],
                    ['therapy', 'Breathing exercises', 'Pulmonary rehab', 'Pursed-lip breathing for 15 minutes.', 2, '10:00'],
                    ['meal', 'Dinner', 'Regular diet', 'Ate well; asked for a second serving of soup.', 1, '18:00'],
                    ['appointment', 'Psychiatry check-in', 'Dr. Santos, via teleconsult', 'Mood improving; continue sertraline.', 0, '11:00'],
                ],
                'guardian' => ['email' => 'princesestoso4@gmail.com', 'relationship' => 'Niece'],
                'admission' => ['accommodation' => 'COMMON', 'billing_cycle' => 'YEARLY', 'days_ago' => 60, 'note' => 'Keep inhaler at bedside.'],
            ],
            [
                'profile' => [
                    'first_name' => 'Domingo',
                    'middle_name' => 'Lacson',
                    'last_name' => 'Espinosa',
                    'gender' => 'Male',
                    'height' => 171,
                    'weight' => 80.5,
                    'blood_type' => 'AB+',
                    'date_of_birth' => '1953-01-09',
                    'phone_number' => '9171100004',
                    'allergies' => ['Ibuprofen'],
                ],
                'address' => ['street' => '12 Mamay Road', 'barangay' => 'Lanang'],
                'assessment' => [
                    'condition' => 'ambulatory',
                    'mental_state' => 'alert',
                    'affect' => 'cheerful',
                    'behavior' => 'cooperative',
                    'communication' => 'Coherent & Logical',
                    'speech' => 'clear',
                    'bathing' => 4,
                    'transferring' => 5,
                    'toileting' => 5,
                    'grooming' => 5,
                    'eating' => 5,
                    'locomotion' => 4,
                    'dressing' => 4,
                ],
                'diagnoses' => [
                    ['Congestive heart failure, NYHA class II', 24, 'Daily weights; report gain of more than 1 kg in a day.'],
                    ['Chronic kidney disease, stage 3a', 30, 'Avoid NSAIDs. Renal panel every 3 months.'],
                ],
                'medications' => [
                    ['name' => 'Furosemide', 'strength' => '40 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take in the morning.', 'taken_for' => 'Fluid retention', 'kind' => 'Scheduled', 'times' => ['08:00']],
                    ['name' => 'Losartan', 'strength' => '50 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take once daily.', 'taken_for' => 'Heart and kidneys', 'kind' => 'Scheduled', 'times' => ['08:00']],
                ],
                'vitals' => [128, 80, 72, 102],
                'activities' => [
                    ['appointment', 'Cardiology follow-up', 'Dr. Tan, Southern Philippines Heart Center', 'Echo stable; continue current dose.', 9, '09:00'],
                    ['activity', 'Morning walk', 'Neighbourhood', '15-minute walk with family.', 2, '06:30'],
                ],
                'guardian' => ['email' => 'princesestoso5@gmail.com', 'relationship' => 'Wife'],
                'homecare' => [
                    'type' => 'Medical',
                    'in_days' => 2,
                    'booked_days_ago' => 3,
                    'time' => [9, 0],
                    'street' => '12 Mamay Road, Lanang',
                    'latitude' => 7.1036,
                    'longitude' => 125.6353,
                    'note' => 'Check weight and ankle swelling during the visit.',
                ],
            ],
            [
                'profile' => [
                    'first_name' => 'Teresita',
                    'middle_name' => 'Ocampo',
                    'last_name' => 'Bacalso',
                    'gender' => 'Female',
                    'height' => 155,
                    'weight' => 62.0,
                    'blood_type' => 'O-',
                    'date_of_birth' => '1944-09-30',
                    'phone_number' => '9171100005',
                    'allergies' => [],
                ],
                'address' => ['street' => '9 Cabaguio Avenue', 'barangay' => 'Agdao'],
                'assessment' => [
                    'condition' => 'wheelchair',
                    'mental_state' => 'alert',
                    'affect' => 'cheerful',
                    'behavior' => 'cooperative',
                    'communication' => 'Coherent & Logical',
                    'speech' => 'slurred',
                    'bathing' => 2,
                    'transferring' => 3,
                    'toileting' => 3,
                    'grooming' => 3,
                    'eating' => 4,
                    'locomotion' => 2,
                    'dressing' => 2,
                ],
                'diagnoses' => [
                    ['Parkinson\'s disease', 72, 'Resting tremor and rigidity; fall risk. Needs help with dressing and bathing.'],
                    ['Osteoporosis', 40, 'History of wrist fracture. Calcium and vitamin D supplementation.'],
                ],
                'medications' => [
                    ['name' => 'Carbidopa/Levodopa', 'strength' => '25/100 mg', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take 30 minutes before meals.', 'taken_for' => 'Parkinson\'s', 'kind' => 'Scheduled', 'times' => ['07:00', '12:00', '17:00']],
                    ['name' => 'Calcium + Vitamin D3', 'strength' => '600 mg/400 IU', 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take with lunch.', 'taken_for' => 'Bones', 'kind' => 'Scheduled', 'times' => ['12:00']],
                ],
                'vitals' => [118, 74, 70, 94],
                'activities' => [
                    ['therapy', 'Balance training', 'Home physical therapy', 'Sit-to-stand practice with walker.', 7, '10:00'],
                    ['meal', 'Merienda', 'Soft foods', 'Needed help holding the cup due to tremor.', 1, '15:00'],
                ],
                'guardian' => ['email' => 'princesestoso6@gmail.com', 'relationship' => 'Granddaughter'],
                'homecare' => [
                    'type' => 'ADL',
                    'hours' => 8,
                    'in_days' => 1,
                    'booked_days_ago' => 2,
                    'time' => [8, 0],
                    'street' => '9 Cabaguio Avenue, Agdao',
                    'latitude' => 7.0921,
                    'longitude' => 125.6230,
                    'note' => 'Assist with bathing, dressing and meals. Use the shower chair.',
                ],
            ],
        ];
    }
}
