<?php

namespace Tests\Unit\Booking;

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\BranchContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class MonthlyAdlBookingTest extends TestCase
{
    use UsesTempDatabase;
    use BookingFixtures;

    private BookingController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        $this->travelTo(now()->setDate(2026, 10, 27)->setTime(8, 0));
        $this->setUpBooking();
        $this->giveHomecareSubscription();

        $monthlyId = $this->contract('Homecare', 'ADL', 'MONTHLY', 45000);
        DB::table('branch_contracts')->where('branch_contract_id', $monthlyId)->update([
            'activities' => json_encode(['Bathing', 'Eating', 'Companionship']),
        ]);

        $this->controller = $this->app->make(BookingController::class);
    }

    private function staffBooking(array $overrides = [])
    {
        return $this->controller->action($this->makeRequest(array_merge([
            'action' => 'staff_homecare',
            'patient' => $this->patientData(),
            'guardian' => $this->guardianData(),
            'type' => Booking::TYPE_ADL,
            'date' => '2026-10-30',
            'prefered_time' => '08:00',
            'time_span' => 744,
            'billing' => 'monthly',
            'months' => 1,
            'address' => 'Matina, Davao City',
            'latitude' => 7.06,
            'longitude' => 125.59,
            'services' => [],
            'price' => 1,
            'rate' => 1,
            'diagnoses' => [],
            'assessment' => [],
        ], $overrides)));
    }

    private function homecare(): array
    {
        return Booking::firstOrFail()->booking_data['homecare'];
    }

    public function test_monthly_adl_costs_the_months_times_the_monthly_base_price(): void
    {
        $this->staffBooking(['months' => 3]);

        $booking = Booking::firstOrFail();

        $this->assertEquals(135000, $booking->booking_data['payment']['total_amount']);
        $this->assertSame('monthly', $booking->booking_data['homecare']['billing']);
        $this->assertSame(3, $booking->booking_data['homecare']['months']);
        $this->assertEquals(45000, $booking->booking_data['homecare']['price']);
    }

    public function test_monthly_adl_ignores_the_price_the_browser_sends(): void
    {
        $this->staffBooking(['months' => 2, 'price' => 1, 'rate' => 1]);

        $this->assertEquals(90000, Booking::firstOrFail()->booking_data['payment']['total_amount']);
    }

    public function test_monthly_adl_length_follows_the_calendar(): void
    {
        $this->staffBooking(['date' => '2026-10-30', 'months' => 15, 'time_span' => 1]);

        $this->assertEquals(10968, $this->homecare()['time_span']);
    }

    public function test_a_month_ending_short_stops_on_the_last_day(): void
    {
        $this->staffBooking(['date' => '2027-01-31', 'months' => 1]);

        $this->assertEquals(28 * 24, $this->homecare()['time_span']);
    }

    public function test_staff_can_change_the_monthly_base_price_for_one_booking(): void
    {
        $this->staffBooking(['months' => 2, 'monthly_rate' => 40000]);

        $homecare = $this->homecare();

        $this->assertEquals(80000, Booking::firstOrFail()->booking_data['payment']['total_amount']);
        $this->assertEquals(40000, $homecare['price']);
        $this->assertEquals(45000, BranchContract::where('accommodation_type', 'ADL')->where('billing_cycle', 'MONTHLY')->value('price'));
    }

    public function test_staff_can_add_activities_to_a_monthly_booking(): void
    {
        $this->staffBooking(['activities' => ['Bathing', 'Wound dressing']]);

        $this->assertSame(['Bathing', 'Wound dressing'], $this->homecare()['activities']);
    }

    public function test_monthly_booking_starts_with_the_monthly_plan_activities(): void
    {
        $this->staffBooking();

        $this->assertSame(['Bathing', 'Eating', 'Companionship'], $this->homecare()['activities']);
    }

    public function test_monthly_booking_is_stay_in_by_default(): void
    {
        $this->staffBooking();

        $this->assertTrue($this->homecare()['stay_in']);
    }

    public function test_staff_can_untick_stay_in(): void
    {
        $this->staffBooking(['stay_in' => false]);

        $this->assertFalse($this->homecare()['stay_in']);
    }

    public function test_hourly_booking_is_never_stay_in(): void
    {
        $this->staffBooking(['billing' => 'hourly', 'months' => null, 'time_span' => 8, 'stay_in' => true]);

        $this->assertFalse($this->homecare()['stay_in']);
    }

    public function test_months_must_be_a_whole_number(): void
    {
        $this->assertFailsWith('Enter a whole number of months.', fn() => $this->staffBooking(['months' => 1.5]));

        $this->assertSame(0, Booking::count());
    }

    public function test_at_least_one_month_is_required(): void
    {
        $this->assertFailsWith('Enter at least 1 month.', fn() => $this->staffBooking(['months' => 0]));
    }

    public function test_monthly_booking_needs_the_number_of_months(): void
    {
        $this->assertFailsWith('Enter how many months of care.', fn() => $this->staffBooking(['months' => null]));
    }

    public function test_the_monthly_base_price_must_be_greater_than_zero(): void
    {
        $this->assertFailsWith(
            'The monthly base price must be greater than zero.',
            fn() => $this->staffBooking(['monthly_rate' => 0])
        );
    }

    public function test_an_activity_can_only_be_added_once(): void
    {
        $this->assertFailsWith(
            'Each activity can only be added once.',
            fn() => $this->staffBooking(['activities' => ['Bathing', 'bathing']])
        );
    }

    public function test_no_active_monthly_adl_plan(): void
    {
        DB::table('branch_contracts')->where('billing_cycle', 'MONTHLY')->where('accommodation_type', 'ADL')->update(['is_active' => false]);

        $this->assertFailsWith(
            'No active monthly ADL plan is set up for this branch.',
            fn() => $this->staffBooking(),
            422
        );

        $this->assertSame(0, Booking::count());
    }

    public function test_hourly_staff_booking_keeps_the_hourly_price_and_package_activities(): void
    {
        $this->staffBooking([
            'billing' => 'hourly',
            'months' => null,
            'time_span' => 8,
            'price' => 2400,
            'rate' => 300,
        ]);

        $homecare = $this->homecare();

        $this->assertSame('hourly', $homecare['billing']);
        $this->assertNull($homecare['months']);
        $this->assertEquals(8, $homecare['time_span']);
        $this->assertEquals(300, $homecare['price']);
        $this->assertEquals(2400, Booking::firstOrFail()->booking_data['payment']['total_amount']);
        $this->assertSame(BranchContract::ADL_ACTIVITIES, $homecare['activities']);
    }
}
