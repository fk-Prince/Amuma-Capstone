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

        $patientId = DB::table('patients')->insertGetId([
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
            'hours_booked' => 4,
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
