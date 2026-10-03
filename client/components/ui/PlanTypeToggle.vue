<template>
    <div
        role="radiogroup"
        aria-label="Plan type"
        class="relative inline-grid grid-cols-2 rounded-full bg-slate-100/80 p-1.5 ring-1 ring-slate-200/70 dark:bg-white/5 dark:ring-white/10"
    >
        <span
            aria-hidden="true"
            class="absolute inset-y-1.5 left-1.5 w-[calc(50%-0.375rem)] rounded-full bg-primary shadow-[0_4px_14px_-4px_rgba(37,99,235,0.55)] transition-transform duration-300 ease-out"
            :class="activeIndex === 1 ? 'translate-x-full' : 'translate-x-0'"
        />

        <button
            v-for="type in PLAN_TYPES"
            :key="type.value"
            type="button"
            role="radio"
            :aria-checked="modelValue === type.value"
            class="relative z-10 whitespace-nowrap rounded-full px-6 py-2.5 text-sm font-semibold transition-colors duration-200"
            :class="
                modelValue === type.value
                    ? 'text-white'
                    : 'text-slate-500 hover:text-secondary dark:text-gray-400 dark:hover:text-white'
            "
            @click="emit('update:modelValue', type.value)"
        >
            {{ type.label }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { PLAN_TYPES, type PlanType } from "~/utils/planType";

const props = defineProps<{
    modelValue: PlanType;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: PlanType): void;
}>();

const activeIndex = computed(() =>
    PLAN_TYPES.findIndex((type) => type.value === props.modelValue),
);
</script>
