<?php

namespace App\Utils;

use App\Models\Invoice;

class OutstandingBalance
{
    // A discharge ends the patient's stay, so the screen has to account for
    // everything still owed on the account, not only the invoice the refund is
    // worked out on.
    public static function forInvoices(
        mixed $invoices,
        ?Invoice $dischargeInvoice = null,
        array $futurePeriodIds = [],
        ?int $admissionId = null
    ): array {
        $invoices = collect($invoices)
            ->reject(fn($invoice) => $invoice->status === Invoice::STATUS_VOID)
            ->values();

        $accommodation = $invoices->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->isNotEmpty()
        );

        $scheduled = $invoices->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->isEmpty()
                && $invoice->invoiceServices->isNotEmpty()
        );

        // A schedule with no service attached is Activities of Daily Living.
        // Both are service work and are totalled together; the ADL share is
        // kept alongside for anyone who needs it broken out.
        $medical = $scheduled->filter(
            fn($invoice) => $invoice->invoiceServices->contains(
                fn($line) => $line->scheduleService?->service_id !== null
            )
        );

        $medicalIds = $medical->pluck('invoice_id')->all();

        $adl = $scheduled->reject(
            fn($invoice) => in_array($invoice->invoice_id, $medicalIds, true)
        );

        $due = fn($invoice) => round(
            max(
                0,
                $invoice->balance_due - $invoice->invoiceAdmissionLines
                    ->whereIn('admission_period_id', $futurePeriodIds)
                    ->sum('price')
            ),
            2
        );

        $balance = fn($list) => round((float) $list->sum($due), 2);

        $ofAdmission = $accommodation->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->contains(
                fn($line) => $admissionId !== null
                    && (int) $line->admissionPeriod?->patient_admission_id === $admissionId
            )
        );

        return [
            'total_balance' => round((float) $invoices->sum('balance_due'), 2),
            'balance_excluding_future' => $balance($invoices),
            'accommodation_balance' => $balance($accommodation),
            'admission_balance' => $balance($ofAdmission),
            'service_balance' => $balance($scheduled),
            'adl_balance' => $balance($adl),

            'other_balance' => $dischargeInvoice
                ? $balance($invoices->reject(
                    fn($invoice) => $invoice->invoice_id === $dischargeInvoice->invoice_id
                ))
                : $balance($invoices),

            'unpaid_invoice_count' => $invoices
                ->filter(fn($invoice) => $due($invoice) > 0)
                ->count(),

            'invoices' => $invoices
                ->filter(fn($invoice) => $due($invoice) > 0)
                ->map(fn($invoice) => [
                    'invoice_code' => $invoice->invoice_code,
                    'description' => $invoice->paymentDescription(),
                    'kind' => self::kind($invoice, $medical, $adl),
                    'balance_due' => $due($invoice),
                    'is_discharge_invoice' => $dischargeInvoice
                        && $invoice->invoice_id === $dischargeInvoice->invoice_id,
                ])
                ->sortByDesc('balance_due')
                ->values()
                ->all(),
        ];
    }

    private static function kind(Invoice $invoice, mixed $medical, mixed $adl): string
    {
        if ($medical->contains(fn($item) => $item->invoice_id === $invoice->invoice_id)) {
            return 'service';
        }

        if ($adl->contains(fn($item) => $item->invoice_id === $invoice->invoice_id)) {
            return 'adl';
        }

        return 'admission';
    }
}
