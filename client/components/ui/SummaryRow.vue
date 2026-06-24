<template>
    <div
        class="flex items-start justify-between gap-4 py-2.5 border-t border-slate-100 first:border-0 first:pt-0"
    >
        <span
            class="text-slate-500 text-[13px] font-semibold tracking-wide shrink-0 pt-0.5"
        >
            {{ label }}
        </span>

        <span
            class="text-slate-800 text-sm font-medium text-right break-words max-w-[60%] leading-snug"
            :class="{ 'text-slate-400 font-normal': !hasValue }"
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
