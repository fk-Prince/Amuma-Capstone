<?php

namespace App\Service;


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


    // DONE MEDICAL
    public function createMedicalPatient(array $payload)
    {
        $patient = $payload['patient'];
        $homecare = $payload['homecare'];
        $assessment = $payload['assessment'];

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
            'medication'         => [],
        ]);

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
        $client = $this->userRepository->createUpdateTypeUser([
            'email' => $guardian['email'],
            'address' => $guardian['address'],
            'first_name' => $guardian['first_name'],
            'middle_name' => $guardian['middle_name'],
            'last_name' => $guardian['last_name'],
            'phone_number' => $guardian['phone_number'],
            'occupation' => $guardian['occupation'],
        ], 'client');

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
}
