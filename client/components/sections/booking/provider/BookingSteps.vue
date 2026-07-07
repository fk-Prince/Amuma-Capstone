<template>
    <div class="flex flex-col gap-3">
        <div
            v-for="(step, index) in steps"
            :key="step.key"
            @click="$emit('go', step.key)"
            class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition hover:bg-gray-50"
        >
            <div
                class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-semibold"
                :class="
                    active === step.key
                        ? 'bg-primary text-white'
                        : 'bg-gray-100 text-gray-500'
                "
            >
                {{ index + 1 }}
            </div>

            <div class="flex flex-col">
                <span
                    class="text-sm font-medium"
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
        </div>
    </div>
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
        desc: "Select service & schedule ",
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
        key: "step5",
        title: "Assessment",
        desc: "Health assessment",
    },
];
</script>
