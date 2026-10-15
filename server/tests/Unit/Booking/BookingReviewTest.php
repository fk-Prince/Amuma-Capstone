<?php

namespace Tests\Unit\Booking;

use App\Enums\PermissionAction;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InvoiceController;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use App\Service\TransactionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class BookingReviewTest extends TestCase
{
    use UsesTempDatabase;
    use BookingFixtures;

    private BookingController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        $this->setUpBooking();
        $this->controller = $this->app->make(BookingController::class);
    }

    private function paidHomecare(): Booking
    {
        $booking = $this->booking([
            'homecare' => $this->medicalVisit(),
            'payment' => [
                'total_amount' => 1100,
                'booking_percent' => 50,
                'booking_amount' => 550,
                'balance_amount' => 550,
                'payment_status' => 'partial',
                'payment_method' => 'CARD',
                'xendit_invoice_id' => 'chg_003',
                'masked_card_number' => '400000XXXXXX0002',
            ],
        ]);

        $this->app->make(TransactionService::class)->forPayment(550, $this->branch->branch_id, null, 'Downpayment', [
            'booking_id' => $booking->booking_id,
            'client_id' => $this->client->client_id,
            'method' => 'CARD',
            'transaction_reference_id' => 'chg_003',
        ]);

        return $booking;
    }

    private function paidAdmission(): Booking
    {
        $booking = $this->booking([
            'facility' => $this->completeAdmission(),
            'payment' => [
                'total_amount' => 15000,
                'booking_amount' => 15000,
                'balance_amount' => 0,
                'payment_status' => 'paid',
                'payment_method' => 'CARD',
                'xendit_invoice_id' => 'chg_004',
            ],
        ], ['valid_until' => now()->addDays(5)->endOfDay()]);

        $this->app->make(TransactionService::class)->forPayment(15000, $this->branch->branch_id, null, 'Reservation fee', [
            'booking_id' => $booking->booking_id,
            'method' => 'CARD',
        ]);

        return $booking;
    }

    private function preAdmission(): Booking
    {
        return $this->booking([
            'facility' => ['type' => Booking::TYPE_PREADMISSION],
            'payment' => ['total_amount' => 0, 'paid' => false],
        ], ['valid_until' => now()->addMonth()]);
    }

    private function approve(Booking $booking, array $extra = [], ?User $user = null)
    {
        $data = $booking->booking_data;

        return $this->controller->action($this->makeRequest($extra + [
            'action' => 'approve',
            'reference_id' => $booking->reference_id,
            'category' => $booking->category,
            'patient' => $data['patient'],
            'guardian' => $data['guardian'],
            'assessment' => $data['assessment'] ?? [],
            'diagnoses' => $data['diagnoses'] ?? [],
            'homecare' => $data['homecare'] ?? null,
            'reserved' => null,
            'payment' => $data['payment'],
        ] + (empty($data['facility']) ? [] : ['facility' => $data['facility']]), $user));
    }

    private function approveAdmission(Booking $booking)
    {
        return $this->approve($booking, [
            'reserved' => [
                'room' => ['room_id' => $this->roomId],
                'bed' => ['bed_id' => $this->bedId],
                'contract_id' => $this->commonContractId,
                'admitted_at' => now()->addDays(5)->toDateString(),
                'billing_cycle' => 'MONTHLY',
            ],
        ]);
    }

    private function reject(Booking $booking, string $reason = 'No caregiver available', ?User $user = null)
    {
        return $this->controller->action($this->makeRequest([
            'action' => 'reject',
            'reference_id' => $booking->reference_id,
            'reason' => $reason,
        ], $user));
    }

    private function counter(array $data)
    {
        return $this->app->make(InvoiceController::class)->action($this->makeRequest(['type' => 'booking_pay'] + $data));
    }

    private function relativeAlert(): string
    {
        return Notification::where('to_user_id', $this->relative->user_id)->latest('notification_id')->firstOrFail()->message;
    }

    public function test_staff_sees_the_bookings_of_their_branch(): void
    {
        $this->paidHomecare();
        $this->preAdmission();

        $result = $this->controller->index($this->makeRequest([]))->response()->getData(true);

        $this->assertCount(2, $result['data']);
        $this->assertSame(Booking::STATUS_PENDING, $result['data'][0]['status']);
    }

    public function test_status_filter_shows_only_matching_bookings(): void
    {
        $this->paidHomecare();
        $this->preAdmission()->update(['status' => Booking::STATUS_REJECTED]);

        $result = $this->controller->index($this->makeRequest(['status' => Booking::STATUS_REJECTED]))->response()->getData(true);

        $this->assertCount(1, $result['data']);
    }

    public function test_booking_details_show_the_patient_and_service(): void
    {
        $booking = $this->paidHomecare();

        $details = $this->controller->show($this->makeRequest(['reference_id' => $booking->reference_id]), $booking->reference_id)
            ->toArray(request());

        $this->assertSame('Lola', $details['patient']['first_name']);
        $this->assertSame(Booking::TYPE_MEDICAL, $details['homecare']['type']);
    }

    public function test_booking_has_been_approved_successfully(): void
    {
        $booking = $this->paidHomecare();

        $response = $this->approve($booking)->getData(true);

        $this->assertSame("Booking {$booking->reference_id} has been approved successfully.", $response['message']);
        $this->assertSame(Booking::STATUS_APPROVED, $booking->fresh()->status);
        $this->assertSame(1, Patient::where('first_name', 'Lola')->count());

        $schedule = Schedule::firstOrFail();
        $this->assertCount(2, $schedule->scheduleServices);

        $invoice = Invoice::firstOrFail();
        $this->assertSame('1100.00', (string) $invoice->total_amount);
        $this->assertSame(1, $booking->patientBookings()->count());
    }

    public function test_downpayment_counts_toward_the_invoice(): void
    {
        $booking = $this->paidHomecare();

        $this->approve($booking);

        $invoice = Invoice::firstOrFail();

        $this->assertSame(550.0, (float) $invoice->fresh()->balance_due);
        $this->assertSame(1, Transaction::where('booking_id', $booking->booking_id)->count());
        $this->assertSame(1, Payment::count());
    }

    public function test_your_booking_has_been_approved_by_amuma_davao_city(): void
    {
        $booking = $this->paidHomecare();

        $this->approve($booking);

        $this->assertSame("Your booking {$booking->reference_id} has been approved by AMUMA Davao City.", $this->relativeAlert());
    }

    public function test_approved_admission_holds_the_bed_and_settles_the_invoice(): void
    {
        $booking = $this->paidAdmission();

        $this->approveAdmission($booking);

        $this->assertSame(Booking::STATUS_APPROVED, $booking->fresh()->status);
        $this->assertSame(PatientAdmission::STATUS_WAITING, PatientAdmission::firstOrFail()->status);
        $this->assertSame('Reserved', DB::table('beds')->where('bed_id', $this->bedId)->value('status'));
        $this->assertSame(0.0, (float) Invoice::firstOrFail()->balance_due);
    }

    public function test_selected_bed_is_already_occupied(): void
    {
        DB::table('beds')->where('bed_id', $this->bedId)->update(['status' => 'Occupied']);
        $booking = $this->paidAdmission();

        $this->assertFailsWith('Selected bed is already occupied.', fn() => $this->approveAdmission($booking), 422);

        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_pre_admission_booking_has_been_approved(): void
    {
        $booking = $this->preAdmission();

        $response = $this->controller->action($this->makeRequest([
            'action' => 'accept',
            'reference_id' => $booking->reference_id,
        ]))->getData(true);

        $this->assertSame("Booking {$booking->reference_id} has been accepted.", $response['message']);
        $this->assertSame(Booking::STATUS_ACCEPTED, $booking->fresh()->status);
        $this->assertSame(0, Patient::count());
        $this->assertSame(0, Invoice::count());
    }

    public function test_only_pre_admission_bookings_can_be_approved_without_processing(): void
    {
        $booking = $this->paidAdmission();

        $this->assertFailsWith(
            'Only pre-admission bookings can be accepted without processing.',
            fn() => $this->controller->action($this->makeRequest([
                'action' => 'accept',
                'reference_id' => $booking->reference_id,
            ])),
            422
        );
    }

    public function test_booking_has_successfully_rejected(): void
    {
        $booking = $this->paidHomecare();

        $response = $this->reject($booking);

        $this->assertSame('Booking has successfully rejected', $response['message']);

        $booking->refresh();
        $this->assertSame(Booking::STATUS_REJECTED, $booking->status);
        $this->assertSame('No caregiver available', $booking->reason);
        $this->assertSame('refunded', $booking->booking_data['payment']['payment_status']);

        $refund = Transaction::where('type', Transaction::TYPE_REFUND)->firstOrFail();
        $this->assertSame('550.00', (string) $refund->amount);
        $this->assertSame($booking->booking_id, (int) $refund->booking_id);

        Http::assertSent(fn($request) => str_contains($request->url(), 'credit_card_charges/chg_003/refunds'));
    }

    public function test_your_booking_was_declined_by_amuma_davao_city_reason_no_caregiver_available(): void
    {
        $booking = $this->paidHomecare();

        $this->reject($booking);

        $this->assertSame(
            "Your booking {$booking->reference_id} was declined by AMUMA Davao City. Reason: No caregiver available",
            $this->relativeAlert()
        );
    }

    public function test_unpaid_booking_is_rejected_without_a_refund(): void
    {
        $this->reject($this->preAdmission());

        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_REFUND)->count());
        Http::assertNothingSent();
    }

    public function test_booking_cannot_be_processed_because_its_current_status_is_approved(): void
    {
        $booking = $this->paidHomecare();
        $this->approve($booking);

        $this->assertFailsWith(
            "Booking cannot be processed because its current status is 'approved', can only process awaiting status.",
            fn() => $this->approve($booking->fresh()),
            400
        );

        $this->assertSame(1, Patient::count());
    }

    public function test_booking_cannot_be_approved_current_status_rejected(): void
    {
        $booking = $this->preAdmission();
        $this->reject($booking);

        $this->assertFailsWith(
            'Booking cannot be accepted. Current status: rejected.',
            fn() => $this->controller->action($this->makeRequest([
                'action' => 'accept',
                'reference_id' => $booking->reference_id,
            ])),
            400
        );
    }

    public function test_insufficient_permissions_to_approve(): void
    {
        $viewer = $this->staff([PermissionAction::Read->value], 'nurse');
        $booking = $this->paidHomecare();

        $this->assertFailsWith('Insufficient permissions', fn() => $this->approve($booking, [], $viewer), 403);
        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_insufficient_permissions_to_reject(): void
    {
        $viewer = $this->staff([PermissionAction::Read->value], 'nurse');
        $booking = $this->paidHomecare();

        $this->assertFailsWith('Insufficient permissions', fn() => $this->reject($booking, 'x', $viewer), 403);
        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_REFUND)->count());
    }

    public function test_insufficient_permissions_to_list_the_branch_bookings(): void
    {
        $this->assertFailsWith('Insufficient permissions', fn() => $this->controller->index($this->makeRequest([], $this->relative)), 403);
    }

    private function walkIn(array $overrides = [])
    {
        return $this->controller->action($this->makeRequest(array_merge([
            'action' => 'staff_homecare',
            'patient' => $this->patientData(),
            'guardian' => ['email' => 'mark.reyes@gmail.com', 'first_name' => 'Mark', 'last_name' => 'Reyes'] + $this->guardianData(),
            'type' => Booking::TYPE_ADL,
            'date' => $this->visitDate(),
            'prefered_time' => '09:00',
            'time_span' => 4,
            'address' => 'Matina, Davao City',
            'price' => 1200,
            'rate' => 300,
        ], $overrides)));
    }

    public function test_homecare_booking_created_it_is_waiting_for_review(): void
    {
        $this->giveHomecareSubscription();

        $response = $this->walkIn();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Homecare booking created. It is waiting for review.', $response->getData(true)['message']);

        $booking = Booking::firstOrFail();
        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
        $this->assertSame('pending', $booking->booking_data['payment']['payment_status']);
        $this->assertEquals(1200, $booking->booking_data['payment']['total_amount']);
        $this->assertSame(0, Transaction::count());
    }

    public function test_this_branch_has_no_homecare_service_subscription(): void
    {
        $this->assertFailsWith('This branch has no Homecare Service subscription.', fn() => $this->walkIn(), 422);
    }

    public function test_the_guardian_email_field_is_required(): void
    {
        $this->giveHomecareSubscription();

        $this->assertFailsWith('The guardian.email field is required.', fn() => $this->walkIn([
            'guardian' => ['email' => null] + $this->guardianData(),
        ]));
    }

    public function test_this_email_belongs_to_a_staff_member(): void
    {
        $this->giveHomecareSubscription();

        $this->assertFailsWith(
            "This email belongs to a staff member and can't be used for a guardian.",
            fn() => $this->walkIn(['guardian' => ['email' => $this->admission->email] + $this->guardianData()]),
            422
        );
    }

    public function test_the_cash_received_must_cover_the_amount_due(): void
    {
        $this->giveHomecareSubscription();
        $this->walkIn();
        $booking = Booking::firstOrFail();

        $this->assertFailsWith(
            'The cash received must cover the amount due.',
            fn() => $this->counter(['reference_id' => $booking->reference_id, 'cash' => 500]),
            422
        );

        $this->assertSame(0, Transaction::count());
    }

    public function test_payment_for_booking_received(): void
    {
        $this->giveHomecareSubscription();
        $this->walkIn();
        $booking = Booking::firstOrFail();

        $response = $this->counter(['reference_id' => $booking->reference_id, 'cash' => 1000])->getData(true);

        $this->assertSame("Payment for booking {$booking->reference_id} received.", $response['message']);
        $this->assertEquals(400, $response['change']);

        $payment = $booking->fresh()->booking_data['payment'];
        $this->assertEquals(600, $payment['booking_amount']);
        $this->assertSame('partial', $payment['payment_status']);
        $this->assertSame('CASH', Transaction::firstOrFail()->method);
    }

    public function test_cash_exactly_the_amount_due_is_valid(): void
    {
        $this->giveHomecareSubscription();
        $this->walkIn();
        $booking = Booking::firstOrFail();

        $response = $this->counter(['reference_id' => $booking->reference_id, 'cash' => 600])->getData(true);

        $this->assertSame("Payment for booking {$booking->reference_id} received.", $response['message']);
        $this->assertEquals(0, $response['change']);
    }

    public function test_cash_1_centavo_short_is_invalid(): void
    {
        $this->giveHomecareSubscription();
        $this->walkIn();
        $booking = Booking::firstOrFail();

        $this->assertFailsWith(
            'The cash received must cover the amount due.',
            fn() => $this->counter(['reference_id' => $booking->reference_id, 'cash' => 599.99]),
            422
        );
    }

    public function test_walk_in_visit_dated_today_is_valid(): void
    {
        $this->giveHomecareSubscription();
        $this->travelTo(now()->setTime(8, 0));

        $response = $this->walkIn(['date' => now()->toDateString(), 'prefered_time' => '10:00']);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_the_date_field_must_be_a_date_after_or_equal_to_today(): void
    {
        $this->giveHomecareSubscription();

        $this->assertFailsWith(
            'The date field must be a date after or equal to today.',
            fn() => $this->walkIn(['date' => now()->subDay()->toDateString()])
        );

        $this->assertSame(0, Booking::count());
    }

    public function test_the_guardian_email_field_must_be_a_valid_email_address(): void
    {
        $this->giveHomecareSubscription();

        $this->assertFailsWith('The guardian.email field must be a valid email address.', fn() => $this->walkIn([
            'guardian' => ['email' => 'mark.reyes'] + $this->guardianData(),
        ]));
    }

    public function test_this_booking_cant_be_paid_right_now(): void
    {
        $booking = $this->preAdmission();

        $this->assertFailsWith(
            "This booking can't be paid right now.",
            fn() => $this->counter(['reference_id' => $booking->reference_id, 'cash' => 1000]),
            422
        );
    }

    public function test_unreviewed_booking_expires_and_is_refunded(): void
    {
        $booking = $this->paidHomecare();
        $booking->update(['valid_until' => now()->subHour()]);

        Artisan::call('bookings:reject-expired');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_EXPIRED, $booking->status);
        $this->assertSame('refunded', $booking->booking_data['payment']['payment_status']);
        $this->assertSame(1, Transaction::where('type', Transaction::TYPE_REFUND)->count());
    }

    public function test_booking_still_in_time_is_left_alone(): void
    {
        $booking = $this->paidHomecare();

        Artisan::call('bookings:reject-expired');

        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_booking_has_expired(): void
    {
        $booking = $this->paidHomecare();
        $booking->update(['valid_until' => now()->subHour()]);

        $this->assertFailsWith('Booking has expired.', fn() => $this->approve($booking), 422);
    }
}
