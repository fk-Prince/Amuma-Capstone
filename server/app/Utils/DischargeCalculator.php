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

        $offset = (float) $plan['offset'];
        $refundAmount = round(max(0, $refundAmount - $offset), 2);
        $totalRefund = round(max(0, $totalRefund - $offset), 2);
        $stillOwed = round(max(0, $stillOwed - $offset), 2);

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
            'remaining_days' => $plan['remaining_days'],
            'period_days' => $plan['period_days'],
            'period_start' => $plan['period_start'],
            'period_end' => $plan['period_end'],
            'period_chain' => $plan['chain']
                ->map(fn(AdmissionPeriod $link) => [
                    'admission_period_id' => $link->admission_period_id,
                    'reason' => $link->reason,
                    'accommodation_type' => $link->branchContract?->accommodation_type,
                    'billing_cycle' => $link->branchContract?->billing_cycle,
                    'start_date' => $link->start_date,
                    'end_date' => $link->end_date,
                    'price' => $link->chargedAmount(),
                    'is_current' => $link->admission_period_id === $period->admission_period_id,
                ])
                ->values()
                ->all(),
            'daily_rate' => $plan['daily_rate'],
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

        $chain = self::chain($period);
        $window = self::chainWindow($chain);

        $consumedDays = $window['consumed_days'];
        $periodPrice = $window['price'];

        $withinWindow = ($cycle === 'YEARLY' && self::isWithinYearlyHalfRefundWindow($admission))
            || ($cycle === 'MONTHLY' && $consumedDays < self::MONTHLY_HALF_REFUND_WINDOW_DAYS);

        $retainedHalf = 0.0;
        $daysStayedAmount = 0.0;
        $periodKeep = $periodPrice;

        if ($withinWindow) {
            $retainedHalf = round($periodPrice / 2, 2);
            $daysStayedAmount = $cycle === 'MONTHLY'
                ? 0.0
                : round((float) $chain->sum(fn(AdmissionPeriod $link) => $link->dailyRate() * $link->consumedDays()), 2);

            $periodKeep = round(min($periodPrice, $retainedHalf + $daysStayedAmount), 2);
        }

        // Everything charged across the chain (earlier periods included) is cut
        // down to what is kept, so the retained amount is exactly the policy's
        // figure and an earlier accommodation's charge is not added on top.
        $chainTotal = round((float) $chain->sum(fn(AdmissionPeriod $link) => $link->chargedAmount()), 2);

        $periodCredit = $withinWindow
            ? round(max(0, $chainTotal - $periodKeep), 2)
            : round(max(0, $periodPrice - $periodKeep), 2);

        $creditRate = $chainTotal > 0 ? $periodCredit / $chainTotal : 0.0;

        $futurePeriods = $admission->periods()
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->where('admission_period_id', '!=', $period->admission_period_id)
            ->where('start_date', '>=', $period->end_date)
            ->with('invoiceAdmissionLines.invoice', 'branchContract')
            ->orderBy('start_date')
            ->orderBy('admission_period_id')
            ->get();

        $entries = collect();

        $currentLines = $chain
            ->flatMap(fn(AdmissionPeriod $link) => $link->invoiceAdmissionLines()->with('invoice')->get()
                ->map(fn($line) => ['line' => $line, 'period' => $link]))
            ->values();
        $assignedCredit = 0.0;

        foreach ($currentLines as $index => $current) {
            $credit = $index === $currentLines->count() - 1
                ? round($periodCredit - $assignedCredit, 2)
                : round((float) $current['line']->price * $creditRate, 2);

            $assignedCredit = round($assignedCredit + $credit, 2);

            $entries->push([
                'scope' => 'current',
                'line' => $current['line'],
                'period' => $current['period'],
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

        // Money refundable on one invoice first settles what is still owed on
        // another, so the resident is never refunded and billed for the same stay.
        $offset = round(min(
            (float) collect($invoices)->sum('refund'),
            (float) collect($invoices)->sum('owed')
        ), 2);

        return [
            'offset' => $offset,
            'within_window' => $withinWindow,
            'period_start' => $window['start'],
            'period_end' => $window['end'],
            'period_days' => $window['total_days'],
            'remaining_days' => $window['total_days'] - $consumedDays,
            'daily_rate' => $window['total_days'] > 0 ? round($periodPrice / $window['total_days'], 2) : 0.0,
            'period_price' => $periodPrice,
            'period_keep' => $periodKeep,
            'retained_half' => $retainedHalf,
            'days_stayed_amount' => $daysStayedAmount,
            'consumed_days' => $consumedDays,
            'chain' => $chain,
            'invoices' => $invoices,
            'entries' => $entries->map(fn(array $entry) => $entry + $shares[$entry['line']->invoice_admission_id])->values(),
        ];
    }

    // Walks parent_admission_period_id up through every accommodation change
    // and extension that produced this period, oldest first, ending with the
    // period being discharged from. Used so the refund window counts days
    // from where the resident's stay actually began, not from the last change.
    public static function chain(AdmissionPeriod $period)
    {
        $chain = collect();
        $current = $period;

        while ($current) {
            $chain->prepend($current);
            $current = $current->parentPeriod;
        }

        return $chain;
    }

    public static function chainWindow($chain): array
    {
        $start = Carbon::parse($chain->first()->start_date)->startOfDay();
        $end = Carbon::parse($chain->last()->end_date)->startOfDay();
        $today = now()->startOfDay();

        $totalDays = max(1, (int) $start->diffInDays($end));
        $consumedDays = $today->lessThan($start)
            ? 0
            : (int) min($totalDays, $start->diffInDays($today) + 1);

        return [
            'start' => $chain->first()->start_date,
            'end' => $chain->last()->end_date,
            'total_days' => $totalDays,
            'consumed_days' => $consumedDays,
            'price' => $chain->last()->chargedAmount(),
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
                'Outside refund window',
                'Discharged 2 weeks or more into the month. The half-retention window has passed, so the full month is charged and no refund applies.',
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
