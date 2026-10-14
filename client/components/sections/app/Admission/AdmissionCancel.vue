<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-primary-900/50 backdrop-blur-sm p-4"
            @click.self="handleClose"
        >
            <div
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 dark:bg-secondary dark:ring-primary-500/20"
            >
                <div
                    class="border-b border-primary-100 p-6 dark:border-primary-500/20"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3
                                class="text-base font-semibold text-primary-900 dark:text-primary-300"
                            >
                                Cancel Admission
                            </h3>

                            <p
                                class="text-xs text-muted mt-1 dark:text-gray-400"
                            >
                                Please provide a reason for cancelling this
                                admission.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600 transition dark:text-gray-500 dark:hover:text-gray-400"
                            :disabled="loading"
                            @click="handleClose"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-5 h-5"
                            >
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div>
                        <label
                            for="cancellation-reason"
                            class="block text-xs font-medium text-primary-900 mb-2 dark:text-primary-300"
                        >
                            Cancellation reason
                        </label>

                        <textarea
                            id="cancellation-reason"
                            v-model="cancellationReason"
                            rows="4"
                            :disabled="loading"
                            placeholder="Enter the reason for cancelling this admission..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-primary-900 placeholder:text-slate-400 outline-none resize-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-slate-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500 dark:disabled:bg-white/5"
                        />

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span
                                class="text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                Quick fill:
                            </span>

                            <button
                                v-for="reason in QUICK_REASONS"
                                :key="reason"
                                type="button"
                                :disabled="loading"
                                class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition disabled:opacity-40"
                                :class="
                                    cancellationReason === reason
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-slate-200 text-slate-500 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-400'
                                "
                                @click="cancellationReason = reason"
                            >
                                {{ reason }}
                            </button>
                        </div>

                        <p
                            v-if="cancellationReasonError"
                            class="mt-1.5 text-xs text-rose-600 dark:text-rose-300"
                        >
                            {{ cancellationReasonError }}
                        </p>
                    </div>

                    <CancelInvoiceAmount
                        v-if="invoiceCode || paidAmount > 0"
                        v-model:amount="keepAmount"
                        v-model:reason="keepReason"
                        v-model:editing="editingAmount"
                        class="mt-4"
                        :invoice-code="invoiceCode"
                        :billed="Math.max(invoiceTotal, paidAmount)"
                        :paid="paidAmount"
                        :max="paidAmount"
                        :default-amount="paidAmount"
                        amount-label="Amount to keep"
                        :max-hint="`the ${formatCurrency(paidAmount)} paid`"
                        :disabled="loading"
                        :amount-error="keepAmountError"
                        :reason-error="keepReasonError"
                    />

                    <div
                        class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2"
                    >
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition disabled:opacity-40 dark:text-gray-400 dark:hover:text-gray-400"
                            :disabled="loading"
                            @click="handleClose"
                        >
                            Keep Admission
                        </button>

                        <button
                            type="button"
                            class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-rose-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="loading || !cancellationReason.trim()"
                            @click="handleConfirm"
                        >
                            {{ loading ? "Cancelling..." : "Cancel Admission" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import CancelInvoiceAmount from "~/components/sections/app/Billing/CancelInvoiceAmount.vue";
import { formatCurrency } from "~/utils/currency";

const props = withDefaults(
    defineProps<{
        open: boolean;
        loading?: boolean;
        paidAmount?: number;
        invoiceCode?: string | null;
        invoiceTotal?: number;
    }>(),
    { paidAmount: 0, invoiceCode: null, invoiceTotal: 0 },
);

const QUICK_REASONS = [
    "Patient didn't show up",
    "Patient requested cancellation",
    "Unable to reach the family",
];

const emit = defineEmits<{
    (
        e: "confirm",
        reason: string,
        keepAmount: number | null,
        keepReason: string | null,
    ): void;
    (e: "cancel"): void;
}>();

const cancellationReason = ref("");
const cancellationReasonError = ref("");
const keepAmount = ref<number | string>(0);
const keepAmountError = ref("");
const keepReason = ref("");
const keepReasonError = ref("");
const editingAmount = ref(false);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            cancellationReason.value = "";
            cancellationReasonError.value = "";
            keepAmount.value = props.paidAmount;
            keepAmountError.value = "";
            keepReason.value = "";
            keepReasonError.value = "";
            editingAmount.value = false;
        }
    },
);

function handleClose() {
    if (props.loading) return;
    emit("cancel");
}

function handleConfirm() {
    const reason = cancellationReason.value.trim();

    if (!reason) {
        cancellationReasonError.value =
            "Please provide a reason for cancelling this admission.";
        return;
    }

    if (reason.length < 3) {
        cancellationReasonError.value =
            "Cancellation reason must be at least 3 characters.";
        return;
    }

    cancellationReasonError.value = "";
    keepAmountError.value = "";
    keepReasonError.value = "";

    if (props.paidAmount > 0) {
        const keep = Number(keepAmount.value);

        if (!Number.isFinite(keep) || keep < 0 || keep > props.paidAmount) {
            keepAmountError.value = `Enter an amount from 0 to ${formatCurrency(props.paidAmount)}.`;
            return;
        }

        const kept = Math.round(keep * 100) / 100;

        if (kept < props.paidAmount) {
            const why = keepReason.value.trim();

            if (why.length < 3) {
                keepReasonError.value =
                    "Give a reason for changing the amount.";
                return;
            }

            emit("confirm", reason, kept, why);
            return;
        }

        emit("confirm", reason, kept, null);
        return;
    }

    emit("confirm", reason, null, null);
}
</script>
