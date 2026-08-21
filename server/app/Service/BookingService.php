<?php

namespace App\Service;

use App\Factories\BookingFactory;
use App\Factories\PaymentFactory;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Service\Booking\BookingHelper;
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
        private BookingFactory $bookingFactory
    ) {}

    public function bookingAction(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $branch = $payload['branch'];

            $nonProcessableStatuses = [
                Booking::STATUS_APPROVED,
                Booking::STATUS_REJECTED,
                Booking::STATUS_EXPIRED,
                Booking::STATUS_COMPLETED,
                Booking::STATUS_CANCELLED,
            ];

            $booking = $this->bookingRepository->findByField([
                ['reference_id', '=', $payload['reference_id']],
                ['branch_id', '=', $branch->branch_id],
            ]);

            if (!$booking)  throw new Exception('Booking doesn\'t exist', 404);

            if (Carbon::now()->isAfter(Carbon::parse($booking['valid_until'])))  throw new Exception('Booking has expired.', 422);

            if (in_array($booking['status'], $nonProcessableStatuses)) {
                throw new Exception(
                    "Booking cannot be processed because its current status is '{$booking['status']}', can only process awaiting status.",
                    400
                );
            }

            if (isset($payload['facility'])) {
                $facility = $payload['facility'];
                if (isset($payload['reserved']['room']['beds'])) {
                    unset($payload['reserved']['room']['beds']);
                }
            }

            $data = $this->bookingFactory->process($payload);

            $bookingData = [
                'patient' =>   $payload['patient'],
                'guardian' =>  $payload['guardian'],
                'facility' =>  $facility ?? [],
                'homecare' =>  $payload['homecare'],
                'reserved' =>  $payload['reserved'],
                'payment'  =>  $payload['payment'],
            ];


            $booking->update([
                'booking_data' =>  $bookingData,
                'status' => Booking::STATUS_APPROVED
            ]);


            $booking->patientBookings()->create([
                'invoice_id' => $data['invoice']['invoice_id'],
                'patient_id' => $data['patient']['patient_id'],
            ]);



            return response()->json([
                'message' => "Booking {$booking['reference_id']} has been approved successfully.",
                'data'  => $booking->fresh()
            ], 200);
        });
    }

    // USED
    public function overview(array $payload)
    {
        return response()->json($this->bookingRepository->overview($payload['branch_id']));
    }

    // USED
    public function listBooking(User $user, array $payload)
    {
        $bookings = $this->bookingRepository
            ->paginate($payload['branch_id'], $payload);
        return BookingResource::collection($bookings);
    }

    //DONE
    public function completePayment(User $user, array $payload)
    {
        $branch = $payload['branch'];
        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $result = $paymentMethod->facilityBilling($payload);
        try {
            return DB::transaction(function () use ($user, $branch, $payload, $result, $paymentMethod) {

                $bookingData = $payload['booking_data'];

                $bookingData['payment']['total_amount'] = $result['total'];
                $bookingData['payment']['payment_status'] = 'paid';
                $bookingData['payment']['payment_method'] = $payload['payment_method'];
                $bookingData['payment']['xendit_invoice_id'] = $result['xendit_invoice_id'];
                $bookingData['assessment'] = $this->bookingHelper->resolveAssessment(
                    $bookingData['assessment'] ?? []
                );

                $validUntil = match ($payload['category']) {
                    Booking::CATEGORY_ONLINE => Carbon::parse(
                        $bookingData['homecare']['date'] . ' ' .
                            $bookingData['homecare']['prefered_time']
                    ),
                    Booking::CATEGORY_FACILITY => Carbon::parse(
                        $bookingData['facility']['admission_date']
                    )->endOfDay(),

                    default => Carbon::now()->addWeek(),
                };

                $booking = $this->bookingRepository->create([
                    'user_id'      => $user->user_id,
                    'branch_id'    => $branch->branch_id,
                    'category'     => $payload['category'],
                    'booking_data' => $bookingData,
                    'valid_until'  => $validUntil,
                    'booking_type' => Booking::BOOKINGTYPE_ONLINE,
                ]);

                if (!$booking) {
                    throw new Exception('Failed to create booking.', 500);
                }

                $this->notificationService->notifyNewBooking($branch, $user, $booking);

                return response()->json([
                    'data' => $booking,
                    'message' => 'Your booking has been submitted successfully! We\'ll review your request and notify you once it has been confirmed.',
                ], 200);
            });
        } catch (Exception $e) {
            XenditService::refundXenditPayment($result['xendit_invoice_id'], $result['total']);
            return response()->json([
                'status' => false,
                'message' => 'Booking failed. Your payment has been refunded.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // DONE 
    public function createBooking(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $branch = $payload['branch'];
            $bookingData = $payload['booking_data'];
            $bookingData['payment'] = $this->bookingHelper->resolvePayment($payload);
            $assessments = $bookingData['assessment'] ?? [];

            if (!is_array($assessments)) {
                $assessments = [$assessments];
            }
            $bookingData['assessment'] = array_values(array_filter(array_map(fn($assessment) => $this->bookingHelper->resolveAssessment($assessment),  $assessments)));

            $validUntil = match ($payload['category']) {
                Booking::CATEGORY_ONLINE => Carbon::parse(
                    $bookingData['homecare']['date'] . ' ' .
                        $bookingData['homecare']['prefered_time']
                ),
                Booking::CATEGORY_FACILITY => Carbon::parse(
                    $bookingData['facility']['admission_date']
                )->endOfDay(),
                default => Carbon::now()->addWeek(),
            };
            $booking = $this->bookingRepository->create([
                'user_id'      => $user->user_id,
                'branch_id'    => $branch->branch_id,
                'category'     => $payload['category'],
                'booking_data' => $bookingData,
                'valid_until'  => $validUntil,
                'booking_type' => Booking::BOOKINGTYPE_ONLINE
            ]);

            if (!$booking) {
                throw new Exception('Failed to create booking.', 500);
            }

            $this->notificationService->notifyNewBooking($branch, $user, $booking);

            return response()->json([
                'data' => $booking,
                'message' => 'Your booking has been submitted successfully! We\'ll review your request and notify you once it has been confirmed.',
            ], 200);
        });
    }
    //USED
    public function show(array $payload)
    {
        $booking = $this->bookingRepository->findByField([
            ['reference_id', '=', $payload['reference_id']],
            ['branch_id', '=', $payload['branch_id']],
        ]);

        if (!$booking) {
            throw new Exception('Booking does not exist.', 404);
        }

        return new BookingResource($booking);
    }

    //USED
    public function reject(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $booking = $this->bookingRepository->findByField([
                ['reference_id', '=', $payload['reference_id']],
                ['branch_id', '=', $payload['branch_id']]
            ]);
            $bookingData = $booking['booking_data'];
            if (!$booking) {
                throw new Exception('Booking doesn\'t exist', 404);
            }


            $payment = $payload['payment'] ?? null;

            if (
                $payment &&
                !empty($payment['xendit_invoice_id']) &&
                !empty($payment['total_amount'])
            ) {
                $bookingData['payment']['payment_status'] = 'refunded';
                XenditService::refundXenditPayment(
                    $payment['xendit_invoice_id'],
                    $payment['total_amount']
                );
            }

            $booking->update([
                'booking_data' => $bookingData,
                'status' => Booking::STATUS_REJECTED,
            ]);


            return [
                'data' => $booking->fresh(),
                'message' => 'Booking has successfully rejected',
            ];
        });
    }
}
