<?php

namespace App\Service;

use App\Factories\BookingFactory;
use App\Factories\PaymentFactory;
use App\Http\Resources\BookingResource;
use App\Guard\GuardianEmailGuard;
use App\Models\Booking;
use App\Models\Patient;
use App\Models\PatientAccess;
use App\Models\Transaction;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\PaymentRepository;
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
        private BookingFactory $bookingFactory,
        private TransactionService $transactions,
        private PaymentRepository $paymentRepository
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

            $data = $this->bookingFactory->process($payload + ['booking_id' => $booking->booking_id]);

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
        if (!$this->bookingHelper->requiresPayment($payload)) {
            throw new Exception('This booking does not take a payment.', 422);
        }

        if ($this->bookingHelper->isHomecare($payload)) {
            $this->bookingHelper->assertHomecareLeadTime(
                $payload['booking_data']['homecare'] ?? [],
                $payload['branch'] ?? null
            );
        }

        $this->bookingHelper->assertDiagnoses($payload['booking_data']['diagnoses'] ?? []);

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

                $this->recordBookingPayment($user, $booking, $bookingData, $breakdown);

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

    private function recordBookingPayment(User $user, Booking $booking, array $bookingData, array $breakdown): void
    {
        $payment = $bookingData['payment'];
        $client = $user->client;

        $transaction = $this->transactions->forPayment(
            $breakdown['booking_amount'],
            $booking->branch_id,
            $bookingData['patient']['patient_id'] ?? null,
            ($this->bookingHelper->isHomecare(['category' => $booking->category])
                ? 'Downpayment'
                : 'Reservation fee') . " for booking {$booking->reference_id}.",
            [
                'booking_id' => $booking->booking_id,
                'client_id' => $client?->client_id,
                'method' => $payment['payment_method'],
                'party_name' => trim(
                    ($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')
                ) ?: null,
                'masked_account_number' => $payment['masked_card_number'] ?? null,
                'transaction_reference_id' => $payment['xendit_invoice_id'] ?? null,
            ]
        );

        $this->paymentRepository->create([
            'transaction_id' => $transaction->transaction_id,
            'prior_balance' => $breakdown['total_amount'],
            'new_balance' => $breakdown['balance_amount'],
            'cash_tendered' => $breakdown['booking_amount'],
            'created_at' => now(),
        ]);
    }

    // DONE
    public function createBooking(User $user, array $payload)
    {
        if ($this->bookingHelper->isHomecare($payload)) {
            throw new Exception('Homecare bookings require a downpayment before they can be submitted.', 422);
        }

        $this->bookingHelper->assertDiagnoses($payload['booking_data']['diagnoses'] ?? []);

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

        $this->bookingHelper->assertHomecareLeadTime($data, $branch);

        $this->bookingHelper->assertDiagnoses($payload['diagnoses'] ?? []);

        $patient = $this->bookingHelper->resolvePatient($payload['patient']);
        $guardian = $payload['guardian'];
        $assessments = $payload['assessment'] ?? [];

        GuardianEmailGuard::assertAllowed($data['guardian']['email']);

        $owner = User::where('email', $data['guardian']['email'])->first();

        $total = round((float) ($data['price'] ?? 0), 2);

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
                'total_amount' => $total,
                'payment_status' => 'pending',
            ],
            'assessment' => array_values(array_filter(is_array($assessments) ? $assessments : [$assessments])),
            'diagnoses' => $this->bookingHelper->resolveDiagnoses($payload['diagnoses'] ?? []),
        ];

        $booking = $this->bookingRepository->create([
            'user_id'      => $owner?->user_id ?? $staff->user_id,
            'branch_id'    => $branch->branch_id,
            'category'     => Booking::CATEGORY_ONLINE,
            'booking_data' => $bookingData,
            'status'       => Booking::STATUS_PENDING,
            'valid_until'  => Carbon::parse($data['date'] . ' ' . $data['prefered_time']),
            'booking_type' => Booking::BOOKINGTYPE_ONLINE,
        ]);

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

    private function refundBookingPayment(Booking $booking, array &$bookingData): bool
    {
        $payment = $bookingData['payment'] ?? [];
        $amount = (float) ($payment['booking_amount'] ?? $payment['total_amount'] ?? 0);

        $refundable = in_array($payment['payment_status'] ?? null, ['paid', 'partial'], true)
            && !empty($payment['xendit_invoice_id'])
            && $amount > 0;

        if (!$refundable) {
            return false;
        }

        $refunded = XenditService::refundXenditPayment(
            $payment['xendit_invoice_id'],
            $amount,
            (bool) ($payment['masked_card_number'] ?? null)
        );

        if (!$refunded) {
            return false;
        }

        $bookingData['payment']['payment_status'] = 'refunded';

        $paid = $this->transactions->bookingPayment($booking->booking_id);

        if ($paid) {
            $this->transactions->forWithdraw(
                $amount,
                $booking->branch_id,
                $paid->patient_id,
                "Refund for booking {$booking->reference_id}.",
                Transaction::STATUS_COMPLETED,
                [
                    'booking_id' => $booking->booking_id,
                    'client_id' => $paid->client_id,
                    'method' => $paid->method,
                    'party_name' => $paid->party_name,
                    'masked_account_number' => $paid->masked_account_number,
                    'transaction_reference_id' => $paid->transaction_reference_id,
                ]
            );
        }

        return true;
    }

    //USED
    public function expire(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $bookingData = $booking->booking_data;
            $refunded = $this->refundBookingPayment($booking, $bookingData);

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
            if (!$booking) {
                throw new Exception('Booking doesn\'t exist', 404);
            }

            $bookingData = $booking['booking_data'];

            $this->refundBookingPayment($booking, $bookingData);

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
