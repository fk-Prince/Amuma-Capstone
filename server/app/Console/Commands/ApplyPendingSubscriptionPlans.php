<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ApplyPendingSubscriptionPlans extends Command
{
    protected $signature = 'subscriptions:apply-pending-plans';

    public function handle(): int
    {
        $due = Subscription::query()
            ->whereNotNull('pending_plan_id')
            ->whereDate('pending_plan_starts_at', '<=', Carbon::now())
            ->get();
        foreach ($due as $subscription) {
            $subscription->update([
                'plan_id' => $subscription->pending_plan_id,
                'pending_plan_id' => null,
                'pending_plan_starts_at' => null,
            ]);
        }
        return self::SUCCESS;
    }
}
