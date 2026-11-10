<template>
    <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <button
            v-for="option in options"
            :key="option.plan_code"
            type="button"
            :disabled="option.disabled"
            class="relative flex h-full flex-col gap-3 rounded-xl border p-5 text-left transition-all"
            :class="[
                modelValue === option.plan_code
                    ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                    : 'border-muted-light hover:border-primary-200 dark:border-white/10',
                option.disabled
                    ? 'cursor-not-allowed opacity-50 hover:border-muted-light dark:hover:border-white/10'
                    : 'cursor-pointer',
            ]"
            @click="emit('update:modelValue', option.plan_code)"
        >
            <span
                class="absolute right-4 top-4 flex h-5 w-5 items-center justify-center rounded-full border-2 transition-colors"
                :class="
                    modelValue === option.plan_code
                        ? 'border-primary'
                        : 'border-slate-300 dark:border-white/20'
                "
            >
                <span
                    v-if="modelValue === option.plan_code"
                    class="h-2.5 w-2.5 rounded-full bg-primary"
                />
            </span>

            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-primary-100 bg-primary-50 dark:bg-primary-500/10"
            >
                <component
                    :is="planIcon(option.plan_code)"
                    class="h-5 w-5 text-primary"
                />
            </div>

            <div>
                <p
                    class="text-base font-semibold leading-tight text-secondary dark:text-white"
                >
                    {{ option.name }}
                </p>

                <p
                    v-if="option.description"
                    class="mt-1.5 text-sm leading-relaxed text-muted dark:text-gray-400"
                >
                    {{ option.description }}
                </p>

                <p
                    v-if="option.reason"
                    class="mt-2 text-xs font-medium text-amber-600 dark:text-amber-400"
                >
                    {{ option.reason }}
                </p>
            </div>

            <div
                v-if="option.label || option.amount"
                class="mt-auto flex flex-wrap items-center justify-between gap-x-2 gap-y-1 border-t border-muted-light/70 pt-4 dark:border-white/10"
            >
                <span
                    class="whitespace-nowrap text-sm font-medium text-muted dark:text-gray-400"
                >
                    {{ option.label }}
                </span>

                <span class="flex items-baseline gap-2 whitespace-nowrap">
                    <span
                        v-if="option.strike"
                        class="text-sm font-semibold text-muted line-through decoration-2 dark:text-gray-500"
                    >
                        {{ option.strike }}
                    </span>
                    <span class="text-lg font-bold text-primary">
                        {{ option.amount }}
                    </span>
                </span>
            </div>
        </button>
    </div>
</template>

<script setup lang="ts">
import { planIcon } from "~/utils/planIcon";

export interface BranchPlanOption {
    plan_code: string;
    name: string;
    description?: string | null;
    label?: string;
    amount?: string;
    strike?: string;
    disabled?: boolean;
    reason?: string;
}

defineProps<{
    options: BranchPlanOption[];
    modelValue: string | null;
}>();

const emit = defineEmits<{
    "update:modelValue": [code: string];
}>();
</script>
