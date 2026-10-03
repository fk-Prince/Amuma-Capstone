<template>
    <div class="flex flex-col gap-1.5 font-primary">
        <label
            v-if="label"
            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
        >
            {{ label }}
        </label>

        <ul v-if="items.length" class="flex flex-col gap-1.5">
            <li
                v-for="(item, index) in items"
                :key="`${item}-${index}`"
                class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-800 dark:border-white/10 dark:bg-secondary dark:text-white"
            >
                <span class="truncate">{{ item }}</span>

                <button
                    type="button"
                    :aria-label="`Remove ${item}`"
                    class="shrink-0 rounded p-1 text-slate-400 transition hover:bg-slate-100 hover:text-red-500 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-red-400"
                    @click="remove(index)"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </li>
        </ul>

        <div class="flex items-center gap-2">
            <input
                v-model="draft"
                type="text"
                :placeholder="placeholder"
                class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500"
                @keydown.enter.prevent="add"
            />

            <button
                type="button"
                :disabled="!draft.trim()"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-gray-300"
                @click="add"
            >
                <Plus class="h-4 w-4" />
                Add
            </button>
        </div>

        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { Plus, X } from "lucide-vue-next";

const props = defineProps<{
    modelValue?: string | string[] | null;
    label?: string;
    placeholder?: string;
    error?: string;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
}>();

const draft = ref("");

const items = computed<string[]>(() => {
    const value = props.modelValue;

    if (Array.isArray(value)) return value.filter(Boolean);

    return String(value ?? "")
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean);
});

const emitItems = (next: string[]) => emit("update:modelValue", next.join(", "));

function add() {
    const value = draft.value.trim();

    if (!value) return;

    const exists = items.value.some(
        (item) => item.toLowerCase() === value.toLowerCase(),
    );

    if (!exists) emitItems([...items.value, value]);

    draft.value = "";
}

function remove(index: number) {
    emitItems(items.value.filter((_, i) => i !== index));
}
</script>
