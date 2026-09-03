<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-primary-950/60 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-4xl max-h-[90dvh] overflow-y-auto rounded-2xl bg-white shadow-[0_20px_60px_-15px_rgba(10,40,87,0.35)] ring-1 ring-black/5 dark:bg-secondary"
                >
                    <div
                        class="flex items-start gap-4 border-b border-slate-100 px-4 sm:px-8 py-5 sm:py-7 dark:border-white/10"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600 ring-1 ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20"
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
                                class="text-xl font-semibold tracking-tight text-primary-950 dark:text-primary-300"
                            >
                                Discharge patient
                            </h3>

                            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                                This ends the patient's current admission and
                                future periods. This action cannot be undone.
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="loading"
                            class="ml-auto flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
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
                    <div class="px-4 sm:px-8 py-5 sm:py-7">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- CURRENT -->
                            <section class="flex flex-col">
                                <h4
                                    class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    Current period
                                </h4>

                                <div
                                    v-if="showCurrentPeriodBlock"
                                    class="flex flex-1 flex-col rounded-xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-base font-semibold text-primary-950 dark:text-primary-300"
                                            >
                                                {{ currentBillingCycleLabel }}
                                                billing
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
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
                                            class="shrink-0 text-base font-semibold text-primary-950 dark:text-primary-300"
                                        >
                                            {{
                                                formatCurrency(
                                                    currentContractPrice,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <!-- PAYMENT SUMMARY -->
                                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-white/5">
                                            <p
                                                class="text-[11px] text-slate-500 dark:text-gray-400"
                                            >
                                                Paid
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold text-primary-950 dark:text-primary-300"
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
                                                    ? 'bg-emerald-50 dark:bg-emerald-500/10'
                                                    : 'bg-slate-100 dark:bg-white/10'
                                            "
                                        >
                                            <p
                                                class="text-[11px]"
                                                :class="
                                                    isEligibleForRefund
                                                        ? 'text-emerald-700 dark:text-emerald-300'
                                                        : 'text-slate-400 dark:text-gray-500'
                                                "
                                            >
                                                Refundable
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold"
                                                :class="
                                                    isEligibleForRefund
                                                        ? 'text-emerald-700 dark:text-emerald-300'
                                                        : 'text-slate-400 dark:text-gray-500'
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
                                        class="mt-5 rounded-lg border p-3.5 dark:border-white/10"
                                        :class="
                                            isEligibleForRefund
                                                ? 'border-rose-200 bg-rose-50 dark:border-rose-500/20 dark:bg-rose-500/10'
                                                : 'border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/5'
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
                                                                ? 'text-rose-800 dark:text-rose-300'
                                                                : 'text-slate-600 dark:text-gray-400'
                                                        "
                                                    >
                                                        {{ refundPolicyTitle }}
                                                    </p>

                                                    <span
                                                        class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                                                        :class="
                                                            isEligibleForRefund
                                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'
                                                                : 'bg-slate-200 text-slate-600 dark:bg-white/15 dark:text-gray-400'
                                                        "
                                                    >
                                                        {{ refundPolicyBadge }}
                                                    </span>
                                                </div>

                                                <p
                                                    class="mt-1.5 text-xs leading-5"
                                                    :class="
                                                        isEligibleForRefund
                                                            ? 'text-rose-700 dark:text-rose-300'
                                                            : 'text-slate-500 dark:text-gray-400'
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
                                        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5"
                                    >
                                        <p
                                            class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                        >
                                            Refund calculation
                                        </p>

                                        <div class="mt-3 space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-slate-500 dark:text-gray-400">
                                                    Amount paid
                                                </span>

                                                <span
                                                    class="font-medium text-slate-700 dark:text-gray-400"
                                                >
                                                    <div
                                                        class="flex justify-between"
                                                    >
                                                        <span
                                                            class="font-medium text-slate-700 dark:text-gray-400"
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
                                                class="flex justify-between text-slate-600 dark:text-gray-400"
                                            >
                                                <span class="text-slate-500 dark:text-gray-400">
                                                    Termination fee ({{
                                                        terminationFeePercent
                                                    }}% of
                                                    {{
                                                        formatCurrency(
                                                            feeBaseAmount,
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
                                                class="space-y-2"
                                            >
                                                <div
                                                    class="flex justify-between text-slate-600 dark:text-gray-400"
                                                >
                                                    <span
                                                        class="text-slate-500 dark:text-gray-400"
                                                    >
                                                        Half of price ({{
                                                            terminationFeePercent
                                                        }}% of
                                                        {{
                                                            formatCurrency(
                                                                feeBaseAmount,
                                                            )
                                                        }})
                                                    </span>

                                                    <span
                                                        class="font-medium"
                                                    >
                                                        {{
                                                            formatCurrency(
                                                                (feeBaseAmount *
                                                                    terminationFeePercent) /
                                                                    100,
                                                            )
                                                        }}
                                                    </span>
                                                </div>

                                                <div
                                                    class="flex justify-between text-slate-600 dark:text-gray-400"
                                                >
                                                    <span
                                                        class="text-slate-500 dark:text-gray-400"
                                                    >
                                                        Days stayed ({{
                                                            daysSinceAdmissionStart
                                                        }}
                                                        {{
                                                            daysSinceAdmissionStart ===
                                                            1
                                                                ? "day"
                                                                : "days"
                                                        }})
                                                    </span>

                                                    <span
                                                        class="font-medium"
                                                    >
                                                        −
                                                        {{
                                                            formatCurrency(
                                                                daysStayedAmount,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                class="border-t border-slate-200 pt-2 flex justify-between dark:border-white/10"
                                            >
                                                <span
                                                    class="font-semibold text-slate-700 dark:text-gray-400"
                                                >
                                                    Refund
                                                </span>

                                                <span
                                                    class="font-bold text-emerald-600 dark:text-emerald-300"
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
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5"
                                >
                                    <p class="text-sm text-slate-500 dark:text-gray-400">
                                        No active billing period.
                                    </p>
                                </div>
                            </section>

                            <!-- FUTURE -->
                            <section class="flex flex-col">
                                <h4
                                    class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    Future periods
                                </h4>

                                <div
                                    v-if="futureInvoiceCount > 0"
                                    class="flex flex-1 flex-col rounded-xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-base font-semibold text-primary-950 dark:text-primary-300"
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
                                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
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
                                            :key="invoice.invoice_accommodation_id"
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-white/5"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <p
                                                            class="text-sm font-semibold text-primary-950 dark:text-primary-300"
                                                        >
                                                            {{
                                                                invoice.invoice_code
                                                            }}
                                                        </p>

                                                        <span
                                                            class="shrink-0 rounded-full border px-1.5 py-0.5 text-[10px] font-semibold uppercase dark:border-white/10"
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
                                                        class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
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
                                                            class="text-slate-500 dark:text-gray-400"
                                                        >
                                                            Paid
                                                            {{
                                                                formatCurrency(
                                                                    invoice.paid_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            class="text-rose-500 dark:text-rose-300"
                                                        >
                                                            Refunded
                                                            {{
                                                                formatCurrency(
                                                                    invoice.refunded_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            class="font-medium text-emerald-600 dark:text-emerald-300"
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
                                                    class="shrink-0 text-sm font-semibold text-primary-950 dark:text-primary-300"
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
                                        class="mt-5 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3.5 transition hover:border-primary/40 hover:bg-primary-50/40 dark:border-white/10 dark:hover:bg-primary-500/10"
                                    >
                                        <input
                                            v-model="refund"
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary dark:border-white/10"
                                        />

                                        <div>
                                            <p
                                                class="text-sm font-medium text-slate-700 dark:text-gray-400"
                                            >
                                                Refund future periods
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                            >
                                                Refund the upcoming periods that
                                                still have a balance.
                                            </p>
                                        </div>
                                    </label>

                                    <p
                                        v-else
                                        class="mt-5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                    >
                                        None of the upcoming periods have a
                                        refundable balance.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5"
                                >
                                    <p class="text-sm text-slate-500 dark:text-gray-400">
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
                            class="mt-6 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-gray-400">
                                    Required payment
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    {{ requiredPaymentDescription }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p
                                    class="text-lg font-bold"
                                    :class="
                                        isUnderRequiredPayment
                                            ? 'text-rose-600 dark:text-rose-300'
                                            : 'text-emerald-600 dark:text-emerald-300'
                                    "
                                >
                                    {{ formatCurrency(requiredPaymentAmount) }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs"
                                    :class="
                                        isUnderRequiredPayment
                                            ? 'text-rose-500 dark:text-rose-300'
                                            : 'text-emerald-600 dark:text-emerald-300'
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

                        <div class="mt-6">
                            <label
                                for="discharge-note"
                                class="block text-sm font-semibold text-slate-700 mb-2 dark:text-gray-400"
                            >
                                Discharge note
                                <span class="font-normal text-slate-400 dark:text-gray-500">
                                    (optional)
                                </span>
                            </label>

                            <textarea
                                id="discharge-note"
                                v-model="dischargeNote"
                                rows="3"
                                :disabled="loading"
                                placeholder="Why is this patient being discharged?"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 outline-none resize-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-slate-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500 dark:disabled:bg-white/5"
                            />
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 sm:px-8 py-5 dark:border-white/10 dark:bg-white/5"
                    >
                        <div
                            v-if="
                                showCurrentPeriodBlock && isUnderRequiredPayment
                            "
                            class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3.5 dark:border-amber-500/20 dark:bg-amber-500/10"
                        >
                            <div>
                                <p class="text-sm font-semibold text-amber-900 dark:text-amber-300">
                                    Payment required before discharge
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-5 text-amber-800 dark:text-amber-300"
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
                                class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200/60 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/15"
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
import type { Admission, InvoiceAccommodation } from "~/types/patient";
import { INVOICE_STATUS } from "~/types/invoice";
import { formatCurrency } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import { useDischargeRefund } from "~/composables/useDischargeRefund";

const props = withDefaults(
    defineProps<{
        open: boolean;
        admission?: Admission;
        futureInvoices?: InvoiceAccommodation[];
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
            note: string;
        },
    ];
    cancel: [];
}>();

const refund = ref(false);
const dischargeNote = ref("");

const currentInvoice = computed(() => {
    return props.admission?.current_invoice ?? null;
});

const {
    currentNetPaidAmount,
    currentContractPrice,
    currentBillingCycleLabel,
    terminationFeePercent,
    terminationFeeAmount,
    daysStayedAmount,
    daysSinceAdmissionStart,
    feeBaseAmount,
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
} = useDischargeRefund(computed(() => props.admission));

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

function getNetPaid(invoice?: InvoiceAccommodation | null): number {
    if (!invoice) {
        return 0;
    }

    return Math.max(0, getNumber(invoice.net_paid_amount));
}

function isRefundable(invoice?: InvoiceAccommodation | null): boolean {
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

    return INVOICE_STATUS[s] ?? "bg-slate-50 text-slate-500 border-slate-200 dark:bg-white/5 dark:text-gray-400 dark:border-white/10";
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

        note: dischargeNote.value.trim(),
    });
}

function handleClose() {
    if (props.loading) {
        return;
    }

    refund.value = false;
    dischargeNote.value = "";

    emit("cancel");
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            refund.value = false;
            dischargeNote.value = "";
        }
    },
);
</script>
