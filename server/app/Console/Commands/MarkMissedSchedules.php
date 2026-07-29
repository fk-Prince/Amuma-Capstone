<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkMissedSchedules extends Command
{
    protected $signature = 'schedules:mark-missed';

    protected $description = 'Mark schedules as Missed if their scheduled_at date has passed and they were never completed or cancelled';


    protected array $excludedStatuses = ['Completed', 'Ongoing', 'Cancelled', 'Missed'];
    public function handle(): int
    {
        $now = Carbon::now();
        $count = Schedule::where('scheduled_at', '<', $now)
            ->whereNotIn('status', $this->excludedStatuses)
            ->update(['status' => 'Missed']);
        $this->info("Marked {$count} schedule(s) as Missed.");
        return self::SUCCESS;
    }
}
