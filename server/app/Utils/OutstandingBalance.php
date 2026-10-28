<?php

namespace App\Utils;

use App\Models\Invoice;

class OutstandingBalance
{

    public static function forInvoices(
        mixed $invoices,
        ?Invoice $dischargeInvoice = null,
        array $futurePeriodIds = [],
        ?int $admissionId = null,
        ?int $currentPeriodId = null
    ): array {
        $invoices = collect($invoices)
            ->reject(fn($invoice) => $invoice->status === Invoice::STATUS_VOID)
            ->values();

        $invoices->each(fn($invoice) => $invoice->loadMissing('additionalCharges', 'diagnosisLines.admissionPeriod'));

        $accommodation = $invoices->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->isNotEmpty()
        );

        $scheduled = $invoices->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->isEmpty()
                && $invoice->invoiceServices->isNotEmpty()
        );

        $medical = $scheduled->filter(
            fn($invoice) => $invoice->invoiceServices->contains(
                fn($line) => $line->scheduleService?->service_id !== null
            )
        );

        $charges = $invoices->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->isEmpty()
                && $invoice->invoiceServices->isEmpty()
                && ($invoice->additionalCharges->isNotEmpty() || $invoice->diagnosisLines->isNotEmpty())
        );

        $medicalIds = $medical->pluck('invoice_id')->all();

        $adl = $scheduled->reject(
            fn($invoice) => in_array($invoice->invoice_id, $medicalIds, true)
        );

        $skippedPeriodIds = array_filter([...$futurePeriodIds, $currentPeriodId]);

        $due = fn($invoice) => round(
            max(
                0,
                $invoice->balance_due - $invoice->invoiceAdmissionLines
                    ->whereIn('admission_period_id', $futurePeriodIds)
                    ->sum('price')
                    - $invoice->diagnosisLines
                    ->filter(fn($line) => in_array($line->admissionPeriod?->admission_period_id, $skippedPeriodIds, true))
                    ->sum('billed_amount')
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

        $chargesOfAdmission = $charges->filter(
            fn($invoice) => $admissionId !== null
                && ($invoice->additionalCharges->contains(
                    fn($charge) => (int) $charge->patientAdmission?->patient_admission_id === $admissionId
                ) || $invoice->diagnosisLines->contains(
                    fn($charge) => (int) $charge->patient_admission_id === $admissionId
                ))
        );

        return [
            'total_balance' => round((float) $invoices->sum('balance_due'), 2),
            'balance_excluding_future' => $balance($invoices),
            'accommodation_balance' => $balance($accommodation),
            'admission_balance' => round($balance($ofAdmission) + $balance($chargesOfAdmission), 2),
            'additional_charge_balance' => $balance($chargesOfAdmission),
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
                    'kind' => self::kind($invoice, $medical, $adl, $charges),
                    'balance_due' => $due($invoice),
                    'is_discharge_invoice' => $dischargeInvoice
                        && $invoice->invoice_id === $dischargeInvoice->invoice_id,
                ])
                ->sortByDesc('balance_due')
                ->values()
                ->all(),

            'balance_invoices' => $invoices
                ->filter(fn($invoice) => $invoice->balance_due > 0)
                ->map(fn($invoice) => [
                    'invoice_code' => $invoice->invoice_code,
                    'description' => preg_replace(
                        '/^DIAGNOSIS CASE - /i',
                        '',
                        $invoice->paymentDescription()
                    ),
                    'kind' => $invoice->additionalCharges->isEmpty() && $invoice->diagnosisLines->isNotEmpty()
                        ? 'diagnosis'
                        : self::kind($invoice, $medical, $adl, $charges),
                    'balance_due' => round((float) $invoice->balance_due, 2),
                ])
                ->sortByDesc('balance_due')
                ->values()
                ->all(),
        ];
    }

    private static function kind(Invoice $invoice, mixed $medical, mixed $adl, mixed $charges): string
    {
        if ($charges->contains(fn($item) => $item->invoice_id === $invoice->invoice_id)) {
            return 'charge';
        }

        if ($medical->contains(fn($item) => $item->invoice_id === $invoice->invoice_id)) {
            return 'service';
        }

        if ($adl->contains(fn($item) => $item->invoice_id === $invoice->invoice_id)) {
            return 'adl';
        }

        return 'admission';
    }
}
