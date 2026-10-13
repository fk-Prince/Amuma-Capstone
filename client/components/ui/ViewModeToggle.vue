<template>
    <div
        class="inline-flex shrink-0 items-center rounded-lg border border-[#E4EFED] bg-white p-1 dark:border-white/10 dark:bg-secondary"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :aria-label="option.label"
            :title="option.label"
            class="flex h-8 w-8 items-center justify-center rounded-md transition"
            :class="
                modelValue === option.value
                    ? 'bg-primary text-white'
                    : 'text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-400'
            "
            @click="emit('update:modelValue', option.value)"
        >
            <component :is="option.icon" class="h-4 w-4" />
        </button>
    </div>
</template>

<script setup lang="ts">
import { LayoutGrid, List } from "lucide-vue-next";

defineProps<{
    modelValue: "table" | "card";
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: "table" | "card"): void;
}>();

const options = [
    { value: "table" as const, label: "Table view", icon: List },
    { value: "card" as const, label: "Card view", icon: LayoutGrid },
];
</script>
