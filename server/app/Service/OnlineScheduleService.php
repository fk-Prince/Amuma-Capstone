<?php

namespace App\Service;

use App\Events\QrScanned;
use App\Models\OnlineSchedule;
use App\Models\ScheduleAssigned;
use App\Repository\OnlineScheduleRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnlineScheduleService
{
    public function __construct(
        private OnlineScheduleRepository $onlineScheduleRepository,
    ) {}

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
        $assigned = ScheduleAssigned::where('employee_id', $payload['employee_id'])
            ->where('schedule_services_id', $payload['schedule_services_id'])
            ->where('is_active', true)
            ->first();

        if (!$assigned) {
            throw new Exception('You are not assigned to this schedule.', 404);
        }

        $session = OnlineSchedule::where('schedule_assigned_id', $assigned->schedule_assigned_id)
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->latest('online_schedule_id')
            ->first();

        if (!$session) {
            throw new Exception('Employee has not clocked in yet.', 409);
        }

        if ($session->qr_out_token) {
            return $session->qr_out_token;
        }

        $token = Str::random(64);

        $session->update([
            'qr_out_token' => $token,
        ]);
        return $token;
    }


    public function generateClockInToken(array $payload)
    {
        $assigned = ScheduleAssigned::where('employee_id', $payload['employee_id'])
            ->where('schedule_services_id', $payload['schedule_services_id'])
            ->where('is_active', true)
            ->first();

        if (!$assigned) {
            throw new Exception('You are not assigned to this schedule.', 404);
        }
        $session = OnlineSchedule::where('schedule_assigned_id', $assigned->schedule_assigned_id)
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->latest('online_schedule_id')
            ->first();

        if ($session) {
            throw new Exception('You are already clocked in for this schedule.', 409);
        }

        $session = OnlineSchedule::where('schedule_assigned_id', $assigned->schedule_assigned_id)
            ->whereNotNull('qr_in_token')
            ->whereNull('in_timestamp')
            ->latest('online_schedule_id')
            ->first();

        if ($session) {
            return $session->qr_in_token;
        }

        $token = Str::random(64);

        OnlineSchedule::create([
            'schedule_assigned_id' => $assigned->schedule_assigned_id,
            'qr_in_token' => $token,
        ]);
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
        return DB::transaction(function () use ($payload) {
            $session = OnlineSchedule::where('qr_in_token', $payload['token'])
                ->lockForUpdate()
                ->first();

            if (!$session) {
                throw new Exception('Invalid QR code.', 404);
            }

            if ($session->in_timestamp) {
                throw new Exception('This QR code has already been used.', 409);
            }

            $session->update([
                'in_timestamp' => now(),
            ]);

            broadcast(new QrScanned($payload['token'], 'in'));

            return $session;
        });
    }

    private function verifyClockOutToken(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $session = OnlineSchedule::where('qr_out_token', $payload['token'])
                ->lockForUpdate()
                ->first();

            if (!$session) {
                throw new Exception('Invalid QR code.', 404);
            }

            if (!$session->in_timestamp) {
                throw new Exception('This session was never clocked in.', 409);
            }

            if ($session->out_timestamp) {
                throw new Exception('This QR code has already been used.', 409);
            }

            $session->update([
                'out_timestamp' => now(),
            ]);

            broadcast(new QrScanned($payload['token'], 'out'));

            return $session;
        });
    }
}
