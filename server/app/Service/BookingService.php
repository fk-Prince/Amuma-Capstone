<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Factories\PaymentFactory;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\BranchContractRepository;
use App\Repository\BranchRepository;
use App\Repository\ServiceRepository;
use App\Service\Booking\BookingHelper;
use App\Service\Booking\FacilityBookingService;
use App\Service\External\SupabaseService;
use App\Service\External\XenditService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{

    public function __construct(
        private BookingRepository $bookingRepository,
        private NotificationService $notificationService,
        private BookingHelper $bookingHelper,
        private PatientService $patientService,
        private FacilityBookingService $facilityBookingService
    ) {}

    public function makeBooking(User $user, array $payload)
    {
        AuthGuard::requireUser($user);
        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        return $paymentMethod->facilityBilling($payload);
    }

    public function overview(array $payload)
    {
        return response()->json($this->bookingRepository->overview($payload['branch_id']));
    }

    public function listBooking(User $user, array $payload)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Read);
        return $this->bookingRepository->paginate($branch->branch_id, $payload);
    }

    public function createBooking(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
            $payload['branch_id'] = $branch->branch_id;
            $bookingData = $payload['booking_data'];
            if ((
                ($payload['category'] ?? null) === 'facility' &&
                ($payload['booking_data']['service']['type'] ?? null) === 'Pre-Admission'
            )) {
                $bookingData['payment'] = [
                    'total_amount' => 0,
                    'paid' => false,
                ];
            } else {
                $bookingData['payment'] = [
                    'total_amount' => $this->bookingHelper->getTotal($payload) ?? 0,
                    'paid' => false,
                ];
            }
            $assessment = $bookingData['assessment'];
            if (!empty($assessment['diagnosis_file'])) {
                try {
                    $uploadResult = SupabaseService::store($assessment['diagnosis_file']);

                    if (empty($uploadResult['url'])) {
                        throw new Exception('Diagnosis file upload failed: no URL returned.');
                    }
                    $assessment['diagnosis_file'] = $uploadResult['url'];
                } catch (\Throwable $e) {
                    throw new Exception('We couldn\'t upload your diagnosis file. Please try again or use a different file.', 422, $e);
                }
            }
            $bookingData['assessment'] = $assessment;

            $booking = $this->bookingRepository->create([
                'user_id'      => $user->user_id,
                'branch_id'    => $branch->branch_id,
                'category'     => ucfirst($payload['category']),
                'booking_data' => $bookingData,
                'valid_until'  => Carbon::now()->addWeek(),
            ]);
            if (!$booking) {
                throw new Exception('Failed to create booking.', 500);
            }
            $this->notificationService->notifyNewBooking($branch, $user, $booking);

            return response()->json([
                'data' => $booking,
                'message' => 'Your booking has been submitted successfully! We\'ll review your request and notify you once it has been confirmed.'
            ], 200);
        });
    }


    public function bookingAction(User $user, array $payload)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Approve);
        return  DB::transaction(function () use ($user, $payload, $branch) {

            $booking = $this->bookingRepository->findByField([
                ['reference_id', '=', $payload['reference_id']]
            ]);
            if (!$booking) {
                throw new Exception('Booking doesn\'t exist', 404);
            }
            if (Carbon::today()->isAfter(Carbon::parse($booking->valid_until))) {
                throw new Exception('Booking has expired.', 422);
            }

            $bookingData = $booking->booking_data;
            $bookingPayload = [
                'branch_id'      => $branch->branch_id,
                'branch_uuid'    => $branch->uuid,
                'type'           => $bookingData['service']['type'],
                'category'       => $booking->category,
                'status'         => $booking->status,
                'patient'        => $bookingData['patient'],
                'guardian'       => $bookingData['guardian'],
                'service'        => $bookingData['service'],
                'assessment'     => $bookingData['assessment'],
                'assignments'    => $payload['assignments'] ?? [],
                'payment'        => $bookingData['payment'] ?? [],
                'reserved'       => $payload['booking_data']['reserved'] ?? [],
            ];

            $message = 'Booking has been accepted';
            if ($booking['category'] == Booking::CATEGORY_ONLINE) {
                if ($booking->status !== Booking::STATUS_PENDING) {
                    throw new Exception("Booking status must be pending. Current status: {$booking->status}", 400);
                }
                $data = $this->patientService->createPatient($bookingPayload, $user);
                if (!$data) {
                    throw new Exception('Unable to insert a new patient.', 500);
                }
                $patient = $data['patient'];
                $booking->update([
                    'status' => Booking::STATUS_APPROVED,
                ]);
                $message = $data['message'] ?? 'Homecare booking has been approved successfully.';
                $patient->bookings()->create([
                    'booking_id' => $booking['booking_id']
                ]);
            } else if ($bookingPayload['type'] == Booking::TYPE_COMPLETEADMISSION) {
                $data = $this->facilityBookingService->completeAdmission($bookingPayload);
            } else if ($bookingPayload['type'] == Booking::TYPE_PREADMISSION) {
                $booking->update([
                    'status' => Booking::STATUS_APPROVED,
                ]);
                $message = 'Facility booking has been approved.';
            } else if ($bookingPayload['type'] == Booking::TYPE_WALKINADMISSION) {
                $data = $this->facilityBookingService->completeAdmission($bookingPayload);
                $booking->update([
                    'status' => Booking::STATUS_COMPLETED,
                ]);
                $message = 'Patient admission completed successfully.';
            }

            return response()->json([
                'data' => $booking,
                'message' => $message
            ], 200);
        });
    }

    public function show(array $payload)
    {
        $booking = $this->bookingRepository->findByField([
            ['reference_id', '=', $payload['reference_id']],
            ['branch_id', '=', $payload['branch_id']]
        ]);
        if (!$booking) {
            throw new Exception("Booking does not exist.", 404);
        }
        return $booking;
    }

    public function reject(User $user, array $payload)
    {
        $booking = $this->bookingRepository->findByField([
            ['reference_id', '=', $payload['reference_id']]
        ]);
        if (!$booking) {
            throw new Exception('Booking doesn\'t exist', 404);
        }

        $booking->update([
            'status' => Booking::STATUS_REJECTED,
        ]);

        return 'Booking has successfully rejected';
    }
}
