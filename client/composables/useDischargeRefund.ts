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

    // The amount the termination-fee % was actually applied to — paid
    // amount for the <7-day tier, invoiceFacility.price for the yearly
    // 7-day-to-6-month tier. Not the same as currentContractPrice, which
    // is just the branch contract's price and may not match either.
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

    const terminationFeePercent = computed(() =>
        getNumber(calculation.value?.termination_fee_percent),
    );

    const terminationFeeAmount = computed(() =>
        getNumber(calculation.value?.termination_fee_amount),
    );

    // Value of the days already stayed (priced off invoiceFacility.price),
    // subtracted from half the fee base to arrive at the actual refund —
    // shown separately so the retained amount isn't mistaken for a flat 50%.
    const daysStayedAmount = computed(() =>
        getNumber(calculation.value?.days_stayed_amount),
    );

    const halfYearlyPrice = computed(() =>
        getNumber(calculation.value?.retention_amount),
    );

    const daysSinceAdmissionStart = computed<number | null>(
        () => calculation.value?.days_since_admission ?? null,
    );

    const isWithinTerminationFeeWindow = computed(
        () => !!calculation.value?.is_within_termination_fee_window,
    );

    const isWithinYearlyHalfRefundWindow = computed(
        () => !!calculation.value?.is_within_yearly_half_refund_window,
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

    const requiredPaymentDescription = computed(() => {
        const feePercent = terminationFeePercent.value;

        if (isWithinTerminationFeeWindow.value) {
            return `A ${feePercent}% termination fee is retained because the patient is being discharged within the first 7 days of admission.`;
        }

        if (isWithinYearlyHalfRefundWindow.value) {
            return "50% of the yearly contract price is retained because the patient is being discharged after 7 days but before 6 months.";
        }

        if (currentBillingCycle.value === "YEARLY") {
            return "The full yearly contract amount is required because the 6-month yearly refund period has passed.";
        }

        if (currentBillingCycle.value === "MONTHLY") {
            return "The full monthly contract amount is required because the 7-day termination-fee period has passed.";
        }

        return "The required amount must be paid before the patient can be discharged.";
    });

    return {
        currentNetPaidAmount,
        currentContractPrice,
        feeBaseAmount,
        currentBillingCycle,
        currentBillingCycleLabel,

        terminationFeePercent,
        terminationFeeAmount,
        halfYearlyPrice,
        daysStayedAmount,

        daysSinceAdmissionStart,

        isWithinTerminationFeeWindow,
        isWithinYearlyHalfRefundWindow,

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
