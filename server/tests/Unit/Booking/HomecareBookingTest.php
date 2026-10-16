<?php

namespace Tests\Unit\Booking;

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Transaction;
use App\Service\Booking\BookingHelper;
use App\Service\BookingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class HomecareBookingTest extends TestCase
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

    private function total(array $homecare)
    {
        return $this->controller->action($this->makeRequest([
            'action' => 'total',
            'category' => Booking::CATEGORY_ONLINE,
            'booking_data' => ['homecare' => $homecare],
        ], $this->relative));
    }

    private function pay(array $homecare, array $extra = [])
    {
        return $this->controller->store($this->makeRequest(
            ['action' => 'homecare', 'payment_method' => 'CARD']
                + $this->bookingRequest(Booking::CATEGORY_ONLINE, ['homecare' => $homecare] + $extra),
            $this->relative
        ));
    }

    private function submitPaid(array $homecare)
    {
        $payload = $this->bookingRequest(Booking::CATEGORY_ONLINE, ['homecare' => $homecare]) + [
            'branch' => $this->branch,
            'branch_id' => $this->branch->branch_id,
            'payment_method' => 'CARD',
        ];

        $breakdown = $this->app->make(BookingHelper::class)->resolveBookingPayment($payload);

        return $this->app->make(BookingService::class)->storePaidBooking($this->relative, $payload, [
            'xendit_invoice_id' => 'TXN-000001',
            'masked_card_number' => '400000XXXXXX0002',
            'total' => $breakdown['booking_amount'],
        ], $breakdown);
    }

    public function test_medical_visit_costs_the_sum_of_the_chosen_services(): void
    {
        $this->assertSame(1100.0, (float) $this->total($this->medicalVisit()));
    }

    public function test_your_booking_has_been_submitted_successfully(): void
    {
        $response = $this->submitPaid($this->medicalVisit());

        $this->assertSame(
            'Your booking has been submitted successfully',
            $response->getData(true)['message']
        );

        $booking = Booking::firstOrFail();
        $payment = $booking->booking_data['payment'];

        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
        $this->assertSame(Booking::CATEGORY_ONLINE, $booking->category);
        $this->assertEquals(1100, $payment['total_amount']);
        $this->assertEquals(550, $payment['booking_amount']);
        $this->assertSame('partial', $payment['payment_status']);
    }

    public function test_downpayment_is_saved_as_a_transaction_of_the_booking(): void
    {
        $this->submitPaid($this->medicalVisit());

        $transaction = Transaction::firstOrFail();

        $this->assertSame(Booking::firstOrFail()->booking_id, (int) $transaction->booking_id);
        $this->assertSame(Transaction::TYPE_PAYMENT, $transaction->type);
        $this->assertSame('550.00', (string) $transaction->amount);
        $this->assertSame($this->client->client_id, (int) $transaction->client_id);
        $this->assertSame('TXN-000001', $transaction->transaction_reference_id);
    }

    public function test_you_have_a_new_booking_request(): void
    {
        $this->submitPaid($this->medicalVisit());

        $alert = Notification::where('to_user_id', $this->admission->user_id)->firstOrFail();

        $this->assertSame(
            'You have a new booking request. Booking #' . Booking::firstOrFail()->reference_id . ' is waiting for your review.',
            $alert->message
        );
    }

    public function test_homecare_bookings_require_a_downpayment_before_they_can_be_submitted(): void
    {
        $this->assertFailsWith(
            'Homecare bookings require a downpayment before they can be submitted.',
            fn() => $this->controller->store($this->makeRequest(
                ['action' => 'regular'] + $this->bookingRequest(Booking::CATEGORY_ONLINE, ['homecare' => $this->medicalVisit()]),
                $this->relative
            )),
            422
        );

        $this->assertSame(0, Booking::count());
    }

    public function test_adl_visit_costs_the_hours_times_the_hourly_rate(): void
    {
        $total = $this->total($this->adlVisit(['time_span' => 4]));

        $this->assertEquals(1200, $total['total']);
        $this->assertEquals(300, $total['price']);
    }

    public function test_adl_visit_is_submitted_with_half_paid(): void
    {
        $this->submitPaid($this->adlVisit(['time_span' => 4]));

        $payment = Booking::firstOrFail()->booking_data['payment'];

        $this->assertEquals(1200, $payment['total_amount']);
        $this->assertEquals(600, $payment['booking_amount']);
        $this->assertEquals(600, $payment['balance_amount']);
    }

    public function test_no_active_adl_pricing_contract_is_configured_for_this_branch(): void
    {
        DB::table('branch_contracts')->where('accommodation_type', 'ADL')->update(['is_active' => false]);

        $this->assertFailsWith(
            'No active ADL pricing contract is configured for this branch.',
            fn() => $this->total($this->adlVisit()),
            404
        );
    }

    public function test_the_booking_amount_could_not_be_determined(): void
    {
        $this->assertFailsWith(
            'The booking amount could not be determined.',
            fn() => $this->pay($this->medicalVisit(['services' => []])),
            422
        );

        $this->assertSame(0, Booking::count());
        Http::assertNothingSent();
    }

    public function test_homecare_must_be_booked_at_least_1_hour_ahead(): void
    {
        $soon = now()->addMinutes(30);

        $this->assertFailsWith(
            'Homecare must be booked at least 1 hour ahead. Please choose a later time.',
            fn() => $this->pay($this->medicalVisit([
                'date' => $soon->toDateString(),
                'prefered_time' => $soon->format('H:i'),
            ])),
            422
        );

        $this->assertSame(0, Booking::count());
        Http::assertNothingSent();
    }

    private function leadTime(string $time): void
    {
        $this->app->make(BookingHelper::class)->assertHomecareLeadTime([
            'date' => now()->toDateString(),
            'prefered_time' => $time,
        ], $this->branch);
    }

    public function test_visit_exactly_1_hour_ahead_is_valid(): void
    {
        $this->travelTo(now()->setTime(10, 30, 45));

        $this->leadTime('11:30');

        $this->assertTrue(true);
    }

    public function test_visit_59_minutes_ahead_is_invalid(): void
    {
        $this->travelTo(now()->setTime(10, 30, 45));

        $this->assertFailsWith(
            'Homecare must be booked at least 1 hour ahead. Please choose a later time.',
            fn() => $this->leadTime('11:29'),
            422
        );
    }

    public function test_adl_visit_of_1_hour_is_valid(): void
    {
        $this->assertEquals(300, $this->total($this->adlVisit(['time_span' => 1]))['total']);
    }

    public function test_the_adl_pricing_contract_has_an_invalid_price(): void
    {
        $this->assertFailsWith(
            'The ADL pricing contract has an invalid price.',
            fn() => $this->total($this->adlVisit(['time_span' => 0])),
            422
        );
    }

    public function test_visit_two_hours_ahead_is_allowed(): void
    {
        $later = now()->addHours(2);

        $this->app->make(BookingHelper::class)->assertHomecareLeadTime([
            'date' => $later->toDateString(),
            'prefered_time' => $later->format('H:i'),
        ], $this->branch);

        $this->assertTrue(true);
    }
}
