<template>
    <div>
        <p v-if="!invoices?.length" class="text-xs text-muted py-1">
            No invoices for this admission.
        </p>

        <ul v-else class="divide-y divide-primary-100">
            <li
                v-for="invoice in invoices"
                :key="invoice.invoice_facility_id"
                class="py-2.5 flex items-center justify-between gap-3"
            >
                <div class="min-w-0">
                    <p class="text-sm font-medium text-primary-900">
                        #{{ invoice.invoice_code ?? invoice.invoice_id }}
                    </p>
                    <p
                        v-if="invoice.contract?.accommodation_type"
                        class="text-xs text-muted mt-0.5 truncate"
                    >
                        {{ invoice.contract.accommodation_type }}
                        <span v-if="invoice.contract.billing_cycle">
                            ·
                            {{
                                invoice.contract.billing_cycle?.toLowerCase() ===
                                "yearly"
                                    ? "Yearly"
                                    : "Monthly"
                            }}
                        </span>
                    </p>
                </div>

                <p class="text-sm font-semibold text-primary-900 shrink-0">
                    {{ formatCurrency(invoice.price) }}
                </p>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import type { InvoiceFacility } from "~/types/patient";

defineProps<{
    invoices?: InvoiceFacility[];
}>();

function formatCurrency(value?: string | number) {
    if (value === undefined || value === null || value === "") return "—";
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    return num.toLocaleString(undefined, {
        style: "currency",
        currency: "PHP",
    });
}
</script>
