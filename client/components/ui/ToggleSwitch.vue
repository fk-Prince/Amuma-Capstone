<script setup lang="ts">
const props = defineProps<{
    modelValue?: boolean;
    label?: string;
    description?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: boolean];
}>();

const toggle = () => {
    if (props.disabled) return;

    emit("update:modelValue", !props.modelValue);
};
</script>

<template>
    <div class="flex items-center justify-between">
        <div v-if="label">
            <p class="text-sm font-semibold text-slate-700">
                {{ label }}
            </p>

            <p v-if="description" class="mt-0.5 text-xs text-slate-500">
                {{ description }}
            </p>
        </div>

        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            @click="toggle"
            :disabled="disabled"
            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50"
            :class="modelValue ? 'bg-primary' : 'bg-slate-300'"
        >
            <span
                class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200"
                :class="modelValue ? 'translate-x-5' : 'translate-x-0.5'"
            />
        </button>
    </div>
</template>
