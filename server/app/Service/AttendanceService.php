<?php

namespace App\Service;

use App\Events\QrScanned;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\ScheduleService;
use App\Models\User;
use App\Repository\AttendanceRepository;
use App\Repository\ScheduleRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AttendanceService
{

    public const QR_TTL_MINUTES = 5;

    public const UNPAID_COMPLETION_MESSAGE = "This service hasn't been fully paid yet. Do you still want to time out?";
    public const UNPAID_VISIT_MESSAGE = "This service hasn't been fully paid yet. Do you still want to mark it as complete?";

    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private NotificationService $notificationService,
        private AttendanceRepository $attendanceRepository,
    ) {}

    public function lookupPatientVisit(array $payload): array
    {
        [$schedule, $services] = $this->resolvePatientVisit($payload['patient_uuid'], (int) $payload['employee_id']);

        return $this->patientVisitPayload($schedule, $services, (int) $payload['employee_id']);
    }

    public function confirmPatientVisit(array $payload, ?User $actor): array
    {
        $scannerId = (int) $payload['employee_id'];

        [$schedule, $services] = $this->resolvePatientVisit($payload['patient_uuid'], $scannerId);

        if ((int) ($payload['schedule_id'] ?? 0) !== (int) $schedule->schedule_id) {
            throw new Exception('This visit has changed. Please scan the patient QR again.', 409);
        }

        $present = collect($payload['employee_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->push($scannerId)
            ->unique();

        $scanner = Employee::find($scannerId);
        $wasCheckedIn = $this->visitAttendance($services)->isNotEmpty();

        if (strtolower((string) $schedule->status) === Schedule::STATUS_COMPLETED) {
            throw new Exception('This visit is already completed. Change who attended from Schedule Details.', 409);
        }

        $timedIn = [];

        DB::transaction(function () use ($schedule, $services, $present, $scannerId, $scanner, $wasCheckedIn, &$timedIn) {
            foreach ($services as $scheduleService) {
                foreach ($scheduleService->assigned as $assigned) {
                    $employeeId = (int) $assigned->employee_id;
                    $isOpen = $assigned->attendances->contains(fn($row) => $row->in_timestamp && !$row->out_timestamp);
                    $attended = $assigned->attendances->contains(fn($row) => $row->in_timestamp !== null);

                    if (!$present->contains($employeeId)) {
                        if (!$wasCheckedIn) {
                            $this->attendanceRepository->removeMarked($assigned);
                        }
                        continue;
                    }

                    if ($isOpen || ($wasCheckedIn && $attended)) {
                        continue;
                    }

                    $this->attendanceRepository->markPresent(
                        $assigned,
                        $employeeId === $scannerId ? Attendance::TYPE_SCANNED : Attendance::TYPE_MARKED,
                        $employeeId === $scannerId ? null : 'Marked present by ' . ($scanner?->full_name ?? 'staff'),
                    );

                    $timedIn[$employeeId] = $employeeId === $scannerId
                        ? $scanner?->full_name
                        : $assigned->employee?->full_name;
                }
            }

            if (strtolower((string) $schedule->status) !== Schedule::STATUS_ONGOING) {
                $schedule->update(['status' => Schedule::STATUS_ONGOING]);
            }
        });

        $names = $this->joinNames(array_filter($timedIn));

        if (!$wasCheckedIn) {
            $this->notifyFamilyOfVisit($schedule, ' has started' . ($names ? " with {$names}" : '') . '.', $actor);
        } elseif ($names) {
            $this->notifyFamilyOfVisit($schedule, ": {$names} timed in.", $actor);
        }

        [$schedule, $services] = $this->resolvePatientVisit($payload['patient_uuid'], $scannerId);

        return $this->patientVisitPayload($schedule, $services, $scannerId);
    }

    public function timeOutPatientVisit(array $payload, ?User $actor): array
    {
        $scannerId = (int) $payload['employee_id'];

        [$schedule, $services] = $this->resolvePatientVisit($payload['patient_uuid'], $scannerId);

        if ((int) ($payload['schedule_id'] ?? 0) !== (int) $schedule->schedule_id) {
            throw new Exception('This visit has changed. Please scan the patient QR again.', 409);
        }

        if ($this->visitAttendance($services)->isEmpty()) {
            throw new Exception('This visit has not been checked in yet.', 409);
        }

        $assigned = $services->flatMap(fn($scheduleService) => $scheduleService->assigned);

        if (empty($payload['complete'])) {
            $mine = $assigned->filter(fn($row) => (int) $row->employee_id === $scannerId);

            $isOpen = $mine
                ->flatMap(fn($row) => $row->attendances)
                ->contains(fn($row) => $row->in_timestamp && !$row->out_timestamp);

            if (!$isOpen) {
                throw new Exception("You've already timed out of this visit.", 409);
            }

            $this->attendanceRepository->closeOpen($mine->pluck('schedule_assigned_id')->all());

            $scanner = Employee::find($scannerId);
            $this->notifyFamilyOfVisit($schedule, ': ' . ($scanner?->full_name ?? 'A staff member') . ' timed out.', $actor);

            return [
                'schedule_id' => $schedule->schedule_id,
                'schedule_code' => $schedule->schedule_code,
                'status' => $schedule->status,
            ];
        }

        $this->guardUnpaidCompletion($schedule, $payload, self::UNPAID_VISIT_MESSAGE);

        DB::transaction(function () use ($schedule, $assigned) {
            $this->attendanceRepository->closeOpen($assigned->pluck('schedule_assigned_id')->all());

            $schedule->update(['status' => Schedule::STATUS_COMPLETED]);
        });

        $this->notifyFamilyOfVisit($schedule, ' has been completed.', $actor);

        return [
            'schedule_id' => $schedule->schedule_id,
            'schedule_code' => $schedule->schedule_code,
            'status' => Schedule::STATUS_COMPLETED,
        ];
    }

    public function updateVisitAttendance(array $payload, User $actor): array
    {
        $schedule = $this->attendanceRepository->medicalVisit((int) $payload['schedule_id']);

        if (!$schedule || (int) $schedule->patient?->branch_id !== (int) $payload['branch_id']) {
            throw new Exception('Schedule not found.', 404);
        }

        if ($schedule->isOnsite()) {
            throw new Exception("Facility schedules don't need attendance.", 422);
        }

        $status = strtolower((string) $schedule->status);

        if (!in_array($status, [Schedule::STATUS_ONGOING, Schedule::STATUS_COMPLETED], true)) {
            throw new Exception('Attendance can only be updated once the visit has started.', 422);
        }

        $services = $schedule->scheduleServices;

        if ($services->isEmpty()) {
            throw new Exception('This schedule has no medical service.', 422);
        }

        $present = collect($payload['employee_ids'] ?? [])->map(fn($id) => (int) $id)->unique();

        if ($status === Schedule::STATUS_ONGOING && $present->isEmpty()) {
            throw new Exception('Keep at least one staff member present while the visit is ongoing.', 422);
        }

        $rows = $this->visitAttendance($services);
        $timeIn = $rows->min('in_timestamp') ?? Carbon::parse($schedule->scheduled_at);
        $timeOut = $status === Schedule::STATUS_ONGOING
            ? null
            : ($rows->max('out_timestamp')
                ?? $timeIn->copy()->addMinutes($this->scheduleRepository->calculateScheduleDurationMinutes($schedule)));

        $actor->loadMissing('employee');
        $editor = $actor->employee?->full_name ?? 'the branch';

        DB::transaction(function () use ($services, $present, $timeIn, $timeOut, $editor) {
            foreach ($services as $scheduleService) {
                foreach ($scheduleService->assigned as $assigned) {
                    $attended = $assigned->attendances->contains(fn($row) => $row->in_timestamp !== null);

                    if (!$present->contains((int) $assigned->employee_id)) {
                        if ($attended) {
                            $this->attendanceRepository->clearAttendance($assigned);
                        }
                        continue;
                    }

                    if (!$attended) {
                        $this->attendanceRepository->recordPresent($assigned, $timeIn, $timeOut, "Marked present by {$editor}");
                    }
                }
            }
        });

        return $this->attendanceRepository->medicalVisit($schedule->schedule_id)->scheduleServices
            ->flatMap(fn($scheduleService) => $scheduleService->assigned->map(fn($assigned) => [
                'schedule_services_id' => $assigned->schedule_services_id,
                'employee_id' => $assigned->employee_id,
                'online' => $assigned->attendances
                    ->filter(fn($row) => $row->in_timestamp !== null)
                    ->map(fn($row) => [
                        'attendance_id' => $row->attendance_id,
                        'type_in' => $row->type_in,
                        'type_out' => $row->type_out,
                        'in_timestamp' => $row->in_timestamp?->toISOString(),
                        'out_timestamp' => $row->out_timestamp?->toISOString(),
                        'notes' => $row->notes,
                    ])
                    ->values(),
            ]))
            ->values()
            ->all();
    }

    private function resolvePatientVisit(string $patientUuid, int $employeeId): array
    {
        $patient = $this->attendanceRepository->findPatientByUuid($patientUuid);

        if (!$patient) {
            throw new Exception("This isn't a valid patient QR code.", 404);
        }

        $schedules = $this->attendanceRepository->todaysMedicalSchedules($patient->patient_id);

        if ($schedules->isEmpty()) {
            throw new Exception("No scheduled medical visit found for {$patient->display_name} today.", 404);
        }

        $schedules = $schedules->reject(fn($schedule) => $schedule->isOnsite());

        if ($schedules->isEmpty()) {
            throw new Exception("{$patient->display_name}'s visit today is a facility schedule. Facility schedules don't need attendance.", 422);
        }

        $isAssigned = fn($scheduleService) => $scheduleService->assigned
            ->contains(fn($assigned) => (int) $assigned->employee_id === $employeeId);

        $mine = $schedules->filter(fn($schedule) => $schedule->scheduleServices->contains($isAssigned));

        if ($mine->isEmpty()) {
            throw new Exception("You are not assigned to {$patient->display_name}'s visit today.", 403);
        }

        $scannable = $mine->filter(fn($schedule) => $schedule->scheduleServices
            ->contains(fn($scheduleService) => $scheduleService->assigned->contains(
                fn($assigned) => (int) $assigned->employee_id === $employeeId && $assigned->can_scan !== false
            )));

        if ($scannable->isEmpty()) {
            throw new Exception("You can't scan the QR for {$patient->display_name}'s visit. Ask the branch to turn on QR for you.", 403);
        }

        $schedule = $scannable
            ->sortBy(fn($schedule) => [
                strtolower((string) $schedule->status) === Schedule::STATUS_ONGOING ? 0 : 1,
                abs(now()->diffInMinutes($schedule->scheduled_at, false)),
            ])
            ->first();

        $schedule->setRelation('patient', $patient);

        return [$schedule, $schedule->scheduleServices->values()];
    }

    private function visitAttendance(Collection $services): Collection
    {
        return $services
            ->flatMap(fn($scheduleService) => $scheduleService->assigned)
            ->flatMap(fn($assigned) => $assigned->attendances)
            ->filter(fn($row) => $row->in_timestamp !== null);
    }

    private function patientVisitPayload(Schedule $schedule, Collection $services, int $scannerId): array
    {
        $staff = $services
            ->flatMap(fn($scheduleService) => $scheduleService->assigned)
            ->groupBy('employee_id')
            ->map(function ($rows, $employeeId) use ($scannerId) {
                $employee = $rows->first()->employee?->employees;
                $attendances = $rows->flatMap(fn($assigned) => $assigned->attendances);

                $attendance = $attendances
                    ->filter(fn($row) => $row->in_timestamp && !$row->out_timestamp)
                    ->sortBy('in_timestamp')
                    ->first();

                $timedOut = $attendance ? null : $attendances
                    ->filter(fn($row) => $row->out_timestamp)
                    ->sortByDesc('out_timestamp')
                    ->first();

                return [
                    'employee_id' => (int) $employeeId,
                    'name' => $employee?->full_name ?? 'Staff',
                    'role' => $rows->first()->employee?->role_name,
                    'avatar' => $employee?->avatar,
                    'note' => $rows->first()->note,
                    'is_me' => (int) $employeeId === $scannerId,
                    'attended' => $attendance !== null,
                    'attended_at' => $attendance?->in_timestamp?->toISOString(),
                    'attendance_note' => $attendance?->notes,
                    'timed_out_at' => $timedOut?->out_timestamp?->toISOString(),
                ];
            })
            ->sortByDesc('is_me')
            ->values();

        $first = $this->visitAttendance($services)->sortBy('in_timestamp')->first();

        $start = $schedule->scheduled_at ? Carbon::parse($schedule->scheduled_at) : null;

        if ($first?->in_timestamp && (!$start || Carbon::parse($first->in_timestamp)->lt($start))) {
            $start = Carbon::parse($first->in_timestamp);
        }

        $endsAt = $start?->copy()->addMinutes($this->scheduleRepository->calculateScheduleDurationMinutes($schedule));

        return [
            'schedule_id' => $schedule->schedule_id,
            'schedule_code' => $schedule->schedule_code,
            'status' => $schedule->status,
            'scheduled_at' => $schedule->scheduled_at?->toISOString(),
            'patient' => [
                'name' => $schedule->patient?->display_name,
                'avatar' => $schedule->patient?->avatar_url,
            ],
            'services' => $services
                ->map(fn($scheduleService) => $scheduleService->service?->service_name ?? 'Medical service')
                ->values(),
            'checked_in_at' => $first?->in_timestamp?->toISOString(),
            'ends_at' => $endsAt?->toISOString(),
            'unpaid' => $this->scheduleRepository->hasUnpaidBalance($schedule),
            'staff' => $staff,
        ];
    }

    private function joinNames(array $names): string
    {
        $names = array_values($names);

        if (count($names) <= 1) {
            return $names[0] ?? '';
        }

        return implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names);
    }

    private function notifyFamilyOfVisit(Schedule $schedule, string $change, ?User $actor): void
    {
        try {
            $patient = $schedule->patient;

            if (!$patient) {
                return;
            }

            $this->notificationService->notifyPatientAccess(
                $patient,
                "{$patient->display_name}'s medical visit {$schedule->schedule_code}{$change}",
                'Schedule',
                $actor,
            );
        } catch (\Throwable $e) {
            Log::error('Medical visit family notice failed', [
                'schedule_id' => $schedule->schedule_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function generateQr(array $payload): array
    {
        $scheduleServicesId = (int) $payload['schedule_services_id'];
        $byEmployee = !empty($payload['employee_id']);
        $scheduleService = ScheduleService::with('schedule')->find($scheduleServicesId);

        if ($scheduleService?->type === ScheduleService::TYPE_MEDICAL) {
            return [
                'type' => $payload['type'],
                'token' => $this->generateMedicalVisitToken($scheduleService, $payload['type'], $byEmployee),
            ];
        }

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

    private function generateMedicalVisitToken(ScheduleService $scheduleService, string $type, bool $byEmployee): string
    {
        $schedule = $scheduleService->schedule;

        if (!in_array($type, ['in', 'out'], true)) {
            throw new Exception('Invalid QR type.', 422);
        }

        if ($byEmployee) {
            throw new Exception('Only the family can generate the time-in and time-out QR for this visit.', 403);
        }

        if (!$schedule || $schedule->isOnsite()) {
            throw new Exception("Facility schedules don't need attendance.", 422);
        }

        if ($schedule->patient?->currentAdmission()->where('status', 'admitted')->exists()) {
            throw new Exception("Admitted patients don't use QR time-in or time-out.", 422);
        }

        if (!in_array(strtolower((string) $schedule->status), [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING], true)) {
            throw new Exception('QR codes are not available for this visit.', 422);
        }

        $this->guardHasActiveAssignment($scheduleService->schedule_services_id);

        $started = $this->medicalVisitStarted($schedule);

        if ($type === 'out' && !$started) {
            throw new Exception("The care team hasn't timed in yet.", 409);
        }

        $token = Str::random(64);

        Cache::put(
            $this->qrCacheKey($type, $token),
            ['medical_schedule_id' => $schedule->schedule_id],
            now()->addMinutes(self::QR_TTL_MINUTES)
        );

        return $token;
    }

    private function medicalVisitStarted(Schedule $schedule): bool
    {
        return Attendance::whereHas(
            'assigned.scheduleService',
            fn($query) => $query->where('schedule_id', $schedule->schedule_id)
        )
            ->whereNotNull('in_timestamp')
            ->exists();
    }

    private function verifyMedicalVisitToken(string $type, array $data, string $cacheKey, array $payload): array
    {
        $schedule = Schedule::with('patient')->find($data['medical_schedule_id']);

        if (!$schedule?->patient) {
            throw new Exception('This QR code is invalid or has expired.', 410);
        }

        $visit = $this->lookupPatientVisit([
            'patient_uuid' => $schedule->patient->uuid,
            'employee_id' => (int) $payload['employee_id'],
        ]);

        if ((int) $visit['schedule_id'] !== (int) $schedule->schedule_id) {
            throw new Exception('This QR code is for a different visit.', 409);
        }

        Cache::forget($cacheKey);

        broadcast(new QrScanned($payload['token'], $type));

        return [
            'patient_uuid' => $schedule->patient->uuid,
            'medical_visit' => $visit,
        ];
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
            ['attendance_id' => $session->attendance_id],
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
            ['attendance_id' => $session->attendance_id],
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
            $session = Attendance::with('assigned')
                ->where('attendance_id', $data['attendance_id'])
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
                'type_out' => Attendance::TYPE_HANDOVER,
            ]);

            $handover = Attendance::create([
                'schedule_assigned_id' => $next->schedule_assigned_id,
                'type_in' => Attendance::TYPE_HANDOVER,
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

        if (isset($data['medical_schedule_id'])) {
            return $this->verifyMedicalVisitToken('in', $data, $cacheKey, $payload);
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
            $activeSession = Attendance::whereHas(
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

            $session = Attendance::create([
                'schedule_assigned_id' => $assigned->schedule_assigned_id,
                'type_in' => Attendance::TYPE_SCANNED,
                'in_timestamp' => now(),
            ]);

            $this->startAdlSchedule($assigned->schedule_services_id);

            DB::afterCommit(fn() => $this->notifyFamilyOfAdlScan($assigned->schedule_services_id, (int) $assigned->employee_id, 'timed in'));

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

        if (isset($data['medical_schedule_id'])) {
            return $this->verifyMedicalVisitToken('out', $data, $cacheKey, $payload);
        }

        return DB::transaction(function () use ($payload, $data, $cacheKey) {
            $session = Attendance::where('attendance_id', $data['attendance_id'])
                ->lockForUpdate()
                ->first();

            if (!$session || $session->out_timestamp) {
                throw new Exception('This QR code is invalid or has expired.', 410);
            }

            $this->guardClockOutEmployee($session->assigned, $payload['employee_id']);

            $dueSchedule = $this->adlScheduleDueToComplete($session->assigned->schedule_services_id);

            if ($dueSchedule) {
                $this->guardUnpaidCompletion($dueSchedule, $payload);
            }

            Cache::forget($cacheKey);

            $session->update([
                'out_timestamp' => now(),
                'type_out' => Attendance::TYPE_SCANNED,
            ]);

            $this->completeAdlScheduleIfDue($session->assigned->schedule_services_id);

            $scheduleServicesId = $session->assigned->schedule_services_id;
            $employeeId = (int) $session->assigned->employee_id;
            DB::afterCommit(fn() => $this->notifyFamilyOfAdlScan(
                $scheduleServicesId,
                $employeeId,
                $dueSchedule ? 'timed out. The visit is now complete' : 'timed out'
            ));

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
            'present' => $this->presentStaff($scheduleServicesId),
        ];
    }

    private function presentStaff(int $scheduleServicesId): array
    {
        $scheduleService = ScheduleService::find($scheduleServicesId);

        $serviceIds = $scheduleService?->type === ScheduleService::TYPE_MEDICAL
            ? ScheduleService::where('schedule_id', $scheduleService->schedule_id)
                ->where('type', ScheduleService::TYPE_MEDICAL)
                ->pluck('schedule_services_id')
            : collect([$scheduleServicesId]);

        return Attendance::with('assigned.employee')
            ->whereHas('assigned', fn($query) => $query->whereIn('schedule_services_id', $serviceIds))
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->orderBy('in_timestamp')
            ->get()
            ->unique(fn(Attendance $row) => $row->assigned?->employee_id)
            ->filter(fn(Attendance $row) => $row->assigned?->employee)
            ->map(fn(Attendance $row) => [
                'employee_id' => $row->assigned->employee_id,
                'name' => $row->assigned->employee->full_name,
                'in_timestamp' => $row->in_timestamp,
            ])
            ->values()
            ->all();
    }

    private function activeSessionFor(int $scheduleServicesId): ?Attendance
    {
        return Attendance::whereHas(
            'assigned',
            fn($query) => $query->where('schedule_services_id', $scheduleServicesId)
        )
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->latest('attendance_id')
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

    private function notifyFamilyOfAdlScan(int $scheduleServicesId, int $employeeId, string $change): void
    {
        try {
            $schedule = ScheduleService::with('schedule.patient')->find($scheduleServicesId)?->schedule;
            $patient = $schedule?->patient;

            if (!$patient) {
                return;
            }

            $employee = Employee::with('users')->find($employeeId);

            $this->notificationService->notifyPatientAccess(
                $patient,
                "{$patient->display_name}'s daily care (ADL) visit {$schedule->schedule_code}: "
                    . ($employee?->full_name ?? 'The caregiver') . " {$change}.",
                'Schedule',
                $employee?->users,
            );
        } catch (\Throwable $e) {
            Log::error('ADL scan family notice failed', [
                'schedule_services_id' => $scheduleServicesId,
                'error' => $e->getMessage(),
            ]);
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

    private function guardUnpaidCompletion(Schedule $schedule, array $payload, string $message = self::UNPAID_COMPLETION_MESSAGE): void
    {
        if (!empty($payload['confirm_unpaid']) || !$this->scheduleRepository->hasUnpaidBalance($schedule)) {
            return;
        }

        throw new Exception($message, 428);
    }

    private function completeAdlScheduleIfDue(int $scheduleServicesId): void
    {
        $this->adlScheduleDueToComplete($scheduleServicesId)
            ?->update(['status' => Schedule::STATUS_COMPLETED]);
    }

    private function adlScheduleDueToComplete(int $scheduleServicesId): ?Schedule
    {
        $scheduleService = ScheduleService::with('schedule.scheduleServices.service')->find($scheduleServicesId);
        $schedule = $scheduleService?->schedule;

        if ($scheduleService?->type !== ScheduleService::TYPE_ADL || !$schedule?->scheduled_at) {
            return null;
        }

        $scheduledAt = Carbon::parse($schedule->scheduled_at);

        $firstScan = Attendance::whereHas(
            'assigned',
            fn($query) => $query->where('schedule_services_id', $scheduleServicesId)
        )->min('in_timestamp');

        $start = $firstScan && Carbon::parse($firstScan)->lt($scheduledAt)
            ? Carbon::parse($firstScan)
            : $scheduledAt;

        $end = $start->copy()
            ->addMinutes($this->scheduleRepository->calculateScheduleDurationMinutes($schedule));

        return now()->diffInSeconds($end, false) < 60 ? $schedule : null;
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
