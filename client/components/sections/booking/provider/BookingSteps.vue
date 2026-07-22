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
                        active === step.key
                            ? 'bg-primary text-white'
                            : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200'
                    "
                >
                    {{ index + 1 }}
                </div>

                <div
                    v-if="index < steps.length - 1"
                    class="w-px flex-1 my-1 bg-gray-200"
                ></div>
            </div>

            <div class="flex flex-col justify-center">
                <span
                    class="text-sm font-medium transition-colors"
                    :class="
                        active === step.key ? 'text-primary' : 'text-gray-700'
                    "
                >
                    {{ step.title }}
                </span>

                <span class="text-xs text-gray-400">
                    {{ step.desc }}
                </span>
            </div>
        </button>
    </nav>
</template>

<script setup lang="ts">
defineProps<{
    active?: string;
}>();

defineEmits<{
    (e: "go", step: string): void;
}>();

const steps = [
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
    },
];
</script>
