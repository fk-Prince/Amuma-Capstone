<template>
    <nav class="flex flex-col">
        <button
            v-for="(step, index) in steps"
            :key="step.key"
            type="button"
            @click="$emit('go', step.key)"
            class="group relative flex min-h-[88px] w-full items-start gap-4 rounded-2xl p-3 text-left transition-all duration-200"
            :class="
                active === step.key
                    ? 'bg-primary-50 shadow-sm ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/20'
                    : 'hover:bg-muted-light dark:hover:bg-white/5'
            "
        >
            <div
                class="relative flex h-full w-10 shrink-0 flex-col items-center"
            >
                <div
                    class="relative z-[999] flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-sm font-semibold transition-all duration-200"
                    :class="
                        isCompleted(step.key) && active !== step.key
                            ? 'border-primary-500 bg-primary-500 text-white shadow-sm shadow-primary-500/20'
                            : active === step.key
                              ? 'border-primary-500 bg-primary-500 text-white shadow-md shadow-primary-500/20 ring-4 ring-primary-100 dark:ring-primary-500/20'
                              : 'border-muted-light bg-white text-muted group-hover:border-primary-200 group-hover:bg-primary-50 group-hover:text-primary-600 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:group-hover:border-primary-500/40 dark:group-hover:bg-primary-500/10 dark:group-hover:text-primary-400'
                    "
                >
                    <Check
                        v-if="isCompleted(step.key) && active !== step.key"
                        class="h-4.5 w-4.5 stroke-[2.5]"
                    />

                    <span v-else>
                        {{ index + 1 }}
                    </span>
                </div>

                <!-- Connector -->
                <div
                    v-if="index < steps.length - 1"
                    class="absolute left-1/2 top-10 h-[48px] w-0.5 -translate-x-1/2 transition-colors duration-300"
                    :class="
                        isCompleted(step.key)
                            ? 'bg-primary-300'
                            : 'bg-gray-200 dark:bg-white/10'
                    "
                />
            </div>

            <div class="min-w-0 flex-1 py-0.5">
                <div class="flex items-center justify-between gap-2">
                    <span
                        class="text-sm font-semibold leading-5 transition-colors"
                        :class="
                            active === step.key
                                ? 'text-primary-600 dark:text-primary-300'
                                : isCompleted(step.key)
                                  ? 'text-secondary dark:text-white'
                                  : 'text-muted-dark dark:text-gray-400'
                        "
                    >
                        {{ step.title }}
                    </span>

                    <span
                        v-if="active === step.key"
                        class="hidden shrink-0 rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary-600 sm:inline-flex dark:bg-primary-500/15 dark:text-primary-400"
                    >
                        Current
                    </span>
                </div>

                <p class="mt-1 text-xs leading-4 text-muted dark:text-gray-400">
                    {{ step.desc }}
                </p>

                <span
                    v-if="step.optional"
                    class="mt-1.5 inline-flex rounded-md bg-accent-50 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-accent-600 dark:bg-accent-500/15 dark:text-accent-400"
                >
                    Optional
                </span>
            </div>
        </button>
    </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Check } from "lucide-vue-next";

const props = withDefaults(
    defineProps<{
        active?: string;
        completed?: string[];
        hideReview?: boolean;
    }>(),
    {
        active: "step1",
        completed: () => [],
        hideReview: false,
    },
);

defineEmits<{
    (e: "go", step: string): void;
}>();

function isCompleted(key: string) {
    return props.completed.includes(key);
}

const steps = computed(() => {
    const items = [
        {
            key: "step1",
            title: "Booking Type & Scheduling",
            desc: "Select service and schedule",
        },
        {
            key: "step2",
            title: "Patient Information",
            desc: "Enter patient details",
        },
        {
            key: "step3",
            title: "Guardian Information",
            desc: "Enter guardian details",
        },
        {
            key: "step4",
            title: "Diagnosis",
            desc: "Known diagnoses and documents",
            optional: true,
        },
        {
            key: "step5",
            title: "Assessment",
            desc: "Condition, mental state and daily activities",
            optional: true,
        },
        {
            key: "step6",
            title: "Review & Submit",
            desc: "Confirm and submit booking",
        },
    ];

    return props.hideReview
        ? items.filter((step) => step.key !== "step6")
        : items;
});
</script>
