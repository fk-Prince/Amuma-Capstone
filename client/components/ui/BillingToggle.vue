<template>
    <div class="flex items-center justify-center gap-3">
        <span
            :class="[
                'text-sm font-medium transition-colors',
                modelValue === 'monthly'
                    ? 'text-gray-900 dark:text-white'
                    : 'text-gray-400 dark:text-gray-500',
            ]"
        >
            Monthly
        </span>

        <button
            role="switch"
            :aria-checked="modelValue === 'yearly'"
            @click="toggle"
            :class="[
                'relative w-12 h-7 rounded-full transition-colors duration-200',
                modelValue === 'yearly' ? 'bg-primary' : 'bg-gray-300 dark:bg-white/20',
            ]"
        >
            <span
                :class="[
                    'absolute top-[3px] left-[3px] w-5 h-5 rounded-full bg-white shadow transition-transform duration-200 dark:bg-secondary',
                    modelValue === 'yearly' ? 'translate-x-5' : 'translate-x-0',
                ]"
            />
        </button>

        <span
            :class="[
                'text-sm font-medium transition-colors',
                modelValue === 'yearly'
                    ? 'text-gray-900 dark:text-white'
                    : 'text-gray-400 dark:text-gray-500',
            ]"
        >
            Yearly
        </span>
    </div>
</template>
<script setup lang="ts">
type BillingCycle = "monthly" | "yearly";

const props = defineProps<{
    modelValue: BillingCycle;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: BillingCycle): void;
}>();

function toggle() {
    emit(
        "update:modelValue",
        props.modelValue === "monthly" ? "yearly" : "monthly",
    );
}
</script>
