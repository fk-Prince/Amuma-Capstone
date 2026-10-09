<?php

namespace App\Service;

use App\Factories\BookingFactory;
use App\Factories\PaymentFactory;
use App\Http\Resources\BookingResource;
use App\Guard\GuardianEmailGuard;
use App\Models\Booking;
use App\Models\Patient;
use App\Models\PatientAccess;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Service\Booking\BookingHelper;
use App\Service\External\XenditService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
                'status' => Booking::STATUS_APPROVED,
                'reviewed_by' => ($payload['user'] ?? null)?->employee?->employee_id,
            ]);


            $booking->patientBookings()->create([
                'invoice_id' => $data['invoice']['invoice_id'],
                'patient_id' => $data['patient']['patient_id'],
            ]);

            $this->notificationService->notifyBookingDecision(
                $branch,
                $booking->fresh(),
                Booking::STATUS_APPROVED,
                $payload['user'] ?? null
            );

            return response()->json([
                'message' => "Booking {$booking['reference_id']} has been approved successfully.",
                'data'  => new BookingResource($booking->fresh())
            ], 200);
        });
    }

    public function accept(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $booking = $this->bookingRepository->findByField([
                ['reference_id', '=', $payload['reference_id']],
                ['branch_id', '=', $payload['branch_id']],
            ]);

            if (!$booking) throw new Exception('Booking doesn\'t exist', 404);

            if ($booking->status !== Booking::STATUS_PENDING) {
                throw new Exception(
                    "Booking cannot be approved. Current status: {$booking->status}.",
                    400
                );
            }

            if ($booking->valid_until && Carbon::now()->isAfter(Carbon::parse($booking->valid_until))) {
                throw new Exception('Booking has expired.', 422);
            }

            $facilityType = $booking->booking_data['facility']['type'] ?? '';

            if (strcasecmp($facilityType, Booking::TYPE_PREADMISSION) !== 0) {
                throw new Exception('Only pre-admission bookings can be approved without processing.', 422);
            }

            $booking->update([
                'status' => Booking::STATUS_APPROVED,
                'reviewed_by' => ($payload['user'] ?? null)?->employee?->employee_id,
            ]);

            $this->notificationService->notifyBookingDecision(
                $payload['branch'],
                $booking->fresh(),
                Booking::STATUS_APPROVED,
                $payload['user'] ?? null
            );

            return response()->json([
                'message' => "Booking {$booking->reference_id} has been approved.",
                'data' => new BookingResource($booking->fresh()),
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
        $breakdown = $this->bookingHelper->resolveBookingPayment($payload);
        $payload['total'] = $breakdown['booking_amount'];
        $payload['breakdown'] = $breakdown;

        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $result = $paymentMethod->facilityBilling($payload);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->storePaidBooking($user, $payload, $result, $breakdown);
    }

    public function storePaidBooking(User $user, array $payload, array $result, array $breakdown)
    {
        $branch = $payload['branch'];

        try {
            return DB::transaction(function () use ($user, $branch, $payload, $result, $breakdown) {

                $bookingData = $payload['booking_data'];

                $bookingData['payment']['total_amount'] = $breakdown['total_amount'];
                $bookingData['payment']['booking_percent'] = $breakdown['booking_percent'];
                $bookingData['payment']['booking_amount'] = $breakdown['booking_amount'];
                $bookingData['payment']['balance_amount'] = $breakdown['balance_amount'];
                $bookingData['payment']['payment_status'] = $breakdown['balance_amount'] > 0 ? 'partial' : 'paid';
                $bookingData['payment']['payment_method'] = $payload['payment_method'];
                $bookingData['payment']['xendit_invoice_id'] = $result['xendit_invoice_id'];
                $bookingData['payment']['masked_card_number'] = $result['masked_card_number'];
                $bookingData['patient'] = $this->bookingHelper->resolvePatient(
                    $bookingData['patient'] ?? []
                );
                $bookingData['diagnoses'] = $this->bookingHelper->resolveDiagnoses(
                    $bookingData['diagnoses'] ?? []
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
            report($e);

            XenditService::refundXenditPayment(
                $result['xendit_invoice_id'],
                $result['total'],
                (bool) ($result['masked_card_number'] ?? null)
            );
            return response()->json([
                'status' => false,
                'message' => 'Booking failed. Your payment has been refunded.',
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
            $bookingData['assessment'] = array_values(array_filter($assessments));
            $bookingData['patient'] = $this->bookingHelper->resolvePatient(
                $bookingData['patient'] ?? []
            );
            $bookingData['diagnoses'] = $this->bookingHelper->resolveDiagnoses(
                $bookingData['diagnoses'] ?? []
            );

            $validUntil = match ($payload['category']) {
                Booking::CATEGORY_ONLINE => Carbon::parse(
                    $bookingData['homecare']['date'] . ' ' .
                        $bookingData['homecare']['prefered_time']
                ),
                Booking::CATEGORY_FACILITY => ($bookingData['facility']['type'] ?? null) === Booking::TYPE_PREADMISSION
                    ? Carbon::now()->addMonth()
                    : Carbon::parse($bookingData['facility']['admission_date'])->endOfDay(),
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
    // A homecare service booked by staff for a new patient and guardian.
    public function createStaffHomecareBooking(User $staff, array $payload)
    {
        $branch = $payload['branch'];

        if (!$branch->hasHomecareSubscription()) {
            throw new Exception('This branch has no Homecare Service subscription.', 422);
        }

        $data = validator($payload, [
            'patient' => ['required', 'array'],
            'patient.first_name' => ['required', 'string', 'max:100'],
            'patient.middle_name' => ['nullable', 'string', 'max:100'],
            'patient.last_name' => ['required', 'string', 'max:100'],
            'patient.suffix' => ['nullable', 'string', 'max:20'],
            'patient.gender' => ['required', 'string', 'max:20'],
            'patient.date_of_birth' => ['required', 'date', 'before:today'],
            'patient.address' => ['required', 'string', 'max:255'],
            'patient.phone_number' => ['nullable', 'string', 'max:30'],
            'guardian' => ['required', 'array'],
            'guardian.first_name' => ['required', 'string', 'max:100'],
            'guardian.middle_name' => ['nullable', 'string', 'max:100'],
            'guardian.last_name' => ['required', 'string', 'max:100'],
            'guardian.email' => ['required', 'email', 'max:255'],
            'guardian.phone_number' => ['required', 'string', 'max:30'],
            'guardian.relationship' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:' . Booking::TYPE_ADL . ',' . Booking::TYPE_MEDICAL],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'prefered_time' => ['required', 'string', 'max:10'],
            'time_span' => ['nullable'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'services' => ['nullable', 'array'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'diagnoses' => ['nullable', 'array'],
            'assessment' => ['nullable', 'array'],
        ])->validate();

        // Only the keys named above survive validation, so the full patient,
        // guardian, diagnosis and assessment forms are taken from the payload.
        $patient = $this->bookingHelper->resolvePatient($payload['patient']);
        $guardian = $payload['guardian'];
        $assessments = $payload['assessment'] ?? [];

        GuardianEmailGuard::assertAllowed($data['guardian']['email']);

        $owner = User::where('email', $data['guardian']['email'])->first();

        $bookingData = [
            'patient' => $patient + ['middle_name' => null],
            'guardian' => $guardian + ['middle_name' => null],
            'homecare' => [
                'type'          => $data['type'],
                'date'          => $data['date'],
                'prefered_time' => $data['prefered_time'],
                'time_span'     => $data['time_span'] ?? null,
                'address'       => $data['address'],
                'latitude'      => $data['latitude'] ?? null,
                'longitude'     => $data['longitude'] ?? null,
                'services'      => $data['services'] ?? [],
                'price'         => $data['type'] === Booking::TYPE_ADL ? ($data['rate'] ?? null) : null,
            ],
            'payment' => [
                'total_amount' => $data['price'] ?? 0,
            ],
            'assessment' => array_values(array_filter(is_array($assessments) ? $assessments : [$assessments])),
            'diagnoses' => $this->bookingHelper->resolveDiagnoses($payload['diagnoses'] ?? []),
        ];

        $booking = $this->bookingRepository->create([
            // The family sees it in their portal; with no guardian account it stays with staff.
            'user_id'      => $owner?->user_id ?? $staff->user_id,
            'branch_id'    => $branch->branch_id,
            'category'     => Booking::CATEGORY_ONLINE,
            'booking_data' => $bookingData,
            'status'       => Booking::STATUS_PENDING,
            'valid_until'  => Carbon::parse($data['date'] . ' ' . $data['prefered_time']),
            'booking_type' => Booking::BOOKINGTYPE_ONLINE,
        ]);

        $this->notificationService->notifyNewBooking($branch, $staff, $booking);

        return response()->json([
            'message' => 'Homecare booking created. It is waiting for review.',
            'data' => new BookingResource($booking->fresh()),
        ], 201);
    }

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
    public function expire(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $bookingData = $booking->booking_data;
            $payment = $bookingData['payment'] ?? [];

            $refundable = ($payment['payment_status'] ?? null) === 'paid'
                && !empty($payment['xendit_invoice_id'])
                && (float) ($payment['total_amount'] ?? 0) > 0;

            $refunded = $refundable && XenditService::refundXenditPayment(
                $payment['xendit_invoice_id'],
                (float) $payment['total_amount'],
                (bool) ($payment['masked_card_number'] ?? null)
            );

            if ($refunded) {
                $bookingData['payment']['payment_status'] = 'refunded';
            }

            $booking->update([
                'booking_data' => $bookingData,
                'status' => Booking::STATUS_EXPIRED,
            ]);

            return $refunded;
        });
    }


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

            $reason = trim((string) ($payload['reason'] ?? '')) ?: null;

            $booking->update([
                'booking_data' => $bookingData,
                'status' => Booking::STATUS_REJECTED,
                'reason' => $reason,
                'reviewed_by' => ($payload['user'] ?? null)?->employee?->employee_id,
            ]);

            $this->notificationService->notifyBookingDecision(
                $payload['branch'],
                $booking->fresh(),
                Booking::STATUS_REJECTED,
                $payload['user'] ?? null,
                $reason
            );

            return [
                'data' => new BookingResource($booking->fresh()),
                'message' => 'Booking has successfully rejected',
            ];
        });
    }
}
