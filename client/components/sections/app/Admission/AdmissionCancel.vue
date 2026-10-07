<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-primary-900/50 backdrop-blur-sm p-4"
            @click.self="handleClose"
        >
            <div
                class="w-full max-w-md rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 dark:bg-secondary dark:ring-primary-500/20"
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

                    <div
                        v-if="paidAmount > 0"
                        class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <span class="font-semibold">
                            {{ formatCurrency(paidAmount) }}
                        </span>
                        was paid for this admission. The amount kept below will
                        not be refunded; anything above it goes back to the
                        patient as credit. Are you sure you want to continue?

                        <label
                            for="keep-amount"
                            class="mt-3 block text-xs font-medium"
                        >
                            Amount to keep (not refunded)
                        </label>

                        <div class="relative mt-1">
                            <span
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted dark:text-gray-500"
                            >
                                ₱
                            </span>

                            <input
                                id="keep-amount"
                                v-model="keepAmount"
                                type="number"
                                inputmode="decimal"
                                min="0"
                                :max="paidAmount"
                                step="0.01"
                                :disabled="loading"
                                class="w-full rounded-xl border border-primary-100 bg-white py-2 pl-7 pr-3 text-sm text-secondary focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                @blur="clampKeepAmount"
                            />
                        </div>

                        <p
                            class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-300/70"
                        >
                            Cannot exceed the {{ formatCurrency(paidAmount) }}
                            paid.
                        </p>

                        <p
                            v-if="keepAmountError"
                            class="mt-1 text-xs text-rose-600 dark:text-rose-300"
                        >
                            {{ keepAmountError }}
                        </p>
                    </div>

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
import { formatCurrency } from "~/utils/currency";

const props = withDefaults(
    defineProps<{
        open: boolean;
        loading?: boolean;
        paidAmount?: number;
    }>(),
    { paidAmount: 0 },
);

const QUICK_REASONS = [
    "Patient didn't show up",
    "Patient requested cancellation",
    "Unable to reach the family",
];

const emit = defineEmits<{
    (e: "confirm", reason: string, keepAmount: number | null): void;
    (e: "cancel"): void;
}>();

const cancellationReason = ref("");
const cancellationReasonError = ref("");
const keepAmount = ref<number | string>(0);
const keepAmountError = ref("");

function clampKeepAmount() {
    const value = Number(keepAmount.value);

    if (!Number.isFinite(value) || value < 0) {
        keepAmount.value = 0;
    } else if (value > props.paidAmount) {
        keepAmount.value = props.paidAmount;
    }

    keepAmountError.value = "";
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            cancellationReason.value = "";
            cancellationReasonError.value = "";
            keepAmount.value = props.paidAmount;
            keepAmountError.value = "";
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

    if (props.paidAmount > 0) {
        const keep = Number(keepAmount.value);

        if (!Number.isFinite(keep) || keep < 0 || keep > props.paidAmount) {
            keepAmountError.value = `Enter an amount from 0 to ${formatCurrency(props.paidAmount)}.`;
            return;
        }

        emit("confirm", reason, Math.round(keep * 100) / 100);
        return;
    }

    emit("confirm", reason, null);
}
</script>
