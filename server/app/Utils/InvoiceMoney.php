<?php

namespace App\Utils;

use App\Models\Invoice;
use App\Models\Refund;
use App\Models\RefundAllocation;
use App\Models\Transaction;

class InvoiceMoney
{
    public static function paid(Invoice $invoice)
    {
        return round((float) $invoice->allocations->sum('amount'), 2);
    }

    public static function refunded(Invoice $invoice)
    {
        return round((float) self::lines($invoice)->sum('amount'), 2);
    }

    public static function netPaid(Invoice $invoice)
    {
        return round(max(0, self::paid($invoice) - self::refunded($invoice)), 2);
    }

    public static function refundable(Invoice $invoice)
    {
        return round(
            (float) self::lines($invoice)
                ->map(fn(RefundAllocation $line) => $line->refund)
                ->filter(fn(?Refund $credit) => $credit?->is_available)
                ->unique('refund_id')
                ->sum('amount'),
            2
        );
    }

    public static function pendingWithdrawal(Invoice $invoice)
    {
        return round(
            (float) self::lines($invoice)
                ->filter(
                    fn(RefundAllocation $line) => $line->refund?->transaction?->status
                        === Transaction::STATUS_REQUESTED
                )
                ->sum('amount'),
            2
        );
    }

    public static function hasPendingWithdrawal(Invoice $invoice): bool
    {
        return self::pendingWithdrawal($invoice) > 0;
    }

    public static function adjustments(Invoice $invoice)
    {
        return round((float) $invoice->invoiceAdjustments->sum('amount'), 2);
    }

    private static function lines(Invoice $invoice)
    {
        $invoice->loadMissing('allocations.refundAllocations.refund.transaction');

        return $invoice->allocations->flatMap(
            fn($allocation) => $allocation->refundAllocations
        );
    }
}
