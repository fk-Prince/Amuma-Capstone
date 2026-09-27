<?php

namespace App\Console\Commands;

use App\Service\SubscriptionReminderService;
use Illuminate\Console\Command;

class RemindSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:remind-renewals';

    public function handle(SubscriptionReminderService $reminders): int
    {
        $this->info("Sent {$reminders->sendDailyReminders()} renewal reminders.");

        return self::SUCCESS;
    }
}
