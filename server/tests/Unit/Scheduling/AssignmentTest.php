<?php

namespace Tests\Unit\Scheduling;

use App\Enums\PermissionAction;
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

    public function test_only_a_caregiver_can_be_assigned_to_an_adl_service(): void
    {
        $nurse = $this->employee('nurse');
        $schedule = $this->schedule();
        $service = $this->adlService($schedule);

        $this->assertFailsWith(
            'Only a caregiver can be assigned to an ADL service.',
            fn() => $this->assignTo($schedule, [$this->row($service, $nurse)]),
            422
        );
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

    public function test_caregiver_assigned_with_a_nurse_is_marked_as_assistant(): void
    {
        $nurse = $this->employee('nurse');
        $caregiver = $this->employee('caregiver');
        $schedule = $this->schedule();
        $service = $this->medicalService($schedule, 'Insulin Injection');

        $this->assignTo($schedule, [$this->row($service, $nurse), $this->row($service, $caregiver)]);

        $this->assertSame(2, ScheduleAssigned::count());
        $this->assertSame('Assistant', ScheduleAssigned::where('employee_id', $caregiver->employee_id)->value('note'));
        $this->assertNull(ScheduleAssigned::where('employee_id', $nurse->employee_id)->value('note'));
    }

    public function test_only_homecare_staff_can_be_assigned_to_a_homecare_schedule(): void
    {
        $facilityNurse = $this->employee('nurse', 'facility');
        $schedule = $this->schedule(Schedule::CATEGORYHOMECARE);
        $service = $this->medicalService($schedule);

        $this->assertFailsWith(
            'Only homecare staff can be assigned to a homecare schedule.',
            fn() => $this->assignTo($schedule, [$this->row($service, $facilityNurse)]),
            422
        );
    }

    public function test_only_facility_staff_can_be_assigned_to_a_facility_schedule(): void
    {
        $homecareNurse = $this->employee('nurse', 'online');
        $schedule = $this->schedule(Schedule::CATEGORYFACILITY);
        $service = $this->medicalService($schedule);

        $this->assertFailsWith(
            'Only facility staff can be assigned to a facility schedule.',
            fn() => $this->assignTo($schedule, [$this->row($service, $homecareNurse)]),
            422
        );
    }

    public function test_the_end_time_must_be_later_than_the_start_time(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->schedule();
        $service = $this->adlService($schedule);

        $this->assertFailsWith(
            'The end time must be later than the start time.',
            fn() => $this->assignTo($schedule, [$this->row($service, $caregiver, ['start_time' => '14:00', 'end_time' => '10:00'])]),
            422
        );
    }

    public function test_pick_both_a_start_time_and_an_end_time(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->schedule();
        $service = $this->adlService($schedule);

        $this->assertFailsWith(
            'Pick both a start time and an end time.',
            fn() => $this->assignTo($schedule, [$this->row($service, $caregiver, ['start_time' => '09:00'])]),
            422
        );
    }

    public function test_caregiver_shift_is_saved_with_the_assignment(): void
    {
        $caregiver = $this->employee('caregiver');
        $schedule = $this->schedule();
        $service = $this->adlService($schedule);

        $this->assignTo($schedule, [$this->row($service, $caregiver, ['start_time' => '09:00', 'end_time' => '13:00'])]);

        $assigned = ScheduleAssigned::firstOrFail();

        $this->assertSame('09:00:00', $assigned->start_time);
        $this->assertSame('13:00:00', $assigned->end_time);
    }

    public function test_schedule_service_not_found_for_this_schedule(): void
    {
        $nurse = $this->employee('nurse');
        $schedule = $this->schedule();
        $this->medicalService($schedule);

        $this->assertFailsWith(
            'Schedule service 999 not found for this schedule.',
            fn() => $this->assignTo($schedule, [['schedule_services_id' => 999, 'employee_id' => $nurse->employee_id]]),
            404
        );
    }

    public function test_this_schedule_has_been_cancelled_and_can_no_longer_be_updated(): void
    {
        $nurse = $this->employee('nurse');
        $schedule = $this->schedule(Schedule::CATEGORYHOMECARE, Schedule::STATUS_CANCELLED);
        $service = $this->medicalService($schedule);

        $this->assertFailsWith(
            'This schedule has been cancelled and can no longer be updated.',
            fn() => $this->assignTo($schedule, [$this->row($service, $nurse)]),
            422
        );
    }

    public function test_this_schedule_has_been_completed_and_can_no_longer_be_updated(): void
    {
        $nurse = $this->employee('nurse');
        $schedule = $this->schedule(Schedule::CATEGORYHOMECARE, Schedule::STATUS_COMPLETED);
        $service = $this->medicalService($schedule);

        $this->assertFailsWith(
            'This schedule has been completed and can no longer be updated.',
            fn() => $this->assignTo($schedule, [$this->row($service, $nurse)]),
            422
        );
    }

    public function test_replaced_staff_is_removed_from_the_care_team(): void
    {
        $first = $this->employee('caregiver');
        $second = $this->employee('caregiver');
        $schedule = $this->schedule();
        $service = $this->adlService($schedule);

        $this->assignTo($schedule, [$this->row($service, $first)]);
        $this->assignTo($schedule, [$this->row($service, $second)]);

        $this->assertFalse((bool) ScheduleAssigned::where('employee_id', $first->employee_id)->value('is_active'));
        $this->assertTrue((bool) ScheduleAssigned::where('employee_id', $second->employee_id)->value('is_active'));
    }

    public function test_the_family_is_told_the_care_team_has_changed(): void
    {
        $nurse = $this->employee('nurse');
        $schedule = $this->schedule();
        $service = $this->medicalService($schedule);

        $this->notifications->expects($this->once())
            ->method('notifyPatientAccess')
            ->with(
                $this->callback(fn($patient) => $patient->patient_id === $this->patient->patient_id),
                $this->stringContains('has an updated care team'),
                'Schedule'
            );

        $this->assignTo($schedule, [$this->row($service, $nurse)]);
    }

    public function test_insufficient_permissions_to_assign(): void
    {
        $viewer = $this->staff([PermissionAction::Read->value], 'nurse');
        $nurse = $this->employee('nurse');
        $schedule = $this->schedule();
        $service = $this->medicalService($schedule);

        $this->assertFailsWith(
            'Insufficient permissions',
            fn() => $this->assignTo($schedule, [$this->row($service, $nurse)], $viewer),
            403
        );

        $this->assertSame(0, ScheduleAssigned::count());
    }
}
