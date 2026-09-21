<template>
    <div class="shrink-0 border-b border-gray-100/80 px-5 py-5 dark:border-white/10">
        <div class="flex items-center justify-between">
            <div>
                <p
                    class="text-[11px] font-bold uppercase tracking-[0.12em] text-primary-600 dark:text-primary-300"
                >
                    {{ title }}
                </p>

                <p class="mt-1 text-xs text-muted dark:text-gray-400">
                    Complete each step to continue
                </p>
            </div>

            <div
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/80 text-primary-500 shadow-sm ring-1 ring-primary-100 dark:bg-white/10 dark:text-primary-300 dark:ring-primary-500/20"
            >
                <ClipboardCheck class="h-4.5 w-4.5" />
            </div>
        </div>

        <div class="mt-4">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-medium text-muted dark:text-gray-400">
                    Overall progress
                </span>

                <span
                    class="text-xs font-bold text-primary-600 dark:text-primary-300"
                >
                    {{ Math.round(progress) }}%
                </span>
            </div>

            <div
                class="h-2 overflow-hidden rounded-full bg-white/80 shadow-inner ring-1 ring-gray-100 dark:bg-white/10 dark:ring-white/10"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-primary-500 to-accent-500 shadow-sm transition-all duration-500 ease-out"
                    :style="{ width: `${progress}%` }"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ClipboardCheck } from "lucide-vue-next";

defineProps<{
    title: string;
    progress: number;
}>();
</script>
