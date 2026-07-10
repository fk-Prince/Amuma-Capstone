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
                class="text-sm font-semibold gap-2 flex mb-1 text-slate-700"
            >
                {{ props.label }}
                <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
            </label>

            <button
                type="button"
                :class="[
                    'w-full border rounded-lg flex items-center gap-2 bg-white text-left transition',
                    props.inputClass ?? 'px-4 py-2',
                    currentError
                        ? 'border-red-400 focus-within:ring-red-500/15'
                        : 'border-slate-200 focus-within:ring-blue-500/15',
                ]"
                @click="toggleOpen"
            >
                <span v-if="selectedOption" class="flex items-center gap-2">
                    <span v-if="!selectedOption.label" class="text-gray-400">
                        {{ placeholder }}
                    </span>
                    <span v-else>{{ selectedOption.label }}</span>
                </span>

                <span v-else class="text-gray-400">
                    {{ placeholder }}
                </span>

                <svg
                    class="ml-auto w-4 h-4 text-gray-400"
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

        <div
            v-if="isOpen"
            class="absolute z-50 w-full mt-1 bg-white border overflow-hidden rounded-lg shadow-lg"
        >
            <div class="p-2 border-b" v-if="searchBar">
                <div class="relative">
                    <input
                        ref="searchInput"
                        v-model="search"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-9 pr-3 py-2 text-sm border rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />

                    <SearchIcon
                        extraClass="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"
                    />
                </div>
            </div>

            <ul class="max-h-48 overflow-y-auto">
                <li
                    v-for="item in filteredItems"
                    :key="item.value"
                    class="px-4 py-2 cursor-pointer hover:bg-gray-100 flex items-center gap-2"
                    :class="{
                        'bg-gray-50 font-medium': modelValue === item.value,
                    }"
                    @mousedown.prevent="selectItem(item)"
                >
                    <img
                        v-if="item.icon"
                        :src="item.icon"
                        class="w-5 object-cover"
                    />
                    <span>{{ item.label }}</span>
                </li>

                <li
                    v-if="filteredItems.length === 0 && !showCreateOption"
                    class="px-4 py-2 text-sm text-gray-400"
                >
                    No results
                </li>

                <li
                    v-if="showCreateOption"
                    class="px-4 py-2 cursor-pointer text-blue-600 hover:bg-blue-50"
                    @mousedown.prevent="createItem(search)"
                >
                    + Add "{{ search }}"
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
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

const attrs = useAttrs();
const { class: className, ...restAttrs } = attrs;

type Item = {
    label: string;
    value: any;
    icon?: string;
};

const props = defineProps<{
    modelValue: any;
    items: Item[];
    label?: string;
    placeholder?: string;
    searchPlaceholder?: string;
    required?: boolean;
    searchBar?: boolean;
    inputClass?: string;
    allowCustom?: boolean;
    error?: string;
}>();

const emit = defineEmits(["update:modelValue", "create:item"]);

const isOpen = ref(false);
const search = ref("");
const searchInput = ref<HTMLInputElement | null>(null);

const wrapperRef = ref<HTMLElement | null>(null);

const baseItems = ref<Item[]>([]);
const localItems = ref<Item[]>([]);

const currentError = ref(props.error);

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

function toggleOpen() {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        syncItems();
        nextTick(() => searchInput.value?.focus());
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

const selectedOption = computed(() => {
    return (
        localItems.value.find((i) => i.value === props.modelValue) || {
            label: props.modelValue,
            value: props.modelValue,
            icon: props.modelValue,
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
    if (!wrapperRef.value) return;
    if (!wrapperRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        search.value = "";
    }
}

onMounted(() => {
    document.addEventListener("mousedown", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("mousedown", handleClickOutside);
});
</script>
