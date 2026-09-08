<template>
    <div
        class="flex items-center justify-center gap-2.5 rounded-xl border border-dashed border-primary-100 px-6 py-12 text-xs text-muted dark:border-primary-500/20 dark:text-gray-400"
    >
        <Loader2 class="h-4 w-4 animate-spin" />
        {{ label }}
    </div>
</template>

<script setup lang="ts">
import { Loader2 } from "lucide-vue-next";

defineProps<{
    label: string;
}>();
</script>
