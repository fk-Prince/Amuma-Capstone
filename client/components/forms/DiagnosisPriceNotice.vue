<template>
    <div
        v-if="charges"
        class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10"
    >
        <Info
            class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-300"
        />

        <p class="text-[13px] leading-5 text-amber-800 dark:text-amber-200">
            Additional charges may be added to the bill depending on the
            patient's diagnosis.
            <template v-if="charges.min === charges.max">
                Diagnosis case price at this facility:
                <span class="font-semibold">{{
                    formatCurrency(charges.min)
                }}</span
                >.
            </template>
            <template v-else>
                Diagnosis case prices at this facility range from
                <span class="font-semibold">{{
                    formatCurrency(charges.min)
                }}</span>
                to
                <span class="font-semibold">{{
                    formatCurrency(charges.max)
                }}</span
                >.
            </template>
        </p>
    </div>
</template>

<script setup lang="ts">
import { Info } from "lucide-vue-next";
import { formatCurrency } from "~/utils/currency";

defineProps<{
    charges?: { min: number; max: number } | null;
}>();
</script>
