<template>
    <div>
        <div
            class="rounded-lg border p-2.5"
            :class="
                displayError
                    ? 'border-red-400 dark:border-red-500/60'
                    : 'border-slate-200 focus-within:border-primary dark:border-white/10'
            "
        >
            <div class="flex flex-wrap gap-1.5">
                <span
                    v-for="activity in modelValue"
                    :key="activity"
                    class="inline-flex items-center gap-1 rounded-full bg-primary/10 py-0.5 pl-2.5 pr-1 text-[11px] font-medium text-primary dark:bg-primary-500/15 dark:text-primary-300"
                >
                    {{ activity }}
                    <button
                        type="button"
                        class="rounded-full p-0.5 hover:bg-primary/15"
                        :disabled="disabled"
                        :aria-label="`Remove ${activity}`"
                        @click="toggle(activity)"
                    >
                        <X class="h-3 w-3" />
                    </button>
                </span>

                <input
                    v-model="draft"
                    type="text"
                    maxlength="60"
                    :disabled="disabled"
                    placeholder="Add activity…"
                    class="h-6 min-w-[8rem] flex-1 bg-transparent px-1 text-xs text-slate-800 outline-none placeholder:text-slate-400 dark:text-white"
                    @keydown.enter.prevent="add"
                />
            </div>
        </div>

        <div
            v-if="unselected.length"
            class="mt-2 flex flex-wrap items-center gap-1.5"
        >
            <button
                type="button"
                class="text-[11px] font-medium text-slate-500 hover:text-primary dark:text-gray-400"
                @click="showSuggestions = !showSuggestions"
            >
                {{
                    showSuggestions
                        ? "Hide suggestions"
                        : `+ ${unselected.length} suggestions`
                }}
            </button>

            <template v-if="showSuggestions">
                <button
                    v-for="activity in unselected"
                    :key="activity"
                    type="button"
                    :disabled="disabled"
                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-2 py-0.5 text-[11px] text-slate-600 transition-colors hover:border-primary-200 hover:text-primary dark:border-white/10 dark:text-gray-400"
                    @click="toggle(activity)"
                >
                    <Plus class="h-3 w-3" />
                    {{ activity }}
                </button>
            </template>
        </div>

        <p v-if="displayError" class="text-xs text-red-500 mt-2 ml-1">
            {{ displayError }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { Plus, X } from "lucide-vue-next";
import { EMOJI_MESSAGE, hasEmoji } from "~/utils/emoji";

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        suggestions?: string[];
        error?: string;
        disabled?: boolean;
    }>(),
    {
        suggestions: () => [],
        error: "",
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: string[]): void;
}>();

const draft = ref("");
const custom = ref<string[]>([]);
const showSuggestions = ref(false);
const localError = ref("");

const displayError = computed(() => localError.value || props.error);

const same = (a: string, b: string) =>
    a.trim().toLowerCase() === b.trim().toLowerCase();

const options = computed(() =>
    [...props.suggestions, ...custom.value, ...props.modelValue].filter(
        (activity, index, list) =>
            list.findIndex((other) => same(other, activity)) === index,
    ),
);

const isSelected = (activity: string) =>
    props.modelValue.some((selected) => same(selected, activity));

const unselected = computed(() =>
    options.value.filter((activity) => !isSelected(activity)),
);

function toggle(activity: string) {
    localError.value = "";
    emit(
        "update:modelValue",
        isSelected(activity)
            ? props.modelValue.filter((selected) => !same(selected, activity))
            : [...props.modelValue, activity],
    );
}

function add() {
    const activity = draft.value.trim().replace(/\s+/g, " ");

    if (!activity) return;

    if (hasEmoji(activity)) {
        localError.value = EMOJI_MESSAGE;
        return;
    }

    const existing = options.value.find((option) => same(option, activity));

    if (!existing) custom.value.push(activity);

    if (!isSelected(existing ?? activity)) {
        emit("update:modelValue", [...props.modelValue, existing ?? activity]);
    }

    draft.value = "";
    localError.value = "";
}

function reset() {
    draft.value = "";
    custom.value = [];
    showSuggestions.value = false;
    localError.value = "";
}

defineExpose({ reset });
</script>
