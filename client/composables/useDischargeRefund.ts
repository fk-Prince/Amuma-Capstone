import { computed, type Ref } from "vue";
import type { Admission, InvoiceFacility } from "~/types/patient";

const TERMINATION_FEE_WINDOW_DAYS = 7;
const YEARLY_NO_REFUND_DAYS = 183;
const MS_PER_DAY = 1000 * 60 * 60 * 24;

export function useDischargeRefund(
    admission: Ref<Admission | undefined>,
    currentInvoice: Ref<InvoiceFacility | null | undefined>,
) {
    function getNumber(
        value: unknown,
        fallback = 0,
    ): number {
        const number = Number(value);

        return Number.isFinite(number) ? number : fallback;
    }

    function roundMoney(
        value: number,
    ): number {
        return Math.round((value + Number.EPSILON) * 100) / 100;
    }

    function getNetPaid(
        invoice?: InvoiceFacility | null,
    ): number {
        if (!invoice) {
            return 0;
        }

        return Math.max(
            0,
            getNumber(invoice.net_paid_amount),
        );
    }

    const currentContractPrice = computed(() => {
        const invoice = currentInvoice.value as any;
        const admissionData = admission.value as any;

        const price =
            invoice?.contract?.price ??
            admissionData?.current_contract?.price ??
            0;

        return Math.max(
            0,
            getNumber(price),
        );
    });

    const currentBillingCycle = computed(() => {
        const invoice = currentInvoice.value as any;
        const admissionData = admission.value as any;

        const cycle =
            invoice?.contract?.billing_cycle ??
            admissionData?.current_contract?.billing_cycle ??
            "";

        return String(cycle).toUpperCase();
    });

    const currentBillingCycleLabel = computed(() => {
        if (currentBillingCycle.value === "YEARLY") {
            return "Yearly";
        }

        if (currentBillingCycle.value === "MONTHLY") {
            return "Monthly";
        }

        return currentBillingCycle.value || "Billing period";
    });

    const branchSettings = computed<Record<string, unknown>>(() => {
        const admissionData = admission.value as any;
        const settings = admissionData?.branch?.settings;

        return settings && typeof settings === "object"
            ? settings
            : {};
    });

    const terminationFeeRate = computed(() => {
        const configured = getNumber(
            branchSettings.value.termination_fee,
            0.2,
        );

        if (configured > 1) {
            return Math.min(
                configured / 100,
                1,
            );
        }

        return Math.max(
            0,
            Math.min(
                configured,
                1,
            ),
        );
    });

    const firstAdmissionDate = computed(() => {
        const admissionData = admission.value as any;

        return (
            admissionData?.first_admitted_at ??
            admissionData?.admitted_at ??
            null
        );
    });

    const daysSinceAdmissionStart = computed<number | null>(() => {
        const startDate = firstAdmissionDate.value;

        if (!startDate) {
            return null;
        }

        const start = new Date(startDate).getTime();

        if (!Number.isFinite(start)) {
            return null;
        }

        return Math.max(
            0,
            (Date.now() - start) / MS_PER_DAY,
        );
    });

    const isWithinTerminationFeeWindow = computed(() => {
        const days = daysSinceAdmissionStart.value;

        if (days === null) {
            return false;
        }

        return days < TERMINATION_FEE_WINDOW_DAYS;
    });

    const isWithinYearlyHalfRefundWindow = computed(() => {
        if (currentBillingCycle.value !== "YEARLY") {
            return false;
        }

        const days = daysSinceAdmissionStart.value;

        if (days === null) {
            return false;
        }

        return (
            days >= TERMINATION_FEE_WINDOW_DAYS &&
            days < YEARLY_NO_REFUND_DAYS
        );
    });

    const isWithinYearlyRefundWindow = computed(() => {
        if (currentBillingCycle.value !== "YEARLY") {
            return false;
        }

        const days = daysSinceAdmissionStart.value;

        if (days === null) {
            return false;
        }

        return days < YEARLY_NO_REFUND_DAYS;
    });

    const isPastRefundWindow = computed(() => {
        const days = daysSinceAdmissionStart.value;

        if (days === null) {
            return false;
        }

        if (currentBillingCycle.value === "MONTHLY") {
            return !isWithinTerminationFeeWindow.value;
        }

        if (currentBillingCycle.value === "YEARLY") {
            return days >= YEARLY_NO_REFUND_DAYS;
        }

        return true;
    });

    const monthlyEquivalentPrice = computed(() => {
        return roundMoney(
            currentContractPrice.value / 12,
        );
    });

    const halfYearlyPrice = computed(() => {
        return roundMoney(
            currentContractPrice.value / 2,
        );
    });

    const terminationFeeBaseAmount = computed(() => {
        const price = currentContractPrice.value;

        if (
            price <= 0 ||
            !isWithinTerminationFeeWindow.value
        ) {
            return 0;
        }

        return price;
    });

    const terminationFeeAmount = computed(() => {
        return roundMoney(
            terminationFeeBaseAmount.value *
            terminationFeeRate.value,
        );
    });

    const yearlyRefundAmount = computed(() => {
        const price = currentContractPrice.value;
        const days = daysSinceAdmissionStart.value;

        if (
            price <= 0 ||
            days === null ||
            currentBillingCycle.value !== "YEARLY"
        ) {
            return 0;
        }

        if (days >= YEARLY_NO_REFUND_DAYS) {
            return 0;
        }

        if (days < TERMINATION_FEE_WINDOW_DAYS) {
            return roundMoney(
                price - terminationFeeAmount.value,
            );
        }

        return halfYearlyPrice.value;
    });

    const currentNetPaidAmount = computed(() => {
        return getNetPaid(
            currentInvoice.value,
        );
    });

    /**
     * Amount that must remain paid before discharge.
     *
     * Contract price is used only to determine
     * the required payment.
     */
    const requiredPaymentAmount = computed<number | null>(() => {
        const price = currentContractPrice.value;

        if (price <= 0) {
            return null;
        }

        if (isWithinTerminationFeeWindow.value) {
            return terminationFeeAmount.value;
        }

        if (currentBillingCycle.value === "MONTHLY") {
            return price;
        }

        if (currentBillingCycle.value === "YEARLY") {
            if (isWithinYearlyHalfRefundWindow.value) {
                return halfYearlyPrice.value;
            }

            return price;
        }

        return null;
    });

    /**
     * Actual refund based on money paid.
     *
     * Net paid - required payment = refund.
     *
     * The invoice price is intentionally NOT used
     * as a cap because the invoice total may differ
     * from the contract price.
     */
    const maximumCurrentRefundAmount = computed(() => {
        const paid = currentNetPaidAmount.value;
        const required = requiredPaymentAmount.value;

        if (
            paid <= 0 ||
            required === null
        ) {
            return 0;
        }

        return roundMoney(
            Math.max(
                0,
                paid - required,
            ),
        );
    });

    const isEligibleForRefund = computed(() => {
        return maximumCurrentRefundAmount.value > 0;
    });

    const currentRefundAmount = computed(() => {
        return maximumCurrentRefundAmount.value;
    });

    const isUnderRequiredPayment = computed(() => {
        if (!currentInvoice.value) {
            return false;
        }

        const required = requiredPaymentAmount.value;

        if (required === null) {
            return false;
        }

        return (
            currentNetPaidAmount.value <
            required
        );
    });

    const requiredPaymentShortfall = computed(() => {
        const required = requiredPaymentAmount.value;

        if (required === null) {
            return 0;
        }

        return roundMoney(
            Math.max(
                0,
                required -
                currentNetPaidAmount.value,
            ),
        );
    });

  const refundPolicyTitle = computed(() => {
    if (isWithinTerminationFeeWindow.value) {
        return "7-day termination policy";
    }

    if (isWithinYearlyHalfRefundWindow.value) {
        return "Yearly refund policy";
    }

    return "Outside refund window";
    });

    const refundPolicyBadge = computed(() => {
        if (isWithinTerminationFeeWindow.value) {
            return isEligibleForRefund.value
                ? "Refund available"
                : "Payment required";
        }

        if (isWithinYearlyHalfRefundWindow.value) {
            return isEligibleForRefund.value
                ? "Refund available"
                : "No refund";
        }

        return "No refund";
    });

    const refundPolicyDescription = computed(() => {
        const feePercent = Math.round(
            terminationFeeRate.value * 100,
        );

        if (isWithinTerminationFeeWindow.value) {
            return `Discharged within 7 days of admission. The payment is refunded less the ${feePercent}% termination fee.`;
        }

        if (currentBillingCycle.value === "MONTHLY") {
            return "The 7-day monthly refund window has passed. No refund applies.";
        }

        if (currentBillingCycle.value === "YEARLY") {
            if (isWithinYearlyHalfRefundWindow.value) {
                return "Discharged after 7 days and before 6 months. Half of the yearly payment is refunded, half is retained.";
            }

            return "The 6-month yearly refund window has passed. No refund applies.";
        }

        return "No refund applies.";
    });

    const requiredPaymentDescription = computed(() => {
        const feePercent = Math.round(
            terminationFeeRate.value * 100,
        );

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
        currentBillingCycle,
        currentBillingCycleLabel,

        terminationFeeRate,
        terminationFeeBaseAmount,
        terminationFeeAmount,

        monthlyEquivalentPrice,
        halfYearlyPrice,
        yearlyRefundAmount,

        daysSinceAdmissionStart,

        isWithinTerminationFeeWindow,
        isWithinYearlyHalfRefundWindow,
        isWithinYearlyRefundWindow,
        isPastRefundWindow,

        isEligibleForRefund,
        maximumCurrentRefundAmount,
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