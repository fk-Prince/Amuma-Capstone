<?php

namespace App\Service;

use App\Events\QrScanned;
use App\Models\OnlineSchedule;
use App\Models\ScheduleAssigned;
use App\Repository\OnlineScheduleRepository;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnlineScheduleService
{

    public const QR_TTL_MINUTES = 2;

    public function __construct() {}

    public function generateQr(array $payload)
    {
        return match ($payload['type']) {
            'in' => $this->generateClockInToken($payload),
            'out' => $this->generateClockOutToken($payload),
            default => throw new Exception('Invalid QR type.', 422),
        };
    }

    public function generateClockOutToken(array $payload)
    {
        $assigned = $this->resolveAssignment($payload);

        $session = OnlineSchedule::where('schedule_assigned_id', $assigned->schedule_assigned_id)
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->latest('online_schedule_id')
            ->first();

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
            ['online_schedule_id' => $session->online_schedule_id],
            now()->addMinutes(self::QR_TTL_MINUTES)
        );

        return $token;
    }


    public function generateClockInToken(array $payload)
    {
        $assigned = $this->resolveAssignment($payload);

        $activeSession = $this->activeSessionFor($assigned->schedule_services_id);

        if ($activeSession) {
            throw new Exception(
                $activeSession->schedule_assigned_id === $assigned->schedule_assigned_id
                    ? 'There is already a caregiver clocked in for this visit. Use Generate QR Out to clock them out.'
                    : 'Another caregiver is already clocked in for this visit.',
                409
            );
        }

        $token = Str::random(64);

        Cache::put(
            $this->qrCacheKey('in', $token),
            ['schedule_assigned_id' => $assigned->schedule_assigned_id],
            now()->addMinutes(self::QR_TTL_MINUTES)
        );

        return $token;
    }

    public function verifyQr(array $payload)
    {
        return match ($payload['type']) {
            'in' => $this->verifyClockInToken($payload),
            'out' => $this->verifyClockOutToken($payload),
            default => throw new Exception('Invalid QR type.', 422),
        };
    }

    private function verifyClockInToken(array $payload)
    {
        $data = Cache::pull($this->qrCacheKey('in', $payload['token']));

        if (!$data) {
            throw new Exception('This QR code is invalid or has expired.', 410);
        }

        $assigned = ScheduleAssigned::find($data['schedule_assigned_id']);

        $this->guardScanningEmployee($assigned, $payload['employee_id']);

        return DB::transaction(function () use ($payload, $assigned) {
            $activeSession = OnlineSchedule::whereHas(
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

            $session = OnlineSchedule::create([
                'schedule_assigned_id' => $assigned->schedule_assigned_id,
                'in_timestamp' => now(),
            ]);

            broadcast(new QrScanned($payload['token'], 'in'));

            return $session;
        });
    }

    private function verifyClockOutToken(array $payload)
    {
        $data = Cache::pull($this->qrCacheKey('out', $payload['token']));

        if (!$data) {
            throw new Exception('This QR code is invalid or has expired.', 410);
        }

        return DB::transaction(function () use ($payload, $data) {
            $session = OnlineSchedule::where('online_schedule_id', $data['online_schedule_id'])
                ->lockForUpdate()
                ->first();

            if (!$session || $session->out_timestamp) {
                throw new Exception('This QR code is invalid or has expired.', 410);
            }

            $this->guardScanningEmployee($session->assigned, $payload['employee_id']);

            $session->update([
                'out_timestamp' => now(),
            ]);

            broadcast(new QrScanned($payload['token'], 'out'));

            return $session;
        });
    }


    private function activeSessionFor(int $scheduleServicesId): ?OnlineSchedule
    {
        return OnlineSchedule::whereHas(
            'assigned',
            fn($query) => $query->where('schedule_services_id', $scheduleServicesId)
        )
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->latest('online_schedule_id')
            ->first();
    }

    private function guardScanningEmployee(?ScheduleAssigned $assigned, mixed $employeeId): void
    {
        if (!$assigned || (int) $assigned->employee_id !== (int) $employeeId) {
            throw new Exception('You are not assigned to this schedule.', 403);
        }
    }

    private function qrCacheKey(string $type, string $token): string
    {
        return "qr:{$type}:{$token}";
    }


    private function resolveAssignment(array $payload): ScheduleAssigned
    {
        $query = ScheduleAssigned::where('schedule_services_id', $payload['schedule_services_id'])
            ->where('is_active', true);

        if (!empty($payload['employee_id'])) {
            $query->where('employee_id', $payload['employee_id']);
        }

        $assigned = $query->first();

        if (!$assigned) {
            throw new Exception('No active caregiver is assigned to this schedule.', 404);
        }

        return $assigned;
    }
}
