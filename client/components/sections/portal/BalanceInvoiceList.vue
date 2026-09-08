<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import { formatCurrency } from "~/utils/currency";
import {
    formatBillingDateTime,
    formatBillingStatus,
    getSourceTypeLabel,
    invoiceStatusClasses,
} from "~/utils/portal-billing";
import type { PortalInvoice } from "~/types/portal-billing";

defineProps<{
    invoices: PortalInvoice[];
}>();

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
}
</script>

<template>
    <div class="divide-y divide-gray-100 dark:divide-white/10">
        <article
            v-for="invoice in invoices"
            :key="invoice.invoice_id"
            class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
        >
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-bold text-gray-900 dark:text-white">
                        {{ invoice.invoice_code }}
                    </p>

                    <span
                        class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                        :class="invoiceStatusClasses(invoice.status)"
                    >
                        {{ formatBillingStatus(invoice.status) }}
                    </span>

                    <span
                        v-if="invoice.adjusted_total !== invoice.total"
                        class="rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-bold text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        ADJUSTED
                    </span>
                </div>

                <p
                    class="mt-1 truncate text-xs text-gray-600 dark:text-gray-300"
                >
                    {{ invoice.description }}
                </p>

                <p
                    v-if="invoice.void_reason"
                    class="mt-0.5 truncate text-[10px] text-rose-600 dark:text-rose-300"
                >
                    Voided: {{ invoice.void_reason }}
                </p>

                <p
                    v-else-if="invoice.adjustments.length"
                    class="mt-0.5 truncate text-[10px] text-amber-600 dark:text-amber-300"
                >
                    {{
                        invoice.adjustments.map((adj) => adj.reason).join(" · ")
                    }}
                </p>

                <p class="mt-0.5 text-[10px] text-gray-400 dark:text-gray-500">
                    {{ formatBillingDateTime(invoice.created_at)
                    }}<template v-if="invoice.source_type">
                        ·
                        {{ getSourceTypeLabel(invoice.source_type) }}</template
                    >
                </p>
            </div>

            <div class="shrink-0 text-right">
                <p
                    v-if="invoice.adjusted_total !== invoice.total"
                    class="text-[10px] text-gray-400 line-through dark:text-gray-500"
                >
                    {{ peso(invoice.total) }}
                </p>

                <p class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ peso(invoice.adjusted_total) }}
                </p>

                <p
                    v-if="invoice.balance_due > 0"
                    class="text-[10px] font-semibold text-rose-500 dark:text-rose-300"
                >
                    {{ peso(invoice.balance_due) }} due
                </p>
            </div>
        </article>

        <div v-if="!invoices.length" class="py-10 text-center">
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 dark:bg-white/5"
            >
                <AppIcon
                    name="file-text"
                    class="h-6 w-6 text-gray-300 dark:text-gray-500"
                />
            </div>

            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                No invoices yet
            </p>
        </div>
    </div>
</template>
