<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\AdditionalCharge;
use App\Service\DiagnosisFeeService;
use App\Models\AdmissionDiagnosis;
use App\Models\AdmissionPeriod;
use App\Models\Bed;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\Client;
use App\Models\DiagnosisCase;
use App\Models\EmployeeBranch;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PatientAccess;
use App\Models\PatientActivity;
use App\Models\PatientAdmission;
use App\Models\PatientAssessment;
use App\Models\PatientDiagnosis;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Models\Vital;
use App\Service\TransactionService;
use App\Utils\AccommodationHelper;
use App\Utils\AdmissionHelper;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsBranches;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PatientSeeder extends Seeder
{
    use SeedsBranches;

    private ?string $guardianEmail = null;

    public function withGuardian(string $email): static
    {
        $this->guardianEmail = $email;

        return $this;
    }

    public function run(): void
    {
        $branches = $this->seedBranches();

        if ($branches->isEmpty()) {
            $this->command?->warn('No branches found. Seed branches first.');
            return;
        }

        foreach ($branches->values() as $index => $branch) {
            $facility = $branch->hasFacilitySubscription();
            $homecare = $branch->hasHomecareSubscription();

            foreach ($this->renamed($this->patients(), $index) as $data) {
                if (isset($data['admission']) ? !$facility : !$homecare) {
                    continue;
                }

                if ($this->guardianEmail) {
                    $data['guardian']['email'] = $this->guardianEmail;
                }

                $existing = Patient::where('branch_id', $branch->branch_id)
                    ->where('first_name', $data['profile']['first_name'])
                    ->where('last_name', $data['profile']['last_name'])
                    ->first();

                if ($existing) {
                    $this->backfillDiagnosisCases($branch, $existing, $data);

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
                        $this->createAdmission($branch, $patient, $data['admission'], $data);
                    }

                    if (isset($data['homecare'])) {
                        $this->createHomecare($branch, $patient, $data);
                    }
                });
            }
        }
    }

    private function renamed(array $patients, int $branchIndex): array
    {
        if ($branchIndex === 0) {
            return $patients;
        }

        $pools = [
            'Female' => ['Marites', 'Corazon', 'Estrella', 'Milagros', 'Remedios', 'Pilar', 'Amparo', 'Luzviminda', 'Natividad', 'Perla', 'Herminia', 'Consolacion', 'Felicidad', 'Erlinda', 'Gloria'],
            'Male' => ['Rodolfo', 'Anselmo', 'Fidel', 'Leopoldo', 'Teodoro', 'Cesar', 'Nestor', 'Alfredo', 'Benjamin'],
        ];
        $middleNames = ['Aquino', 'Bautista', 'Castillo', 'Dela Cruz', 'Evangelista', 'Fernandez', 'Gonzales', 'Hernandez', 'Ilagan', 'Jimenez', 'Katigbak', 'Lim', 'Mercado', 'Navarro', 'Ortega', 'Panganiban', 'Quizon', 'Ramos', 'Salazar', 'Tolentino', 'Urbano', 'Vergara', 'Yap', 'Zamora'];
        $lastNames = ['Abad', 'Balderama', 'Cabrera', 'Dumalagan', 'Escario', 'Fuentes', 'Generalao', 'Hilario', 'Inocencio', 'Jaranilla', 'Labrador', 'Magallanes', 'Natividad', 'Olaguer', 'Pelayo', 'Quiroga', 'Rivera', 'Sarmiento', 'Tabada', 'Ugay', 'Valdez', 'Wenceslao', 'Zaldivar', 'Yulo'];

        $perBranch = array_count_values(array_column(array_column($patients, 'profile'), 'gender'));
        $total = count($patients);
        $counters = ['Female' => 0, 'Male' => 0];
        $position = 0;

        return array_map(function (array $data) use ($branchIndex, $pools, $middleNames, $lastNames, $perBranch, $total, &$counters, &$position) {
            $gender = $data['profile']['gender'];
            $pick = ($branchIndex - 1) * $perBranch[$gender] + $counters[$gender]++;
            $slot = ($branchIndex - 1) * $total + $position++;

            $data['profile']['first_name'] = $pools[$gender][$pick % count($pools[$gender])];
            $data['profile']['middle_name'] = $middleNames[$slot % count($middleNames)];
            $data['profile']['last_name'] = $lastNames[$slot % count($lastNames)];
            $data['profile']['phone_number'] = (string) ((int) $data['profile']['phone_number'] + $branchIndex * 100);

            return $data;
        }, $patients);
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

    private function cashier(int $branchId): ?int
    {
        return EmployeeBranch::query()
            ->join('employees', 'employees.employee_id', '=', 'employee_branches.employee_id')
            ->where('employee_branches.branch_id', $branchId)
            ->whereIn('employee_branches.role_name', [RoleEnum::Cashier->value, RoleEnum::AgencyOwner->value])
            ->orderByRaw('employee_branches.role_name = ? desc', [RoleEnum::Cashier->value])
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

    private function createAdmission(Branch $branch, Patient $patient, array $admission, array $data = []): void
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
            $this->command?->warn("Skipped admission for {$patient->first_name} {$patient->last_name}: no matching contract or available bed.");
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

        if ($admission['complete'] ?? false) {
            $this->createCompleteBooking($branch, $patient, $data, $contract, $invoice, $admittedAt);
        }

        $this->payInvoice($invoice, $patient, $data['guardian']['email'], $admittedAt->copy()->subHours(2));

        $this->createCharges($branch, $patient, $patientAdmission, $admission['charges'] ?? [], $data);
    }

    private function createCharges(
        Branch $branch,
        Patient $patient,
        PatientAdmission $admission,
        array $charges,
        array $data
    ): void {
        if (!$charges) {
            return;
        }

        $loadCases = fn() => DiagnosisCase::where('branch_id', $branch->branch_id)->get()->keyBy('title');

        $cases = $loadCases();

        if ($cases->isEmpty()) {
            $this->call(DiagnosisCaseSeeder::class);
            $cases = $loadCases();
        }

        $diagnoses = PatientDiagnosis::where('patient_id', $patient->patient_id)
            ->orderBy('patient_diagnosis_id')
            ->get()
            ->values();

        foreach ($charges as $spec) {
            $case = isset($spec['case']) ? $cases->get($spec['case']) : null;
            $amount = round((float) ($spec['amount'] ?? $case?->price ?? 0), 2);
            $chargedAt = now()->subDays($spec['days_ago'])->setTime(11, 0);

            if ($spec['type'] === 'diagnosis_case') {
                $fee = $this->createDiagnosisFee(
                    $branch,
                    $admission,
                    $diagnoses->get($spec['diagnosis'] ?? -1),
                    $case,
                    $amount,
                    $chargedAt
                );

                if ($fee) {
                    $this->payInvoice($fee, $patient, $data['guardian']['email'], $chargedAt->copy()->addHours(2));
                }

                continue;
            }

            $invoice = Invoice::create([
                'branch_id' => $branch->branch_id,
                'total_amount' => $amount,
                'status' => Invoice::STATUS_PENDING,
            ]);

            $charge = AdditionalCharge::create([
                'patient_id' => $patient->patient_id,
                'invoice_id' => $invoice->invoice_id,
                'type' => $spec['type'],
                'description' => $spec['description'] ?? $case?->title,
                'amount' => $amount,
            ]);

            DB::table('invoices')
                ->where('invoice_id', $invoice->invoice_id)
                ->update(['created_at' => $chargedAt]);

            DB::table('additional_charges')
                ->where('additional_charge_id', $charge->additional_charge_id)
                ->update(['created_at' => $chargedAt, 'updated_at' => $chargedAt]);

            $this->payInvoice($invoice, $patient, $data['guardian']['email'], $chargedAt->copy()->addHours(2));
        }
    }

    // Patients seeded before diagnosis cases moved to admission_diagnosis have
    // none on the new tables, so they get them here.
    private function backfillDiagnosisCases(Branch $branch, Patient $patient, array $data): void
    {
        $cases = collect($data['admission']['charges'] ?? [])
            ->where('type', 'diagnosis_case')
            ->values()
            ->all();

        $admission = $cases
            ? PatientAdmission::where('patient_id', $patient->patient_id)->latest('patient_admission_id')->first()
            : null;

        if (!$admission) {
            return;
        }

        $billed = AdmissionDiagnosis::whereHas(
            'charge.admissionPeriod',
            fn($query) => $query->where('patient_admission_id', $admission->patient_admission_id)
        )->exists();

        if ($billed) {
            return;
        }

        DB::transaction(fn() => $this->createCharges($branch, $patient, $admission, $cases, $data));
    }

    private function createDiagnosisFee(
        Branch $branch,
        PatientAdmission $admission,
        ?PatientDiagnosis $diagnosis,
        ?DiagnosisCase $case,
        float $amount,
        Carbon $chargedAt
    ): ?Invoice {
        $period = $admission->periods()->orderBy('admission_period_id')->first();

        if (!$diagnosis || !$period || $amount <= 0) {
            return null;
        }

        $invoice = app(DiagnosisFeeService::class)->bill(
            $admission,
            $period,
            collect([[
                'diagnosis' => $diagnosis,
                'case' => $case,
                'amount' => $amount,
                'description' => null,
            ]]),
            $branch->branch_id
        );

        if ($invoice) {
            DB::table('invoices')
                ->where('invoice_id', $invoice->invoice_id)
                ->update(['created_at' => $chargedAt]);

            DB::table('admission_charge_diagnosis')
                ->where('invoice_id', $invoice->invoice_id)
                ->update(['created_at' => $chargedAt, 'updated_at' => $chargedAt]);
        }

        return $invoice;
    }

    private function createCompleteBooking(
        Branch $branch,
        Patient $patient,
        array $data,
        BranchContract $contract,
        Invoice $invoice,
        Carbon $admittedAt
    ): void {
        $guardianUser = User::where('email', $data['guardian']['email'])->first();
        $client = $guardianUser?->client;
        $bookedAt = $admittedAt->copy()->subDays(2)->setTime(14, 30);

        $booking = Booking::create([
            'user_id' => $guardianUser?->user_id,
            'branch_id' => $branch->branch_id,
            'category' => Booking::CATEGORY_FACILITY,
            'booking_type' => Booking::BOOKINGTYPE_ONLINE,
            'status' => Booking::STATUS_APPROVED,
            'reviewed_by' => EmployeeBranch::where('branch_id', $branch->branch_id)
                ->where('role_name', RoleEnum::AgencyOwner->value)
                ->value('employee_id'),
            'valid_until' => $admittedAt->copy()->endOfDay(),
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
                'facility' => [
                    'type' => Booking::TYPE_COMPLETEADMISSION,
                    'plan' => $contract->accommodation_type,
                    'billing_cycle' => $contract->billing_cycle,
                    'admission_date' => $admittedAt->toDateString(),
                ],
                'homecare' => [],
                'assessment' => [$data['assessment']],
                'diagnoses' => array_map(fn($diagnosis) => [
                    'diagnosis' => $diagnosis[0],
                    'diagnosis_date' => now()->subMonths($diagnosis[1])->toDateString(),
                    'diagnosis_notes' => $diagnosis[2],
                ], $data['diagnoses']),
                'reserved' => [
                    'contract_id' => $contract->branch_contract_id,
                    'accommodation_plan' => $contract->accommodation_type,
                    'billing_cycle' => $contract->billing_cycle,
                    'admitted_at' => $admittedAt->toDateTimeString(),
                ],
                'payment' => [
                    'total_amount' => (float) $invoice->total_amount,
                    'booking_percent' => 100,
                    'booking_amount' => (float) $invoice->total_amount,
                    'balance_amount' => 0,
                    'payment_status' => 'paid',
                    'payment_method' => 'CASH',
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
    }

    private function payInvoice(Invoice $invoice, Patient $patient, string $guardianEmail, Carbon $paidAt): void
    {
        $client = User::where('email', $guardianEmail)->first()?->client;
        $amount = round((float) $invoice->total_amount, 2);

        $transaction = app(TransactionService::class)->forPayment(
            $amount,
            $invoice->branch_id,
            $patient->patient_id,
            'Payment received in cash.',
            [
                'client_id' => $client?->client_id,
                'method' => 'CASH',
                'party_name' => $client ? trim("{$client->first_name} {$client->last_name}") : null,
                'created_at' => $paidAt,
            ]
        );

        $payment = Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'prior_balance' => $amount,
            'new_balance' => 0,
            'cash_tendered' => $amount,
            'issued_by' => $this->cashier($invoice->branch_id),
            'created_at' => $paidAt,
        ]);

        $payment->allocations()->create([
            'invoice_id' => $invoice->invoice_id,
            'amount' => $amount,
            'description' => $invoice->paymentDescription(),
            'created_at' => $paidAt,
        ]);

        $invoice->refresh()->syncStatus();

        AccommodationHelper::activate($invoice);
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
            $findServices = fn() => Service::where('branch_id', $branch->branch_id)
                ->whereIn('type', ['online', 'both'])
                ->where('is_available', true)
                ->orderBy('service_id')
                ->get();

            $services = $findServices();

            if ($services->isEmpty()) {
                $this->call(ServiceSeeder::class);
                $services = $findServices();
            }

            $service = $services->isEmpty()
                ? null
                : $services->get(($homecare['service_offset'] ?? 0) % $services->count());


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
                    'payment_status' => 'paid',
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

        $this->payInvoice($invoice, $patient, $data['guardian']['email'], $bookedAt->copy()->addHours(4));
    }

    private function patients(): array
    {
        return array_merge([
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
                'guardian' => ['email' => 'princesestoso5@gmail.com', 'relationship' => 'Daughter'],
                'admission' => ['accommodation' => 'COMMON', 'billing_cycle' => 'MONTHLY', 'days_ago' => 12, 'note' => 'Needs supervision at night due to wandering.', 'charges' => [
                    ['type' => 'diagnosis_case', 'case' => 'Dementia & Alzheimer\'s Care', 'diagnosis' => 0, 'days_ago' => 8, 'paid' => true],
                    ['type' => 'medication', 'description' => 'Donepezil refill (30 tablets)', 'amount' => 1800, 'days_ago' => 3],
                ]],
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
                'guardian' => ['email' => 'princesestoso5@gmail.com', 'relationship' => 'Son'],
                'admission' => ['accommodation' => 'VIP', 'billing_cycle' => 'MONTHLY', 'days_ago' => 5, 'note' => 'Turn every 2 hours to prevent pressure sores.', 'charges' => [
                    ['type' => 'diagnosis_case', 'case' => 'Stroke Recovery', 'diagnosis' => 0, 'days_ago' => 3],
                    ['type' => 'diagnosis_case', 'case' => 'Dysphagia Management', 'diagnosis' => 2, 'days_ago' => 4, 'paid' => true],
                ]],
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
                'guardian' => ['email' => 'princesestoso5@gmail.com', 'relationship' => 'Niece'],
                'admission' => ['accommodation' => 'COMMON', 'billing_cycle' => 'YEARLY', 'days_ago' => 60, 'note' => 'Keep inhaler at bedside.', 'complete' => true, 'charges' => [
                    ['type' => 'diagnosis_case', 'case' => 'COPD Management', 'diagnosis' => 0, 'days_ago' => 40, 'paid' => true],
                    ['type' => 'supplies', 'description' => 'Oxygen cannula and nebulizer kit', 'amount' => 2400, 'days_ago' => 10],
                ]],
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
                'guardian' => ['email' => 'princesestoso4@gmail.com', 'relationship' => 'Wife'],
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
                'guardian' => ['email' => 'princesestoso4@gmail.com', 'relationship' => 'Granddaughter'],
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
        ], $this->homecarePatients());
    }

    private function homecarePatients(): array
    {
        $assessment = [
            'condition' => 'ambulatory',
            'mental_state' => 'alert',
            'affect' => 'cheerful',
            'behavior' => 'cooperative',
            'communication' => 'Coherent & Logical',
            'speech' => 'clear',
            'bathing' => 4,
            'transferring' => 4,
            'toileting' => 4,
            'grooming' => 4,
            'eating' => 5,
            'locomotion' => 4,
            'dressing' => 4,
        ];

        $guardian = ['email' => 'princesestoso4@gmail.com', 'relationship' => 'Daughter'];

        $specs = [
            [
                'name' => ['Fe', 'Dizon', 'Alcantara'],
                'gender' => 'Female',
                'dob' => '1948-05-17',
                'phone' => '9171100006',
                'blood' => 'A-',
                'street' => ['5 Ecoland Drive', 'Ecoland', 7.0527, 125.6013],
                'diagnosis' => ['Type 2 diabetes mellitus', 84, 'Monitor blood glucose before meals.'],
                'medication' => ['Metformin', '500 mg', 'Diabetes', '08:00'],
                'vitals' => [130, 82, 74, 128],
                'homecare' => ['type' => 'Medical', 'in_days' => 3, 'booked_days_ago' => 2, 'time' => [10, 0], 'service_offset' => 1, 'note' => 'Check blood sugar and review the diet log.'],
            ],
            [
                'name' => ['Armando', 'Soriano', 'Tiu'],
                'gender' => 'Male',
                'dob' => '1945-12-03',
                'phone' => '9171100007',
                'blood' => 'B-',
                'street' => ['88 Torres Street', 'Poblacion District', 7.0647, 125.6089],
                'diagnosis' => ['Chronic obstructive pulmonary disease', 48, 'Keep rescue inhaler within reach; watch for breathlessness.'],
                'medication' => ['Salbutamol', '100 mcg', 'Breathing', '07:00'],
                'vitals' => [126, 78, 80, 98],
                'homecare' => ['type' => 'Medical', 'in_days' => 4, 'booked_days_ago' => 1, 'time' => [14, 0], 'service_offset' => 2, 'note' => 'Check oxygen saturation and inhaler technique.'],
            ],
            [
                'name' => ['Concepcion', 'Reyes', 'Palma'],
                'gender' => 'Female',
                'dob' => '1941-02-26',
                'phone' => '9171100008',
                'blood' => 'O+',
                'street' => ['31 Sandawa Road', 'Matina Pangi', 7.0561, 125.5974],
                'diagnosis' => ['Osteoarthritis of the hips', 96, 'Needs help with transfers and long walks.'],
                'medication' => ['Paracetamol', '500 mg', 'Joint pain', '09:00'],
                'vitals' => [124, 76, 70, 96],
                'homecare' => ['type' => 'ADL', 'hours' => 6, 'in_days' => 2, 'booked_days_ago' => 1, 'time' => [9, 0], 'note' => 'Assist with bathing, grooming and lunch.'],
            ],
        ];

        return array_map(function (array $spec) use ($assessment, $guardian) {
            [$first, $middle, $last] = $spec['name'];
            [$street, $barangay, $latitude, $longitude] = $spec['street'];
            [$diagnosis, $monthsAgo, $notes] = $spec['diagnosis'];
            [$medication, $strength, $takenFor, $time] = $spec['medication'];

            return [
                'profile' => [
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'last_name' => $last,
                    'gender' => $spec['gender'],
                    'height' => 155,
                    'weight' => 60.0,
                    'blood_type' => $spec['blood'],
                    'date_of_birth' => $spec['dob'],
                    'phone_number' => $spec['phone'],
                    'allergies' => [],
                ],
                'address' => ['street' => $street, 'barangay' => $barangay],
                'assessment' => $assessment,
                'diagnoses' => [[$diagnosis, $monthsAgo, $notes]],
                'medications' => [
                    ['name' => $medication, 'strength' => $strength, 'dosage_amount' => 1, 'dosage_unit' => 'tablet', 'route' => 'oral', 'instructions' => 'Take as prescribed.', 'taken_for' => $takenFor, 'kind' => 'Scheduled', 'times' => [$time]],
                ],
                'vitals' => $spec['vitals'],
                'activities' => [
                    ['activity', 'Morning walk', 'Neighbourhood', '15-minute walk with family.', 2, '06:30'],
                ],
                'guardian' => $guardian,
                'homecare' => $spec['homecare'] + [
                    'street' => "{$street}, {$barangay}",
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
            ];
        }, $specs);
    }
}
