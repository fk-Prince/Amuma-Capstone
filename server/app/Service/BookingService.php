<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Factories\PaymentFactory;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Models\Branch;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\BranchContractRepository;
use App\Repository\BranchRepository;
use App\Repository\ServiceRepository;
use App\Service\External\SupabaseService;
use App\Service\External\XenditService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_APPROVED = 'approved';


    public function __construct(
        private BookingRepository $bookingRepository,
        private BranchRepository $branchRepository,
        private NotificationService $notificationService,
        private BranchContractRepository $branchContractRepository,
        private ServiceRepository $serviceRepository,
        private PatientService $patientService,
    ) {}

    public function makeBooking(User $user, array $payload)
    {
        AuthGuard::requireUser($user);
        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        return $paymentMethod->facilityBilling($payload);
    }

    public function listBooking(User $user, array $payload)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Read);
        return $this->bookingRepository->paginate($branch->branch_id, $payload);
    }

    public function createPaymentBooking(User $user, array $payload)
    {
        $referenceId = $payload['xendit_invoice_id'] ?? null;

        if (!$referenceId) {
            throw new Exception('Payment failed. Invalid card or charge was not created.', 400);
        }

        try {
            return DB::transaction(function () use ($user, $payload) {
                $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
                $bookingData = $payload['metadata']['booking_data'];
                $bookingData['payment'] = [
                    'xendit_invoice_id' => $payload['xendit_invoice_id'],
                    'payment_method'    => $payload['metadata']['payment_method'],
                    'total_amount'      => $payload['metadata']['total_amount'],
                    'payed'             => true,
                ];

                $booking = $this->storeBooking($user, $branch, $payload['metadata']['category'], $bookingData);
                $this->notifyNewBooking($branch, $user, $booking);
                return $booking;
            });
        } catch (Exception $e) {
            XenditService::refundXenditPayment($referenceId, $payload['metadata']['total_amount']);
            return response()->json([
                'status'  => false,
                'message' => 'Payment failed. Your payment has been refunded.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function createBooking(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);

            $bookingData = $payload['booking_data'];
            $bookingData['payment'] = [
                'total_amount' => $this->getTotal($payload) ?? 0,
                'payed'        => false,
            ];
            $booking = $this->storeBooking($user, $branch, $payload['category'], $bookingData);
            $this->notifyNewBooking($branch, $user, $booking);
            return $booking;
        });
    }

    public function bookingAccepted(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Create);


            $booking = $this->findPendingBookingOrFail($payload['booking_id']);

            $bookingData = $booking->booking_data;
            $serviceData = $bookingData['service'];

            $payload = [
                'branch_id' => $branch->branch_id,
                'type' => $serviceData['type'],
                'category' => $booking->category,
                'patientData' => $bookingData['patient'],
                'guardianData' => $bookingData['guardian'],
                'serviceData' => $bookingData['service'],
                'assessmentData' => $bookingData['assessment'],
            ];

            $patient = $this->patientService->createPatient($payload, $user);

            $booking->update(['status' => self::STATUS_APPROVED]);

            return response()->json([
                'message' => 'Booking successfully processed.',
                'data'    => ['patient' => $patient],
            ], 200);
        });
    }

    public function admissionAccepted(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);

            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Create);

            $booking = $this->findPendingBookingOrFail($payload['booking_id']);
            $bookingData = $booking->booking_data;
            $serviceData = $bookingData['service'];
            $admissionPayload = [
                'branch_id'      => $branch->branch_id,
                'branch_uuid'    => $branch->uuid,
                'type'           => $serviceData['type'],
                'category'       => $booking->category,
                'patientData'    => $bookingData['patient'],
                'guardianData'   => $bookingData['guardian'],
                'serviceData'    => $serviceData,
                'assessmentData' => $bookingData['assessment'],
            ];
            $data = $this->patientService->createPatient($admissionPayload, $user);

            if (!$data) {
                throw new Exception('Unable to admit patient.', 500);
            }

            $booking->update(['status' => self::STATUS_APPROVED]);

            return response()->json([
                'data'    => $data,
                'message' => 'You successfully admitted a patient',
            ], 200);
        });
    }

    public function getTotal(array $payload)
    {
        return match (strtolower($payload['category'])) {
            'homecare' => match ($payload['booking_data']['service']['type']) {
                'Medical' => $this->getMedicalTotal($payload),
                'ADL'     => $this->getAdlTotal($payload),
                default   => 0,
            },
            'facility' => $this->getFacilityTotal($payload),
            default    => 0,
        };
    }

    protected function getMedicalTotal(array $payload)
    {
        $serviceIds = collect($payload['booking_data']['service']['services'])
            ->pluck('service_id')
            ->toArray();

        $services = $this->serviceRepository->findByFields([
            ['service_id', 'IN', $serviceIds],
        ]);

        return $services->sum('price');
    }

    protected function getAdlTotal(array $payload)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branch->branch_id],
            ['accommodation_type', '=', strtoupper($payload['booking_data']['service']['type'])],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception('No active ADL pricing contract is configured for this branch.', 404);
        }

        $total = $payload['booking_data']['service']['time_span'] * $contract->price;

        if ($total <= 0) {
            throw new Exception('The ADL pricing contract has an invalid price.', 422);
        }

        return $total;
    }

    protected function getFacilityTotal(array $payload)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branch->branch_id],
            ['accommodation_type', '=', strtoupper($payload['booking_data']['service']['plan'])],
            ['billing_cycle', '=', strtoupper($payload['booking_data']['service']['billing_cycle'])],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception(
                sprintf(
                    'No active %s (%s) pricing contract is configured for this branch.',
                    $payload['booking_data']['service']['plan'],
                    $payload['booking_data']['service']['billing_cycle']
                ),
                404
            );
        }

        if ($contract->price <= 0) {
            throw new Exception(
                sprintf(
                    'The %s (%s) pricing contract has an invalid price.',
                    $contract->accommodation_type,
                    $contract->billing_cycle
                ),
                422
            );
        }

        return $contract->price;
    }

    private function findPendingBookingOrFail(int $bookingId)
    {
        $booking = $this->bookingRepository->findByField([
            ['booking_id', '=', $bookingId],
        ]);

        if (!$booking) {
            throw new Exception('Booking doesn\'t exist', 404);
        }

        if ($booking->status !== self::STATUS_PENDING) {
            throw new Exception("Booking status must be pending. Current status: {$booking->status}", 400);
        }

        return $booking;
    }

    private function storeBooking(User $user, Branch $branch, string $category, array $bookingData)
    {

        $assessment = $bookingData['assessment'];

        if (!empty($assessment['diagnosis_file'])) {
            $assessment['diagnosis_file'] = SupabaseService::store(
                $assessment['diagnosis_file']
            )['url'];
        }

        $bookingData['assessment'] = $assessment;

        $booking = $this->bookingRepository->create([
            'user_id'      => $user->user_id,
            'branch_id'    => $branch->branch_id,
            'category'     => ucfirst($category),
            'booking_data' => $bookingData,
            'valid_until'  => Carbon::now()->addWeek(),
        ]);



        if (!$booking) {
            throw new Exception('Failed to create booking.', 500);
        }

        return $booking;
    }

    private function notifyNewBooking(Branch $branch, User $user, object $booking): void
    {
        $this->notificationService->sendNotification([
            'branch_id'    => $branch->branch_id,
            'branch_uuid'  => $branch->uuid,
            'user_id'      => $user->user_id,
            'reference_id' => $booking->booking_id,
            'message'      => "You have a new booking request. Booking #{$booking->reference_id} is waiting for your review.",
        ]);
    }
}
