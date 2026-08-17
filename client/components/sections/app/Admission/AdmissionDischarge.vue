<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-primary-900/50 p-4 backdrop-blur-sm"
                @click.self="handleClose"
            >
                <div
                    class="w-full max-w-3xl rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60"
                >
                    <div class="flex items-start gap-4 p-7 pb-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-6 w-6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14A2 2 0 003.84 21h16.32a2 2 0 001.73-3.14l-8.18-14a2 2 0 00-3.42 0z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3 class="text-xl font-semibold text-primary-900">
                                Discharge Patient
                            </h3>

                            <p class="mt-1 text-base text-slate-500">
                                Are you sure you want to discharge this patient?
                            </p>
                        </div>
                    </div>

                    <div class="px-7 pb-7">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <!-- Current billing period -->
                            <div class="flex flex-col">
                                <p
                                    class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Current period
                                </p>

                                <div
                                    v-if="showCurrentPeriodBlock"
                                    class="flex flex-1 flex-col rounded-xl border border-amber-200 bg-amber-50 p-4"
                                >
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                class="h-4 w-4"
                                            >
                                                <circle cx="12" cy="12" r="9" />
                                                <path
                                                    stroke-linecap="round"
                                                    d="M12 7v5l3 2"
                                                />
                                            </svg>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <p
                                                    class="text-base font-semibold text-amber-900"
                                                >
                                                    Billing period
                                                </p>

                                                <p
                                                    class="shrink-0 text-sm font-semibold text-amber-900"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            currentContractPrice,
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <p
                                                class="mt-1 text-sm leading-5 text-amber-800"
                                            >
                                                <span
                                                    v-if="
                                                        isCurrentInvoiceRefundable
                                                    "
                                                >
                                                    This invoice has a
                                                    refundable balance.
                                                </span>

                                                <span v-else>
                                                    This invoice has no
                                                    refundable balance.
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="isCurrentInvoiceRefundable"
                                        class="mt-4 grid grid-cols-3 gap-2"
                                    >
                                        <div
                                            class="rounded-lg border border-amber-200 bg-white p-2.5"
                                        >
                                            <p class="text-xs text-slate-500">
                                                Paid
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold text-primary-900"
                                            >
                                                {{
                                                    formatCurrency(
                                                        currentInvoice?.paid_amount,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-lg border border-amber-200 bg-white p-2.5"
                                        >
                                            <p class="text-xs text-slate-500">
                                                Refunded
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold text-rose-600"
                                            >
                                                {{
                                                    formatCurrency(
                                                        currentInvoice?.refunded_amount,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-lg border border-amber-200 bg-white p-2.5"
                                        >
                                            <p class="text-xs text-slate-500">
                                                Refundable
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold text-emerald-600"
                                            >
                                                {{
                                                    formatCurrency(
                                                        currentNetPaidAmount,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="isCurrentInvoiceRefundable"
                                        class="mt-4"
                                    >
                                        <label
                                            class="mb-1.5 block text-sm font-medium text-primary-900"
                                        >
                                            Refund amount
                                        </label>

                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400"
                                            >
                                                ₱
                                            </span>

                                            <input
                                                v-model.number="
                                                    currentRefundAmount
                                                "
                                                type="number"
                                                min="0"
                                                :max="currentNetPaidAmount"
                                                step="0.01"
                                                class="w-full rounded-xl border bg-white py-2.5 pl-7 pr-3 text-base text-primary-900 outline-none transition focus:ring-2"
                                                :class="
                                                    isCurrentRefundAmountValid
                                                        ? 'border-slate-200 focus:border-primary focus:ring-primary/10'
                                                        : 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                "
                                            />
                                        </div>

                                        <p
                                            v-if="!isCurrentRefundAmountValid"
                                            class="mt-1.5 text-xs font-medium text-rose-600"
                                        >
                                            {{ refundAmountErrorMessage }}
                                        </p>

                                        <div
                                            class="mt-2 flex items-center justify-between gap-2"
                                        >
                                            <p class="text-xs text-slate-500">
                                                Suggested: half of net paid ({{
                                                    formatCurrency(
                                                        currentNetPaidAmount,
                                                    )
                                                }})
                                            </p>

                                            <button
                                                type="button"
                                                class="shrink-0 text-xs font-medium text-primary hover:underline"
                                                @click="applySuggestedRefund"
                                            >
                                                Use
                                                {{
                                                    formatCurrency(
                                                        suggestedRefundAmount,
                                                    )
                                                }}
                                            </button>
                                        </div>
                                    </div>

                                    <p
                                        v-else
                                        class="mt-3 inline-flex w-fit rounded-full border px-1.5 py-0.5 text-xs font-medium uppercase tracking-wide"
                                        :class="
                                            statusClasses(
                                                currentInvoice?.status,
                                            )
                                        "
                                    >
                                        Status:
                                        {{ currentInvoice?.status ?? "—" }}
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            class="h-4 w-4"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 12h14"
                                            />
                                        </svg>
                                    </div>

                                    <p class="text-base text-slate-600">
                                        No active billing period.
                                    </p>
                                </div>
                            </div>

                            <!-- Future billing periods -->
                            <div class="flex flex-col">
                                <p
                                    class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Future periods
                                </p>

                                <div
                                    v-if="futureInvoiceCount > 0"
                                    class="flex flex-1 flex-col rounded-xl border border-amber-200 bg-amber-50 p-4"
                                >
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                class="h-4 w-4"
                                            >
                                                <circle cx="12" cy="12" r="9" />
                                                <path
                                                    stroke-linecap="round"
                                                    d="M12 7v5l3 2"
                                                />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="text-base font-semibold text-amber-900"
                                            >
                                                {{ futureInvoiceCount }}
                                                {{
                                                    futureInvoiceCount === 1
                                                        ? "billing period"
                                                        : "billing periods"
                                                }}
                                                has not started yet.
                                            </p>

                                            <p
                                                class="mt-1 text-sm leading-5 text-amber-800"
                                            >
                                                There are future invoices
                                                associated with this patient's
                                                admission.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="futureInvoices.length"
                                        class="mt-4 max-h-64 space-y-2 overflow-y-auto pr-1"
                                    >
                                        <div
                                            v-for="invoice in futureInvoices"
                                            :key="invoice.invoice_facility_id"
                                            class="rounded-lg border border-amber-200 bg-white px-3 py-2.5"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <p
                                                            class="text-sm font-semibold text-primary-900"
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
                                                        —
                                                        {{
                                                            formatDate(
                                                                invoice.end_date,
                                                            )
                                                        }}
                                                    </p>

                                                    <div
                                                        class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs"
                                                    >
                                                        <span
                                                            class="text-slate-500"
                                                        >
                                                            Paid:
                                                            {{
                                                                formatCurrency(
                                                                    invoice.paid_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            class="text-rose-500"
                                                        >
                                                            Refunded:
                                                            {{
                                                                formatCurrency(
                                                                    invoice.refunded_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            class="font-medium text-emerald-600"
                                                        >
                                                            Refundable:
                                                            {{
                                                                formatCurrency(
                                                                    invoice.net_paid_amount,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <p
                                                    class="shrink-0 text-sm font-semibold text-primary-900"
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
                                        class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-white p-3 transition hover:bg-amber-50"
                                    >
                                        <input
                                            v-model="refund"
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                        />

                                        <div>
                                            <p
                                                class="text-base font-medium text-slate-700"
                                            >
                                                Refund future invoices
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm leading-5 text-slate-500"
                                            >
                                                Refund future billing periods
                                                that still have a refundable
                                                balance.
                                            </p>
                                        </div>
                                    </label>

                                    <p
                                        v-else
                                        class="mt-3 text-xs leading-5 text-amber-800"
                                    >
                                        None of the future invoices have a
                                        refundable balance.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            class="h-4 w-4"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 12h14"
                                            />
                                        </svg>
                                    </div>

                                    <p class="text-base text-slate-600">
                                        There are no future billing periods to
                                        refund.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3"
                        >
                            <p class="text-sm leading-5 text-rose-700">
                                Discharging the patient will end their current
                                admission. This action cannot be undone.
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                :disabled="loading"
                                class="rounded-lg px-4 py-2 text-base font-medium text-slate-500 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="handleClose"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                :disabled="loading || !canConfirm"
                                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-base font-medium text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
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
                                        ? "Discharging..."
                                        : "Discharge Patient"
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

const props = withDefaults(
    defineProps<{
        open: boolean;
        admission?: Admission | null;
        futureInvoices?: InvoiceFacility[];
        loading?: boolean;
    }>(),
    {
        admission: null,
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
const currentRefundAmount = ref<number | null>(null);

function getNetPaid(invoice?: InvoiceFacility | null): number {
    if (!invoice) return 0;

    const amount = Number(invoice.net_paid_amount);

    return Number.isNaN(amount) ? 0 : Math.max(0, amount);
}

function isRefundable(invoice?: InvoiceFacility | null): boolean {
    return getNetPaid(invoice) > 0;
}

function statusClasses(status?: string | null) {
    const s = (status ?? "").toLowerCase();

    return INVOICE_STATUS[s] ?? "bg-slate-50 text-slate-500 border-slate-200";
}

const futureInvoices = computed(() => props.futureInvoices ?? []);

const futureInvoiceCount = computed(() => futureInvoices.value.length);

const hasRefundableFutureInvoices = computed(() =>
    futureInvoices.value.some((invoice) => isRefundable(invoice)),
);

const currentInvoice = computed(() => {
    return props.admission?.current_invoice ?? null;
});

const currentNetPaidAmount = computed(() => {
    return getNetPaid(currentInvoice.value);
});

const currentContractPrice = computed(() => {
    const price =
        currentInvoice.value?.contract?.price ??
        props.admission?.current_contract?.price;

    const num = Number(price);

    return Number.isNaN(num) ? null : num;
});

const isCurrentInvoiceRefundable = computed(() => {
    return isRefundable(currentInvoice.value);
});

const isCurrentYearly = computed(() => {
    const cycle =
        currentInvoice.value?.contract?.billing_cycle ??
        props.admission?.current_contract?.billing_cycle;

    return (cycle ?? "").toUpperCase() === "YEARLY";
});

const currentPeriodElapsedRatio = computed(() => {
    const invoice = currentInvoice.value;

    if (!invoice?.start_date || !invoice?.end_date) {
        return null;
    }

    const start = new Date(invoice.start_date).getTime();
    const end = new Date(invoice.end_date).getTime();
    const now = Date.now();

    if (Number.isNaN(start) || Number.isNaN(end) || end <= start) {
        return null;
    }

    const ratio = (now - start) / (end - start);

    return Math.min(1, Math.max(0, ratio));
});

const showCurrentPeriodBlock = computed(() => !!currentInvoice.value);

const isEligibleForCurrentRefund = computed(() => {
    if (!currentInvoice.value) return false;
    if (!isCurrentInvoiceRefundable.value) return false;
    if (!isCurrentYearly.value) return false;

    const ratio = currentPeriodElapsedRatio.value;

    if (ratio === null) return false;

    return ratio < 0.5;
});

const suggestedRefundAmount = computed(() => {
    return Math.round((currentNetPaidAmount.value / 2) * 100) / 100;
});

const isCurrentRefundAmountValid = computed(() => {
    if (!isCurrentInvoiceRefundable.value) {
        return true;
    }

    if (currentRefundAmount.value === null) {
        return false;
    }

    const amount = Number(currentRefundAmount.value);

    return (
        !Number.isNaN(amount) &&
        amount >= 0 &&
        amount <= currentNetPaidAmount.value
    );
});

const refundAmountErrorMessage = computed(() => {
    if (currentRefundAmount.value === null) {
        return "";
    }

    const amount = Number(currentRefundAmount.value);

    if (Number.isNaN(amount)) {
        return "Enter a valid number.";
    }

    if (amount < 0) {
        return "Refund amount cannot be negative.";
    }

    return `Refund amount cannot exceed ${formatCurrency(currentNetPaidAmount.value)}.`;
});

const canConfirm = computed(() => isCurrentRefundAmountValid.value);

function applySuggestedRefund() {
    currentRefundAmount.value = suggestedRefundAmount.value;
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            refund.value = false;
            currentRefundAmount.value = null;
        }
    },
);

watch(
    [currentInvoice, isCurrentInvoiceRefundable, isEligibleForCurrentRefund],
    () => {
        if (
            isCurrentInvoiceRefundable.value &&
            currentRefundAmount.value === null
        ) {
            currentRefundAmount.value = isEligibleForCurrentRefund.value
                ? suggestedRefundAmount.value
                : null;
        }
    },
);

function handleConfirm() {
    if (!canConfirm.value) return;

    const refundAmount = isCurrentInvoiceRefundable.value
        ? Number(currentRefundAmount.value)
        : null;

    emit("confirm", {
        refund: refund.value && hasRefundableFutureInvoices.value,
        currentRefundAmount: refundAmount,
    });
}

function handleClose() {
    if (props.loading) return;

    refund.value = false;
    currentRefundAmount.value = null;

    emit("cancel");
}

function formatDate(value?: string | null) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatCurrency(value?: string | number | null) {
    if (value === undefined || value === null || value === "") {
        return "—";
    }

    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return String(value);
    }

    return amount.toLocaleString(undefined, {
        style: "currency",
        currency: "PHP",
    });
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.97) translateY(4px);
}
</style>
