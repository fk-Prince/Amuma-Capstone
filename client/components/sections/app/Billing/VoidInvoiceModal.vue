<template>
    <Teleport to="body">
        <div
            v-if="invoice"
            class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
            >
                <p
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                >
                    Void invoice
                </p>

                <h3
                    class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                >
                    Void {{ invoice.invoice_code }}?
                </h3>

                <p class="mt-2 text-xs leading-5 text-muted dark:text-gray-400">
                    <template v-if="Number(invoice.amount_paid ?? 0) > 0">
                        This invoice will be voided and the ₱{{
                            formatMoney(invoice.amount_paid)
                        }}
                        already paid on it will be transferred to the patient's
                        credit. This cannot be undone.
                    </template>

                    <template v-else>
                        This invoice will be voided, and if there is any payment
                        made on it, it will be transferred to the patient's
                        credit. This cannot be undone.
                    </template>
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
                        class="rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-danger/90 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="emit('confirm')"
                    >
                        {{ processing ? "Voiding..." : "Void invoice" }}
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
    // The invoice being voided, or null when the dialog is closed.
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
