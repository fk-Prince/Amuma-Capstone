<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Transaction;
use App\Utils\MaskUtil;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RemaskAccountDetails extends Command
{
    protected $signature = 'payments:remask {--dry-run : Show what would change without writing}';

    protected $description = 'Re-mask account details stored before MaskUtil stopped treating GCash as a cash method';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $payments = $this->remask(
            Payment::query(),
            'payment_method',
            'masked_account_detail',
            'payment_id',
            $dryRun,
            'payment'
        );

        $withdrawals = $this->remask(
            Transaction::query()->where('type', Transaction::TYPE_WITHDRAW),
            'method',
            'masked_account_number',
            'transaction_id',
            $dryRun,
            'withdrawal'
        );

        $total = $payments + $withdrawals;

        if ($total === 0) {
            $this->info('Nothing to re-mask.');

            return self::SUCCESS;
        }

        $this->info(
            $dryRun
                ? "{$total} row(s) would be re-masked. Re-run without --dry-run to apply."
                : "Re-masked {$total} row(s): {$payments} payment(s), {$withdrawals} withdrawal(s)."
        );

        return self::SUCCESS;
    }

    private function remask(
        Builder $query,
        string $methodColumn,
        string $maskedColumn,
        string $key,
        bool $dryRun,
        string $label
    ): int {
        $changed = 0;

        $query->whereNotNull($maskedColumn)
            ->orderBy($key)
            ->chunkById(200, function ($rows) use ($methodColumn, $maskedColumn, $dryRun, $label, &$changed) {
                foreach ($rows as $row) {
                    $method = (string) ($row->{$methodColumn} ?? '');
                    $current = (string) $row->{$maskedColumn};

                    if (preg_match('/[*x]{4,}/i', $current)) {
                        continue;
                    }

                    $masked = MaskUtil::accountDetails($method, $current);

                    if ($masked === $current) {
                        continue;
                    }

                    $this->line("  {$label} #{$row->getKey()}  {$method}: {$current} -> {$masked}");

                    if (!$dryRun) {
                        $row->forceFill([$maskedColumn => $masked])->save();
                    }

                    $changed++;
                }
            }, $key);

        return $changed;
    }
}
