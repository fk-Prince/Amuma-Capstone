<script setup lang="ts" generic="T">
import { computed, ref, watch } from "vue";
import AppIcon from "~/components/ui/AppIcon.vue";

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        subtitle?: string;
        items: T[];
        pageSize?: number;
        loading?: boolean;
        loadingLabel?: string;
        emptyLabel?: string;
        filters?: { value: string; label: string }[];
        filter?: string;
    }>(),
    {
        pageSize: 15,
        loading: false,
        loadingLabel: "Loading…",
        emptyLabel: "Nothing to show yet.",
    },
);

const emit = defineEmits<{
    (event: "close"): void;
    (event: "update:filter", value: string): void;
}>();

const page = ref(1);

const pageCount = computed(() =>
    Math.max(1, Math.ceil(props.items.length / props.pageSize)),
);

const pageItems = computed(() => {
    const start = (page.value - 1) * props.pageSize;

    return props.items.slice(start, start + props.pageSize);
});

const rangeStart = computed(() =>
    props.items.length ? (page.value - 1) * props.pageSize + 1 : 0,
);

const rangeEnd = computed(() =>
    Math.min(page.value * props.pageSize, props.items.length),
);

watch(
    () => [props.open, props.filter, props.items.length] as const,
    () => {
        page.value = 1;
    },
);

function go(step: number) {
    page.value = Math.min(pageCount.value, Math.max(1, page.value + step));
}
</script>

<template>
    <Transition name="modal">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-sm font-bold text-gray-900 dark:text-white"
                        >
                            {{ title }}
                        </p>

                        <p
                            v-if="subtitle"
                            class="mt-0.5 truncate text-xs text-gray-400 dark:text-gray-500"
                        >
                            {{ subtitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        <AppIcon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div
                    v-if="filters?.length"
                    class="flex items-center gap-1.5 border-b border-gray-100 px-6 py-3 dark:border-white/10"
                >
                    <button
                        v-for="option in filters"
                        :key="option.value"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-[11px] font-semibold transition"
                        :class="
                            filter === option.value
                                ? 'bg-primary text-white'
                                : 'bg-gray-50 text-gray-500 hover:bg-gray-100 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10'
                        "
                        @click="emit('update:filter', option.value)"
                    >
                        {{ option.label }}
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                    <div
                        v-if="loading"
                        class="flex items-center justify-center gap-2 py-14 text-xs text-gray-400 dark:text-gray-500"
                    >
                        <AppIcon
                            name="loader-circle"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ loadingLabel }}
                    </div>

                    <p
                        v-else-if="!items.length"
                        class="py-14 text-center text-xs text-gray-400 dark:text-gray-500"
                    >
                        {{ emptyLabel }}
                    </p>

                    <slot v-else :items="pageItems" />
                </div>

                <div
                    v-if="!loading && pageCount > 1"
                    class="flex items-center justify-between gap-3 border-t border-gray-100 px-6 py-3 dark:border-white/10"
                >
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">
                        Showing {{ rangeStart }}–{{ rangeEnd }} of
                        {{ items.length }}
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            :disabled="page === 1"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="go(-1)"
                        >
                            <AppIcon name="chevron-left" class="h-4 w-4" />
                        </button>

                        <span
                            class="px-2 text-[11px] font-semibold text-gray-600 dark:text-gray-300"
                        >
                            {{ page }} / {{ pageCount }}
                        </span>

                        <button
                            type="button"
                            :disabled="page === pageCount"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="go(1)"
                        >
                            <AppIcon name="chevron-right" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(12px) scale(0.98);
}
</style>
