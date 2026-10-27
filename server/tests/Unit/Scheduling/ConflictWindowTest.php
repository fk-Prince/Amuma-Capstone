<?php

namespace Tests\Unit\Scheduling;

use App\Http\Controllers\ScheduleController;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\ScheduleService;
use App\Repository\ScheduleRepository;
use App\Service\NotificationService;
use Carbon\Carbon;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class ConflictWindowTest extends TestCase
{
    use UsesTempDatabase;
    use SchedulingFixtures;

    private ScheduleController $controller;
    private array $busy = [];
    private array $checked = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-27 08:00'));
        $this->setUpScheduling();

        $repository = $this->createPartialMock(ScheduleRepository::class, ['activeConflictCodes']);
        $repository->method('activeConflictCodes')->willReturnCallback(
            function (int $employeeId, string $branchId, string $excludeScheduleId, Carbon $start, Carbon $end) {
                $this->checked[$employeeId] = [$start->copy(), $end->copy()];

                return $this->busy[$employeeId] ?? [];
            }
        );

        $this->app->instance(ScheduleRepository::class, $repository);
        $this->app->instance(NotificationService::class, $this->createMock(NotificationService::class));

        $this->controller = $this->app->make(ScheduleController::class);
    }

    private function scheduleAt(string $at): Schedule
    {
        return Schedule::create([
            'patient_id' => $this->patient->patient_id,
            'scheduled_at' => Carbon::parse($at),
            'status' => Schedule::STATUS_PENDING,
            'category' => Schedule::CATEGORYHOMECARE,
        ]);
    }

    private function monthlyAdl(Schedule $schedule, int $months): ScheduleService
    {
        $start = $schedule->scheduled_at->copy()->startOfDay();

        return $schedule->scheduleServices()->create([
            'type' => ScheduleService::TYPE_ADL,
            'billing_cycle' => ScheduleService::BILLING_MONTHLY,
            'duration' => $start->diffInHours($start->copy()->addMonthsNoOverflow($months)),
        ]);
    }

    private function assignTo(Schedule $schedule, ScheduleService $service, mixed $employee, array $shift = [])
    {
        return $this->controller->action($this->makeRequest([
            'type' => 'assign',
            'schedule_id' => $schedule->schedule_id,
            'assignments' => [[
                'schedule_services_id' => $service->schedule_services_id,
                'employee_id' => $employee->employee_id,
            ] + $shift],
        ]));
    }

    private function assertWindow(mixed $employee, string $start, string $end): void
    {
        [$checkedStart, $checkedEnd] = $this->checked[$employee->employee_id];

        $this->assertSame($start, $checkedStart->format('Y-m-d H:i'));
        $this->assertSame($end, $checkedEnd->format('Y-m-d H:i'));
    }

    public function test_monthly_adl_holds_the_caregiver_for_the_whole_stay(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->scheduleAt('2026-10-30 09:00');
        $service = $this->monthlyAdl($schedule, 1);

        $this->assignTo($schedule, $service, $caregiver);

        $this->assertWindow($caregiver, '2026-10-30 09:00', '2026-11-30 09:00');
    }

    public function test_monthly_adl_with_a_day_shift_still_checks_every_day_of_the_stay(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->monthlyAdl($schedule, 15);

        $this->assignTo($schedule, $service, $caregiver, ['start_time' => '08:00', 'end_time' => '16:00']);

        $this->assertWindow($caregiver, '2026-10-30 08:00', '2028-01-30 08:00');
    }

    public function test_caregiver_busy_during_the_month_cannot_take_a_monthly_adl(): void
    {
        $caregiver = $this->employee('caregiver', 'online', 'Ana');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->monthlyAdl($schedule, 1);
        $this->busy[$caregiver->employee_id] = ['SCH01_000042'];

        $this->assertFailsWith(
            'Ana Staff has a schedule conflict with SCH01_000042.',
            fn() => $this->assignTo($schedule, $service, $caregiver, ['start_time' => '08:00', 'end_time' => '16:00']),
            409
        );

        $this->assertSame(0, ScheduleAssigned::count());
    }

    public function test_long_hourly_adl_with_a_shift_checks_the_whole_stay_too(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->adlService($schedule, 24 * 7);

        $this->assignTo($schedule, $service, $caregiver, ['start_time' => '08:00', 'end_time' => '16:00']);

        $this->assertWindow($caregiver, '2026-10-30 08:00', '2026-11-06 08:00');
    }

    public function test_short_hourly_adl_only_checks_the_caregiver_shift(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->adlService($schedule, 8);

        $this->assignTo($schedule, $service, $caregiver, ['start_time' => '10:00', 'end_time' => '12:00']);

        $this->assertWindow($caregiver, '2026-10-30 10:00', '2026-10-30 12:00');
    }

    public function test_short_hourly_adl_without_a_shift_checks_the_whole_visit(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->adlService($schedule, 8);

        $this->assignTo($schedule, $service, $caregiver);

        $this->assertWindow($caregiver, '2026-10-30 08:00', '2026-10-30 16:00');
    }

    public function test_hourly_adl_of_exactly_one_day_still_checks_only_the_shift(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->adlService($schedule, 24);

        $this->assignTo($schedule, $service, $caregiver, ['start_time' => '08:00', 'end_time' => '20:00']);

        $this->assertWindow($caregiver, '2026-10-30 08:00', '2026-10-30 20:00');
    }

    public function test_caregiver_busy_at_the_same_hour_cannot_take_an_hourly_adl(): void
    {
        $caregiver = $this->employee('caregiver', 'online', 'Ben');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->adlService($schedule, 4);
        $this->busy[$caregiver->employee_id] = ['SCH01_000007'];

        $this->assertFailsWith(
            'Ben Staff has a schedule conflict with SCH01_000007.',
            fn() => $this->assignTo($schedule, $service, $caregiver),
            409
        );

        $this->assertSame(0, ScheduleAssigned::count());
    }

    public function test_free_caregiver_is_assigned_to_a_monthly_adl(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->scheduleAt('2026-10-30 08:00');
        $service = $this->monthlyAdl($schedule, 3);

        $this->assignTo($schedule, $service, $caregiver);

        $this->assertTrue((bool) ScheduleAssigned::where('employee_id', $caregiver->employee_id)->value('is_active'));
    }

    public function test_schedule_length_uses_the_booked_duration_for_monthly_and_hourly(): void
    {
        $repository = new ScheduleRepository();

        $monthly = $this->scheduleAt('2026-10-30 08:00');
        $this->monthlyAdl($monthly, 1);

        $hourly = $this->scheduleAt('2026-10-30 08:00');
        $this->adlService($hourly, 8);

        $this->assertEquals(31 * 24 * 60, $repository->calculateScheduleDurationMinutes($monthly->fresh('scheduleServices.service')));
        $this->assertEquals(8 * 60, $repository->calculateScheduleDurationMinutes($hourly->fresh('scheduleServices.service')));
    }
}
