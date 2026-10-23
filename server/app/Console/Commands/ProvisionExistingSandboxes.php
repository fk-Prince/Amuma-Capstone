<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Service\Sandbox\SandboxClient;
use Illuminate\Console\Command;

class ProvisionExistingSandboxes extends Command
{
    protected $signature = 'sandbox:provision-existing {--branch= : Only this branch UUID} {--pause=3 : Seconds to wait between branches}';

    protected $description = 'Send existing branches to the sandbox server so each gets its own sandbox copy (branches already there are skipped by the sandbox)';

    public function handle(SandboxClient $sandboxClient): int
    {
        if (!$sandboxClient->enabled()) {
            $this->error('Set SANDBOX_API_URL and SANDBOX_SECRET on this (live) server first.');

            return self::FAILURE;
        }

        $branches = Branch::query()
            ->whereHas('subscriptionLink', fn($link) => $link->where('status', '!=', BranchSubscription::STATUS_REJECTED))
            ->when($this->option('branch'), fn($query, $uuid) => $query->where('uuid', $uuid))
            ->orderBy('branch_id')
            ->get(['branch_id', 'name']);

        if ($branches->isEmpty()) {
            $this->info('No branches to send.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($branches as $index => $branch) {
            if ($index > 0) {
                sleep(max(0, (int) $this->option('pause')));
            }

            $sent = $sandboxClient->send($branch->branch_id);
            $failed += $sent ? 0 : 1;

            $this->line(($sent ? '<info>sent</info>   ' : '<error>failed</error> ') . $branch->name);

            if (!$sent && $sandboxClient->lastError) {
                $this->line('       ' . $sandboxClient->lastError);
            }
        }

        $this->info("Done: " . ($branches->count() - $failed) . " sent, {$failed} failed.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
