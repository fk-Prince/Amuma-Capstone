<?php

namespace App\Repository;

use App\Models\AdlAttendance;

class AdlAttendanceRepository
{
    public function forceClockOutSchedule(int $scheduleId): int
    {
        return AdlAttendance::whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->whereHas(
                'assigned.scheduleService',
                fn($service) => $service->where('schedule_id', $scheduleId)
            )
            ->update([
                'out_timestamp' => now(),
                'type_out' => AdlAttendance::TYPE_FORCE,
            ]);
    }
}
