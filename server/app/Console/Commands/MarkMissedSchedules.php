<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkMissedSchedules extends Command
{
    protected $signature = 'schedules:mark-missed';

    protected $description = 'Mark schedules as Missed once their start time is more than the late grace period past and nobody started them';


    protected array $excludedStatuses = [
        Schedule::STATUS_COMPLETED,
        Schedule::STATUS_ONGOING,
        Schedule::STATUS_CANCELLED,
        Schedule::STATUS_MISSED
    ];

    public function handle(): int
    {
        $cutoff = Carbon::now()->subMinutes(Schedule::LATE_GRACE_MINUTES);
        $count = Schedule::where('scheduled_at', '<', $cutoff)
            ->whereNotIn('status', $this->excludedStatuses)
            ->update(['status' => 'missed']);
        $this->info("Marked {$count} schedule(s) as Missed.");
        return self::SUCCESS;
    }
}
