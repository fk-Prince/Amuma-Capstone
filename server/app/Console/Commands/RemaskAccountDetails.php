<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Refund;
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
            $dryRun,
            'payment'
        );

        $refunds = $this->remask(
            Refund::query(),
            'refund_method',
            $dryRun,
            'refund'
        );

        $total = $payments + $refunds;

        if ($total === 0) {
            $this->info('Nothing to re-mask.');

            return self::SUCCESS;
        }

        $this->info(
            $dryRun
                ? "{$total} row(s) would be re-masked. Re-run without --dry-run to apply."
                : "Re-masked {$total} row(s): {$payments} payment(s), {$refunds} refund(s)."
        );

        return self::SUCCESS;
    }

    private function remask(Builder $query, string $methodColumn, bool $dryRun, string $label): int
    {
        $changed = 0;

        $query->whereNotNull('masked_card_number')
            ->orderBy($this->keyFor($label))
            ->chunkById(200, function ($rows) use ($methodColumn, $dryRun, $label, &$changed) {
                foreach ($rows as $row) {
                    $method = (string) ($row->{$methodColumn} ?? '');
                    $current = (string) $row->masked_card_number;

                    // Xendit already redacts card numbers as 400000XXXXXX2503.
                    // Re-masking those would drop the BIN for no privacy gain.
                    if (preg_match('/[*x]{4,}/i', $current)) {
                        continue;
                    }

                    $masked = MaskUtil::accountDetails($method, $current);

                    if ($masked === $current) {
                        continue;
                    }

                    $this->line("  {$label} #{$row->getKey()}  {$method}: {$current} -> {$masked}");

                    if (!$dryRun) {
                        $row->forceFill(['masked_card_number' => $masked])->save();
                    }

                    $changed++;
                }
            }, $this->keyFor($label));

        return $changed;
    }

    private function keyFor(string $label): string
    {
        return $label === 'payment' ? 'payment_id' : 'refund_id';
    }
}
