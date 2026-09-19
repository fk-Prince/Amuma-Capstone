import { computed, type Ref } from "vue";
import type { Admission } from "~/types/patient";
import type { DischargeCalculation } from "~/types/invoice";

export function useDischargeRefund(admission: Ref<Admission | undefined>) {
    function getNumber(value: unknown, fallback = 0): number {
        const number = Number(value);

        return Number.isFinite(number) ? number : fallback;
    }

    const calculation = computed<DischargeCalculation | null>(() => {
        return admission.value?.discharge_calculation ?? null;
    });

    const currentNetPaidAmount = computed(() =>
        getNumber(calculation.value?.amount_paid),
    );

    const currentContractPrice = computed(() =>
        getNumber(calculation.value?.contract_price),
    );

    // What the halving is applied to: this period's own charged price.
    const feeBaseAmount = computed(() =>
        getNumber(calculation.value?.fee_base_amount),
    );

    const currentBillingCycle = computed(() =>
        String(calculation.value?.billing_cycle ?? "").toUpperCase(),
    );

    const currentBillingCycleLabel = computed(() => {
        if (currentBillingCycle.value === "YEARLY") {
            return "Yearly";
        }

        if (currentBillingCycle.value === "MONTHLY") {
            return "Monthly";
        }

        return currentBillingCycle.value || "Billing period";
    });




    const consumedDays = computed(() =>
        getNumber(calculation.value?.consumed_days),
    );

    const remainingDays = computed(() =>
        getNumber(calculation.value?.remaining_days),
    );

    const dailyRate = computed(() => getNumber(calculation.value?.daily_rate));

    const hasDaysStayed = computed(() => daysStayedAmount.value > 0);

    // What this period is actually charged, taken from its invoice lines. The
    // contract's own price is wrong to show once an accommodation change has
    // re-priced the period to a prorated remainder.
    const periodPrice = computed(() =>
        getNumber(calculation.value?.period_price),
    );

    // What the invoice as a whole asks for. An accommodation change splits a
    // month across two periods on one invoice, so this can exceed the current
    // period's own price, and it is what the paid and required amounts are
    // measured against.
    const invoiceTotal = computed(() =>
        getNumber(calculation.value?.invoice_total),
    );

    const invoiceCoversMorePeriods = computed(
        () => invoiceTotal.value > 0 && invoiceTotal.value > periodPrice.value,
    );

    const retainedHalf = computed(() =>
        getNumber(calculation.value?.retained_half),
    );

    const periodDays = computed(() =>
        getNumber(calculation.value?.period_days),
    );

    const periodStart = computed(() => calculation.value?.period_start ?? null);

    const periodEnd = computed(() => calculation.value?.period_end ?? null);

    // Value of the days already lived in, priced at the period's own rate.
    const daysStayedAmount = computed(() =>
        getNumber(calculation.value?.days_stayed_amount),
    );

    const halfYearlyPrice = computed(() =>
        getNumber(calculation.value?.retained_amount),
    );

    const daysSinceAdmissionStart = computed<number | null>(
        () => calculation.value?.days_since_admission ?? null,
    );

    const isWithinRefundWindow = computed(
        () => !!calculation.value?.is_within_refund_window,
    );

    const isClosedInvoice = computed(
        () => !!calculation.value?.is_closed_invoice,
    );

    const isEligibleForRefund = computed(
        () => !!calculation.value?.eligible_for_refund,
    );

    const currentRefundAmount = computed(() =>
        getNumber(calculation.value?.refund_amount),
    );

    const requiredPaymentAmount = computed<number | null>(() => {
        if (!calculation.value) {
            return null;
        }

        return getNumber(calculation.value.required_payment);
    });

    const isUnderRequiredPayment = computed(
        () => !!calculation.value?.is_under_required_payment,
    );

    const requiredPaymentShortfall = computed(() =>
        getNumber(calculation.value?.payment_shortfall),
    );

    const refundPolicyTitle = computed(
        () => calculation.value?.policy_title ?? "Outside refund window",
    );

    const refundPolicyBadge = computed(
        () => calculation.value?.policy ?? "No refund",
    );

    const refundPolicyDescription = computed(
        () => calculation.value?.policy_description ?? "No refund applies.",
    );

    // Describes the period actually being discharged, not the branch contract —
    // an accommodation change re-prices the period, so the contract's own figure
    // no longer matches what is being charged.
    const requiredPaymentDescription = computed(() => {
        if (isClosedInvoice.value) {
            return refundPolicyDescription.value;
        }

        if (!isWithinRefundWindow.value) {
            return currentBillingCycle.value === "MONTHLY"
                ? "A monthly plan is charged in full for the month, so the days stayed are not worked out and nothing is refunded."
                : "The whole period is charged because the patient is being discharged after 6 months.";
        }

        const stayed = consumedDays.value;

        if (stayed <= 0) {
            return "Half of the period is retained. No days have been stayed yet, so the rest is refunded.";
        }

        return `Half of the period is retained, plus the ${stayed} ${
            stayed === 1 ? "day" : "days"
        } already stayed. The rest is refunded.`;
    });

    return {
        currentNetPaidAmount,
        currentContractPrice,
        feeBaseAmount,
        currentBillingCycle,
        currentBillingCycleLabel,
        consumedDays,
        remainingDays,
        dailyRate,
        hasDaysStayed,
        periodPrice,
        invoiceTotal,
        invoiceCoversMorePeriods,
        retainedHalf,
        periodDays,
        periodStart,
        periodEnd,
        halfYearlyPrice,
        daysStayedAmount,

        daysSinceAdmissionStart,
        isWithinRefundWindow,
        isClosedInvoice,

        isEligibleForRefund,
        currentRefundAmount,

        requiredPaymentAmount,
        requiredPaymentDescription,
        isUnderRequiredPayment,
        requiredPaymentShortfall,

        refundPolicyTitle,
        refundPolicyBadge,
        refundPolicyDescription,
    };
}
