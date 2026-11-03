<template>
    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <header
            class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 dark:border-white/10"
        >
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300"
            >
                <component :is="icon" class="h-[18px] w-[18px]" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-bold text-secondary sm:text-base dark:text-white">
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="text-xs leading-5 text-slate-500 sm:text-sm dark:text-gray-400"
                >
                    {{ description }}
                </p>
            </div>

            <div v-if="$slots.actions" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <slot name="actions" />
            </div>
        </header>

        <div :class="flush ? '' : 'p-4 sm:p-6'">
            <slot />
        </div>
    </section>
</template>

<script setup lang="ts">
import type { Component } from "vue";

/** Card used across the profile page: icon, title, short help, then content. */
defineProps<{
    icon: Component;
    title: string;
    description?: string;
    /** No padding around the content (for lists that run edge to edge). */
    flush?: boolean;
}>();
</script>
