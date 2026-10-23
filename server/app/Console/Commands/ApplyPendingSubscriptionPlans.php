<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ApplyPendingSubscriptionPlans extends Command
{
    protected $signature = 'subscriptions:apply-pending-plans';

    protected $description = 'Start paid pending plans that are due, including taking test subscriptions live when their free trial ends';

    public function handle(): int
    {
        $due = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED])
            ->whereNotNull('pending_plan_id')
            ->whereDate('pending_plan_starts_at', '<=', Carbon::now())
            ->where(fn($query) => $query
                ->where('mode', Subscription::MODE_LIVE)
                ->orWhereHas('payments', fn($payments) => $payments->where('status', SubscriptionPayment::STATUS_PAID)))
            ->get();

        foreach ($due as $subscription) {
            $subscription->update([
                ...$subscription->pendingPlanChanges(),
                'status' => Subscription::STATUS_ACTIVE,
            ]);
        }
        $this->info("Applied {$due->count()} pending plan(s).");

        return self::SUCCESS;
    }
}
