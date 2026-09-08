<?php

namespace App\Utils;

use App\Models\Invoice;
use App\Models\Refund;


class InvoiceMoney
{
    public static function paid(Invoice $invoice)
    {
        return round((float) $invoice->allocations->sum('amount'), 2);
    }

    // Summed from the refund allocations, not the refunds: a refund's own
    // amount is its total across every allocation it drew from, so adding
    // those up would count the parts that came off other invoices.
    public static function refunded(Invoice $invoice)
    {
        return round(
            (float) $invoice->allocations
                ->flatMap(fn($allocation) => $allocation->refundAllocations)
                ->filter(
                    fn($line) => in_array(
                        $line->refund?->status,
                        Refund::SETTLED_STATUSES,
                        true
                    )
                )
                ->sum('amount'),
            2
        );
    }

    public static function netPaid(Invoice $invoice)
    {
        return round(max(0, self::paid($invoice) - self::refunded($invoice)), 2);
    }

    public static function refundable(Invoice $invoice)
    {
        $invoice->loadMissing('allocations.refundAllocations.refund', 'invoiceAdjustments');

        return round(
            max(0, self::netPaid($invoice) - (float) $invoice->adjusted_total),
            2
        );
    }

    public static function adjustments(Invoice $invoice)
    {
        return round((float) $invoice->invoiceAdjustments->sum('amount'), 2);
    }
}
