<script setup lang="ts">
defineProps<{
    hint?: string;
}>();
</script>

<template>
    <div
        class="sticky bottom-3 z-20 mt-6 sm:bottom-4"
        style="padding-bottom: env(safe-area-inset-bottom)"
    >
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 shadow-lg shadow-slate-900/10 backdrop-blur dark:border-white/10 dark:bg-secondary/90"
        >
            <p
                v-if="hint || $slots.hint"
                class="min-w-0 text-xs font-medium text-slate-500 dark:text-gray-400"
            >
                <slot name="hint">{{ hint }}</slot>
            </p>

            <div class="ml-auto flex items-center gap-2">
                <slot />
            </div>
        </div>
    </div>
</template>