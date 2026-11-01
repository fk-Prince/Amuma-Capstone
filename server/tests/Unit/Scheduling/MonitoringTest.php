<?php

namespace Tests\Unit\Scheduling;

use App\Enums\PermissionAction;
use App\Http\Controllers\ScheduleController;
use App\Models\Schedule;
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
}
