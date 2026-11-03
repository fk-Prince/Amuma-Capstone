<?php

namespace App\Repository;

use App\Models\Attendance;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use App\Models\ScheduleService;
use Illuminate\Support\Collection;

class AttendanceRepository
{
    public function forceClockOutSchedule(int $scheduleId): int
    {
        return Attendance::whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->whereHas(
                'assigned.scheduleService',
                fn($service) => $service->where('schedule_id', $scheduleId)
            )
            ->update([
                'out_timestamp' => now(),
                'type_out' => Attendance::TYPE_FORCE,
            ]);
    }

    public function findPatientByUuid(string $uuid): ?Patient
    {
        return Patient::where('uuid', $uuid)->first();
    }

    public function todaysMedicalSchedules(int $patientId): Collection
    {
        return Schedule::with([
            'scheduleServices' => fn($query) => $query->where('type', ScheduleService::TYPE_MEDICAL),
            'scheduleServices.service',
            'scheduleServices.assigned' => fn($query) => $query->where('is_active', true),
            'scheduleServices.assigned.attendances',
            'scheduleServices.assigned.employee.employees',
        ])
            ->where('patient_id', $patientId)
            ->whereIn('status', [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING])
            ->whereDate('scheduled_at', today())
            ->whereHas('scheduleServices', fn($query) => $query->where('type', ScheduleService::TYPE_MEDICAL))
            ->orderBy('scheduled_at')
            ->get();
    }

    public function medicalVisit(int $scheduleId): ?Schedule
    {
        return Schedule::with([
            'patient',
            'scheduleServices' => fn($query) => $query->where('type', ScheduleService::TYPE_MEDICAL),
            'scheduleServices.service',
            'scheduleServices.assigned' => fn($query) => $query->where('is_active', true),
            'scheduleServices.assigned.attendances',
        ])->find($scheduleId);
    }

    public function recordPresent(ScheduleAssigned $assigned, $inAt, $outAt, ?string $notes): Attendance
    {
        return Attendance::create([
            'schedule_assigned_id' => $assigned->schedule_assigned_id,
            'type_in' => Attendance::TYPE_MARKED,
            'type_out' => Attendance::TYPE_FORCE,
            'in_timestamp' => $inAt,
            'out_timestamp' => $outAt,
            'notes' => $notes,
        ]);
    }

    public function clearAttendance(ScheduleAssigned $assigned): int
    {
        return Attendance::where('schedule_assigned_id', $assigned->schedule_assigned_id)->delete();
    }

    public function markPresent(ScheduleAssigned $assigned, string $type, ?string $notes): Attendance
    {
        return Attendance::create([
            'schedule_assigned_id' => $assigned->schedule_assigned_id,
            'type_in' => $type,
            'in_timestamp' => now(),
            'notes' => $notes,
        ]);
    }

    public function closeOpen(array $scheduleAssignedIds): int
    {
        return Attendance::whereIn('schedule_assigned_id', $scheduleAssignedIds)
            ->whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->update([
                'out_timestamp' => now(),
                'type_out' => Attendance::TYPE_SCANNED,
            ]);
    }

    public function removeMarked(ScheduleAssigned $assigned): int
    {
        return Attendance::where('schedule_assigned_id', $assigned->schedule_assigned_id)
            ->where('type_in', Attendance::TYPE_MARKED)
            ->whereNull('out_timestamp')
            ->delete();
    }
}
