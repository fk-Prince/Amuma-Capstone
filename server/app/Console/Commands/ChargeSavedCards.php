<?php

namespace App\Console\Commands;

use App\Service\SavedCardChargeService;
use Illuminate\Console\Command;

class ChargeSavedCards extends Command
{
    protected $signature = 'subscriptions:charge-saved-cards';

    protected $description = 'Charge the saved card of every free trial that ended unpaid and every auto-renew subscription that ends today';

    public function handle(SavedCardChargeService $service): int
    {
        $charged = $service->chargeDue();

        $this->info("Charged {$charged} saved card(s).");

        return self::SUCCESS;
    }
}
