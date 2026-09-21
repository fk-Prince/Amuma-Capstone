<script setup lang="ts">
import { Lock } from "lucide-vue-next";

defineProps<{
    title: string;
    message: string;
}>();
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/20 dark:bg-amber-500/10"
    >
        <Lock
            class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-300"
        />

        <div class="min-w-0">
            <p
                class="text-sm font-semibold text-amber-800 dark:text-amber-300"
            >
                {{ title }}
            </p>
            <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300/80">
                {{ message }}
            </p>
        </div>
    </div>
</template>
