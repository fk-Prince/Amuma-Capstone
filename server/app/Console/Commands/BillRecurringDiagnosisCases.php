<?php

namespace App\Console\Commands;

use App\Service\DiagnosisFeeService;
use Illuminate\Console\Command;

class BillRecurringDiagnosisCases extends Command
{
    protected $signature = 'diagnosis-cases:bill-recurring';

    protected $description = 'Bill the diagnosis cases set to be billed monthly once they are due';

    public function handle(DiagnosisFeeService $fees): int
    {
        $this->info("Billed {$fees->billDue()} diagnosis case(s).");

        return self::SUCCESS;
    }
}
