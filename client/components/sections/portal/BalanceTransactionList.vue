<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import { formatCurrency } from "~/utils/currency";
import {
    formatBillingStatus,
    transactionAmountColor,
    transactionIcon,
    transactionIconClasses,
    transactionMovedAmount,
    transactionRole,
    transactionSign,
    transactionStatusClasses,
} from "~/utils/portal-billing";
import type { PortalTransaction } from "~/types/portal-billing";

defineProps<{
    transactions: PortalTransaction[];
    loadingReceiptNo?: string | null;
}>();

const emit = defineEmits<{
    (event: "receipt", receiptNo: string): void;
}>();

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
}

function movedAmount(transaction: PortalTransaction) {
    return transactionMovedAmount(
        transaction.type,
        transaction.amount,
        transaction.status,
    );
}

function reasonFor(transaction: PortalTransaction) {
    if (!transaction.reason) return null;

    if (transaction.type !== "refund") return transaction.reason;

    return (transaction.status || "").toLowerCase() === "rejected"
        ? `Reason for refusal: ${transaction.reason}`
        : transaction.reason;
}
</script>

<template>
    <div class="divide-y divide-gray-100 dark:divide-white/10">
        <div
            v-for="transaction in transactions"
            :key="transaction.id"
            class="flex items-start gap-3 py-3 first:pt-0 last:pb-0"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                :class="transactionIconClasses(transaction.type)"
            >
                <AppIcon
                    :name="transactionIcon(transaction.type)"
                    class="h-4 w-4"
                />
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p
                                class="truncate text-xs font-semibold text-gray-800 dark:text-white"
                            >
                                {{ transaction.label }}
                            </p>

                            <span
                                v-if="transaction.status"
                                class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                                :class="
                                    transactionStatusClasses(transaction.status)
                                "
                            >
                                {{ formatBillingStatus(transaction.status) }}
                            </span>
                        </div>

                        <div
                            class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[10px] text-gray-400 dark:text-gray-500"
                        >
                            <span>{{ transaction.date }}</span>

                            <span
                                v-if="
                                    (transaction.invoiceCodes?.length ?? 0) > 1
                                "
                            >
                                {{ transaction.invoiceCodes?.length }} bills
                            </span>

                            <span v-else-if="transaction.invoiceCode">
                                {{ transaction.invoiceCode }}
                            </span>

                            <span v-if="transaction.maskedCardNumber">
                                {{ transaction.maskedCardNumber }}
                            </span>

                            <button
                                v-if="transaction.receiptNo"
                                type="button"
                                :disabled="
                                    loadingReceiptNo === transaction.receiptNo
                                "
                                class="inline-flex items-center gap-1 font-semibold text-primary-600 hover:underline disabled:cursor-wait disabled:opacity-70 dark:text-primary-300"
                                @click="emit('receipt', transaction.receiptNo)"
                            >
                                <AppIcon
                                    v-if="
                                        loadingReceiptNo ===
                                        transaction.receiptNo
                                    "
                                    name="loader-circle"
                                    class="h-3 w-3 animate-spin"
                                />

                                {{ transaction.receiptNo }}
                            </button>
                        </div>

                        <p
                            v-if="reasonFor(transaction)"
                            class="mt-1.5 truncate rounded-lg bg-gray-50 px-2 py-1 text-[10px] text-gray-500 dark:bg-white/5 dark:text-gray-400"
                        >
                            {{ reasonFor(transaction) }}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p
                            class="text-sm font-bold"
                            :class="
                                movedAmount(transaction)
                                    ? transactionAmountColor(transaction.type)
                                    : 'text-gray-400 dark:text-gray-500'
                            "
                        >
                            {{
                                transactionSign(
                                    transaction.type,
                                    movedAmount(transaction),
                                )
                            }}{{ peso(movedAmount(transaction)) }}
                        </p>

                        <p class="text-[10px] text-gray-400 dark:text-gray-500">
                            {{
                                transactionRole(
                                    transaction.type,
                                    transaction.status,
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!transactions.length" class="py-10 text-center">
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 dark:bg-white/5"
            >
                <AppIcon
                    name="receipt"
                    class="h-6 w-6 text-gray-300 dark:text-gray-500"
                />
            </div>

            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                No transactions yet
            </p>
        </div>
    </div>
</template>
