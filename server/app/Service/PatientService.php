<?php

namespace App\Service;


use App\Http\Resources\PatientReportResource;
use App\Http\Resources\PatientResource;
use App\Models\Schedule;
use App\Models\User;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class PatientService
{
    public function __construct(
        private PatientRepository $patientRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * The booking form collects allergies as a single comma-separated
     * string; stored as a JSON array to match how the portal renders them
     * as individual badges.
     */
    private function parseAllergies(?string $allergies): ?array
    {
        if (!$allergies) {
            return null;
        }

        $items = array_values(array_filter(array_map(
            'trim',
            explode(',', $allergies)
        )));

        return $items ?: null;
    }


    // DONE MEDICAL
    public function createMedicalPatient(array $payload)
    {
        $patient = $payload['patient'];
        $homecare = $payload['homecare'];
        $assessment = $payload['assessment'];
        $guardian = $payload['guardian'];

        $patient = $this->patientRepository->create([
            'branch_id'          => $payload['branch_id'],
            'address'            => $homecare['address'],
            'first_name'         => $patient['first_name'],
            'middle_name'        => $patient['middle_name'],
            'last_name'          => $patient['last_name'],
            'gender'             => $patient['gender'] ?? null,
            'height'             => $patient['height'] ?? null,
            'weight'             => $patient['weight'] ?? null,
            'blood_type'         => $patient['blood_type'] ?? null,
            'date_of_birth'      => $patient['date_of_birth'] ?? null,
            'phone_number'       => $patient['phone_number'] ?? null,
            'citizenship'        => $patient['citizenship'] ?? null,
            'initial_assessment' => $assessment,
            'allergies'          => $this->parseAllergies($patient['allergies'] ?? null),
        ]);


        $patientAccess = $this->patientAccess($guardian, $patient);

        if (!$patientAccess) {
            throw new Exception('Unable to create patient.', 500);
        }


        $scheduledAt = Carbon::parse(
            $homecare['date'] . ' ' . $homecare['prefered_time']
        );

        $schedule = $patient->schedules()->create([
            'scheduled_at'          => $scheduledAt,
            'status'                => Schedule::STATUS_PENDING,
            'category'              => ucfirst($payload['category']),
        ]);

        $invoiceServices = [];

        // FOR MEDICAL
        if (!empty($homecare['services'])) {
            foreach ($homecare['services'] as $item) {
                $scheduleService = $schedule->scheduleServices()->create([
                    'service_id'   => $item['service_id'] ?? null,
                    'hours_booked' => $homecare['time_span'] ?? null,
                    'type'         => $homecare['type'],
                ]);

                $invoiceServices[] = [
                    'schedule_services_id' => $scheduleService->schedule_services_id,
                    'price' => $item['price']
                ];
            }
        }

        // FOR ADL
        if (empty($homecare['services'])) {
            $scheduleService = $schedule->scheduleServices()->create([
                'hours_booked' => $homecare['time_span'] ?? null,
                'type'         => $homecare['type'],
            ]);

            $invoiceServices[] = [
                'schedule_services_id' => $scheduleService->schedule_services_id,
                'price' =>  $payload['payment']['total_amount'],
            ];
        }


        return [
            'invoiceServices' => $invoiceServices,
            'schedule'        => $schedule,
            'patient'         => $patient,
        ];
    }

    // DONE FACILITY
    public function createFacilityPatient(array $payload)
    {
        $patient = $payload['patient'];
        $guardian = $payload['guardian'];
        $assessment = $payload['assessment'];
        $patient = $this->patientRepository->create([
            'branch_id'          => $payload['branch_id'],
            'address'            => $patient['address'] ?? null,
            'first_name'         => $patient['first_name'],
            'middle_name'        => $patient['middle_name'],
            'last_name'          => $patient['last_name'],
            'gender'             => $patient['gender'] ?? null,
            'height'             => $patient['height'] ?? null,
            'weight'             => $patient['weight'] ?? null,
            'blood_type'         => $patient['blood_type'] ?? null,
            'date_of_birth'      => $patient['date_of_birth'] ?? null,
            'phone_number'       => $patient['phone_number'] ?? null,
            'citizenship'        => $patient['citizenship'] ?? null,
            'occupation'         => $patient['occupation'] ?? null,
            'marital_status'     => $patient['marital_status'] ?? null,
            'initial_assessment' => $assessment,
            'allergies'          => $this->parseAllergies($patient['allergies'] ?? null),
        ]);

        if (!$patient) {
            throw new Exception('Unable to create patient.', 500);
        }

        $patientAccess = $this->patientAccess($guardian, $patient);

        if (!$patientAccess) {
            throw new Exception('Unable to create patient.', 500);
        }

        return [
            'patient' => $patient,
            'patientAccess' => $patientAccess,
        ];
    }

    // DONE PATIENT ACCESS
    public function patientAccess(array $guardian, object $patient)
    {
        $user = $this->userRepository->createUpdateTypeUser([
            'email' => $guardian['email'],
            'address' => $guardian['address'],
            'first_name' => $guardian['first_name'],
            'middle_name' => $guardian['middle_name'],
            'last_name' => $guardian['last_name'],
            'phone_number' => $guardian['phone_number'],
            'occupation' => $guardian['occupation'],
        ], 'client');

        $client = $user->client;

        $client->patientAccess()->create([
            'patient_id' => $patient->patient_id,
            'have_access' => true,
            'relationship_type' => $guardian['relationship'] ?? 'relative',
        ]);

        return $client;
    }

    // DONE RETREIVE PATIETN
    public function retrievePatients(array $payload, User $user)
    {
        return PatientResource::collection($this->patientRepository->getPatient($payload));
    }

    // DONE SHOW SPECIFIC PATIENT 
    public function showPatient(string $uuid)
    {
        $patient = $this->patientRepository->showPatient($uuid);

        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found.',
            ], 404);
        }

        return new PatientResource($patient);
    }

    public const REPORT_SECTIONS = [
        'profile',
        'admission',
        'billing',
        'schedule',
        'medication',
        'vitals',
        'activity',
    ];

    public function buildPatientReport(string $uuid, array $requestedSections)
    {
        $sections = array_values(array_intersect(
            array_map('strtolower', $requestedSections),
            self::REPORT_SECTIONS
        ));

        if (empty($sections)) {
            throw new Exception('Select at least one section to print.', 422);
        }

        $patient = $this->patientRepository->findForReport($uuid);

        if (!$patient) {
            throw new Exception('Patient not found.', 404);
        }

        $built = [];

        foreach ($sections as $section) {
            $built[$section] = match ($section) {
                'profile' => $this->reportProfile($patient),
                'admission' => $this->reportAdmissions($patient),
                'billing' => $this->reportBilling($patient),
                'schedule' => $this->reportSchedules($patient),
                'medication' => $this->reportMedications($patient),
                'vitals' => $this->reportVitals($patient),
                'activity' => $this->reportActivities($patient),
            };
        }

        return new PatientReportResource([
            'patient' => $patient,
            'sections' => $built,
        ]);
    }

    private function reportProfile($patient): array
    {
        return [
            'initial_assessment' => $patient->initial_assessment,
            'allergies' => $patient->allergies ?? [],
        ];
    }

    private function reportAdmissions($patient): array
    {
        return $patient->admissions->map(fn($admission) => [
            'status' => $admission->status,
            'note' => $admission->note,
            'admitted_at' => $admission->admitted_at?->format('Y-m-d H:i'),
            'end_date' => $admission->end_date?->format('Y-m-d H:i'),
            'room' => $admission->bed?->room?->room_no,
            'room_type' => $admission->bed?->room?->room_type,
            'bed' => $admission->bed?->bed_no,
            'floor' => $admission->bed?->room?->floor,
        ])->values()->all();
    }

    private function reportBilling($patient): array
    {
        return [
            'summary' => $patient->billing_summary,
            'invoices' => $patient->patient_invoices->map(fn($invoice) => [
                'invoice_code' => $invoice->invoice_code,
                'status' => $invoice->status,
                'created_at' => $invoice->created_at?->format('Y-m-d'),
                'total' => (float) $invoice->total,
                'amount_paid' => (float) $invoice->amount_paid,
                'refunded_amount' => (float) $invoice->refunded_amount,
                'balance_due' => (float) $invoice->balance_due,
                'payments' => $invoice->payments->map(fn($payment) => [
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'reference_id' => $payment->reference_id,
                    'paid_at' => $payment->created_at?->format('Y-m-d H:i'),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function reportSchedules($patient): array
    {
        return $patient->schedules->map(fn($schedule) => [
            'schedule_code' => $schedule->schedule_code,
            'status' => $schedule->status,
            'scheduled_at' => $schedule->scheduled_at?->format('Y-m-d H:i'),
            'services' => $schedule->scheduleServices->map(fn($scheduleService) => [
                'service_name' => $scheduleService->service?->name,
                'type' => $scheduleService->type,
                'hours_booked' => $scheduleService->hours_booked,
                'duration_minutes' => $scheduleService->service?->maximum_duration,
            ])->values()->all(),
        ])->values()->all();
    }

    private function reportMedications($patient): array
    {
        return $patient->medications->map(fn($medication) => [
            'name' => $medication->name,
            'strength' => $medication->strength,
            'dosage_amount' => $medication->dosage_amount,
            'dosage_unit' => $medication->dosage_unit,
            'route' => $medication->route,
            'frequency' => $medication->frequency,
            'kind' => $medication->kind,
            'times' => $medication->times,
            'instructions' => $medication->instructions,
            'taken_for' => $medication->taken_for,
            'duration' => $medication->duration,
            'start_date' => $medication->start_date?->format('Y-m-d'),
        ])->values()->all();
    }

    private function reportVitals($patient): array
    {
        return $patient->vitals->map(fn($vital) => [
            'recorded_date' => $vital->recorded_date?->format('Y-m-d'),
            'recorded_time' => $vital->recorded_time,
            'blood_pressure' => $vital->blood_pressure_systolic && $vital->blood_pressure_diastolic
                ? "{$vital->blood_pressure_systolic}/{$vital->blood_pressure_diastolic}"
                : null,
            'heart_rate' => $vital->heart_rate,
            'respiratory_rate' => $vital->respiratory_rate,
            'temperature' => $vital->temperature,
            'oxygen_saturation' => $vital->oxygen_saturation,
            'blood_glucose' => $vital->blood_glucose,
            'pain_level' => $vital->pain_level,
            'notes' => $vital->notes,
        ])->values()->all();
    }

    private function reportActivities($patient): array
    {
        return $patient->activities->map(fn($activity) => [
            'title' => $activity->title,
            'subtitle' => $activity->subtitle,
            'description' => $activity->description,
            'type' => $activity->type,
            'occurred_at' => $activity->occurred_at?->format('Y-m-d H:i'),
        ])->values()->all();
    }
}
