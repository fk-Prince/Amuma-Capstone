<template>
    <label
        class="flex items-start gap-2"
        :class="disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'"
        :title="disabled ? 'You need permission to update the subscription.' : undefined"
    >
        <input
            v-model="enabled"
            type="checkbox"
            :disabled="disabled"
            class="mt-0.5 h-3.5 w-3.5 shrink-0 cursor-pointer rounded border-slate-300 text-primary outline-none focus:outline-none focus:ring-0 disabled:cursor-not-allowed dark:border-white/20 dark:bg-secondary"
        />

        <span class="text-xs text-slate-500 dark:text-gray-400">
            Renew automatically every year with this card.
        </span>
    </label>
</template>

<script setup lang="ts">
defineProps<{
    disabled?: boolean;
}>();

const enabled = defineModel<boolean>({ default: false });
</script>
