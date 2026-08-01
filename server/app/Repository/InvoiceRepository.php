<?php

namespace App\Repository;

use App\Models\Invoice;

class InvoiceRepository
{
    public function create(array $payload)
    {
        return Invoice::create($payload);
    }

    public function getInvoiceBalanceForSchedule(string $scheduleCode)
    {
        $invoice = Invoice::query()
            ->whereHas('invoiceServices.scheduleService.schedule.patient', function ($query) use ($scheduleCode) {
                $query->where('schedule_code', $scheduleCode);
            })
            ->with('payments')
            ->first();

        if (! $invoice) {
            return null;
        }

        return $invoice;
        // return [
        //     'invoice_id' => $invoice->invoice_id,
        //     'invoice_code' => $invoice->invoice_code,
        //     'invoice_total' => (float) $invoice->total,
        //     'amount_paid' => $invoice->amount_paid,
        //     'balance_due' => $invoice->balance_due,
        //     'status' => $invoice->status,
        //     'is_collected' => $invoice->is_collected,
        // ];
    }
}
