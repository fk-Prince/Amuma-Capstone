<?php

namespace App\Repository;

use App\Models\OnlineSchedule;

class OnlineScheduleRepository
{
    public function forceClockOutSchedule(int $scheduleId): int
    {
        return OnlineSchedule::whereNotNull('in_timestamp')
            ->whereNull('out_timestamp')
            ->whereHas(
                'assigned.scheduleService',
                fn($service) => $service->where('schedule_id', $scheduleId)
            )
            ->update([
                'out_timestamp' => now(),
                'type_out' => OnlineSchedule::TYPE_FORCE,
            ]);
    }
}
