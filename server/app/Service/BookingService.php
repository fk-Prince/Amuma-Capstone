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
use App\Http\Resources\BookingReceiptResource;
use App\Service\Booking\BookingHelper;
use App\Service\External\XenditService;
use App\Service\Payment\GCashPayment;
use Carbon\Carbon;
use Exception;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                Booking::STATUS_ACCEPTED,
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
                    "Booking cannot be accepted. Current status: {$booking->status}.",
                    400
                );
            }

            if ($booking->valid_until && Carbon::now()->isAfter(Carbon::parse($booking->valid_until))) {
                throw new Exception('Booking has expired.', 422);
            }

            $facilityType = $booking->booking_data['facility']['type'] ?? '';

            if (strcasecmp($facilityType, Booking::TYPE_PREADMISSION) !== 0) {
                throw new Exception('Only pre-admission bookings can be accepted without processing.', 422);
            }

            $booking->update([
                'status' => Booking::STATUS_ACCEPTED,
                'reviewed_by' => ($payload['user'] ?? null)?->employee?->employee_id,
            ]);

            $this->notificationService->notifyBookingDecision(
                $payload['branch'],
                $booking->fresh(),
                Booking::STATUS_ACCEPTED,
                $payload['user'] ?? null
            );

            return response()->json([
                'message' => "Booking {$booking->reference_id} has been accepted.",
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

        $this->bookingHelper->assertNoEmoji($payload['booking_data'] ?? []);
        $this->bookingHelper->assertDiagnoses($payload['booking_data']['diagnoses'] ?? []);

        $breakdown = $this->bookingHelper->resolveBookingPayment($payload);
        $payload['total'] = $breakdown['booking_amount'];
        $payload['breakdown'] = $breakdown;

        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $result = $paymentMethod->bookingBilling($payload);

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

                $booking = $this->createUnaudited([
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

                $this->recordDownpayment($user, $booking, $bookingData['payment']);

                $this->notificationService->notifyNewBooking($branch, $user, $booking);

                return response()->json([
                    'data' => $booking,
                    'message' => 'Your booking has been submitted successfully',
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

    // A failed alert must never undo a payment that has already gone through.
    private function notifyPayment(Booking $booking, float $amount, ?User $actor): void
    {
        try {
            $branch = $booking->branch ?? $booking->load('branch')->branch;

            if ($branch) {
                $this->notificationService->notifyBookingPayment($branch, $booking, $amount, $actor);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    // Bookings made on the public page aren't audited; only staff actions are.
    private function createUnaudited(array $attributes): Booking
    {
        return app(AuditService::class)->withoutAuditing(
            fn() => $this->bookingRepository->create($attributes)
        );
    }

    // Only a booking that was actually paid for has a downpayment to record.
    private function recordDownpayment(User $user, Booking $booking, array $payment): void
    {
        $amount = (float) ($payment['booking_amount'] ?? 0);

        if ($amount <= 0) {
            return;
        }

        $client = $user->client;

        $this->transactions->forPayment(
            $amount,
            $booking->branch_id,
            null,
            "Downpayment for booking {$booking->reference_id}.",
            [
                'booking_id' => $booking->booking_id,
                'client_id' => $client?->client_id,
                'method' => $payment['payment_method'] ?? null,
                'party_name' => trim(
                    ($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')
                ) ?: null,
                'masked_account_number' => $payment['masked_card_number'] ?? null,
                'transaction_reference_id' => $payment['xendit_invoice_id'] ?? null,
            ]
        );
    }

    // DONE
    public function createBooking(User $user, array $payload)
    {
        if ($this->bookingHelper->isHomecare($payload)) {
            throw new Exception('Homecare bookings require a downpayment before they can be submitted.', 422);
        }

        $this->bookingHelper->assertNoEmoji($payload['booking_data'] ?? []);
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
            $booking = $this->createUnaudited([
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
                'message' => 'Your booking has been submitted successfully',
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

        $this->bookingHelper->assertNoEmoji($payload);
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

    // A rejected booking is always marked refunded; $markRefundedAnyway keeps
    // that true even when the gateway call fails, and logs it for follow-up.
    private function refundBookingPayment(
        Booking $booking,
        array &$bookingData,
        bool $markRefundedAnyway = false
    ): bool {
        $payment = $bookingData['payment'] ?? [];
        $amount = (float) ($payment['booking_amount'] ?? $payment['total_amount'] ?? 0);

        $refundable = in_array($payment['payment_status'] ?? null, ['paid', 'partial'], true)
            && $amount > 0;

        if (!$refundable) {
            return false;
        }

        // Paid online it goes back through the gateway; paid at the counter
        // it is handed back in person.
        $refunded = empty($payment['xendit_invoice_id']) || XenditService::refundXenditPayment(
            $payment['xendit_invoice_id'],
            $amount,
            (bool) ($payment['masked_card_number'] ?? null)
        );

        if (!$refunded && !$markRefundedAnyway) {
            return false;
        }

        if (!$refunded) {
            Log::warning('Booking marked refunded although the gateway refund failed', [
                'booking_id' => $booking->booking_id,
                'reference_id' => $booking->reference_id,
                'xendit_invoice_id' => $payment['xendit_invoice_id'],
                'amount' => $amount,
            ]);
        }

        $bookingData['payment']['payment_status'] = 'refunded';

        $paid = $this->transactions->bookingPayment($booking->booking_id);

        if ($paid) {
            $this->transactions->forRefund(
                $amount,
                $booking->branch_id,
                $paid->patient_id,
                "Refund for booking {$booking->reference_id}.",
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

            $this->refundBookingPayment($booking, $bookingData, true);

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

    /*
        BOOKINGS TAB OF THE INVOICES PAGE
    */
    public function billingList(array $payload)
    {
        $bookings = $this->bookingRepository->billing($payload['branch_id'], $payload);

        $payments = $this->transactions->paymentsForBookings(
            $bookings->pluck('booking_id')->all()
        );

        return response()->json([
            'data' => $bookings->getCollection()
                ->map(fn(Booking $booking) => $this->billingRow(
                    $booking,
                    $payments->get($booking->booking_id),
                    $payload['branch']
                ))
                ->values(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
            ],
        ]);
    }

    // Cash taken at the counter for a booking still waiting for review.
    public function payBooking(array $payload, User $user)
    {
        return DB::transaction(function () use ($payload, $user) {
            $booking = $this->bookingRepository->lockByReference(
                $payload['reference_id'],
                $payload['branch_id']
            );

            if (!$booking) {
                throw new Exception('Booking doesn\'t exist', 404);
            }

            if (!$this->canPay($booking)) {
                throw new Exception('This booking can\'t be paid right now.', 422);
            }

            $data = $booking->booking_data;
            $payment = $data['payment'];
            $total = round((float) $payment['total_amount'], 2);

            $percent = $this->downpaymentPercent($booking, $payload['branch']);
            $due = round($total * $percent / 100, 2);
            $cash = round((float) ($payload['cash'] ?? 0), 2);

            if ($cash < $due) {
                throw new Exception('The cash received must cover the amount due.', 422);
            }

            $guardian = $data['guardian'] ?? [];
            $client = User::where('email', $guardian['email'] ?? null)->first()?->client;
            $payor = trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? ''));

            $transaction = $this->transactions->forPayment(
                $due,
                $booking->branch_id,
                $data['patient']['patient_id'] ?? null,
                "Downpayment for booking {$booking->reference_id}.",
                [
                    'booking_id' => $booking->booking_id,
                    'client_id' => $client?->client_id,
                    'method' => 'CASH',
                    'party_name' => $payor !== '' ? $payor : null,
                ]
            );

            $balance = round($total - $due, 2);

            $this->paymentRepository->create([
                'transaction_id' => $transaction->transaction_id,
                'prior_balance' => $total,
                'new_balance' => $balance,
                'cash_tendered' => $cash,
                'issued_by' => $user->user_id,
                'created_at' => now(),
            ]);

            $data['payment'] = array_merge($payment, [
                'booking_percent' => $percent,
                'booking_amount' => $due,
                'balance_amount' => $balance,
                'payment_status' => $balance > 0 ? 'partial' : 'paid',
                'payment_method' => 'CASH',
            ]);

            $booking->update(['booking_data' => $data]);

            $this->notifyPayment($booking, $due, $user);

            return response()->json([
                'message' => "Payment for booking {$booking->reference_id} received.",
                'change' => round($cash - $due, 2),
                'data' => $this->billingRow(
                    $booking->fresh(),
                    $this->transactions->paymentsForBookings([$booking->booking_id])->get($booking->booking_id),
                    $payload['branch']
                ),
            ]);
        });
    }

    // A family paying the downpayment on a homecare booking made for them.
    public function payPortalBooking(User $user, array $payload)
    {
        $booking = Booking::with('branch')
            ->where('reference_id', $payload['reference_id'] ?? null)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$booking) {
            throw new Exception('Booking doesn\'t exist', 404);
        }

        $due = $this->bookingHelper->portalPaymentDue($booking);

        if (!$due) {
            throw new Exception('This booking has no payment due.', 422);
        }

        $method = $payload['payment_method'] ?? null;

        if ($method === 'GCASH') {
            return app(GCashPayment::class)->portalBookingInvoice($user, [
                'reference_id' => $booking->reference_id,
                'amount' => $due['amount'],
            ]);
        }

        if ($method !== 'CREDIT-CARD') {
            throw new Exception('Choose a payment method.', 422);
        }

        $charge = PaymentFactory::make('CREDIT-CARD')->bookingBilling([
            'total' => $due['amount'],
            'token_id' => $payload['token_id'] ?? null,
            'authentication_id' => $payload['authentication_id'] ?? null,
        ]);

        if ($charge instanceof JsonResponse) {
            return $charge;
        }

        try {
            return $this->settlePortalPayment($user, $booking->reference_id, [
                'payment_method' => 'CREDIT-CARD',
                'xendit_invoice_id' => $charge['xendit_invoice_id'],
                'masked_card_number' => $charge['masked_card_number'],
            ]);
        } catch (Exception $e) {
            report($e);

            XenditService::refundXenditPayment($charge['xendit_invoice_id'], (float) $due['amount'], true);

            throw new Exception('We couldn\'t apply your payment. It has been refunded.', 422);
        }
    }

    public function settlePortalPayment(User $user, string $reference, array $paid)
    {
        return DB::transaction(function () use ($user, $reference, $paid) {
            $booking = Booking::with('branch')
                ->where('reference_id', $reference)
                ->where('user_id', $user->user_id)
                ->lockForUpdate()
                ->first();

            $due = $booking ? $this->bookingHelper->portalPaymentDue($booking) : null;

            if (!$due) {
                throw new Exception('This booking has no payment due.', 422);
            }

            $data = $booking->booking_data;
            $balance = round($due['total'] - $due['amount'], 2);

            $data['payment'] = array_merge($data['payment'], [
                'booking_percent' => $due['percent'],
                'booking_amount' => $due['amount'],
                'balance_amount' => $balance,
                'payment_status' => $balance > 0 ? 'partial' : 'paid',
                'payment_method' => $paid['payment_method'],
                'xendit_invoice_id' => $paid['xendit_invoice_id'],
                'masked_card_number' => $paid['masked_card_number'],
            ]);

            app(AuditService::class)->withoutAuditing(
                fn() => $booking->update(['booking_data' => $data])
            );

            $this->recordDownpayment($user, $booking, $data['payment']);

            $this->notifyPayment($booking, (float) $due['amount'], $user);

            return response()->json([
                'message' => "Payment for booking {$booking->reference_id} received.",
            ]);
        });
    }

    public function refundBooking(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $booking = $this->bookingRepository->lockByReference(
                $payload['reference_id'],
                $payload['branch_id']
            );

            if (!$booking) {
                throw new Exception('Booking doesn\'t exist', 404);
            }

            if (!$this->canRefund($booking)) {
                throw new Exception('This booking has no payment to refund.', 422);
            }

            $data = $booking->booking_data;

            $this->refundBookingPayment($booking, $data, true);

            $booking->update(['booking_data' => $data]);

            return response()->json([
                'message' => "Booking {$booking->reference_id} has been refunded.",
                'data' => $this->billingRow(
                    $booking->fresh(),
                    $this->transactions->paymentsForBookings([$booking->booking_id])->get($booking->booking_id),
                    $payload['branch']
                ),
            ]);
        });
    }

    private function downpaymentPercent(Booking $booking, mixed $branch): int
    {
        return $this->bookingHelper->getBookingPercent([
            'category' => $booking->category,
            'branch' => $branch,
            'booking_data' => $booking->booking_data,
        ]);
    }

    private function canPay(Booking $booking): bool
    {
        $data = $booking->booking_data ?? [];
        $payment = $data['payment'] ?? [];

        return $booking->status === Booking::STATUS_PENDING
            && ($payment['payment_status'] ?? null) === 'pending'
            && (float) ($payment['total_amount'] ?? 0) > 0
            && ($data['facility']['type'] ?? null) !== Booking::TYPE_PREADMISSION;
    }

    private function canRefund(Booking $booking): bool
    {
        $payment = ($booking->booking_data ?? [])['payment'] ?? [];

        return $booking->status === Booking::STATUS_REJECTED
            && in_array($payment['payment_status'] ?? null, ['paid', 'partial'], true);
    }

    private function billingRow(Booking $booking, ?Transaction $transaction, mixed $branch): array
    {
        $data = $booking->booking_data ?? [];
        $payment = $data['payment'] ?? [];
        $patient = $data['patient'] ?? [];
        $booking->loadMissing('patientBookings.invoice');

        $patientName = trim(implode(' ', array_filter([
            $patient['first_name'] ?? null,
            $patient['middle_name'] ?? null,
            $patient['last_name'] ?? null,
            $patient['suffix'] ?? null,
        ])));

        return [
            'booking_id' => $booking->booking_id,
            'reference_id' => $booking->reference_id,
            'category' => $booking->category,
            'type' => $data['facility']['type'] ?? $data['homecare']['type'] ?? null,
            'status' => $booking->status,
            'patient_name' => $patientName !== '' ? $patientName : null,
            'total_amount' => (float) ($payment['total_amount'] ?? 0),
            'booking_amount' => (float) ($payment['booking_amount'] ?? 0),
            'balance_amount' => (float) ($payment['balance_amount'] ?? $payment['total_amount'] ?? 0),
            'payment_status' => $payment['payment_status'] ?? null,
            'payment_method' => $payment['payment_method'] ?? null,
            'created_at' => $booking->created_at?->toIso8601String(),
            'invoice_code' => $booking->patientBookings->first()?->invoice?->invoice_code,
            'receipt' => $transaction ? (new BookingReceiptResource($transaction))->resolve() : null,
            'can_pay' => $this->canPay($booking),
            'can_refund' => $this->canRefund($booking),
            'amount_due' => $this->canPay($booking)
                ? round(
                    (float) $payment['total_amount'] * $this->downpaymentPercent($booking, $branch) / 100,
                    2
                )
                : null,
        ];
    }
}
