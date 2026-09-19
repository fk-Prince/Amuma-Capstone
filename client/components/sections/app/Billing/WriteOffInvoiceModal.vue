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
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-600 dark:text-amber-300"
                >
                    Write off invoice
                </p>

                <h3
                    class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                >
                    Write off {{ invoice.invoice_code }}?
                </h3>

                <p class="mt-2 text-xs leading-5 text-muted dark:text-gray-400">
                    The remaining ₱{{ formatMoney(invoice.balance_due) }}
                    balance will be marked as bad debt and will no longer count
                    toward the patient's balance. No credit or refund is created
                    for the patient. This cannot be undone.
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
                        :disabled="processing || !reason.trim()"
                        class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="emit('confirm')"
                    >
                        {{
                            processing ? "Writing off..." : "Write off invoice"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { formatAmount } from "~/utils/currency";
import type { PatientInvoiceItem } from "~/types/invoice";

defineProps<{
    invoice: PatientInvoiceItem | null;
    reason: string;
    processing: boolean;
}>();

const emit = defineEmits<{
    (event: "update:reason", reason: string): void;
    (event: "confirm"): void;
    (event: "close"): void;
}>();

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}
</script>
