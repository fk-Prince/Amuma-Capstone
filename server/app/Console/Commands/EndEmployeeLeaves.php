<?php

namespace App\Console\Commands;

use App\Service\EmployeeService;
use Illuminate\Console\Command;

class EndEmployeeLeaves extends Command
{
    protected $signature = 'employees:end-leave';

    protected $description = 'Return employees to Active once their leave reaches its back-on date';

    public function handle(EmployeeService $employeeService): int
    {
        $count = $employeeService->endDueLeaves();
        $this->info("Ended {$count} leave(s).");

        return self::SUCCESS;
    }
}
