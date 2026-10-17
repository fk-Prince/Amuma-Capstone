<template>
    <div
        class="space-y-5 text-secondary"
        :class="print ? 'bg-white p-8 text-black' : ''"
    >
        <div
            class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4"
        >
            <div>
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted dark:text-gray-400"
                >
                    {{ slip.branch_name || "Amuma Care" }}
                </p>

                <h1
                    class="mt-1 text-lg font-semibold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    {{ slip.title || "Charge Slip" }}
                </h1>
            </div>

            <div v-if="slip.invoice_code" class="text-right">
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted dark:text-gray-400"
                >
                    Invoice No.
                </p>

                <p
                    class="text-sm font-semibold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    {{ slip.invoice_code }}
                </p>
            </div>
        </div>

        <dl class="grid gap-x-6 gap-y-1.5 sm:grid-cols-2">
            <div
                v-for="row in details"
                :key="row.label"
                class="flex items-baseline justify-between gap-3 border-b border-dashed border-gray-200 py-1"
            >
                <dt class="text-[11px] text-muted dark:text-gray-400">
                    {{ row.label }}
                </dt>

                <dd
                    class="text-right text-[12px] font-medium"
                    :class="print ? '' : 'dark:text-gray-100'"
                >
                    {{ row.value }}
                </dd>
            </div>
        </dl>

        <section class="space-y-2">
            <p
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
            >
                {{ slip.section || "Charges" }}
            </p>

            <div
                v-if="slip.wide"
                class="border-y border-gray-200"
            >
                <div
                    class="grid grid-cols-[1.3fr_1.6fr_1.1fr_auto] gap-x-6 border-b border-gray-200 py-2 text-[10px] font-semibold uppercase tracking-wide text-muted dark:text-gray-400"
                >
                    <p>Diagnosis case</p>
                    <p>Diagnosis</p>
                    <p>Billed</p>
                    <p class="text-right">Amount</p>
                </div>

                <div
                    v-for="charge in slip.charges"
                    :key="charge.id"
                    class="grid grid-cols-[1.3fr_1.6fr_1.1fr_auto] gap-x-6 border-b border-gray-100 py-3 text-[12px] last:border-b-0"
                    :class="print ? '' : 'dark:text-gray-100'"
                >
                    <p class="break-words font-semibold">
                        {{ charge.type_label }}
                    </p>
                    <p class="break-words">{{ charge.description }}</p>
                    <p class="break-words">{{ charge.billed || "—" }}</p>
                    <p class="text-right font-semibold">
                        {{ formatCurrency(charge.amount) }}
                    </p>
                </div>
            </div>

            <ul
                v-else
                class="divide-y divide-gray-200 border-y border-gray-200"
            >
                <li
                    v-for="charge in slip.charges"
                    :key="charge.id"
                    class="flex items-start justify-between gap-4 py-2.5"
                >
                    <div class="min-w-0">
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-muted dark:text-gray-400"
                        >
                            {{ charge.type_label }}
                        </p>

                        <p
                            class="mt-0.5 break-words text-[12px] font-medium"
                            :class="print ? '' : 'dark:text-gray-100'"
                        >
                            {{ charge.description }}
                        </p>

                    </div>

                    <p
                        class="shrink-0 text-[12px] font-semibold"
                        :class="print ? '' : 'dark:text-white'"
                    >
                        {{ formatCurrency(charge.amount) }}
                    </p>
                </li>
            </ul>

            <div class="flex items-center justify-between pt-1">
                <p class="text-[12px] font-semibold uppercase tracking-wide">
                    {{ slip.total_label || "Amount due" }}
                </p>

                <p
                    class="text-base font-bold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    {{ formatCurrency(slip.total) }}
                </p>
            </div>
        </section>

        <section
            v-if="!slip.hide_cashier_note"
            class="rounded-xl border border-gray-300 p-4"
            :class="print ? '' : 'bg-slate-50 dark:bg-white/5'"
        >
            <p
                class="text-[11px] leading-5"
                :class="
                    print ? 'text-black' : 'text-gray-600 dark:text-gray-300'
                "
            >
                Please present this slip to the cashier to settle the amount
                due<template v-if="slip.invoice_code">
                    for invoice
                    <span class="font-semibold">{{ slip.invoice_code }}</span></template
                >.
            </p>
        </section>

        <div class="w-1/2 pt-6">
            <div
                class="border-b border-gray-400 pb-1 text-[12px] font-medium"
                :class="print ? '' : 'dark:text-gray-100'"
            >
                <template v-if="slip.prepared_by">{{ slip.prepared_by }}</template>
                <template v-else>&nbsp;</template>
            </div>
            <p
                class="mt-1 text-[10px] uppercase tracking-wide text-muted dark:text-gray-400"
            >
                Prepared by
            </p>
        </div>

        <p
            class="border-t border-gray-200 pt-3 text-[10px] text-muted dark:text-gray-500"
        >
            Issued {{ issuedAt }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatCurrency } from "~/utils/currency";
import { stringToDateTime } from "~/utils/time";
import type { ChargeSlip } from "~/types/charge-slip";

const props = defineProps<{
    slip: ChargeSlip;
    print?: boolean;
}>();

const issuedAt = computed(() => stringToDateTime(new Date()));

const details = computed(() =>
    [
        { label: "Patient", value: props.slip.patient_name },
    ].filter((row) => !!row.value),
);
</script>
