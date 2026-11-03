<template>
    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    aria-label="Previous month"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                    @click="emit('prev')"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    aria-label="Next month"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                    @click="emit('next')"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    class="h-8 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                    @click="emit('today')"
                >
                    Today
                </button>
            </div>

            <h3 class="text-base font-bold text-slate-800 sm:text-lg dark:text-white">
                {{ title }}
            </h3>

            <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold sm:ml-auto">
                <slot name="chips" />
            </div>
        </div>

        <div
            v-if="$slots.legend"
            class="-mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 dark:text-gray-400"
        >
            <slot name="legend" />
        </div>

        <div
            v-if="partial"
            class="flex flex-wrap items-center gap-2 rounded-xl border border-primary-100 bg-primary-50 px-3 py-2 text-xs text-primary-700 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-200"
        >
            <Info class="h-3.5 w-3.5 shrink-0" />
            <span class="min-w-0 flex-1">{{ partial }}</span>
            <button
                type="button"
                class="rounded-md bg-white px-2 py-1 font-semibold text-primary shadow-sm transition hover:bg-primary-100 dark:bg-white/10 dark:text-primary-200"
                @click="emit('whole-month')"
            >
                Show all of {{ monthName }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ChevronLeft, ChevronRight, Info } from "lucide-vue-next";

defineProps<{
    title: string;
    monthName: string;
    partial?: string;
}>();

const emit = defineEmits<{
    (e: "prev"): void;
    (e: "next"): void;
    (e: "today"): void;
    (e: "whole-month"): void;
}>();
</script>
