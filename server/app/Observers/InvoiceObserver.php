<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Schedule;

class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if (
            !$invoice->wasChanged('status')
            || $invoice->status !== Invoice::STATUS_VOID
        ) {
            return;
        }

        $this->cancelSchedules($invoice);
    }

    private function cancelSchedules(Invoice $invoice): void
    {
        $invoice->loadMissing('invoiceServices.scheduleService.schedule');

        $invoice->invoiceServices
            ->map(fn($line) => $line->scheduleService?->schedule)
            ->filter()
            ->unique('schedule_id')
            ->reject(fn(Schedule $schedule) => $schedule->status === Schedule::STATUS_CANCELLED)
            ->each(fn(Schedule $schedule) => $schedule->update([
                'status' => Schedule::STATUS_CANCELLED,
            ]));
    }
}
