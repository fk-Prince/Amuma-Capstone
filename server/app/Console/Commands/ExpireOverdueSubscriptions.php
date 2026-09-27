<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireOverdueSubscriptions extends Command
{
    protected $signature = 'subscriptions:mark-expired';

    protected $description = 'Mark active subscriptions as Expired once their end_date has passed';

    public function handle(): int
    {
        $count = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('end_date', '<', Carbon::today())
            ->update(['status' => Subscription::STATUS_EXPIRED]);

        $this->info("Marked {$count} subscription(s) as Expired.");

        return self::SUCCESS;
    }
}
