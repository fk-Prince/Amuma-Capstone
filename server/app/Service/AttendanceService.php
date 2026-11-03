<?php

namespace App\Service;

use App\Events\QrScanned;
use App\Models\Employee;
use App\Models\AdlAttendance;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\ScheduleService;
use App\Repository\AdlAttendanceRepository;
use App\Repository\ScheduleRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdlAttendanceService
{

    public const QR_TTL_MINUTES = 5;

    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private NotificationService $notificationService,
    ) {}

    public function generateQr(array $payload): array
    {
        $scheduleServicesId = (int) $payload['schedule_services_id'];
        $byEmployee = !empty($payload['employee_id']);
        $scheduleService = ScheduleService::with('schedule')->find($scheduleServicesId);

        if ($scheduleService?->type === ScheduleService::TYPE_ADL) {
            if (!in_array(
                strtolower((string) $scheduleService->schedule?->status),
                [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING],
                true
            )) {
                throw new Exception('QR codes are not available for this ADL schedule.', 422);
            }

            if ($byEmployee && in_array($payload['type'], ['in', 'out'], true)) {
                throw new Exception('Only the family can generate the time-in and time-out QR for this visit.', 403);
            }

            if (!$byEmployee && $payload['type'] === 'handover') {
                throw new Exception('Only the caregiver on duty can generate the shift change QR.', 403);
            }
        }

        return match ($payload['type']) {
            'in' => ['type' => 'in', 'token' => $this->generateClockInToken($payload)],
            'out' => ['type' => 'out', 'token' => $this->generateClockOutToken($payload)],
            'handover' => ['type' => 'handover', 'token' => $this->generateHandoverToken($payload)],
            default => throw new Exception('Invalid QR type.', 422),
        };
    }

    public function generateHandoverToken(array $payload): string
    {
        $scheduleServicesId = (int) $payload['schedule_services_id'];
        $session = $this->activeSessionFor($scheduleServicesId);

        if (!$session) {
            throw new Exception('No caregiver is clocked in for this visit yet.', 409);
        }

        if ((int) $session->assigned?->employee_id !== (int) ($payload['employee_id'] ?? 0)) {
            throw new Exception('Only the caregiver on duty can generate the shift change QR.', 403);
        }

        $hasNextShift = ScheduleAssigned::where('schedule_services_id', $scheduleServicesId)
            ->where('is_active', true)
            ->where('employee_id', '!=', $session->assigned?->employee_id)
            ->exists();

        if (!$hasNextShift) {
            throw new Exception('No other caregiver is assigned to take over this visit.', 409);
        }

        $token = Str::random(64);

        Cache::put(
            $this->qrCacheKey('handover', $token),
            ['adl_attendance_id' => $session->adl_attendance_id],
            now()->addMinutes(self::QR_TTL_MINUTES)
        );

        return $token;
    }

    public function generateClockOutToken(array $payload)
    {
        $session = $this->activeSessionFor($payload['schedule_services_id']);

        if (!$session) {
            throw new Exception(
                !empty($payload['as_family'])
                    ? 'The assigned caregiver has not clocked in yet.'
                    : 'Employee has not clocked in yet.',
                409
            );
        }

        $token = Str::random(64);

        Cache::put(
            $this->qrCacheKey('out', $token),
            ['adl_attendance_id' => $session->adl_attendance_id],
            now()->addMinutes(self::QR_TTL_MINUTES)
        );

        return $token;
    }


    public function generateClockInToken(array $payload)
    {
        $scheduleServicesId = $payload['schedule_services_id'];

        $this->guardHasActiveAssignment($scheduleServicesId);
        $this->guardAdlCanClockIn($scheduleServicesId);

        $activeSession = $this->activeSessionFor($scheduleServicesId);

        if ($activeSession) {
            throw new Exception(
                'There is already a caregiver clocked in for this visit. Use Generate QR Out to clock them out.',
                409
            );
        }

        $token = Str::random(64);

        Cache::put(
            $this->qrCacheKey('in', $token),
            ['schedule_services_id' => $scheduleServicesId],
            now()->addMinutes(self::QR_TTL_MINUTES)
        );

        return $token;
    }

    public function verifyQr(array $payload)
    {
        return match ($payload['type']) {
            'in' => $this->verifyClockInToken($payload),
            'out' => $this->verifyClockOutToken($payload),
            'handover' => $this->verifyHandoverToken($payload),
            default => throw new Exception('Invalid QR type.', 422),
        };
    }

    private function verifyHandoverToken(array $payload)
    {
        $cacheKey = $this->qrCacheKey('handover', $payload['token']);
        $data = Cache::get($cacheKey);

        if (!$data) {
            throw new Exception('This QR code is invalid or has expired.', 410);
        }

        return DB::transaction(function () use ($payload, $data, $cacheKey) {
            $session = AdlAttendance::with('assigned')
                ->where('adl_attendance_id', $data['adl_attendance_id'])
                ->lockForUpdate()
                ->first();

            if (!$session || $session->out_timestamp || !$session->assigned) {
                throw new Exception('This QR code is invalid or has expired.', 410);
            }

            $scheduleServicesId = $session->assigned->schedule_services_id;

            if ((int) $session->assigned->employee_id === (int) $payload['employee_id']) {
                throw new Exception("You're already on duty. The next shift needs to scan this code.", 409);
            }

            $next = ScheduleAssigned::where('schedule_services_id', $scheduleServicesId)
                ->where('employee_id', $payload['employee_id'])
                ->where('is_active', true)
                ->first();

            if (!$next) {
                throw new Exception('You are not assigned to this schedule.', 403);
            }

            Cache::forget($cacheKey);

            $now = now();

            $session->update([
                'out_timestamp' => $now,
                'type_out' => AdlAttendance::TYPE_HANDOVER,
            ]);

            $handover = AdlAttendance::create([
                'schedule_assigned_id' => $next->schedule_assigned_id,
                'type_in' => AdlAttendance::TYPE_HANDOVER,
                'in_timestamp' => $now,
            ]);

            $this->startAdlSchedule($scheduleServicesId);

            $previous = $session->assigned->employee_id;

            DB::afterCommit(fn() => $this->notifyFamilyOfHandover($scheduleServicesId, $previous, $next->employee_id));

            broadcast(new QrScanned($payload['token'], 'handover'));

            return $handover;
        });
    }

    private function verifyClockInToken(array $payload)
    {
        $cacheKey = $this->qrCacheKey('in', $payload['token']);
        $data = Cache::get($cacheKey);

        if (!$data) {
            throw new Exception('This QR code is invalid or has expired.', 410);
        }

        $assigned = ScheduleAssigned::where('schedule_services_id', $data['schedule_services_id'])
            ->where('employee_id', $payload['employee_id'])
            ->where('is_active', true)
            ->first();

        if (!$assigned) {
            throw new Exception('You are not assigned to this schedule.', 403);
        }

        $this->guardAdlCanClockIn($assigned->schedule_services_id);
        $this->guardWithinShift($assigned);

        Cache::forget($cacheKey);

        return DB::transaction(function () use ($payload, $assigned) {
            $activeSession = AdlAttendance::whereHas(
                'assigned',
                fn($query) => $query->where('schedule_services_id', $assigned->schedule_services_id)
            )
                ->whereNotNull('in_timestamp')
                ->whereNull('out_timestamp')
                ->lockForUpdate()
                ->first();

            if ($activeSession) {
                throw new Exception('Another caregiver is already clocked in for this visit.', 409);
            }

            $session = AdlAttendance::create([
                'schedule_assigned_id' => $assigned->schedule_assigned_id,
                'type_in' => AdlAttendance::TYPE_SCANNED,
                'in_timestamp' => now(),
            ]);

            $this->startAdlSchedule($assigned->schedule_services_id);

            broadcast(new QrScanned($payload['token'], 'in'));

            return $session;
        });
    }

    private function verifyClockOutToken(array $payload)
    {
        $cacheKey = $this->qrCacheKey('out', $payload['token']);
        $data = Cache::get($cacheKey);

        if (!$data) {
            throw new Exception('This QR code is invalid or has expired.', 410);
        }

        return DB::transaction(function () use ($payload, $data, $cacheKey) {
            $session = AdlAttendance::where('adl_attendance_id', $data['adl_attendance_id'])
                ->lockForUpdate()
                ->first();

            if (!$session || $session->out_timestamp) {
                throw new Exception('This QR code is invalid or has expired.', 410);
            }

            $this->guardClockOutEmployee($session->assigned, $payload['employee_id']);

            Cache::forget($cacheKey);

            $session->update([
                'out_timestamp' => now(),
                'type_out' => AdlAttendance::TYPE_SCANNED,
            ]);

            $this->completeAdlScheduleIfDue($session->assigned->schedule_services_id);

            broadcast(new QrScanned($payload['token'], 'out'));

            return $session;
        });
    }


    public function qrContext(int $scheduleServicesId): array
    {
        $assigned = ScheduleAssigned::where('schedule_services_id', $scheduleServicesId)
            ->where('is_active', true)
            ->pluck('employee_id');

        $employees = Employee::whereIn('employee_id', $assigned)->get()->keyBy('employee_id');

        $session = $this->activeSessionFor($scheduleServicesId);
        $checkedInId = $session?->assigned?->employee_id;
        $checkedIn = $checkedInId
            ? ($employees->get($checkedInId) ?? Employee::find($checkedInId))
            : null;

        return [
            'caregivers' => $employees
                ->map(fn(Employee $employee) => [
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->full_name,
                ])
                ->values(),
            'checked_in' => $checkedIn ? [
                'employee_id' => $checkedIn->employee_id,
                'name' => $checkedIn->full_name,
                'in_timestamp' => $session->in_timestamp,
            ] : null,
        ];
    }

    private function activeSessionFor(int $scheduleServicesId): ?AdlAttendance
    {
        return AdlAttendance::whereHas(
            'assigned',
            fn($query) => $query->where('schedule_services_id', $scheduleServicesId)
        )
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->latest('adl_attendance_id')
            ->first();
    }

    private function guardClockOutEmployee(?ScheduleAssigned $assigned, mixed $employeeId): void
    {
        if (!$assigned) {
            throw new Exception('You are not assigned to this schedule.', 402);
        }

        if ((int) $assigned->employee_id !== (int) $employeeId) {
            throw new Exception(
                'Only the caregiver who clocked in can clock out of this visit.',
                409
            );
        }
    }

    private function qrCacheKey(string $type, string $token): string
    {
        return "qr:{$type}:{$token}";
    }


    private function guardHasActiveAssignment(int $scheduleServicesId): void
    {
        $exists = ScheduleAssigned::where('schedule_services_id', $scheduleServicesId)
            ->where('is_active', true)
            ->exists();

        if (!$exists) {
            throw new Exception('No active caregiver is assigned to this schedule.', 404);
        }
    }

    private function notifyFamilyOfHandover(int $scheduleServicesId, int $fromEmployeeId, int $toEmployeeId): void
    {
        try {
            $schedule = ScheduleService::with('schedule.patient')->find($scheduleServicesId)?->schedule;
            $patient = $schedule?->patient;

            if (!$patient) {
                return;
            }

            $from = Employee::find($fromEmployeeId);
            $to = Employee::with('users')->find($toEmployeeId);

            $this->notificationService->notifyPatientAccess(
                $patient,
                "{$patient->display_name}'s daily care (ADL) visit {$schedule->schedule_code}: shift change. "
                    . ($to?->full_name ?? 'The next caregiver') . ' took over from '
                    . ($from?->full_name ?? 'the previous caregiver') . '.',
                'Schedule',
                $to?->users,
            );
        } catch (\Throwable $e) {
            Log::error('Shift change family notice failed', [
                'schedule_services_id' => $scheduleServicesId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public const EARLY_CLOCK_IN_MINUTES = 60;

    private function guardWithinShift(ScheduleAssigned $assigned): void
    {
        if (!$assigned->start_time || !$assigned->end_time || !$this->isAdl($assigned->schedule_services_id)) {
            return;
        }

        $schedule = ScheduleService::with('schedule')->find($assigned->schedule_services_id)?->schedule;

        if (
            strtolower((string) $schedule?->status) === Schedule::STATUS_ONGOING
            && !$this->activeSessionFor($assigned->schedule_services_id)
        ) {
            return;
        }

        $now = now();

        foreach ([-1, 0] as $offset) {
            $day = $now->copy()->startOfDay()->addDays($offset);
            $start = $day->copy()->setTimeFromTimeString((string) $assigned->start_time);
            $end = $day->copy()->setTimeFromTimeString((string) $assigned->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            if ($now->betweenIncluded($start->copy()->subMinutes(self::EARLY_CLOCK_IN_MINUTES), $end)) {
                return;
            }
        }

        $shift = trim(($assigned->note ? $assigned->note . ', ' : '')
            . Carbon::parse((string) $assigned->start_time)->format('g:i A')
            . ' – '
            . Carbon::parse((string) $assigned->end_time)->format('g:i A'));

        throw new Exception(
            "You can only clock in during your shift ({$shift}), or up to 1 hour before it starts.",
            422
        );
    }

    private function isAdl(int $scheduleServicesId): bool
    {
        return ScheduleService::where('schedule_services_id', $scheduleServicesId)
            ->value('type') === ScheduleService::TYPE_ADL;
    }

    private function guardAdlCanClockIn(int $scheduleServicesId): void
    {
        $scheduleService = ScheduleService::with('schedule')->find($scheduleServicesId);

        if (
            $scheduleService?->type === ScheduleService::TYPE_ADL
            && !in_array(
                strtolower((string) $scheduleService->schedule?->status),
                [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING],
                true
            )
        ) {
            throw new Exception('Time in is not available for this ADL schedule.', 422);
        }
    }

    private function completeAdlScheduleIfDue(int $scheduleServicesId): void
    {
        $scheduleService = ScheduleService::with('schedule.scheduleServices.service')->find($scheduleServicesId);
        $schedule = $scheduleService?->schedule;

        if ($scheduleService?->type !== ScheduleService::TYPE_ADL || !$schedule?->scheduled_at) {
            return;
        }

        $scheduledAt = Carbon::parse($schedule->scheduled_at);

        $firstScan = AdlAttendance::whereHas(
            'assigned',
            fn($query) => $query->where('schedule_services_id', $scheduleServicesId)
        )->min('in_timestamp');

        $start = $firstScan && Carbon::parse($firstScan)->lt($scheduledAt)
            ? Carbon::parse($firstScan)
            : $scheduledAt;

        $end = $start->copy()
            ->addMinutes($this->scheduleRepository->calculateScheduleDurationMinutes($schedule));

        if (now()->diffInSeconds($end, false) < 60) {
            $schedule->update(['status' => Schedule::STATUS_COMPLETED]);
        }
    }

    private function startAdlSchedule(int $scheduleServicesId): void
    {
        $scheduleService = ScheduleService::with('schedule')->find($scheduleServicesId);
        $schedule = $scheduleService?->schedule;

        if (
            $scheduleService?->type === ScheduleService::TYPE_ADL
            && $schedule
            && strtolower((string) $schedule->status) !== Schedule::STATUS_ONGOING
        ) {
            $schedule->update(['status' => Schedule::STATUS_ONGOING]);
        }
    }
}
