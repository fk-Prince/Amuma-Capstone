<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Events\NotificationEvent;
use App\Repository\BookingRepository;
use App\Http\Resources\BookingResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\LocationRepository;
use App\Repository\ModuleRepository;
use App\Repository\NotificationRepository;
use App\Repository\PatientRepository;
use App\Repository\ScheduleRepository;
use App\Repository\UserRepository;
use App\Service\Utils\AuthGuard;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stevebauman\Location\Facades\Location;

class BookingService
{
    private BookingRepository $bookingRepository;
    private NotificationRepository $notificationRepository;
    private BranchRepository $branchRepository;
    private ModuleRepository $moduleRepository;
    private PatientRepository $patientRepository;
    private LocationRepository $locationRepository;

    public function __construct(
        BookingRepository $bookingRepository,
        BranchRepository $branchRepository,
        NotificationRepository $notificationRepository,
        ModuleRepository $moduleRepository,
        PatientRepository $patientRepository,
        LocationRepository $locationRepository,
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->branchRepository = $branchRepository;
        $this->notificationRepository = $notificationRepository;
        $this->moduleRepository = $moduleRepository;
        $this->patientRepository = $patientRepository;
        $this->locationRepository = $locationRepository;
    }


    public function createBooking(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {

            $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

            if (!$branch) {
                throw new Exception("Branch doesnt exist");
            }


            $bookingData = [
                'user_id' => $user->user_id,
                'branch_id' => $branch->branch_id,
                'category' => ucfirst($payload['category']),
                'booking_data' => $payload['booking_data'],
                'valid_until' => Carbon::now()->addDay(),
            ];

            $booking = $this->bookingRepository->create($bookingData);

            if (!$booking) {
                throw new Exception("Failed to create booking.", 500);
            }

            $message = "You have a new booking request. Booking #{$booking->reference_id} is waiting for your review.";

            $employees = $this->moduleRepository->getEmployeesModuleWithPermission(
                [PermissionAction::Read],
                ModuleEnum::Bookings,
                $branch->branch_id
            );

            foreach ($employees as $employee) {
                $this->notificationRepository->create([
                    'branch_id' => $branch->branch_id,
                    'to_user_id' => $employee['user_id'],
                    'from_user_id' => $user->user_id,
                    'message_type' => 'Booking',
                    'message' => $message,
                ]);

                event(new NotificationEvent(
                    $employee['uuid'],
                    $message,
                    $booking->reference_id,
                    $branch->uuid
                ));
            }

            return $booking;
        });
    }

    public function listBooking(User $user, array $payload)
    {

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception("Branch doesnt exist");
        }

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Read);

        return $this->bookingRepository->paginate($branch->branch_id, $payload);
    }

    public function bookingAccepted(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {

            $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

            if (!$branch)  throw new Exception("Branch doesn't exist", 404);

            AuthGuard::requireModule($user, $branch->branch_id,   ModuleEnum::Bookings,  PermissionAction::Create);

            $booking = $this->bookingRepository->findByField([
                ['booking_id', '=', $payload['booking_id']],
            ]);

            if (!$booking) throw new Exception("Booking doesn't exist", 404);


            if ($booking->status !== 'pending') {
                throw new Exception("Booking status must be pending. Current status: {$booking->status}",  400);
            }

            $bookingData = $booking->booking_data;

            $patientData = $bookingData['patient'];
            $serviceData = $bookingData['service'];
            $assessmentData = $bookingData['assessment'];

            $patientLocation = $this->locationRepository->create([
                'full_address' => $patientData['address'],
            ]);
            $scheduledLocation = $this->locationRepository->create([
                'full_address' => $serviceData['address'],
            ]);

            $patient = $this->patientRepository->create([
                'branch_id' => $branch->branch_id,
                'location_id' => $patientLocation->location_id,
                'first_name' => $patientData['first_name'],
                'middle_name' => $patientData['middle_name'],
                'last_name' => $patientData['last_name'],
                'gender' => $patientData['gender'] ?? null,
                'height' => $patientData['height'] ?? null,
                'weight' => $patientData['weight'] ?? null,
                'blood_type' => $patientData['blood_type'] ?? null,
                'date_of_birth' => $patientData['date_of_birth'] ?? null,
                'phone_number' => $patientData['phone_number'] ?? null,
                'citizenship' => $patientData['citizenship'] ?? null,
                'initial_assessment' => $assessmentData,
                'medication' => [],
            ]);

            $schedule = $patient->schedules()->create([
                'scheduled_location_id' => $scheduledLocation->location_id,
                'scheduled_at' => $serviceData['date'],
                'status' => 'Pending',
                'category' => $bookingData['category'],
            ]);

            foreach ($serviceData['services'] as $service) {
                $schedule->scheduleServices()->create([
                    'service_id' => $service['service_id'] ?? null,
                    'hours_booked' => $serviceData['time_span'],
                    'status' => 'Pending',
                    'type' => $serviceData['type'],
                ]);
            }

            $booking->update([
                'status' => 'completed', // update this to confirmed
            ]);

            return response()->json([
                'message' => 'Booking successfully processed.',
                'data' => [
                    'patient' => $patient,
                ],
            ], 200);
        });
    }
}
