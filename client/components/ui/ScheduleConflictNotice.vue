<script setup lang="ts">
withDefaults(
    defineProps<{
        lines: string[];
        title?: string;
    }>(),
    { title: "Schedule conflict" },
);
</script>

<template>
    <div
        v-if="lines.length"
        class="shrink-0 border-b border-amber-200 bg-amber-50 px-6 py-3 dark:border-amber-500/20 dark:bg-amber-500/10"
    >
        <p
            class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300"
        >
            {{ title }}
        </p>

        <ul class="mt-1 space-y-0.5 text-sm text-amber-800 dark:text-amber-200">
            <li v-for="line in lines" :key="line">{{ line }}</li>
        </ul>

        <slot />
    </div>
</template>
