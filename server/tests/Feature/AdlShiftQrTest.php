<?php

namespace Tests\Feature;

use App\Events\QrScanned;
use App\Models\Employee;
use App\Models\AdlAttendance;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\User;
use App\Service\AdlAttendanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class AdlShiftQrTest extends TestCase
{
    use UsesTempDatabase;

    private AdlAttendanceService $service;
    private int $scheduleId;
    private int $scheduleServicesId;
    private int $firstShift;
    private int $secondShift;
    private int $patientId;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([QrScanned::class]);

        $this->service = app(AdlAttendanceService::class);

        $owner = $this->makeUser('owner@amuma.com');

        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => $owner->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'agency_id' => $agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = $this->patientId = DB::table('patients')->insertGetId([
            'branch_id' => $branchId,
            'uuid' => (string) Str::uuid(),
            'patient_code' => 'PT-0001',
            'first_name' => 'Lola',
            'last_name' => 'Cruz',
            'gender' => 'female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->scheduleId = DB::table('schedules')->insertGetId([
            'schedule_code' => 'SCH-0001',
            'patient_id' => $patientId,
            'scheduled_at' => now()->subHours(2),
            'status' => Schedule::STATUS_PENDING,
            'category' => 'Homecare',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->scheduleServicesId = DB::table('schedule_services')->insertGetId([
            'schedule_id' => $this->scheduleId,
            'duration' => 4,
            'type' => 'ADL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->firstShift = $this->assignCaregiver('first@amuma.com');
        $this->secondShift = $this->assignCaregiver('second@amuma.com');
    }

    private function makeUser(string $email): User
    {
        return User::create([
            'uuid' => (string) Str::uuid(),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function assignCaregiver(string $email): int
    {
        $employee = Employee::create([
            'user_id' => $this->makeUser($email)->user_id,
            'first_name' => 'Care',
            'last_name' => 'Giver',
        ]);

        ScheduleAssigned::create([
            'schedule_services_id' => $this->scheduleServicesId,
            'employee_id' => $employee->employee_id,
            'is_active' => true,
        ]);

        return $employee->employee_id;
    }

    private function familyQr(string $type): array
    {
        return $this->service->generateQr([
            'type' => $type,
            'schedule_services_id' => $this->scheduleServicesId,
        ]);
    }

    private function scan(array $qr, int $employeeId)
    {
        return $this->service->verifyQr([
            'token' => $qr['token'],
            'type' => $qr['type'],
            'employee_id' => $employeeId,
        ]);
    }

    private function onDuty(): ?int
    {
        return AdlAttendance::whereNull('out_timestamp')
            ->with('assigned')
            ->get()
            ->first()?->assigned?->employee_id;
    }

    private function setShift(int $employeeId, string $start, string $end): void
    {
        ScheduleAssigned::where('employee_id', $employeeId)
            ->update(['start_time' => $start, 'end_time' => $end, 'note' => 'AM Shift']);
    }

    public function test_clock_in_is_refused_outside_the_shift(): void
    {
        $this->setShift($this->firstShift, '08:00', '12:00');
        $this->travelTo(now()->startOfDay()->setTime(14, 0));

        $this->expectExceptionCode(422);

        $this->scan($this->familyQr('in'), $this->firstShift);
    }

    public function test_clock_in_is_allowed_up_to_an_hour_before_the_shift(): void
    {
        $this->setShift($this->firstShift, '08:00', '12:00');
        $this->travelTo(now()->startOfDay()->setTime(7, 15));

        $this->scan($this->familyQr('in'), $this->firstShift);

        $this->assertSame($this->firstShift, (int) $this->onDuty());
    }

    public function test_a_refused_clock_in_keeps_the_qr_usable(): void
    {
        $this->setShift($this->firstShift, '08:00', '12:00');
        $this->travelTo(now()->startOfDay()->setTime(14, 0));

        $qr = $this->familyQr('in');

        try {
            $this->scan($qr, $this->firstShift);
        } catch (\Exception) {
        }

        $this->scan($qr, $this->secondShift);

        $this->assertSame($this->secondShift, (int) $this->onDuty());
    }

    public function test_an_ongoing_visit_with_nobody_on_duty_can_be_picked_up_any_time(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);
        $this->scan($this->familyQr('out'), $this->firstShift);

        $this->setShift($this->secondShift, '08:00', '12:00');
        $this->travelTo(now()->startOfDay()->setTime(20, 0));

        $this->scan($this->familyQr('in'), $this->secondShift);

        $this->assertSame($this->secondShift, (int) $this->onDuty());
    }

    public function test_the_family_is_told_about_a_shift_change(): void
    {
        $family = $this->makeUser('family@amuma.com');
        $clientId = DB::table('clients')->insertGetId([
            'user_id' => $family->user_id,
            'first_name' => 'Gina',
            'last_name' => 'Cruz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('patient_accesses')->insert([
            'client_id' => $clientId,
            'patient_id' => $this->patientId,
            'have_access' => true,
            'relationship_type' => 'relative',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->scan($this->familyQr('in'), $this->firstShift);
        $this->scan($this->handoverQr($this->firstShift), $this->secondShift);

        $this->assertTrue(
            DB::table('notifications')
                ->where('to_user_id', $family->user_id)
                ->where('message_type', 'Schedule')
                ->where('message', 'like', '%shift change%')
                ->exists()
        );
    }

    public function test_shift_change_is_allowed_outside_the_shift(): void
    {
        $this->setShift($this->secondShift, '08:00', '12:00');
        $this->travelTo(now()->startOfDay()->setTime(20, 0));

        $this->scan($this->familyQr('in'), $this->firstShift);
        $this->scan($this->handoverQr($this->firstShift), $this->secondShift);

        $this->assertSame($this->secondShift, (int) $this->onDuty());
    }

    public function test_employee_cannot_generate_the_time_in_qr_for_adl(): void
    {
        $this->expectExceptionCode(403);

        $this->service->generateQr([
            'type' => 'in',
            'schedule_services_id' => $this->scheduleServicesId,
            'employee_id' => $this->firstShift,
        ]);
    }

    public function test_first_scan_clocks_in_and_starts_the_schedule(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        $this->assertSame($this->firstShift, (int) $this->onDuty());
        $this->assertSame(Schedule::STATUS_ONGOING, Schedule::find($this->scheduleId)->status);
    }

    private function handoverQr(int $employeeId): array
    {
        return $this->service->generateQr([
            'type' => 'handover',
            'schedule_services_id' => $this->scheduleServicesId,
            'employee_id' => $employeeId,
        ]);
    }

    public function test_family_cannot_generate_a_shift_change_qr(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        $this->expectExceptionCode(403);

        $this->familyQr('handover');
    }

    public function test_family_time_in_is_refused_while_someone_is_on_duty(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        $this->expectExceptionCode(409);

        $this->familyQr('in');
    }

    public function test_second_shift_can_scan_the_first_shifts_handover_qr(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        $this->scan($this->handoverQr($this->firstShift), $this->secondShift);

        $this->assertSame($this->secondShift, (int) $this->onDuty());
        $this->assertSame(1, AdlAttendance::where('type_out', AdlAttendance::TYPE_HANDOVER)->count());
        $this->assertSame(1, AdlAttendance::where('type_in', AdlAttendance::TYPE_HANDOVER)->count());
    }

    public function test_missed_schedule_has_no_qr(): void
    {
        Schedule::where('schedule_id', $this->scheduleId)->update(['status' => Schedule::STATUS_MISSED]);

        $this->expectExceptionCode(422);

        $this->familyQr('in');
    }

    public function test_only_the_caregiver_on_duty_can_generate_a_handover_qr(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        $this->expectExceptionCode(403);

        $this->handoverQr($this->secondShift);
    }

    public function test_the_caregiver_on_duty_cannot_scan_their_own_handover(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        $this->expectExceptionCode(409);

        $this->scan($this->handoverQr($this->firstShift), $this->firstShift);
    }

    public function test_either_shift_can_time_out_with_the_family_qr(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);
        $this->scan($this->familyQr('out'), $this->firstShift);
        $this->assertNull($this->onDuty());

        $this->scan($this->familyQr('in'), $this->secondShift);
        $this->scan($this->familyQr('out'), $this->secondShift);
        $this->assertNull($this->onDuty());

        $this->assertSame(Schedule::STATUS_ONGOING, Schedule::find($this->scheduleId)->status);
    }

    public function test_time_out_completes_the_schedule_when_under_a_minute_remains(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        Schedule::where('schedule_id', $this->scheduleId)
            ->update(['scheduled_at' => now()->subHours(4)->addSeconds(30)]);

        $this->scan($this->familyQr('out'), $this->firstShift);

        $this->assertSame(Schedule::STATUS_COMPLETED, Schedule::find($this->scheduleId)->status);
    }

    public function test_early_first_scan_moves_the_visit_end_earlier(): void
    {
        Schedule::where('schedule_id', $this->scheduleId)
            ->update(['scheduled_at' => now()->addHour()]);

        $this->scan($this->familyQr('in'), $this->firstShift);

        AdlAttendance::query()->update(['in_timestamp' => now()->subHours(4)->addSeconds(30)]);

        $this->scan($this->familyQr('out'), $this->firstShift);

        $this->assertSame(Schedule::STATUS_COMPLETED, Schedule::find($this->scheduleId)->status);
    }

    public function test_on_time_first_scan_keeps_the_scheduled_end(): void
    {
        $this->scan($this->familyQr('in'), $this->firstShift);

        AdlAttendance::query()->update(['in_timestamp' => now()->subHour()]);

        $this->scan($this->familyQr('out'), $this->firstShift);

        $this->assertSame(Schedule::STATUS_ONGOING, Schedule::find($this->scheduleId)->status);
    }
}
