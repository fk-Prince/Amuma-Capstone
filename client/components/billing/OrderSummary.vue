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
                {{ formatCurrency(amount) }}
            </span>
        </div>

        <div
            class="mt-3 flex items-center justify-between gap-4 border-t border-slate-100 pt-3 dark:border-white/10"
        >
            <span class="text-base font-semibold text-slate-900 dark:text-white">
                {{ totalLabel ?? "Total" }}
            </span>
            <span class="text-lg font-bold text-slate-900 dark:text-white">
                {{ formatCurrency(amount) }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { formatCurrency } from "~/utils/currency";

defineProps<{
    amount: number;
    title?: string;
    heading?: string;
    itemLabel?: string;
    totalLabel?: string;
}>();
</script>
