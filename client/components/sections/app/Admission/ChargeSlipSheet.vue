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
                    Charge Slip
                </h1>
            </div>

            <div class="text-right">
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted dark:text-gray-400"
                >
                    Invoice No.
                </p>

                <p
                    class="text-sm font-semibold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    {{ slip.invoice_code || "—" }}
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
                Charges
            </p>

            <ul class="divide-y divide-gray-200 border-y border-gray-200">
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

                        <p
                            v-if="charge.diagnosis"
                            class="mt-0.5 text-[11px] text-muted dark:text-gray-400"
                        >
                            Diagnosis: {{ charge.diagnosis }}
                            <template v-if="charge.diagnosis_case">
                                · Case: {{ charge.diagnosis_case }}
                            </template>
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
                    Amount due
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
                due for invoice
                <span class="font-semibold">{{ slip.invoice_code }}</span
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
