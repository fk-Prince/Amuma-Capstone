<?php

namespace Tests\Unit\Booking;

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Service\Booking\BookingHelper;
use App\Service\BookingService;
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
}
