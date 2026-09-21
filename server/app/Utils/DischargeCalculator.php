<?php

namespace App\Utils;

use App\Models\AdmissionPeriod;
use App\Models\Invoice;
use App\Models\PatientAdmission;
use Carbon\Carbon;


class DischargeCalculator
{
    private const YEARLY_HALF_REFUND_WINDOW_DAYS = 183;
    private const MONTHLY_HALF_REFUND_WINDOW_DAYS = 14;

    public static function getDischargeCalculation(Invoice $invoice,  PatientAdmission $admission,  AdmissionPeriod $period)
    {
        $contract = $period->branchContract;

        $admissionDate = $admission->admitted_at
            ? Carbon::parse($admission->admitted_at)
            : null;

        $dischargeDate = $admission->end_date
            ? Carbon::parse($admission->end_date)
            : (
                $admission->discharge_date
                ? Carbon::parse($admission->discharge_date)
                : null
            );

        $paid = InvoiceMoney::netPaid($invoice);

        if (in_array($invoice->status, Invoice::CLOSED_STATUSES, true)) {
            return self::closedInvoiceDischargeCalculation($invoice, $admission, $admissionDate, $dischargeDate, $paid);
        }

        if (!$contract) {
            return self::emptyDischargeCalculation($admission, $admissionDate,  $dischargeDate, $paid);
        }

        $billingCycle = self::getBillingCycle($contract);
        $contractPrice = self::getContractPrice($contract);
        $days = self::calculateAdmissionDays($admissionDate);

        $plan = self::plan($admission, $period);

        $current = $plan['entries']->where('scope', 'current');
        $future = $plan['entries']->where('scope', 'future');

        $currentPaid = round((float) $current->sum('paid'), 2);
        $refundAmount = round((float) $current->sum('refundable'), 2);
        $requiredPayment = round((float) collect($plan['invoices'])->sum('new_total'), 2);
        $stillOwed = round((float) collect($plan['invoices'])->sum('owed'), 2);
        $totalPaid = round((float) collect($plan['invoices'])->sum('net_paid'), 2);
        $totalRefund = round((float) collect($plan['invoices'])->sum('refund'), 2);

        $eligibleForRefund = $refundAmount > 0;

        [$policy, $policyTitle, $policyDescription] = self::getDischargePolicyText(
            $plan['within_window'],
            $eligibleForRefund,
            $billingCycle
        );

        return [
            'admission_id' => $admission->patient_admission_id,
            'eligible_for_refund' => $eligibleForRefund,
            'billing_cycle' => $billingCycle,
            'admission_date' => $admissionDate?->toIso8601String(),
            'discharge_date' => $dischargeDate?->toIso8601String(),
            'days_since_admission' => $days,
            'contract_price' => $contractPrice,
            'amount_paid' => $currentPaid,
            'total_paid' => $totalPaid,
            'required_payment' => $requiredPayment,
            'fee_base_amount' => $plan['period_price'],
            'days_stayed_amount' => $plan['days_stayed_amount'],
            'retained_amount' => $plan['period_keep'],
            'refund_amount' => $refundAmount,
            'total_refund_amount' => $totalRefund,

            'consumed_days' => $plan['consumed_days'],
            'remaining_days' => $period->remainingDays(),
            'period_days' => $period->totalDays(),
            'period_start' => $period->start_date,
            'period_end' => $period->end_date,
            'daily_rate' => round($period->dailyRate(), 2),
            'period_price' => $plan['period_price'],
            'invoice_total' => $plan['period_price'],
            'invoice_code' => $invoice->invoice_code,
            'retained_half' => $plan['retained_half'],

            'policy' => $policy,
            'policy_title' => $policyTitle,
            'policy_description' => $policyDescription,
            'is_within_refund_window' => $plan['within_window'],
            'is_under_required_payment' => $stillOwed > 0,
            'payment_shortfall' => $stillOwed,

            'period_code' => AdmissionPeriod::codeFor($period->admission_period_id),
            'invoice_codes' => $current
                ->map(fn(array $entry) => $entry['line']->invoice?->invoice_code)
                ->filter()
                ->unique()
                ->values()
                ->all(),

            'future_periods' => $future
                ->groupBy(fn(array $entry) => $entry['period']->admission_period_id)
                ->map(function ($group) {
                    $futurePeriod = $group->first()['period'];
                    $price = round((float) $group->sum(fn(array $entry) => (float) $entry['line']->price), 2);
                    $paid = round((float) $group->sum('paid'), 2);

                    return [
                        'admission_period_id' => $futurePeriod->admission_period_id,
                        'period_code' => AdmissionPeriod::codeFor($futurePeriod->admission_period_id),
                        'billing_cycle' => $futurePeriod->branchContract?->billing_cycle,
                        'accommodation_type' => $futurePeriod->branchContract?->accommodation_type,
                        'start_date' => $futurePeriod->start_date,
                        'end_date' => $futurePeriod->end_date,
                        'price' => $price,
                        'paid' => $paid,
                        'refundable' => round((float) $group->sum('refundable'), 2),
                        'invoice_codes' => $group
                            ->map(fn(array $entry) => $entry['line']->invoice?->invoice_code)
                            ->filter()
                            ->unique()
                            ->values()
                            ->all(),
                        'status' => $paid >= $price - 0.01
                            ? 'paid'
                            : ($paid > 0 ? 'partially_paid' : 'unpaid'),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public static function plan(PatientAdmission $admission, AdmissionPeriod $period): array
    {
        $contract = $period->branchContract;
        $cycle = $contract ? self::getBillingCycle($contract) : '';

        $consumedDays = $period->consumedDays();
        $periodPrice = self::periodPrice($period);
        $dailyRate = $period->dailyRate();

        $withinWindow = ($cycle === 'YEARLY' && self::isWithinYearlyHalfRefundWindow($admission))
            || ($cycle === 'MONTHLY' && self::isWithinMonthlyHalfRefundWindow($period));

        $retainedHalf = 0.0;
        $daysStayedAmount = 0.0;
        $periodKeep = $periodPrice;

        if ($withinWindow) {
            $retainedHalf = round($periodPrice / 2, 2);
            $daysStayedAmount = $cycle === 'MONTHLY'
                ? 0.0
                : round($dailyRate * $consumedDays, 2);

            $periodKeep = round(min($periodPrice, $retainedHalf + $daysStayedAmount), 2);
        }

        $periodCredit = round(max(0, $periodPrice - $periodKeep), 2);
        $creditRate = $periodPrice > 0 ? $periodCredit / $periodPrice : 0.0;

        $futurePeriods = $admission->periods()
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->where('admission_period_id', '!=', $period->admission_period_id)
            ->where('start_date', '>=', $period->end_date)
            ->with('invoiceAdmissionLines.invoice', 'branchContract')
            ->orderBy('start_date')
            ->orderBy('admission_period_id')
            ->get();

        $entries = collect();

        $currentLines = $period->invoiceAdmissionLines()->with('invoice')->get()->values();
        $assignedCredit = 0.0;

        foreach ($currentLines as $index => $line) {
            $credit = $index === $currentLines->count() - 1
                ? round($periodCredit - $assignedCredit, 2)
                : round((float) $line->price * $creditRate, 2);

            $assignedCredit = round($assignedCredit + $credit, 2);

            $entries->push([
                'scope' => 'current',
                'line' => $line,
                'period' => $period,
                'credit' => $credit,
            ]);
        }

        foreach ($futurePeriods as $futurePeriod) {
            foreach ($futurePeriod->invoiceAdmissionLines as $line) {
                $entries->push([
                    'scope' => 'future',
                    'line' => $line,
                    'period' => $futurePeriod,
                    'credit' => round((float) $line->price, 2),
                ]);
            }
        }

        $entries = $entries
            ->filter(fn(array $entry) => $entry['line']->invoice
                && !in_array($entry['line']->invoice->status, Invoice::CLOSED_STATUSES, true))
            ->values();

        $invoices = [];
        $shares = [];

        foreach ($entries->groupBy(fn(array $entry) => $entry['line']->invoice_id) as $invoiceId => $group) {
            $invoice = $group->first()['line']->invoice;
            $invoice->loadMissing(
                'allocations.refundAllocations.refund.transaction',
                'invoiceAdjustments',
                'invoiceAdmissionLines'
            );

            $adjusted = round((float) $invoice->adjusted_total, 2);
            $netPaid = round((float) $invoice->net_paid_amount, 2);
            $groupCredit = round((float) $group->sum('credit'), 2);
            $credit = round(min($groupCredit, $adjusted), 2);
            $newTotal = round(max(0, $adjusted - $credit), 2);
            $refund = round(max(0, min($credit, $netPaid - $newTotal)), 2);
            $lineTotal = (float) $invoice->invoiceAdmissionLines->sum('price');

            $invoices[$invoiceId] = [
                'invoice' => $invoice,
                'credit' => $credit,
                'new_total' => $newTotal,
                'net_paid' => $netPaid,
                'refund' => $refund,
                'owed' => round(max(0, $newTotal - $netPaid), 2),
                'has_current' => $group->contains('scope', 'current'),
            ];

            foreach ($group as $entry) {
                $price = (float) $entry['line']->price;

                $shares[$entry['line']->invoice_admission_id] = [
                    'paid' => $lineTotal > 0 ? round($netPaid * $price / $lineTotal, 2) : 0.0,
                    'refundable' => $groupCredit > 0
                        ? round($refund * $entry['credit'] / $groupCredit, 2)
                        : 0.0,
                ];
            }
        }

        return [
            'within_window' => $withinWindow,
            'period_price' => $periodPrice,
            'period_keep' => $periodKeep,
            'retained_half' => $retainedHalf,
            'days_stayed_amount' => $daysStayedAmount,
            'consumed_days' => $consumedDays,
            'invoices' => $invoices,
            'entries' => $entries->map(fn(array $entry) => $entry + $shares[$entry['line']->invoice_admission_id])->values(),
        ];
    }

    public static function getDischargePolicyText(
        bool $withinRefundWindow,
        bool $eligibleForRefund,
        string $billingCycle
    ): array {
        if ($withinRefundWindow && $billingCycle === 'MONTHLY') {
            return [
                $eligibleForRefund ? 'Refund available' : 'No refund',
                'Half-retention policy',
                $eligibleForRefund
                    ? 'Discharged less than 2 weeks into the month. Half of the month is retained and the other half is refunded. The days already stayed are not counted.'
                    : 'Discharged less than 2 weeks into the month. Half of the month is retained and the other half is refunded, but nothing has been paid beyond the retained half, so there is nothing to refund.',
            ];
        }

        if ($withinRefundWindow) {
            return [
                $eligibleForRefund ? 'Refund available' : 'No refund',
                'Half-retention policy',
                $eligibleForRefund
                    ? 'Discharged before 6 months. Half of the period is retained, and the days already stayed are deducted from the other half. What is left is refunded.'
                    : 'Discharged before 6 months. Half of the period is retained, and the days already stayed have used up the other half, so nothing is left to refund.',
            ];
        }

        if ($billingCycle === 'MONTHLY') {
            return [
                'No refund',
                'Monthly plan',
                'A monthly plan is charged in full for the month, whenever the resident leaves. The days stayed are not worked out and no refund applies.',
            ];
        }

        if ($billingCycle === 'YEARLY') {
            return [
                'No refund',
                'Outside refund window',
                'Discharged after 6 months. No refund applies.',
            ];
        }

        return [
            'No refund',
            'Outside refund window',
            'No refund applies.',
        ];
    }

    public static function calculateAdmissionDays(?Carbon $admissionDate)
    {
        if (!$admissionDate) {
            return null;
        }

        $now = now();

        if ($now->isBefore($admissionDate)) {
            return 0;
        }

        return (int) $admissionDate
            ->copy()
            ->startOfDay()
            ->diffInDays(
                $now->copy()->startOfDay()
            ) + 1;
    }


    public static function isWithinYearlyHalfRefundWindow(PatientAdmission $admission): bool
    {
        $days = self::calculateAdmissionDays(
            $admission->admitted_at
                ? Carbon::parse($admission->admitted_at)
                : null
        );

        return $days !== null && $days <= self::YEARLY_HALF_REFUND_WINDOW_DAYS;
    }

    public static function isWithinMonthlyHalfRefundWindow(AdmissionPeriod $period): bool
    {
        return $period->consumedDays() < self::MONTHLY_HALF_REFUND_WINDOW_DAYS;
    }

    public static function getContractPrice(mixed $contract)
    {
        return round((float) ($contract->price ?? 0), 2);
    }

    public static function getBillingCycle(mixed $contract)
    {
        return strtoupper(trim($contract->billing_cycle ?? ''));
    }

    public static function emptyDischargeCalculation(PatientAdmission $admission, ?Carbon $admissionDate, ?Carbon $dischargeDate,  float $paid)
    {
        return [
            'admission_id' => $admission->patient_admission_id,
            'eligible_for_refund' => false,
            'billing_cycle' => null,
            'admission_date' => $admissionDate?->toIso8601String(),
            'discharge_date' => $dischargeDate?->toIso8601String(),
            'days_since_admission' => self::calculateAdmissionDays($admissionDate),
            'contract_price' => 0,
            'amount_paid' => round($paid, 2),
            'required_payment' => 0,
            'fee_base_amount' => 0,
            'days_stayed_amount' => 0,
            'retained_amount' => 0,
            'refund_amount' => 0,
            'consumed_days' => 0,
            'remaining_days' => 0,
            'period_days' => 0,
            'period_start' => null,
            'period_end' => null,
            'daily_rate' => 0,
            'period_price' => 0,
            'invoice_total' => 0,
            'invoice_code' => null,
            'retained_half' => 0,
            'policy' => 'No refund',
            'policy_title' => 'Outside refund window',
            'policy_description' => 'No refund applies.',
            'is_within_refund_window' => false,
            'is_under_required_payment' => false,
            'payment_shortfall' => 0,
        ];
    }

    public static function periodPrice(AdmissionPeriod $period)
    {
        return round((float) $period->invoiceAdmissionLines()->sum('price'), 2);
    }

    // A void/written-off invoice is closed: no further payment is required and
    // no refund is worked out against it, since void already credited the
    // patient and written-off already gave up on collecting it.
    public static function closedInvoiceDischargeCalculation(
        Invoice $invoice,
        PatientAdmission $admission,
        ?Carbon $admissionDate,
        ?Carbon $dischargeDate,
        float $paid
    ) {
        $isWrittenOff = $invoice->status === Invoice::STATUS_WRITTEN_OFF;

        return [
            'admission_id' => $admission->patient_admission_id,
            'eligible_for_refund' => false,
            'billing_cycle' => null,
            'admission_date' => $admissionDate?->toIso8601String(),
            'discharge_date' => $dischargeDate?->toIso8601String(),
            'days_since_admission' => self::calculateAdmissionDays($admissionDate),
            'contract_price' => 0,
            'amount_paid' => round($paid, 2),
            'required_payment' => 0,
            'fee_base_amount' => 0,
            'days_stayed_amount' => 0,
            'retained_amount' => 0,
            'refund_amount' => 0,
            'consumed_days' => 0,
            'remaining_days' => 0,
            'period_days' => 0,
            'period_start' => null,
            'period_end' => null,
            'daily_rate' => 0,
            'period_price' => 0,
            'invoice_total' => round((float) $invoice->adjusted_total, 2),
            'invoice_code' => $invoice->invoice_code,
            'retained_half' => 0,
            'policy' => 'No refund',
            'policy_title' => $isWrittenOff ? 'Written off' : 'Void',
            'policy_description' => $isWrittenOff
                ? 'This invoice has been written off as bad debt. It no longer requires payment and is not eligible for a refund.'
                : 'This invoice has been voided. It no longer requires payment and is not eligible for a refund.',
            'is_within_refund_window' => false,
            'is_under_required_payment' => false,
            'payment_shortfall' => 0,
            'is_closed_invoice' => true,
        ];
    }
}
