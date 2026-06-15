<template>
    <div class="relative" :class="className" v-bind="restAttrs">
        <div>
            <label
                v-if="props.label"
                class="text-sm font-semibold gap-2 flex mb-1 text-slate-700"
            >
                {{ props.label
                }}<span v-if="props.required" class="text-red-500 ml-0.5"
                    >*</span
                >
            </label>
            <!-- <button
                type="button"
                class="w-full border rounded-lg px-4 py-2 flex items-center gap-2 bg-white text-left"
                @click="toggleOpen"
            > -->
            <button
                type="button"
                :class="[
                    'w-full border rounded-lg flex items-center gap-2 bg-white text-left transition',
                    props.inputClass ?? 'px-4 py-2',
                ]"
                @click="toggleOpen"
            >
                <span v-if="selectedOption" class="flex items-center gap-2">
                    <img
                        v-if="selectedOption.icon"
                        :src="selectedOption.icon"
                        class="w-5 object-cover"
                    />
                    <span>{{ selectedOption.label }} </span>
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
        </div>
        <div
            v-if="isOpen"
            class="absolute z-50 w-full mt-1 bg-white border rounded-lg shadow-lg"
        >
            <div class="p-2 border-b" v-if="searchBar">
                <input
                    ref="searchInput"
                    v-model="search"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="w-full px-3 py-2 text-sm border rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                    @keydown.esc="close"
                />
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
                    v-if="filteredItems.length === 0"
                    class="px-4 py-2 text-sm text-gray-400"
                >
                    No results
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, nextTick, useAttrs } from "vue";
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
}>();

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const search = ref("");
const searchInput = ref<HTMLInputElement | null>(null);

const placeholder = props.placeholder ?? "Select option";
const searchPlaceholder = props.searchPlaceholder ?? "Search...";

function toggleOpen() {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        search.value = "";

        nextTick(() => {
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
    close();
}

const selectedOption = computed(() =>
    props.items.find((i) => i.value === props.modelValue),
);

const filteredItems = computed(() => {
    if (!search.value) return props.items;

    const q = search.value.toLowerCase();

    return props.items.filter((i) => i.label.toLowerCase().includes(q));
});
</script>
