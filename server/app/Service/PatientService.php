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
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class PatientService
{
    public function __construct(
        private PatientRepository $patientRepository,
        private LocationRepository $locationRepository,
        private UserRepository $userRepository,
        private InvoiceService $invoiceService
    ) {}

    public function createPatient(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings,  PermissionAction::Create);
        return match (true) {
            $payload['type'] === 'Medical' || $payload['type'] === 'ADL'
            => $this->createMedicalPatient($branch->branch_id, $payload),

            default => throw new Exception('Unsupported booking type.', 422),
        };
    }

    // DONE MEDICAL
    private function createMedicalPatient(int $branchId, array $payload)
    {
        $patient = $payload['patient'];
        $service = $payload['service'];
        $assessment = $payload['assessment'];
        $assignments = $payload['assignments'] ?? [];

        $scheduledLocation = $this->locationRepository->create([
            'full_address' => $service['address'],
        ]);


        $patient = $this->patientRepository->create([
            'branch_id'          => $branchId,
            'location_id'        => $scheduledLocation->location_id,
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
            $service['date'] . ' ' . $service['prefered_time']
        );

        $schedule = $patient->schedules()->create([
            'scheduled_at'          => $scheduledAt,
            'status'                => 'Pending',
            'category'              => $payload['category'],
        ]);

        $invoiceServices = [];
        $scheduleServices = [];


        // FOR MEDICAL
        if (!empty($service['services'])) {
            foreach ($service['services'] as $item) {
                $scheduleService = $schedule->scheduleServices()->create([
                    'service_id'   => $item['service_id'] ?? null,
                    'hours_booked' => $service['time_span'] ?? null,
                    'status'       => 'pending',
                    'type'         => $service['type'],
                ]);

                $scheduleServices[] = $scheduleService;

                $invoiceServices[] = [
                    'schedule_services_id' => $scheduleService->schedule_services_id,
                    'price' => $item['price']
                ];
            }
        }

        // FOR ADL
        if (empty($service['services'])) {
            $scheduleService = $schedule->scheduleServices()->create([
                'hours_booked' => $service['time_span'] ?? null,
                'status'       => 'pending',
                'type'         => $service['type'],
            ]);

            $invoiceServices[] = [
                'schedule_services_id' => $scheduleService->schedule_services_id,
                'price' =>  $payload['payment']['total_amount'],
            ];
        }

        // FOR ASSIGNEMTN IF THERE IS 
        if (!empty($assignments)) {
            foreach ($assignments as $assignment) {
                foreach ($scheduleServices as $scheduleService) {
                    $scheduleService->assigned()->create([
                        'employee_id' => $assignment['employee_id'] ?? null,
                    ]);
                }
            }
        }

        $invoice = $this->invoiceService->createInvoiceService($invoiceServices, $branchId);

        return [
            'patient' => $patient,
            'invoice' =>   $invoice,
            'message' => 'Homecare booking has been approved successfully.',
        ];
    }

    // DONE MEDICAL
    public function createFacilityPatient(int $branchId, array $payload)
    {
        $patient = $payload['patient'];
        $guardian = $payload['guardian'];
        $assessment = $payload['assessment'];


        $patientLocation = null;
        if (!empty($patient['address'])) {
            $patientLocation = $this->locationRepository->create([
                'full_address' => $patient['address'],
            ]);
        }

        $patient = $this->patientRepository->create([
            'branch_id'          => $branchId,
            'location_id'        => $patientLocation?->location_id ?? null,
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
        return [
            'patient' => $patient,
            'patientAccess' => $patientAccess,
        ];
    }

    public function patientAccess(array $guardian, object $patient)
    {
        $guardianLocation = $this->locationRepository->create([
            'full_address' => $guardian['address'],
        ]);
        $guardian['location_id'] = $guardianLocation->location_id;

        $client = $this->userRepository->createUpdateTypeUser([
            'email' => $guardian['email'],
            'first_name' => $guardian['first_name'],
            'middle_name' => $guardian['middle_name'],
            'last_name' => $guardian['last_name'],
            'location_id' =>  $guardian['location_id'],
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

    public function retrievePatients(array $payload, User $user)
    {
        return PatientResource::collection($this->patientRepository->getPatient($payload));
    }

    public function showPatient(array $payload, User $user, string $uuid)
    {
        return PatientResource::collection($this->patientRepository->showPatient($uuid));
    }
}
