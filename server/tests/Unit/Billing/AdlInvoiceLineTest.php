<?php

namespace Tests\Unit\Billing;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Schedule;
use App\Models\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class AdlInvoiceLineTest extends TestCase
{
    use UsesTempDatabase;

    private int $branchId;
    private int $patientId;

    protected function setUp(): void
    {
        parent::setUp();

        Invoice::forgetPatientInvoiceIds();

        $this->branchId = DB::table('branches')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'agency_id' => 1,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patientId = DB::table('patients')->insertGetId([
            'branch_id' => $this->branchId,
            'uuid' => (string) Str::uuid(),
            'patient_code' => 'PT-000001',
            'first_name' => 'Lola',
            'last_name' => 'Cruz',
            'gender' => 'female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function adlInvoice(string $start, string $cycle, float $duration, float $price): Invoice
    {
        $schedule = Schedule::create([
            'patient_id' => $this->patientId,
            'scheduled_at' => Carbon::parse($start),
            'status' => Schedule::STATUS_PENDING,
            'category' => Schedule::CATEGORYHOMECARE,
        ]);

        $service = $schedule->scheduleServices()->create([
            'type' => ScheduleService::TYPE_ADL,
            'billing_cycle' => $cycle,
            'duration' => $duration,
        ]);

        $invoice = Invoice::create([
            'branch_id' => $this->branchId,
            'total_amount' => 0,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceServices()->create([
            'schedule_services_id' => $service->schedule_services_id,
            'price' => $price,
            'description' => 'Activities of Daily Living',
        ]);

        return $invoice->fresh('invoiceServices.scheduleService.schedule');
    }

    private function monthsFrom(string $start, int $months): int
    {
        $from = Carbon::parse($start)->startOfDay();

        return (int) $from->diffInHours($from->copy()->addMonthsNoOverflow($months));
    }

    private function line(Invoice $invoice): array
    {
        $resource = (new InvoiceResource($invoice))->toArray(Request::create('/'));

        return collect($resource['services'])->first();
    }

    public function test_monthly_line_is_billed_per_month(): void
    {
        $invoice = $this->adlInvoice('2026-10-30 08:00', 'monthly', $this->monthsFrom('2026-10-30', 3), 45000);

        $line = $this->line($invoice);

        $this->assertEquals(3, $line['quantity']);
        $this->assertSame('month', $line['unit']);
        $this->assertEquals(135000, $line['amount']);
    }

    public function test_hourly_line_is_billed_per_hour(): void
    {
        $invoice = $this->adlInvoice('2026-10-30 08:00', 'hourly', 8, 350);

        $line = $this->line($invoice);

        $this->assertEquals(8, $line['quantity']);
        $this->assertSame('hour', $line['unit']);
        $this->assertEquals(2800, $line['amount']);
    }

    public function test_long_monthly_stay_counts_every_calendar_month(): void
    {
        $invoice = $this->adlInvoice('2026-10-30 08:00', 'monthly', $this->monthsFrom('2026-10-30', 15), 45000);

        $this->assertSame(15, $invoice->adlMonthsBilled());
        $this->assertEquals(675000, $this->line($invoice)['amount']);
    }

    public function test_a_short_february_still_counts_as_one_month(): void
    {
        $invoice = $this->adlInvoice('2027-01-31 08:00', 'monthly', $this->monthsFrom('2027-01-31', 1), 45000);

        $this->assertSame(1, $invoice->adlMonthsBilled());
        $this->assertEquals(45000, $this->line($invoice)['amount']);
    }

    public function test_hourly_invoice_has_no_months_billed(): void
    {
        $invoice = $this->adlInvoice('2026-10-30 08:00', 'hourly', 720, 350);

        $this->assertNull($invoice->adlMonthsBilled());
        $this->assertEquals(720, $invoice->adlDuration());
    }

    public function test_payment_allocation_weighs_a_monthly_line_by_months(): void
    {
        $invoice = $this->adlInvoice('2026-10-30 08:00', 'monthly', $this->monthsFrom('2026-10-30', 2), 45000);

        $this->assertEquals(90000, $invoice->paymentLines()->first()['weight']);
    }

    public function test_adl_without_a_billing_cycle_is_saved_as_hourly(): void
    {
        $schedule = Schedule::create([
            'patient_id' => $this->patientId,
            'scheduled_at' => now(),
            'status' => Schedule::STATUS_PENDING,
            'category' => Schedule::CATEGORYHOMECARE,
        ]);

        $service = $schedule->scheduleServices()->create([
            'type' => ScheduleService::TYPE_ADL,
            'duration' => 8,
        ]);

        $this->assertSame(ScheduleService::BILLING_HOURLY, $service->fresh()->billing_cycle);
    }

    public function test_a_schedule_is_not_stay_in_unless_set(): void
    {
        $schedule = Schedule::create([
            'patient_id' => $this->patientId,
            'scheduled_at' => now(),
            'status' => Schedule::STATUS_PENDING,
            'category' => Schedule::CATEGORYHOMECARE,
        ]);

        $this->assertFalse($schedule->fresh()->is_stay_in);
    }
}
