<template>
    <Teleport to="body">
        <div
            v-if="invoice"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
            >
                <p
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary"
                >
                    {{ isRefund ? "Issue refund/credit" : "Adjust invoice" }}
                </p>

                <h3
                    class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                >
                    {{ isRefund ? "Refund/credit" : "Adjust" }}
                    {{ invoice.invoice_code }}
                </h3>

                <p class="mt-2 text-xs leading-5 text-muted dark:text-gray-400">
                    <template v-if="isRefund">
                        This reduces the invoice's billed total and turns the
                        difference into credit on the patient's account, which
                        can be withdrawn or applied to a future invoice.
                    </template>

                    <template v-else>
                        Deducting lowers what's owed on this invoice — it does
                        not refund any payment already made. Adding raises
                        what's owed on this invoice.
                    </template>
                </p>

                <div v-if="!isRefund" class="mt-3 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
                        :class="
                            direction === 'deduct'
                                ? 'border-danger/40 bg-danger/10 text-danger'
                                : 'border-slate-200 text-muted hover:bg-slate-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5'
                        "
                        @click="emit('update:direction', 'deduct')"
                    >
                        Deduct
                    </button>

                    <button
                        type="button"
                        class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
                        :class="
                            direction === 'add'
                                ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-600 dark:text-emerald-300'
                                : 'border-slate-200 text-muted hover:bg-slate-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5'
                        "
                        @click="emit('update:direction', 'add')"
                    >
                        Add
                    </button>
                </div>

                <div
                    class="mt-3 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs text-muted dark:bg-white/5 dark:text-gray-400"
                >
                    <span>Current billed total</span>
                    <span class="font-semibold text-secondary dark:text-white">
                        ₱{{ formatMoney(invoice.adjusted_total ?? invoice.total) }}
                    </span>
                </div>

                <label
                    class="mt-4 block text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                >
                    {{
                        isRefund
                            ? "Refund/Credit Amount"
                            : direction === "add"
                              ? "Amount to Add"
                              : "Amount to Deduct"
                    }}
                    <span class="text-danger">*</span>
                </label>

                <div class="relative mt-1.5">
                    <span
                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted dark:text-gray-500"
                    >
                        ₱
                    </span>

                    <input
                        :value="amount"
                        type="number"
                        min="0"
                        :max="direction === 'deduct' ? maxAmount || undefined : undefined"
                        step="0.01"
                        class="w-full rounded-xl border border-primary-100 py-2 pl-7 pr-3 text-sm text-secondary focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        @input="
                            emit(
                                'update:amount',
                                Number(
                                    ($event.target as HTMLInputElement).value,
                                ) || 0,
                            )
                        "
                    />
                </div>

                <p
                    v-if="direction === 'deduct' && maxAmount > 0"
                    class="mt-1.5 text-[11px] text-muted dark:text-gray-500"
                >
                    Cannot exceed the current billed total of ₱{{
                        formatMoney(maxAmount)
                    }}.
                </p>

                <label
                    class="mt-4 block text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                >
                    Reason
                    <span class="text-danger">*</span>
                </label>

                <textarea
                    :value="reason"
                    rows="2"
                    class="mt-1.5 w-full rounded-xl border border-primary-100 px-3 py-2 text-sm text-secondary focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    @input="
                        emit(
                            'update:reason',
                            ($event.target as HTMLTextAreaElement).value,
                        )
                    "
                />

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-slate-100 hover:text-secondary disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="processing || !reason.trim() || !(amount > 0)"
                        class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="emit('confirm')"
                    >
                        {{
                            processing
                                ? isRefund
                                    ? "Issuing..."
                                    : "Adjusting..."
                                : isRefund
                                  ? "Issue refund/credit"
                                  : "Adjust invoice"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatAmount } from "~/utils/currency";
import type { PatientInvoiceItem } from "~/types/invoice";

const props = defineProps<{
    invoice: PatientInvoiceItem | null;
    amount: number;
    direction: "add" | "deduct";
    reason: string;
    processing: boolean;
}>();

const emit = defineEmits<{
    (event: "update:amount", amount: number): void;
    (event: "update:direction", direction: "add" | "deduct"): void;
    (event: "update:reason", reason: string): void;
    (event: "confirm"): void;
    (event: "close"): void;
}>();

const maxAmount = computed(() =>
    Number(props.invoice?.adjusted_total ?? props.invoice?.total ?? 0),
);

const isRefund = computed(
    () => (props.invoice?.status ?? "").toLowerCase() === "paid",
);

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}
</script>
