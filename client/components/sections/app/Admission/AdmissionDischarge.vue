<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-primary-950/60 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-[0_20px_60px_-15px_rgba(10,40,87,0.35)] ring-1 ring-black/5"
                >
                    <!-- HEADER -->
                    <div
                        class="flex items-start gap-4 border-b border-slate-100 px-8 py-7"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600 ring-1 ring-rose-100"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-5.5 w-5.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14A2 2 0 003.84 21h16.32a2 2 0 001.73-3.14l-8.18-14a2 2 0 00-3.42 0z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3
                                class="text-xl font-semibold tracking-tight text-primary-950"
                            >
                                Discharge patient
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                This ends the patient's current admission and
                                future periods. This action cannot be undone.
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="loading"
                            class="ml-auto flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-40"
                            @click="handleClose"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-4.5 w-4.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="max-h-[78vh] overflow-y-auto px-8 py-7">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- CURRENT -->
                            <section class="flex flex-col">
                                <h4
                                    class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-slate-400"
                                >
                                    Current period
                                </h4>

                                <div
                                    v-if="showCurrentPeriodBlock"
                                    class="flex flex-1 flex-col rounded-xl border border-slate-200 bg-white p-5"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-base font-semibold text-primary-950"
                                            >
                                                {{ currentBillingCycleLabel }}
                                                billing
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-slate-500"
                                            >
                                                {{
                                                    formatDate(
                                                        currentInvoice?.start_date,
                                                    )
                                                }}
                                                –
                                                {{
                                                    formatDate(
                                                        currentInvoice?.end_date,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <p
                                            class="shrink-0 text-base font-semibold text-primary-950"
                                        >
                                            {{
                                                formatCurrency(
                                                    currentContractPrice,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <!-- PAYMENT SUMMARY -->
                                    <div class="mt-5 grid grid-cols-2 gap-2.5">
                                        <div class="rounded-lg bg-slate-50 p-3">
                                            <p
                                                class="text-[11px] text-slate-500"
                                            >
                                                Paid
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold text-primary-950"
                                            >
                                                {{
                                                    formatCurrency(
                                                        currentNetPaidAmount,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-lg p-3"
                                            :class="
                                                isEligibleForRefund
                                                    ? 'bg-emerald-50'
                                                    : 'bg-slate-100'
                                            "
                                        >
                                            <p
                                                class="text-[11px]"
                                                :class="
                                                    isEligibleForRefund
                                                        ? 'text-emerald-700'
                                                        : 'text-slate-400'
                                                "
                                            >
                                                Refundable
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold"
                                                :class="
                                                    isEligibleForRefund
                                                        ? 'text-emerald-700'
                                                        : 'text-slate-400'
                                                "
                                            >
                                                {{
                                                    formatCurrency(
                                                        currentRefundAmount,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- REFUND POLICY -->
                                    <div
                                        class="mt-5 rounded-lg border p-3.5"
                                        :class="
                                            isEligibleForRefund
                                                ? 'border-rose-200 bg-rose-50'
                                                : 'border-slate-200 bg-slate-50'
                                        "
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <p
                                                        class="text-sm font-semibold"
                                                        :class="
                                                            isEligibleForRefund
                                                                ? 'text-rose-800'
                                                                : 'text-slate-600'
                                                        "
                                                    >
                                                        {{ refundPolicyTitle }}
                                                    </p>

                                                    <span
                                                        class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                                                        :class="
                                                            isEligibleForRefund
                                                                ? 'bg-rose-100 text-rose-700'
                                                                : 'bg-slate-200 text-slate-600'
                                                        "
                                                    >
                                                        {{ refundPolicyBadge }}
                                                    </span>
                                                </div>

                                                <p
                                                    class="mt-1.5 text-xs leading-5"
                                                    :class="
                                                        isEligibleForRefund
                                                            ? 'text-rose-700'
                                                            : 'text-slate-500'
                                                    "
                                                >
                                                    {{
                                                        refundPolicyDescription
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CALCULATION -->
                                    <div
                                        v-if="isEligibleForRefund"
                                        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4"
                                    >
                                        <p
                                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                        >
                                            Refund calculation
                                        </p>

                                        <div class="mt-3 space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-slate-500">
                                                    Amount paid
                                                </span>

                                                <span
                                                    class="font-medium text-slate-700"
                                                >
                                                    <div
                                                        class="flex justify-between"
                                                    >
                                                        <span
                                                            class="text-slate-500"
                                                        >
                                                            Amount paid
                                                        </span>

                                                        <span
                                                            class="font-medium text-slate-700"
                                                        >
                                                            {{
                                                                formatCurrency(
                                                                    currentNetPaidAmount,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </span>
                                            </div>

                                            <div
                                                v-if="
                                                    isWithinTerminationFeeWindow
                                                "
                                                class="flex justify-between text-slate-600"
                                            >
                                                <span class="text-slate-500">
                                                    Termination fee ({{
                                                        terminationFeePercent
                                                    }}% of
                                                    {{
                                                        formatCurrency(
                                                            currentContractPrice,
                                                        )
                                                    }})
                                                </span>

                                                <span class="font-medium">
                                                    {{
                                                        formatCurrency(
                                                            terminationFeeAmount,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                v-else-if="
                                                    isWithinYearlyHalfRefundWindow
                                                "
                                                class="flex justify-between text-slate-600"
                                            >
                                                <span class="text-slate-500">
                                                    Retained (50% of
                                                    {{
                                                        formatCurrency(
                                                            currentContractPrice,
                                                        )
                                                    }})
                                                </span>

                                                <span class="font-medium">
                                                    {{
                                                        formatCurrency(
                                                            halfYearlyPrice,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="border-t border-slate-200 pt-2 flex justify-between"
                                            >
                                                <span
                                                    class="font-semibold text-slate-700"
                                                >
                                                    Refund
                                                </span>

                                                <span
                                                    class="font-bold text-emerald-600"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            currentRefundAmount,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5"
                                >
                                    <p class="text-sm text-slate-500">
                                        No active billing period.
                                    </p>
                                </div>
                            </section>

                            <!-- FUTURE -->
                            <section class="flex flex-col">
                                <h4
                                    class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-slate-400"
                                >
                                    Future periods
                                </h4>

                                <div
                                    v-if="futureInvoiceCount > 0"
                                    class="flex flex-1 flex-col rounded-xl border border-slate-200 bg-white p-5"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-base font-semibold text-primary-950"
                                        >
                                            {{ futureInvoiceCount }}
                                            {{
                                                futureInvoiceCount === 1
                                                    ? "period"
                                                    : "periods"
                                            }}
                                            not started
                                        </p>

                                        <span
                                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-700"
                                        >
                                            Upcoming
                                        </span>
                                    </div>

                                    <div
                                        v-if="futureInvoices.length"
                                        class="mt-4 max-h-60 space-y-2.5 overflow-y-auto pr-1"
                                    >
                                        <div
                                            v-for="invoice in futureInvoices"
                                            :key="invoice.invoice_facility_id"
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <p
                                                            class="text-sm font-semibold text-primary-950"
                                                        >
                                                            {{
                                                                invoice.invoice_code
                                                            }}
                                                        </p>

                                                        <span
                                                            class="shrink-0 rounded-full border px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                                                            :class="
                                                                statusClasses(
                                                                    invoice.status,
                                                                )
                                                            "
                                                        >
                                                            {{ invoice.status }}
                                                        </span>
                                                    </div>

                                                    <p
                                                        class="mt-0.5 text-xs text-slate-500"
                                                    >
                                                        {{
                                                            formatDate(
                                                                invoice.start_date,
                                                            )
                                                        }}
                                                        –
                                                        {{
                                                            formatDate(
                                                                invoice.end_date,
                                                            )
                                                        }}
                                                    </p>

                                                    <div
                                                        class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs"
                                                    >
                                                        <span
                                                            class="text-slate-500"
                                                        >
                                                            Paid
                                                            {{
                                                                formatCurrency(
                                                                    invoice.paid_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            class="text-rose-500"
                                                        >
                                                            Refunded
                                                            {{
                                                                formatCurrency(
                                                                    invoice.refunded_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            class="font-medium text-emerald-600"
                                                        >
                                                            Refundable
                                                            {{
                                                                formatCurrency(
                                                                    invoice.net_paid_amount,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <p
                                                    class="shrink-0 text-sm font-semibold text-primary-950"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            invoice.price,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <label
                                        v-if="hasRefundableFutureInvoices"
                                        class="mt-5 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3.5 transition hover:border-primary/40 hover:bg-primary-50/40"
                                    >
                                        <input
                                            v-model="refund"
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                        />

                                        <div>
                                            <p
                                                class="text-sm font-medium text-slate-700"
                                            >
                                                Refund future periods
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs leading-5 text-slate-500"
                                            >
                                                Refund the upcoming periods that
                                                still have a balance.
                                            </p>
                                        </div>
                                    </label>

                                    <p
                                        v-else
                                        class="mt-5 text-xs leading-5 text-slate-500"
                                    >
                                        None of the upcoming periods have a
                                        refundable balance.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5"
                                >
                                    <p class="text-sm text-slate-500">
                                        No future billing periods to refund.
                                    </p>
                                </div>
                            </section>
                        </div>

                        <div
                            v-if="
                                showCurrentPeriodBlock &&
                                requiredPaymentAmount !== null
                            "
                            class="mt-6 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-5 py-4"
                        >
                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Required payment
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-5 text-slate-500"
                                >
                                    {{
                                        requiredPaymentDescription
                                    }}
                   
                                </p>
                            </div>

                            <div class="text-right">
                                <p
                                    class="text-lg font-bold"
                                    :class="
                                        isUnderRequiredPayment
                                            ? 'text-rose-600'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{ formatCurrency(requiredPaymentAmount) }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs"
                                    :class="
                                        isUnderRequiredPayment
                                            ? 'text-rose-500'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{
                                        isUnderRequiredPayment
                                            ? `Short by ${formatCurrency(
                                                  requiredPaymentShortfall,
                                              )}`
                                            : "Payment threshold met"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-8 py-5"
                    >
                        <div
                            v-if="
                                showCurrentPeriodBlock && isUnderRequiredPayment
                            "
                            class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3.5"
                        >
                            <div>
                                <p class="text-sm font-semibold text-amber-900">
                                    Payment required before discharge
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-5 text-amber-800"
                                >
                                    The patient has paid
                                    {{ formatCurrency(currentNetPaidAmount) }}
                                    but must have at least
                                    {{ formatCurrency(requiredPaymentAmount) }}
                                    paid before discharge.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button
                                type="button"
                                :disabled="loading"
                                class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200/60 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="handleClose"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                :disabled="loading || isUnderRequiredPayment"
                                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="handleConfirm"
                            >
                                <svg
                                    v-if="loading"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>

                                {{
                                    loading
                                        ? "Discharging…"
                                        : "Discharge patient"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Admission, InvoiceFacility } from "~/types/patient";
import { INVOICE_STATUS } from "~/types/invoice";
import { formatCurrency } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import { useDischargeRefund } from "~/composables/useDischargeRefund";

const props = withDefaults(
    defineProps<{
        open: boolean;
        admission?: Admission;
        futureInvoices?: InvoiceFacility[];
        loading?: boolean;
    }>(),
    {
        futureInvoices: () => [],
        loading: false,
    },
);

const emit = defineEmits<{
    confirm: [
        payload: {
            refund: boolean;
            currentRefundAmount: number | null;
        },
    ];
    cancel: [];
}>();

const refund = ref(false);

const currentInvoice = computed(() => {
    return props.admission?.current_invoice ?? null;
});

const {
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
} = useDischargeRefund(
    computed(() => props.admission),
    currentInvoice,
);

const futureInvoices = computed(() => {
    return props.futureInvoices ?? [];
});

const futureInvoiceCount = computed(() => {
    return futureInvoices.value.length;
});

function getNumber(value: unknown, fallback = 0): number {
    const number = Number(value);

    return Number.isFinite(number) ? number : fallback;
}

function getNetPaid(invoice?: InvoiceFacility | null): number {
    if (!invoice) {
        return 0;
    }

    return Math.max(0, getNumber(invoice.net_paid_amount));
}

function isRefundable(invoice?: InvoiceFacility | null): boolean {
    return getNetPaid(invoice) > 0;
}

const hasRefundableFutureInvoices = computed(() => {
    return futureInvoices.value.some((invoice) => isRefundable(invoice));
});

const showCurrentPeriodBlock = computed(() => {
    return !!currentInvoice.value;
});

const isCurrentInvoiceRefundable = computed(() => {
    return getNetPaid(currentInvoice.value) > 0;
});

const terminationFeePercent = computed(() => {
    return Math.round(terminationFeeRate.value * 100);
});

const canDischarge = computed(() => {
    if (!showCurrentPeriodBlock.value) {
        return true;
    }

    if (requiredPaymentAmount.value === null) {
        return true;
    }

    return !isUnderRequiredPayment.value;
});

function statusClasses(status?: string | null) {
    const s = (status ?? "").toLowerCase();

    return INVOICE_STATUS[s] ?? "bg-slate-50 text-slate-500 border-slate-200";
}

function handleConfirm() {
    if (props.loading) {
        return;
    }

    if (!canDischarge.value) {
        return;
    }

    proceedWithDischarge();
}

function proceedWithDischarge() {
    emit("confirm", {
        refund: refund.value && hasRefundableFutureInvoices.value,

        currentRefundAmount: isCurrentInvoiceRefundable.value
            ? currentRefundAmount.value
            : null,
    });
}

function handleClose() {
    if (props.loading) {
        return;
    }

    refund.value = false;

    emit("cancel");
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            refund.value = false;
        }
    },
);
</script>
