<?php

namespace App\Console\Commands;

use App\Service\AdmissionDischargeReminderService;
use Illuminate\Console\Command;

class RemindAdmissionDischarges extends Command
{
    protected $signature = 'admissions:remind-discharge {--days=3 : Days before the stay ends}';

    protected $description = "Notify a patient's family when their admitted stay is a few days from ending";

    public function handle(AdmissionDischargeReminderService $reminders): int
    {
        $this->info("Sent {$reminders->sendReminders((int)$this->option('days'))} discharge reminder(s).");

        return self::SUCCESS;
    }
}
