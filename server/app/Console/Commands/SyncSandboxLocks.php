<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Service\Sandbox\SandboxClient;
use Illuminate\Console\Command;

class SyncSandboxLocks extends Command
{
    protected $signature = 'sandbox:sync-locks';

    protected $description = 'Tell the demo server which free trials have ended unpaid, so their demo copy is locked until they subscribe';

    public function handle(SandboxClient $sandboxClient): int
    {
        if (!$sandboxClient->enabled()) {
            return self::SUCCESS;
        }

        Subscription::query()
            ->where('mode', Subscription::MODE_TEST)
            ->whereIn('status', [
                Subscription::STATUS_PENDING,
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_EXPIRED,
            ])
            ->each(fn(Subscription $subscription) => $sandboxClient->syncLock($subscription));

        return self::SUCCESS;
    }
}
