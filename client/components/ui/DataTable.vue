<template>
    <div
        class="h-full rounded-2xl border border-[#E4EFED] bg-white shadow-sm overflow-hidden flex flex-col dark:border-white/10 dark:bg-secondary"
    >
        <div
            v-if="searchable || $slots.actions"
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-b border-[#E4EFED] shrink-0 dark:border-white/10"
        >
            <div v-if="searchable" class="w-full sm:max-w-sm">
                <BaseInput
                    v-model="searchInput"
                    :placeholder="searchPlaceholder"
                    is-search
                    @keyup.enter="emitSearchNow"
                />
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>

        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full min-w-[42rem] text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50/70 dark:bg-white/5">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-3 sm:px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap bg-slate-50/70 dark:text-gray-400 dark:bg-white/5"
                            :class="[
                                alignClass(col.align),
                                col.sortable
                                    ? 'cursor-pointer select-none hover:text-slate-700 dark:hover:text-gray-200'
                                    : '',
                            ]"
                            @click="col.sortable && toggleSort(col.key)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ col.label }}

                                <span
                                    v-if="col.sortable"
                                    class="text-slate-300 dark:text-gray-600"
                                >
                                    <span v-if="sortKey === col.key">
                                        {{ sortDir === "asc" ? "↑" : "↓" }}
                                    </span>

                                    <span v-else> ↕ </span>
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#EEF3F1] dark:divide-white/10">
                    <!-- <template v-if="loading">
                        <tr
                            v-for="n in pagination.pageSize.value"
                            :key="`skeleton-row-${n}`"
                        >
                            <td
                                v-for="(col, colIndex) in columns"
                                :key="col.key"
                                class="px-5 py-4"
                            >
                                <div
                                    class="h-3 rounded bg-slate-100 animate-pulse dark:bg-white/10"
                                    :class="[
                                        colIndex === 0
                                            ? 'w-[60%]'
                                            : colIndex === 1
                                              ? 'w-[75%]'
                                              : colIndex === 2
                                                ? 'w-[45%]'
                                                : colIndex === 3
                                                  ? 'w-[65%]'
                                                  : 'w-[50%]',
                                    ]"
                                />
                            </td>
                        </tr>
                    </template> -->

                    <template v-if="loading">
                        <tr
                            v-for="n in pagination.pageSize.value"
                            :key="`skeleton-row-${n}`"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-5 py-4"
                            >
                                <div
                                    class="h-4 rounded bg-slate-200 dark:bg-white/10 animate-pulse"
                                    :style="{
                                        width: `${50 + ((n * 17 + columns.indexOf(col) * 23) % 51)}%`,
                                    }"
                                />
                            </td>
                        </tr>
                    </template>
                    <tr v-else-if="!sortedRows.length">
                        <td :colspan="columns.length" class="px-5 py-14">
                            <div class="flex flex-col items-center text-center">
                                <p class="text-sm font-medium text-slate-600 dark:text-gray-300">
                                    {{ emptyTitle }}
                                </p>

                                <p
                                    v-if="emptyDescription"
                                    class="text-sm text-slate-400 mt-1 dark:text-gray-500"
                                >
                                    {{ emptyDescription }}
                                </p>
                            </div>
                        </td>
                    </tr>

                    <template v-else>
                        <tr
                            v-for="row in sortedRows"
                            :key="String(rowKey(row))"
                            class="hover:bg-slate-50/60 dark:hover:bg-white/5 transition"
                            :class="onRowClick ? 'cursor-pointer' : ''"
                            @click="onRowClick?.(row)"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-3 sm:px-5 py-3.5 text-slate-700 dark:text-gray-300 whitespace-nowrap"
                                :class="alignClass(col.align)"
                            >
                                <slot
                                    :name="`cell-${col.key}`"
                                    :row="row"
                                    :value="row[col.key]"
                                >
                                    {{ row[col.key] ?? "—" }}
                                </slot>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div
            v-if="!loading && pagination.totalItems.value > 0"
            class="flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-3.5 border-t border-[#E4EFED] bg-white shrink-0 dark:border-white/10 dark:bg-secondary"
        >
            <p class="text-xs text-slate-400 dark:text-gray-500">
                Showing
                <span class="font-medium text-slate-600 dark:text-gray-300">
                    {{ pagination.rangeStart.value }}
                </span>
                –
                <span class="font-medium text-slate-600 dark:text-gray-300">
                    {{ pagination.rangeEnd.value }}
                </span>
                of
                <span class="font-medium text-slate-600 dark:text-gray-300">
                    {{ pagination.totalItems.value }}
                </span>
            </p>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="h-8 w-8 rounded-lg hover:bg-slate-100 disabled:opacity-30 dark:text-gray-300 dark:hover:bg-white/10"
                    :disabled="!pagination.canGoPrev.value"
                    @click="goTo(pagination.currentPage.value - 1)"
                >
                    ‹
                </button>

                <button
                    v-if="firstPageNumber > 1"
                    class="h-8 min-w-8 px-2 rounded-lg hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-white/10"
                    @click="goTo(1)"
                >
                    1
                </button>

                <span v-if="firstPageNumber > 2" class="text-slate-300 px-1 dark:text-gray-600">
                    …
                </span>

                <button
                    v-for="page in pagination.pageNumbers.value"
                    :key="page"
                    class="h-8 min-w-8 px-2 rounded-lg"
                    :class="
                        page === pagination.currentPage.value
                            ? 'bg-primary text-white'
                            : 'hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-white/10'
                    "
                    @click="goTo(page)"
                >
                    {{ page }}
                </button>

                <span
                    v-if="lastPageNumber < pagination.totalPages.value - 1"
                    class="text-slate-300 px-1 dark:text-gray-600"
                >
                    …
                </span>

                <button
                    v-if="lastPageNumber < pagination.totalPages.value"
                    class="h-8 min-w-8 px-2 rounded-lg hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-white/10"
                    @click="goTo(pagination.totalPages.value)"
                >
                    {{ pagination.totalPages.value }}
                </button>

                <button
                    type="button"
                    class="h-8 w-8 rounded-lg hover:bg-slate-100 disabled:opacity-30 dark:text-gray-300 dark:hover:bg-white/10"
                    :disabled="!pagination.canGoNext.value"
                    @click="goTo(pagination.currentPage.value + 1)"
                >
                    ›
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts" generic="T extends Record<string, any>">
import { ref, computed, watch } from "vue";

import BaseInput from "~/components/ui/BaseInput.vue";
import type { usePagination } from "~/composables/usePagination";

export interface DataTableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    align?: "left" | "center" | "right";
}

const props = withDefaults(
    defineProps<{
        columns: DataTableColumn[];
        rows: T[];
        pagination: ReturnType<typeof usePagination>;
        rowKey?: (row: T) => string | number;
        loading?: boolean;
        searchable?: boolean;
        searchPlaceholder?: string;
        searchDebounce?: number;
        emptyTitle?: string;
        emptyDescription?: string;
        onRowClick?: (row: T) => void;
    }>(),
    {
        loading: false,
        searchable: true,
        searchPlaceholder: "Search…",
        searchDebounce: 400,
        emptyTitle: "No results found",
        emptyDescription: "",
    },
);

const emit = defineEmits<{
    search: [query: string];
    "page-change": [page: number];
}>();

const searchInput = ref("");

let debounceHandle: ReturnType<typeof setTimeout> | null = null;

watch(searchInput, (value) => {
    if (debounceHandle) {
        clearTimeout(debounceHandle);
    }

    debounceHandle = setTimeout(() => {
        props.pagination.reset();
        emit("search", value.trim());
    }, props.searchDebounce);
});

function emitSearchNow() {
    if (debounceHandle) {
        clearTimeout(debounceHandle);
    }

    props.pagination.reset();

    emit("search", searchInput.value.trim());
}

const sortKey = ref<string | null>(null);

const sortDir = ref<"asc" | "desc">("asc");

function toggleSort(key: string) {
    if (sortKey.value !== key) {
        sortKey.value = key;
        sortDir.value = "asc";

        return;
    }

    if (sortDir.value === "asc") {
        sortDir.value = "desc";
    } else {
        sortKey.value = null;
    }
}

const sortedRows = computed(() => {
    if (!sortKey.value) {
        return props.rows;
    }

    const key = sortKey.value;

    const dir = sortDir.value === "asc" ? 1 : -1;

    return [...props.rows].sort((a, b) => {
        const av = a[key];
        const bv = b[key];

        if (av == null) return 1;

        if (bv == null) return -1;

        return String(av).localeCompare(String(bv)) * dir;
    });
});

/**
 * Pagination helpers
 */

const firstPageNumber = computed(() =>
    paginationValue(props.pagination.pageNumbers.value[0]),
);

const lastPageNumber = computed(() =>
    paginationValue(
        props.pagination.pageNumbers.value[
            props.pagination.pageNumbers.value.length - 1
        ],
    ),
);

function paginationValue(value: number | undefined) {
    return value ?? 0;
}

function goTo(page: number) {
    if (page < 1 || page > props.pagination.totalPages.value) {
        return;
    }

    props.pagination.currentPage.value = page;

    emit("page-change", page);
}

function rowKey(row: T) {
    return props.rowKey ? props.rowKey(row) : (row.id ?? JSON.stringify(row));
}

function alignClass(align?: "left" | "center" | "right") {
    if (align === "right") return "text-right";

    if (align === "center") return "text-center";

    return "text-left";
}

function skeletonWidth() {
    const widths = ["40%", "60%", "75%", "50%"];

    return widths[Math.floor(Math.random() * widths.length)];
}
</script>
