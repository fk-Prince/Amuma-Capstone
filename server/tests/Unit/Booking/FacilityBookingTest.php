<?php

namespace Tests\Unit\Booking;

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\Transaction;
use App\Service\Booking\BookingHelper;
use App\Service\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class FacilityBookingTest extends TestCase
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

    private function preAdmission(array $extra = [])
    {
        return $this->controller->store($this->makeRequest(
            ['action' => 'regular'] + $this->bookingRequest(Booking::CATEGORY_FACILITY, [
                'facility' => ['type' => Booking::TYPE_PREADMISSION],
                'assessment' => [['condition' => 'ambulatory', 'mental_state' => 'alert']],
            ] + $extra),
            $this->relative
        ));
    }

    private function submitPaid(array $facility)
    {
        $payload = $this->bookingRequest(Booking::CATEGORY_FACILITY, ['facility' => $facility]) + [
            'branch' => $this->branch,
            'branch_id' => $this->branch->branch_id,
            'payment_method' => 'CARD',
        ];

        $breakdown = $this->app->make(BookingHelper::class)->resolveBookingPayment($payload);

        return $this->app->make(BookingService::class)->storePaidBooking($this->relative, $payload, [
            'xendit_invoice_id' => 'chg_002',
            'masked_card_number' => '400000XXXXXX0002',
            'total' => $breakdown['booking_amount'],
        ], $breakdown);
    }

    public function test_pre_admission_form_is_submitted_without_payment(): void
    {
        $response = $this->preAdmission();

        $this->assertSame(
            'Your booking has been submitted successfully',
            $response->getData(true)['message']
        );

        $booking = Booking::firstOrFail();

        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
        $this->assertEquals(0, $booking->booking_data['payment']['total_amount']);
        $this->assertSame(0, Transaction::count());
        Http::assertNothingSent();
    }

    public function test_pre_admission_form_stays_valid_for_one_month(): void
    {
        $this->preAdmission();

        $validUntil = Carbon::parse(Booking::firstOrFail()->valid_until);

        $this->assertTrue($validUntil->isSameDay(now()->addMonth()));
    }

    public function test_this_booking_does_not_take_a_payment(): void
    {
        $this->assertFailsWith(
            'This booking does not take a payment.',
            fn() => $this->controller->store($this->makeRequest(
                ['action' => 'complete-admission', 'payment_method' => 'CARD']
                    + $this->bookingRequest(Booking::CATEGORY_FACILITY, ['facility' => ['type' => Booking::TYPE_PREADMISSION]]),
                $this->relative
            )),
            422
        );
    }

    public function test_complete_admission_costs_the_plan_price(): void
    {
        $total = $this->controller->action($this->makeRequest([
            'action' => 'total',
            'category' => Booking::CATEGORY_FACILITY,
            'booking_data' => ['facility' => $this->completeAdmission()],
        ], $this->relative));

        $this->assertEquals(15000, $total);
    }

    public function test_complete_admission_is_submitted_fully_paid(): void
    {
        $this->submitPaid($this->completeAdmission());

        $booking = Booking::firstOrFail();
        $payment = $booking->booking_data['payment'];

        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
        $this->assertEquals(15000, $payment['booking_amount']);
        $this->assertEquals(0, $payment['balance_amount']);
        $this->assertSame('paid', $payment['payment_status']);
        $this->assertSame('15000.00', (string) Transaction::firstOrFail()->amount);
    }

    public function test_complete_admission_is_valid_until_the_admission_date(): void
    {
        $this->submitPaid($this->completeAdmission());

        $validUntil = Carbon::parse(Booking::firstOrFail()->valid_until);

        $this->assertSame(now()->addDays(5)->endOfDay()->toDateTimeString(), $validUntil->toDateTimeString());
    }

    public function test_no_active_vip_yearly_pricing_contract_is_configured_for_this_branch(): void
    {
        $this->assertFailsWith(
            'No active VIP (Yearly) pricing contract is configured for this branch.',
            fn() => $this->controller->action($this->makeRequest([
                'action' => 'total',
                'category' => Booking::CATEGORY_FACILITY,
                'booking_data' => ['facility' => $this->completeAdmission(['plan' => 'VIP', 'billing_cycle' => 'Yearly'])],
            ], $this->relative)),
            404
        );
    }

    public function test_diagnosis_must_be_from_the_past_3_months(): void
    {
        $this->assertFailsWith('Diagnosis must be from the past 3 months.', fn() => $this->preAdmission([
            'diagnoses' => [['diagnosis' => 'Hypertension', 'diagnosis_date' => now()->subMonths(5)->toDateString()]],
        ]));

        $this->assertSame(0, Booking::count());
    }

    public function test_diagnosis_date_cannot_be_in_the_future(): void
    {
        $this->assertFailsWith('Diagnosis date cannot be in the future.', fn() => $this->preAdmission([
            'diagnoses' => [['diagnosis' => 'Hypertension', 'diagnosis_date' => now()->addDay()->toDateString()]],
        ]));
    }

    public function test_primary_diagnosis_is_required(): void
    {
        $this->assertFailsWith('Primary Diagnosis is required.', fn() => $this->preAdmission([
            'diagnoses' => [['diagnosis_date' => now()->subMonth()->toDateString()]],
        ]));
    }

    public function test_diagnosis_exactly_3_months_old_is_valid(): void
    {
        $this->preAdmission([
            'diagnoses' => [['diagnosis' => 'Hypertension', 'diagnosis_date' => now()->subMonths(3)->toDateString()]],
        ]);

        $this->assertSame(1, Booking::count());
    }

    public function test_diagnosis_dated_today_is_valid(): void
    {
        $this->preAdmission([
            'diagnoses' => [['diagnosis' => 'Hypertension', 'diagnosis_date' => now()->toDateString()]],
        ]);

        $this->assertSame(1, Booking::count());
    }

    public function test_diagnosis_3_months_and_1_day_old_is_invalid(): void
    {
        $this->assertFailsWith('Diagnosis must be from the past 3 months.', fn() => $this->preAdmission([
            'diagnoses' => [['diagnosis' => 'Hypertension', 'diagnosis_date' => now()->subMonths(3)->subDay()->toDateString()]],
        ]));

        $this->assertSame(0, Booking::count());
    }

    public function test_recent_diagnosis_is_accepted(): void
    {
        $this->preAdmission([
            'diagnoses' => [['diagnosis' => 'Hypertension', 'diagnosis_date' => now()->subMonths(2)->toDateString()]],
        ]);

        $this->assertSame('Hypertension', Booking::firstOrFail()->booking_data['diagnoses'][0]['diagnosis']);
    }
}
