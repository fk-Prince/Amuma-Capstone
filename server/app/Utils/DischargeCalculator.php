<?php

namespace App\Utils;

use App\Models\AdmissionPeriod;
use App\Models\Invoice;
use App\Models\PatientAdmission;
use Carbon\Carbon;


class DischargeCalculator
{
    private const YEARLY_HALF_REFUND_WINDOW_DAYS = 183;

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

        if (!$contract) {
            return self::emptyDischargeCalculation($admission, $admissionDate,  $dischargeDate, $paid);
        }

        $billingCycle = self::getBillingCycle($contract);
        $contractPrice = self::getContractPrice($contract);
        $days = self::calculateAdmissionDays($admissionDate);

        $consumedDays = $period->consumedDays();
        $remainingDays = $period->remainingDays();
        $dailyRate = $period->dailyRate();
        $periodPrice = self::periodPrice($period);


        $feeBaseAmount = $periodPrice;


        $withinRefundWindow = $billingCycle === 'YEARLY'
            && self::isWithinYearlyHalfRefundWindow($admission);

        $daysStayedAmount = 0.0;
        $retainedHalf = 0.0;
        $periodKeep = $periodPrice;

        if ($withinRefundWindow) {
            $daysStayedAmount = round($dailyRate * $consumedDays, 2);
            $retainedHalf = round($periodPrice / 2, 2);

            $periodKeep = round(
                min($periodPrice, $retainedHalf + $daysStayedAmount),
                2
            );
        }


        $adjustedTotal = round((float) $invoice->adjusted_total, 2);
        $periodCredit = round(max(0, $periodPrice - $periodKeep), 2);

        $requiredPayment = round(max(0, $adjustedTotal - $periodCredit), 2);


        $refundAmount = round(max(0, $paid - $requiredPayment), 2);
        $stillOwed = round(max(0, $requiredPayment - $paid), 2);

        $eligibleForRefund = $refundAmount > 0;

        [$policy, $policyTitle, $policyDescription] = self::getDischargePolicyText(
            $withinRefundWindow,
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
            'amount_paid' => round($paid, 2),
            'required_payment' => $requiredPayment,
            'fee_base_amount' => $feeBaseAmount,
            'days_stayed_amount' => $daysStayedAmount,
            'retained_amount' => $requiredPayment,
            'refund_amount' => $refundAmount,

            'consumed_days' => $consumedDays,
            'remaining_days' => $remainingDays,
            'period_days' => $period->totalDays(),
            'period_start' => $period->start_date,
            'period_end' => $period->end_date,
            'daily_rate' => round($dailyRate, 2),
            'period_price' => $periodPrice,
            'invoice_total' => $adjustedTotal,
            'invoice_code' => $invoice->invoice_code,
            'retained_half' => $retainedHalf,

            'policy' => $policy,
            'policy_title' => $policyTitle,
            'policy_description' => $policyDescription,
            'is_within_refund_window' => $withinRefundWindow,
            'is_under_required_payment' => $stillOwed > 0,
            'payment_shortfall' => $stillOwed,
        ];
    }

    public static function getDischargePolicyText(
        bool $withinRefundWindow,
        bool $eligibleForRefund,
        string $billingCycle
    ): array {
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
}
