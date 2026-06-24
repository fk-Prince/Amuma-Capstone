<template>
    <button
        type="button"
        @click="emit('click')"
        class="w-full flex items-center bg-secondary text-white gap-2.5 px-5 py-2 text-sm transition-colors hover:bg-[#141d2c] hover:shadow-md hover:shadow-black/30"
        :class="[
            danger ? 'text-red-500 hover:bg-red-50' : 'text-gray-700',
            disabled
                ? 'opacity-40 cursor-not-allowed pointer-events-none'
                : 'cursor-pointer',
        ]"
    >
        <span class="flex-1 text-left">{{ label }}</span>
        <span
            v-if="badge"
            class="text-[11px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500"
        >
            {{ badge }}
        </span>
    </button>
</template>

<script setup lang="ts">
defineProps<{
    label: string;
    icon?: string;
    badge?: string;
    danger?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{ (e: "click"): void }>();
</script>
