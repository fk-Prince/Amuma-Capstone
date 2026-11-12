<template>
    <div
        ref="wrapperRef"
        class="relative"
        :class="className"
        v-bind="restAttrs"
    >
        <div>
            <label
                v-if="props.label"
                class="text-sm font-semibold gap-2 flex mb-1 text-slate-700 dark:text-gray-300"
            >
                {{ props.label }}
                <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
            </label>

            <button
                ref="triggerRef"
                type="button"
                :disabled="disabled"
                :class="[
                    'w-full border rounded-lg flex items-center gap-2 bg-white dark:bg-secondary dark:text-white text-left transition',
                    props.inputClass ?? 'px-4 py-2',
                    currentError
                        ? 'border-red-400 focus-within:ring-red-500/15'
                        : 'border-slate-200 dark:border-white/10 focus-within:ring-blue-500/15',
                    disabled
                        ? 'opacity-60 cursor-not-allowed bg-gray-50 dark:bg-white/5'
                        : '',
                ]"
                @click="toggleOpen"
            >
                <span v-if="selectedOption" class="flex items-center gap-2">
                    <img
                        v-if="selectedOption.icon"
                        :src="selectedOption.icon"
                        :class="
                            selectedOption.iconClass ?? 'h-5 w-5 object-cover'
                        "
                    />

                    <component
                        v-else-if="selectedOption.iconComponent"
                        :is="selectedOption.iconComponent"
                        class="h-4 w-4 text-gray-500 dark:text-gray-400"
                    />

                    <span
                        v-if="!selectedOption.label"
                        class="text-gray-400 dark:text-gray-500"
                    >
                        {{ placeholder }}
                    </span>

                    <span v-else>
                        {{ selectedOption.label }}
                    </span>
                </span>
                <span v-else class="text-gray-400 dark:text-gray-500">
                    {{ placeholder }}
                </span>

                <svg
                    class="ml-auto w-4 h-4 text-gray-400 dark:text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>

            <p v-if="currentError" class="text-xs text-red-500 mt-0.5">
                {{ currentError }}
            </p>
        </div>

        <Teleport to="body">
            <div
                v-if="isOpen"
                data-combobox-panel
                class="fixed z-[10000] flex bg-white dark:bg-secondary dark:border-white/10 border overflow-hidden rounded-lg shadow-lg"
                :class="openUpward ? 'flex-col-reverse' : 'flex-col'"
                :style="dropdownStyle"
            >
                <div
                    v-if="searchBar"
                    class="p-2 dark:border-white/10"
                    :class="openUpward ? 'border-t' : 'border-b'"
                >
                    <div class="relative">
                        <input
                            ref="searchInput"
                            v-model="search"
                            type="text"
                            :placeholder="
                                searchBarPlaceHolder || searchPlaceholder
                            "
                            :style="{ height: `${triggerHeight}px` }"
                            class="w-full pl-9 pr-3 text-sm border dark:border-white/10 dark:bg-secondary dark:text-white rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />

                        <SearchIcon
                            extraClass="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                        />
                    </div>
                </div>

                <ul class="max-h-48 overflow-y-auto">
                    <li
                        v-for="item in filteredItems"
                        :key="item.value"
                        class="px-4 py-2 flex items-center gap-2"
                        :class="[
                            item.disabled
                                ? 'cursor-not-allowed text-gray-400 dark:text-gray-500'
                                : 'cursor-pointer hover:bg-gray-100 dark:text-white dark:hover:bg-white/5',
                            {
                                'bg-gray-50 dark:bg-white/5 font-medium':
                                    modelValue === item.value,
                            },
                        ]"
                        :aria-disabled="item.disabled || undefined"
                        :title="item.disabled ? item.hint : undefined"
                        @mousedown.prevent="!item.disabled && selectItem(item)"
                    >
                        <img
                            v-if="item.icon"
                            :src="item.icon"
                            :class="item.iconClass ?? 'h-5 w-5 object-cover'"
                        />

                        <component
                            v-else-if="item.iconComponent"
                            :is="item.iconComponent"
                            class="h-4 w-4 text-gray-500 dark:text-gray-400"
                        />

                        <span>{{ item.label }}</span>

                        <Lock
                            v-if="item.disabled"
                            class="ml-auto h-3.5 w-3.5 shrink-0 text-gray-400 dark:text-gray-500"
                            :aria-label="item.hint || 'Locked'"
                        />
                    </li>

                    <li
                        v-if="filteredItems.length === 0 && !showCreateOption"
                        class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500"
                    >
                        No results
                    </li>

                    <li
                        v-if="showCreateOption"
                        class="px-4 py-2 cursor-pointer text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-500/10 dark:text-blue-300"
                        @mousedown.prevent="createItem(search)"
                    >
                        + Add "{{ search }}"
                    </li>
                </ul>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import type { Component, CSSProperties } from "vue";
import {
    computed,
    ref,
    nextTick,
    useAttrs,
    watch,
    onMounted,
    onBeforeUnmount,
} from "vue";
import SearchIcon from "../icons/search.vue";
import { Lock } from "lucide-vue-next";

const attrs = useAttrs();
const { class: className, ...restAttrs } = attrs;

type Item = {
    label: string;
    value: any;
    icon?: string;
    iconClass?: string;
    iconComponent?: Component;
    disabled?: boolean;
    hint?: string;
};
const props = withDefaults(
    defineProps<{
        modelValue: any;
        items: Item[];
        label?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        required?: boolean;
        searchBar?: boolean;
        searchBarPlaceHolder?: string;
        inputClass?: string;
        allowCustom?: boolean;
        error?: string;
        position?: "top" | "bottom";
        disabled?: boolean;
    }>(),
    {
        position: "bottom",
        disabled: false,
    },
);

const emit = defineEmits(["update:modelValue", "create:item"]);

const isOpen = ref(false);
const search = ref("");
const searchInput = ref<HTMLInputElement | null>(null);

const wrapperRef = ref<HTMLElement | null>(null);
const triggerRef = ref<HTMLElement | null>(null);

const baseItems = ref<Item[]>([]);
const localItems = ref<Item[]>([]);

const currentError = ref(props.error);

const dropdownStyle = ref<CSSProperties>({});

watch(
    () => props.error,
    (val) => {
        currentError.value = val;
    },
);

function syncItems() {
    baseItems.value = [...props.items];
    localItems.value = [...props.items];
}

const openUpward = ref(false);
const triggerHeight = ref(0);

const SEARCH_INSET = 9;

function updatePosition() {
    if (!triggerRef.value) return;

    const rect = triggerRef.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;

    openUpward.value = props.position === "top" || spaceBelow < 260;
    triggerHeight.value = rect.height;

    if (props.searchBar) {
        dropdownStyle.value = {
            left: `${rect.left - SEARCH_INSET}px`,
            width: `${rect.width + SEARCH_INSET * 2}px`,
            ...(openUpward.value
                ? { bottom: `${window.innerHeight - rect.bottom}px` }
                : { top: `${rect.top}px` }),
        };

        return;
    }

    dropdownStyle.value = {
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        ...(openUpward.value
            ? { bottom: `${window.innerHeight - rect.top + 4}px` }
            : { top: `${rect.bottom + 4}px` }),
    };
}

function toggleOpen() {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        syncItems();
        nextTick(() => {
            updatePosition();
            searchInput.value?.focus();
        });
    }
}

function close() {
    isOpen.value = false;
    search.value = "";
}

function selectItem(item: Item) {
    emit("update:modelValue", item.value);

    if (currentError.value) {
        currentError.value = "";
    }

    close();
}

function createItem(label: string) {
    const q = label.trim().toLowerCase();
    const existing = localItems.value.find((i) => i.label.toLowerCase() === q);

    if (existing) {
        emit("update:modelValue", existing.value);

        if (currentError.value) {
            currentError.value = "";
        }

        close();
        return;
    }

    const value = label;
    const newItem: Item = { label, value };

    emit("create:item", newItem);
    emit("update:modelValue", value);

    localItems.value = [...baseItems.value, newItem];

    if (currentError.value) {
        currentError.value = "";
    }

    close();
}

const placeholder = props.placeholder;
const searchPlaceholder = props.searchPlaceholder ?? "Search...";

const selectedOption = computed<Item>(() => {
    return (
        localItems.value.find((i) => i.value === props.modelValue) || {
            label: props.modelValue,
            value: props.modelValue,
        }
    );
});

const filteredItems = computed(() => {
    const list = localItems.value;

    if (!search.value) return list;

    const q = search.value.toLowerCase();

    return list.filter((i) => i.label.toLowerCase().includes(q));
});

const showCreateOption = computed(() => {
    if (!props.allowCustom) return false;

    const q = search.value?.trim();
    if (!q) return false;

    return !localItems.value.some(
        (i) => i.label.toLowerCase() === q.toLowerCase(),
    );
});

watch(
    () => props.items,
    (val) => {
        baseItems.value = [...val];
        localItems.value = [...val];
    },
    { immediate: true, deep: true },
);

function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!wrapperRef.value) return;
    if (
        !wrapperRef.value.contains(target) &&
        !target.closest("[data-combobox-panel]")
    ) {
        isOpen.value = false;
        search.value = "";
    }
}

function handleReposition() {
    if (isOpen.value) updatePosition();
}

onMounted(() => {
    document.addEventListener("mousedown", handleClickOutside);
    window.addEventListener("scroll", handleReposition, true);
    window.addEventListener("resize", handleReposition);
});

onBeforeUnmount(() => {
    document.removeEventListener("mousedown", handleClickOutside);
    window.removeEventListener("scroll", handleReposition, true);
    window.removeEventListener("resize", handleReposition);
});
</script>
