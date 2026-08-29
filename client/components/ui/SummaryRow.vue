<template>
    <div
        class="flex flex-col sm:flex-row items-start sm:justify-between gap-1 sm:gap-4 py-2.5 border-t border-slate-100 dark:border-white/10 first:border-0 first:pt-0"
    >
        <span
            class="text-slate-500 dark:text-gray-400 text-[13px] font-semibold tracking-wide shrink-0 pt-0.5"
        >
            {{ label }}
        </span>

        <span
            class="text-slate-800 dark:text-white text-sm font-medium sm:text-right break-words max-w-full sm:max-w-[60%] leading-snug"
            :class="{ 'text-slate-400 dark:text-gray-500 font-normal': !hasValue }"
        >
            {{ displayValue }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    label: string;
    value?: string | number | null;
}>();

const hasValue = computed(
    () =>
        props.value !== undefined && props.value !== null && props.value !== "",
);

const displayValue = computed(() =>
    hasValue.value ? props.value : "Not provided",
);
</script>
