<template>
    <span class="group relative inline-flex align-middle">
        <CircleHelp
            class="h-3.5 w-3.5 cursor-help text-slate-400 transition group-hover:text-primary dark:text-gray-500"
            tabindex="0"
            :aria-label="text"
        />
        <span
            role="tooltip"
            class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 w-max max-w-[260px] -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-left text-xs font-normal leading-5 text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-slate-700"
        >
            {{ text }}
        </span>
    </span>
</template>

<script setup lang="ts">
import { CircleHelp } from "lucide-vue-next";

defineProps<{
    text: string;
}>();
</script>
