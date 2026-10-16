<?php

namespace Tests\Unit\Scheduling;

use App\Enums\PermissionAction;
use App\Http\Controllers\ScheduleController;
use App\Models\EmployeeBranch;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\User;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use UsesTempDatabase;
    use SchedulingFixtures;

    private ScheduleController $controller;
    private User $nurseUser;
    private User $caregiverUser;
    private Schedule $nursesVisit;
    private Schedule $caregiversVisit;
    private Schedule $nursesFinishedVisit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpScheduling();
        $this->controller = $this->app->make(ScheduleController::class);

        $this->nurseUser = $this->staff([PermissionAction::Read->value], 'nurse', 'online', 'Maria');
        $this->caregiverUser = $this->staff([PermissionAction::Read->value], 'caregiver', 'online', 'Ana');

        $second = $this->patient('Pedro', 'Reyes');

        $this->nursesVisit = $this->schedule(Schedule::CATEGORYHOMECARE, Schedule::STATUS_PENDING, $this->patient, '+2 days 09:00');
        $this->assign($this->medicalService($this->nursesVisit), $this->nurseUser->employee);

        $this->caregiversVisit = $this->schedule(Schedule::CATEGORYHOMECARE, Schedule::STATUS_ONGOING, $second, '+3 days 08:00');
        $this->assign($this->adlService($this->caregiversVisit), $this->caregiverUser->employee);

        $this->nursesFinishedVisit = $this->schedule(Schedule::CATEGORYHOMECARE, Schedule::STATUS_COMPLETED, $second, '+4 days 10:00');
        $this->assign($this->medicalService($this->nursesFinishedVisit), $this->nurseUser->employee);
    }

    private function codes(array $payload = [], ?User $user = null): array
    {
        return collect(
            $this->controller->index($this->makeRequest($payload, $user))->response()->getData(true)['data']
        )->pluck('schedule_code')->sort()->values()->all();
    }

    private function workload(array $payload = [], ?User $user = null): array
    {
        return collect(
            $this->controller->action($this->makeRequest(['type' => 'employee_schedules'] + $payload, $user))
                ->response()->getData(true)['data']
        )->mapWithKeys(fn($row) => [
            $row['full_name'] => collect($row['schedules'])->pluck('schedule_code')->sort()->values()->all(),
        ])->all();
    }

    private function sorted(Schedule ...$schedules): array
    {
        return collect($schedules)->pluck('schedule_code')->sort()->values()->all();
    }

    public function test_staff_sees_every_schedule_of_the_branch(): void
    {
        $this->assertSame(
            $this->sorted($this->nursesVisit, $this->caregiversVisit, $this->nursesFinishedVisit),
            $this->codes()
        );
    }

    public function test_nurse_sees_only_the_schedules_assigned_to_them(): void
    {
        $this->assertSame(
            $this->sorted($this->nursesVisit, $this->nursesFinishedVisit),
            $this->codes(['assigned_only' => true], $this->nurseUser)
        );
    }

    public function test_schedules_can_be_filtered_by_status(): void
    {
        $this->assertSame($this->sorted($this->caregiversVisit), $this->codes(['status' => ['ongoing']]));
        $this->assertSame($this->sorted($this->nursesFinishedVisit), $this->codes(['status' => ['completed']]));
    }

    public function test_schedules_of_one_patient_are_listed(): void
    {
        $this->assertSame($this->sorted($this->nursesVisit), $this->codes(['patient_uuid' => $this->patient->uuid]));
    }

    public function test_each_staff_member_sees_the_patients_they_look_after(): void
    {
        $workload = $this->workload();

        $this->assertSame($this->sorted($this->nursesVisit, $this->nursesFinishedVisit), $workload['Maria Staff']);
        $this->assertSame($this->sorted($this->caregiversVisit), $workload['Ana Staff']);
    }

    public function test_staff_only_sees_their_own_workload(): void
    {
        $workload = $this->workload(['assigned_only' => true], $this->caregiverUser);

        $this->assertSame(['Ana Staff'], array_keys($workload));
        $this->assertSame($this->sorted($this->caregiversVisit), $workload['Ana Staff']);
    }

    public function test_the_workload_can_be_narrowed_to_one_status(): void
    {
        $workload = $this->workload(['status' => ['completed']]);

        $this->assertSame($this->sorted($this->nursesFinishedVisit), $workload['Maria Staff']);
        $this->assertSame([], $workload['Ana Staff']);
    }

    public function test_staff_on_leave_is_not_listed(): void
    {
        EmployeeBranch::where('employee_id', $this->caregiverUser->employee->employee_id)
            ->update(['status' => EmployeeBranch::STATUS_ONLEAVE]);

        $this->assertArrayNotHasKey('Ana Staff', $this->workload());
    }

    public function test_replaced_staff_no_longer_has_the_schedule_in_their_workload(): void
    {
        ScheduleAssigned::where('employee_id', $this->nurseUser->employee->employee_id)
            ->whereHas('scheduleService', fn($q) => $q->where('schedule_id', $this->nursesVisit->schedule_id))
            ->update(['is_active' => false]);

        $this->assertSame($this->sorted($this->nursesFinishedVisit), $this->workload()['Maria Staff']);
    }

    public function test_insufficient_permissions_to_view_the_schedules(): void
    {
        $outsider = $this->makeUser();

        $this->assertFailsWith('Insufficient permissions', fn() => $this->codes([], $outsider), 403);
    }

    public function test_insufficient_permissions_to_see_the_staff_workload(): void
    {
        $noAccess = $this->staff([], 'nurse');

        $this->assertFailsWith('Insufficient permissions', fn() => $this->workload([], $noAccess), 403);
    }
}
