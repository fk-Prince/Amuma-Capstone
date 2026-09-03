<template>
    <a
        :href="url"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600 transition hover:border-primary hover:bg-primary-50 hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-primary-500/10"
    >
        <svg
            v-if="isPdf"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="h-3.5 w-3.5"
        >
            <path
                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
            />
            <path d="M14 2v6h6" />
            <path d="M8 13h2" />
            <path d="M8 17h6" />
        </svg>

        <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="h-3.5 w-3.5"
        >
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="m21 15-5-5L5 21" />
        </svg>

        {{ label }}
    </a>
</template>

<script setup lang="ts">
const props = defineProps<{
    url: string;
    label: string;
}>();

const isPdf = computed(() => /\.pdf($|\?)/i.test(props.url));
</script>
