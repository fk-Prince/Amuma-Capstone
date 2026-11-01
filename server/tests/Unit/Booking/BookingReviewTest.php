<?php

namespace Tests\Unit\Booking;

use App\Enums\PermissionAction;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use App\Service\TransactionService;
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

    public function test_selected_bed_is_already_occupied(): void
    {
        DB::table('beds')->where('bed_id', $this->bedId)->update(['status' => 'Occupied']);
        $booking = $this->paidAdmission();

        $this->assertFailsWith('Selected bed is already occupied.', fn() => $this->approveAdmission($booking), 422);

        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
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

    public function test_booking_has_expired(): void
    {
        $booking = $this->paidHomecare();
        $booking->update(['valid_until' => now()->subHour()]);

        $this->assertFailsWith('Booking has expired.', fn() => $this->approve($booking), 422);
    }
}
