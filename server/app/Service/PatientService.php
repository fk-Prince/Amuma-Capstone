<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\PatientResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\LocationRepository;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;
use Exception;

class PatientService
{
    public function __construct(
        private PatientRepository $patientRepository,
        private LocationRepository $locationRepository,
        private BranchRepository $branchRepository,
        private UserRepository $userRepository,
        private PatientAdmissionService $patientAdmissionService,
    ) {}

    public function createPatient(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings,  PermissionAction::Create);

        return match (true) {
            $payload['type'] === 'Medical'
            => $this->createMedicalPatient($branch->branch_id, $payload),

            $payload['type'] === 'Complete'
                && $payload['category'] === 'Facility'
            => $this->createFacilityPatient($branch->branch_id, $payload),

            default => throw new Exception('Unsupported booking type.', 422),
        };
    }

    private function createMedicalPatient(int $branchId, array $payload)
    {
        $patientData = $payload['patientData'];
        $serviceData = $payload['serviceData'];
        $assessment = $payload['assessmentData'];

        if (!empty($patientData['address'])) {
            $patientLocation = $this->locationRepository->create([
                'full_address' => $patientData['address'],
            ]);
        }

        $scheduledLocation = $this->locationRepository->create([
            'full_address' => $serviceData['address'],
        ]);


        $patient = $this->patientRepository->create([
            'branch_id'          => $branchId,
            'location_id'        => $scheduledLocation->location_id,
            'first_name'         => $patientData['first_name'],
            'middle_name'        => $patientData['middle_name'],
            'last_name'          => $patientData['last_name'],
            'gender'             => $patientData['gender'] ?? null,
            'height'             => $patientData['height'] ?? null,
            'weight'             => $patientData['weight'] ?? null,
            'blood_type'         => $patientData['blood_type'] ?? null,
            'date_of_birth'      => $patientData['date_of_birth'] ?? null,
            'phone_number'       => $patientData['phone_number'] ?? null,
            'citizenship'        => $patientData['citizenship'] ?? null,
            'initial_assessment' => $assessment,
            'medication'         => [],
        ]);

        $schedule = $patient->schedules()->create([
            // 'scheduled_location_id' => $scheduledLocation->location_id,
            'scheduled_at'          => $serviceData['date'],
            'status'                => 'Pending',
            'category'              => $payload['category'],
        ]);

        foreach ($serviceData['services'] as $service) {
            $schedule->scheduleServices()->create([
                'service_id'   => $service['service_id'] ?? null,
                'hours_booked' => $serviceData['time_span'],
                'status'       => 'pending',
                'type'         => $serviceData['type'],
            ]);
        }

        return $patient;
    }

    private function createFacilityPatient(int $branchId, array $payload)
    {
        $patientData = $payload['patientData'];
        $guardianData = $payload['guardianData'];
        $serviceData = $payload['serviceData'];
        $assessment = $payload['assessmentData'];


        $patientLocation = null;
        if (!empty($patientData['address'])) {
            $patientLocation = $this->locationRepository->create([
                'full_address' => $patientData['address'],
            ]);
        }

        $patient = $this->patientRepository->create([
            'branch_id'          => $branchId,
            'location_id'        => $patientLocation?->location_id ?? null,
            'first_name'         => $patientData['first_name'],
            'middle_name'        => $patientData['middle_name'],
            'last_name'          => $patientData['last_name'],
            'gender'             => $patientData['gender'] ?? null,
            'height'             => $patientData['height'] ?? null,
            'weight'             => $patientData['weight'] ?? null,
            'blood_type'         => $patientData['blood_type'] ?? null,
            'date_of_birth'      => $patientData['date_of_birth'] ?? null,
            'phone_number'       => $patientData['phone_number'] ?? null,
            'citizenship'        => $patientData['citizenship'] ?? null,
            'occupation'         => $patientData['occupation'] ?? null,
            'marital_status'     => $patientData['marital_status'] ?? null,
            'initial_assessment' => $assessment,
        ]);

        if (!$patient) {
            throw new Exception('Unable to create patient.', 500);
        }

        $admission = $this->patientAdmissionService->registerPatientBed([
            'branch_id' => $branchId,
            'bed_id' => 3, // Replace with selected bed later
            'patient' => [
                'patient_id' => $patient->patient_id,
            ],
            'plan' => $serviceData['plan'],
            'billing_cycle' => $serviceData['billing_cycle'],
            'type' => $serviceData['type'],
            'admission_date' => $serviceData['admission_date'],
        ]);

        $guardianLocation = $this->locationRepository->create([
            'full_address' => $guardianData['address'],
        ]);

        $guardianData['location_id'] = $guardianLocation->location_id;

        $this->patientAccess($guardianData, $patient);

        return [
            'patient' => $patient,
            'patient_admission' => $admission,
        ];
    }

    public function patientAccess(array $guardianData, object $patient)
    {
        $client = $this->userRepository->createUpdateTypeUser([
            'email' => $guardianData['email'],
            'first_name' => $guardianData['first_name'],
            'middle_name' => $guardianData['middle_name'],
            'last_name' => $guardianData['last_name'],
            'location_id' =>  $guardianData['location_id'],
            'phone_number' => $guardianData['phone_number'],
            'occupation' => $guardianData['occupation'],
        ], 'client');

        $client->patientAccess()->create([
            'patient_id' => $patient->patient_id,
            'have_access' => true,
            'relationship_type' => $guardianData['relationship'] ?? 'relative',
        ]);

        return $client;
    }

    public function retrievePatients(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Patients, PermissionAction::Read);
        return PatientResource::collection($this->patientRepository->getPatient($branch->branch_id));
    }

    public function showPatient(array $payload, User $user, string $uuid)
    {
        return PatientResource::collection($this->patientRepository->showPatient($uuid));
    }
}
