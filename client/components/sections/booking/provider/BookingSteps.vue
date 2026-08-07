<template>
    <nav class="flex flex-col">
        <button
            v-for="(step, index) in steps"
            :key="step.key"
            type="button"
            @click="$emit('go', step.key)"
            class="group relative flex items-center gap-3 rounded-xl p-3 text-left transition-colors"
            :class="
                active === step.key
                    ? 'bg-primary/10 ring-1 ring-primary/20'
                    : 'hover:bg-gray-50'
            "
        >
            <div class="flex flex-col items-center self-stretch">
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-colors"
                    :class="
                        isCompleted(step.key)
                            ? 'bg-primary text-white'
                            : active === step.key
                              ? 'bg-primary text-white'
                              : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200'
                    "
                >
                    <Check
                        v-if="isCompleted(step.key) && active !== step.key"
                        class="h-4 w-4"
                    />
                    <template v-else>{{ index + 1 }}</template>
                </div>

                <div
                    v-if="index < steps.length - 1"
                    class="w-px flex-1 my-1 transition-colors"
                    :class="
                        isCompleted(step.key) ? 'bg-primary/40' : 'bg-gray-200'
                    "
                ></div>
            </div>

            <div class="flex flex-col justify-center py-1">
                <span
                    class="text-sm font-medium transition-colors"
                    :class="
                        active === step.key ? 'text-primary' : 'text-gray-700'
                    "
                >
                    {{ step.title }}
                    <span
                        v-if="step.optional"
                        class="ml-1 text-[10px] font-medium uppercase tracking-wide text-gray-400"
                    >
                        (Optional)
                    </span>
                </span>

                <span class="text-xs text-gray-400">
                    {{ step.desc }}
                </span>
            </div>
        </button>
    </nav>
</template>

<script setup lang="ts">
import { Check } from "lucide-vue-next";

const props = withDefaults(
    defineProps<{
        active?: string;
        completed?: string[];
        hideReview?: boolean;
    }>(),
    {
        hideReview: false,
    },
);

defineEmits<{
    (e: "go", step: string): void;
}>();

function isCompleted(key: string) {
    return props.completed?.includes(key) ?? false;
}

const steps = computed(() => {
    const items = [
        {
            key: "step1",
            title: "Booking Type & Scheduling",
            desc: "Select service & schedule",
        },
        {
            key: "step2",
            title: "Patient Information",
            desc: "Enter patient information",
        },
        {
            key: "step3",
            title: "Guardian Information",
            desc: "Enter guardian information",
        },
        {
            key: "step4",
            title: "Assessment",
            desc: "Health assessment",
            optional: true,
        },
        {
            key: "step5",
            title: "Review & Submit",
            desc: "Confirm your details",
        },
    ];

    return props.hideReview
        ? items.filter((step) => step.key !== "step5")
        : items;
});
</script>
