<template>
    <Teleport to="body">
        <div
            v-if="mode"
            class="fixed inset-0 z-[70] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="emit('close')"
            />

            <div
                class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-semibold text-gray-400 dark:text-gray-500"
                        >
                            {{
                                mode === "receipts"
                                    ? "Payment receipts"
                                    : "Refund history"
                            }}
                        </p>

                        <h2
                            class="mt-0.5 truncate text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            {{ patientName ?? "Patient" }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <ul
                    v-if="mode === 'receipts'"
                    class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                >
                    <li
                        v-for="receipt in receipts"
                        :key="receipt.key"
                        class="flex items-start justify-between gap-4 px-6 py-4 transition-colors"
                        :class="
                            receipt.receipt_no
                                ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5'
                                : ''
                        "
                        @click="emit('open-receipt', receipt.receipt_no)"
                    >
                        <div class="min-w-0">
                            <p
                                class="flex items-center gap-1.5 font-mono text-sm font-semibold text-secondary dark:text-white"
                            >
                                <Loader2
                                    v-if="loadingReceipt === receipt.receipt_no"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                {{ receipt.receipt_no ?? "No receipt" }}
                            </p>

                            <p class="text-xs text-muted dark:text-gray-400">
                                {{ receipt.invoice_codes.join(", ") }}

                                <span
                                    v-if="receipt.invoice_codes.length > 1"
                                    class="text-gray-400 dark:text-gray-500"
                                >
                                    ({{ receipt.invoice_codes.length }}
                                    invoices)
                                </span>
                            </p>

                            <p
                                class="text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                {{ receipt.payment_method }} ·
                                {{ formatDateTime(receipt.created_at) }}
                            </p>
                        </div>

                        <p
                            class="shrink-0 text-sm font-semibold text-primary-700 dark:text-primary-300"
                        >
                            ₱{{ formatMoney(receipt.amount) }}
                        </p>
                    </li>
                </ul>

                <ul
                    v-else
                    class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                >
                    <li
                        v-for="refund in refunds"
                        :key="refund.refund_id"
                        class="flex items-start justify-between gap-4 px-6 py-4"
                    >
                        <div class="min-w-0">
                            <p
                                class="font-mono text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ refund.invoice_code }}
                            </p>

                            <p class="text-xs text-muted dark:text-gray-400">
                                {{
                                    refund.refund_method ||
                                    refund.payment_method
                                }}
                                <template v-if="refund.declined_reason">
                                    · {{ refund.declined_reason }}
                                </template>
                            </p>

                            <p
                                class="text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                {{ formatDateTime(refund.created_at) }}
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p
                                class="text-sm font-semibold text-accent-700 dark:text-accent-300"
                            >
                                ₱{{ formatMoney(refund.amount) }}
                            </p>

                            <span
                                class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                :class="statusClasses(refund.status)"
                            >
                                {{ refund.status }}
                            </span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Loader2, X } from "lucide-vue-next";

import { formatAmount } from "~/utils/currency";
import { statusClasses } from "~/utils/invoiceStatus";
import { stringToDateTime } from "~/utils/time";

export interface ReceiptGroup {
    key: string;
    receipt_no: string | null;
    amount: number;
    invoice_codes: string[];
    payment_method: string;
    created_at: string | null;
}

export interface RefundEntry {
    refund_id: number;
    invoice_code: string;
    refund_method?: string | null;
    payment_method?: string | null;
    declined_reason?: string | null;
    amount: number | string;
    status: string;
    created_at: string | null;
}

defineProps<{
    // Which list is showing, or null when the dialog is closed.
    mode: "receipts" | "refunds" | null;
    patientName?: string | null;
    receipts: ReceiptGroup[];
    refunds: RefundEntry[];
    loadingReceipt?: string | null;
}>();

const emit = defineEmits<{
    (event: "open-receipt", receiptNo: string | null): void;
    (event: "close"): void;
}>();

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

function formatDateTime(value: string | null | undefined) {
    return value ? stringToDateTime(value) : "—";
}
</script>
