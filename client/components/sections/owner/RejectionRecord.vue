<template>
    <button
        v-if="compact"
        type="button"
        :title="label"
        class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg bg-rose-50 px-2.5 py-1.5 text-[11px] font-medium text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
        @click="emit('view')"
    >
        <History class="h-3.5 w-3.5 shrink-0" />
        View logs
    </button>

    <div
        v-else
        class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg bg-rose-50 px-2.5 py-1.5 text-[11px] dark:bg-rose-500/10"
    >
        <span class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
            <History class="h-3.5 w-3.5 shrink-0" />
            {{ label }}
        </span>

        <button
            type="button"
            class="font-medium text-primary underline-offset-2 hover:underline dark:text-primary-300"
            @click="emit('view')"
        >
            View logs
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { History } from "lucide-vue-next";

const props = defineProps<{
    count: number;
    compact?: boolean;
}>();

const emit = defineEmits<{
    view: [];
}>();

const label = computed(
    () =>
        `${props.count} ${props.count === 1 ? "rejection" : "rejections"} on record`,
);
</script>
