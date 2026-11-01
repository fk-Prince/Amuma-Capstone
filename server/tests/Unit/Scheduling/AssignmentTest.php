<?php

namespace Tests\Unit\Scheduling;

use App\Http\Controllers\ScheduleController;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\ScheduleService;
use App\Models\User;
use App\Repository\ScheduleRepository;
use App\Service\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use UsesTempDatabase;
    use SchedulingFixtures;

    private ScheduleController $controller;
    private NotificationService&MockObject $notifications;
    private array $busy = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpScheduling();

        $repository = $this->createPartialMock(ScheduleRepository::class, ['activeConflictCodes']);
        $repository->method('activeConflictCodes')
            ->willReturnCallback(fn(int $employeeId) => $this->busy[$employeeId] ?? []);

        $this->notifications = $this->createMock(NotificationService::class);

        $this->app->instance(ScheduleRepository::class, $repository);
        $this->app->instance(NotificationService::class, $this->notifications);

        $this->controller = $this->app->make(ScheduleController::class);
    }

    private function assignTo(Schedule $schedule, array $rows, ?User $user = null)
    {
        return $this->controller->action($this->makeRequest([
            'type' => 'assign',
            'schedule_id' => $schedule->schedule_id,
            'assignments' => $rows,
        ], $user));
    }

    private function row(ScheduleService $service, $employee, array $extra = []): array
    {
        return [
            'schedule_services_id' => $service->schedule_services_id,
            'employee_id' => $employee->employee_id,
        ] + $extra;
    }

    public function test_staff_is_assigned_to_the_medical_service(): void
    {
        $nurse = $this->employee('nurse');
        $schedule = $this->schedule();
        $service = $this->medicalService($schedule);

        $response = $this->assignTo($schedule, [$this->row($service, $nurse)]);

        $this->assertSame('Schedule services have been updated successfully.', $response->getData(true)['message']);

        $assigned = ScheduleAssigned::firstOrFail();

        $this->assertSame($service->schedule_services_id, $assigned->schedule_services_id);
        $this->assertSame($nurse->employee_id, $assigned->employee_id);
        $this->assertTrue((bool) $assigned->is_active);
    }

    public function test_staff_already_booked_at_that_time_cannot_be_assigned(): void
    {
        $nurse = $this->employee('nurse', 'online', 'Maria');
        $schedule = $this->schedule();
        $service = $this->medicalService($schedule);
        $this->busy[$nurse->employee_id] = ['SCH01_000009'];

        $this->assertFailsWith(
            'Maria Staff has a schedule conflict with SCH01_000009.',
            fn() => $this->assignTo($schedule, [$this->row($service, $nurse)]),
            409
        );

        $this->assertSame(0, ScheduleAssigned::count());
    }

    public function test_a_medical_service_needs_a_nurse_a_caregiver_can_only_assist(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->schedule();
        $service = $this->medicalService($schedule, 'Insulin Injection');

        $this->assertFailsWith(
            'A medical service needs a nurse. A caregiver can only assist, not be assigned alone.',
            fn() => $this->assignTo($schedule, [$this->row($service, $caregiver)]),
            422
        );

        $this->assertSame(0, ScheduleAssigned::count());
    }
}
