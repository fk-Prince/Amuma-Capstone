<template>
    <div
        class="rounded-2xl border border-slate-200 bg-white p-5 text-sm dark:border-white/10 dark:bg-secondary"
    >
        <p
            v-if="title"
            class="text-base font-semibold text-slate-900 dark:text-white"
        >
            {{ title }}
        </p>

        <p
            v-if="heading"
            class="mt-3 text-[13px] font-semibold text-slate-800 dark:text-gray-200"
        >
            {{ heading }}
        </p>

        <div class="mt-2 flex items-center justify-between gap-4 py-1">
            <span class="text-slate-600 dark:text-gray-400">
                {{ itemLabel ?? "Subtotal" }}
            </span>
            <span class="font-semibold text-slate-900 dark:text-white">
                {{ formatCurrency(breakdown.subtotal) }}
            </span>
        </div>

        <div
            class="mt-3 flex items-center justify-between gap-4 border-t border-slate-100 pt-3 dark:border-white/10"
        >
            <span
                class="flex items-center gap-1.5 text-slate-600 dark:text-gray-400"
            >
                Taxes
                <span class="group relative inline-flex">
                    <CircleHelp
                        class="h-3.5 w-3.5 cursor-help text-slate-400 dark:text-gray-500"
                        tabindex="0"
                    />
                    <span
                        role="tooltip"
                        class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 w-max max-w-[220px] -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-xs leading-5 text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-slate-700"
                    >
                        VAT: {{ breakdown.rate }}%
                    </span>
                </span>
            </span>
            <span class="font-semibold text-slate-900 dark:text-white">
                {{ formatCurrency(breakdown.vat) }}
            </span>
        </div>

        <div
            class="mt-3 flex items-center justify-between gap-4 border-t border-slate-100 pt-3 dark:border-white/10"
        >
            <span class="text-base font-semibold text-slate-900 dark:text-white">
                {{ totalLabel ?? "Total" }}
            </span>
            <span class="text-lg font-bold text-slate-900 dark:text-white">
                {{ formatCurrency(breakdown.total) }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { CircleHelp } from "lucide-vue-next";
import { formatCurrency } from "~/utils/currency";
import { VAT_RATE, withVat } from "~/utils/vat";

const props = defineProps<{
    subtotal: number;
    rate?: number;
    title?: string;
    heading?: string;
    itemLabel?: string;
    totalLabel?: string;
}>();

const breakdown = computed(() => withVat(props.subtotal, props.rate ?? VAT_RATE));
</script>
