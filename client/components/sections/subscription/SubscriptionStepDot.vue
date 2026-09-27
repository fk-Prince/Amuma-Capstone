<template>
    <div class="relative">
        <div
            class="flex h-7 w-7 sm:h-9 sm:w-9 items-center justify-center rounded-full border-2 text-xs sm:text-sm font-semibold transition-all duration-200"
            :class="
                hasError
                    ? 'border-danger bg-white dark:bg-secondary text-danger'
                    : currentStep > number
                      ? 'border-primary bg-primary text-white'
                      : currentStep === number
                        ? 'border-primary bg-white dark:bg-secondary text-primary shadow-sm ring-4 ring-primary/10'
                        : 'border-slate-200 dark:border-white/10 bg-white dark:bg-secondary text-slate-400 dark:text-gray-500'
            "
        >
            <Check
                v-if="currentStep > number && !hasError"
                class="h-3.5 w-3.5 sm:h-4 sm:w-4 stroke-[2.5]"
            />

            <span v-else>{{ number }}</span>
        </div>

        <span
            v-if="hasError"
            class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-danger dark:border-secondary"
            aria-hidden="true"
        />
    </div>
</template>

<script setup lang="ts">
import { Check } from "lucide-vue-next";

defineProps<{
    number: number;
    currentStep: number;
    hasError: boolean;
}>();
</script>
