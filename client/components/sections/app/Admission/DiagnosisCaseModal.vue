<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] flex h-full w-full items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                @click="$emit('close')"
            />

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="open"
                    class="relative flex max-h-[85vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Select Diagnosis Case
                            </h2>

                            <p
                                class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                            >
                                Only one diagnosis case per diagnosis
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                            aria-label="Close"
                            @click="$emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        class="border-b border-gray-100 px-6 py-3 dark:border-white/10"
                    >
                        <div class="relative">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                            />

                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search diagnosis cases..."
                                class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder-gray-500"
                            />
                        </div>
                    </div>

                    <div class="flex-1 space-y-2.5 overflow-y-auto px-6 py-5">
                        <div
                            v-if="!filteredCases.length"
                            class="flex flex-col items-center gap-2 py-14 text-center"
                        >
                            <PackageSearch
                                class="h-6 w-6 text-gray-300 dark:text-gray-600"
                            />

                            <p
                                class="text-sm font-medium text-gray-500 dark:text-gray-400"
                            >
                                No diagnosis cases found
                            </p>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Try a different search term.
                            </p>
                        </div>

                        <button
                            v-for="item in filteredCases"
                            :key="item.uuid"
                            type="button"
                            role="radio"
                            :aria-checked="item.uuid === localSelected"
                            class="group flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all"
                            :class="
                                item.uuid === localSelected
                                    ? 'border-primary bg-primary/5 shadow-sm shadow-primary/10 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                    : 'border-gray-200 hover:border-primary/40 hover:bg-gray-50/70 dark:border-white/10 dark:hover:border-primary/40 dark:hover:bg-white/5'
                            "
                            @click="select(item)"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                                :class="
                                    item.uuid === localSelected
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-gray-300 group-hover:border-primary/60 dark:border-white/20'
                                "
                            >
                                <Check
                                    v-if="item.uuid === localSelected"
                                    class="h-3 w-3"
                                    stroke-width="3"
                                />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <p
                                        class="text-sm font-semibold leading-tight text-gray-900 dark:text-white"
                                    >
                                        {{ item.title }}
                                    </p>

                                    <span
                                        class="whitespace-nowrap text-sm font-bold text-primary"
                                    >
                                        {{ formatCurrency(item.price) }}
                                        <span
                                            class="text-[11px] font-medium text-gray-400"
                                            >/ month</span
                                        >
                                    </span>
                                </div>

                                <template v-if="item.description">
                                    <p
                                        class="mt-1.5 text-xs leading-5 text-gray-500 dark:text-gray-400"
                                        :class="
                                            isExpanded(item)
                                                ? ''
                                                : 'line-clamp-2'
                                        "
                                    >
                                        {{ item.description }}
                                    </p>

                                    <span
                                        v-if="
                                            item.description.length >
                                            DESCRIPTION_PREVIEW_LENGTH
                                        "
                                        role="button"
                                        tabindex="0"
                                        class="mt-1 inline-flex items-center gap-0.5 text-xs font-semibold text-primary hover:underline"
                                        @click.stop="toggleDescription(item)"
                                        @keydown.enter.stop.prevent="
                                            toggleDescription(item)
                                        "
                                    >
                                        {{
                                            isExpanded(item)
                                                ? "Show less"
                                                : "Show more"
                                        }}
                                        <ChevronDown
                                            class="h-3.5 w-3.5 transition-transform"
                                            :class="
                                                isExpanded(item)
                                                    ? 'rotate-180'
                                                    : ''
                                            "
                                        />
                                    </span>
                                </template>
                            </div>
                        </button>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50/60 px-6 py-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{
                                    selectedCase
                                        ? selectedCase.title
                                        : "No diagnosis case selected"
                                }}
                            </p>
                            <p
                                class="text-lg font-bold leading-tight text-gray-900 dark:text-white"
                            >
                                {{
                                    formatCurrency(selectedCase?.price ?? 0)
                                }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                :disabled="!selectedCase"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm shadow-primary/30 transition-all hover:opacity-90 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                                @click="apply"
                            >
                                Select Diagnosis Case
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Check, ChevronDown, PackageSearch, Search, X } from "lucide-vue-next";

import type { DiagnosisCase } from "~/types/additional-charge";
import { formatCurrency } from "~/utils/currency";

const props = defineProps<{
    open: boolean;
    cases: DiagnosisCase[];
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
    (e: "close"): void;
}>();

const DESCRIPTION_PREVIEW_LENGTH = 110;

const searchQuery = ref("");
const localSelected = ref("");
const expanded = ref<Record<string, boolean>>({});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        searchQuery.value = "";
        expanded.value = {};
        localSelected.value = props.modelValue;
    },
    { immediate: true },
);

const filteredCases = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.cases.filter(
        (item) => !query || item.title.toLowerCase().includes(query),
    );
});

const selectedCase = computed(
    () => props.cases.find((item) => item.uuid === localSelected.value) ?? null,
);

const isExpanded = (item: DiagnosisCase) => !!expanded.value[item.uuid];

function toggleDescription(item: DiagnosisCase) {
    expanded.value[item.uuid] = !isExpanded(item);
}

function select(item: DiagnosisCase) {
    localSelected.value = item.uuid === localSelected.value ? "" : item.uuid;
}

function apply() {
    if (!selectedCase.value) return;

    emit("update:modelValue", selectedCase.value.uuid);
    emit("close");
}
</script>
