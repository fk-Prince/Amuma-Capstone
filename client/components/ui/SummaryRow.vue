<template>
    <div
        class="flex flex-col items-start gap-1 py-2.5 border-t border-slate-100 dark:border-white/10 first:border-0 first:pt-0"
        :class="expandable ? '' : 'sm:flex-row sm:justify-between sm:gap-4'"
    >
        <span
            class="text-slate-500 dark:text-gray-400 text-[13px] font-semibold tracking-wide shrink-0 pt-0.5"
        >
            {{ label }}
        </span>

        <div
            v-if="expandable && hasValue"
            class="w-full text-left text-sm font-medium leading-relaxed text-slate-800 dark:text-white"
        >
            <p class="whitespace-pre-line break-words">
                {{ shownText }}
            </p>

            <button
                v-if="needsToggle"
                type="button"
                class="mt-1 text-xs font-semibold text-primary hover:underline"
                @click="expanded = !expanded"
            >
                {{ expanded ? "See less" : "See more" }}
            </button>
        </div>

        <span
            v-else
            class="text-slate-800 dark:text-white text-sm font-medium break-words max-w-full leading-snug"
            :class="[
                expandable ? 'text-left' : 'sm:text-right sm:max-w-[60%]',
                { 'text-slate-400 dark:text-gray-500 font-normal': !hasValue },
            ]"
        >
            {{ displayValue }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

const MAX_SENTENCES = 5;
const MAX_CHARACTERS = 500;

const props = defineProps<{
    label: string;
    value?: string | number | null;
    expandable?: boolean;
}>();

const expanded = ref(false);

const hasValue = computed(
    () =>
        props.value !== undefined && props.value !== null && props.value !== "",
);

const displayValue = computed(() =>
    hasValue.value ? props.value : "Not provided",
);

const text = computed(() => String(props.value ?? "").trim());

const sentences = computed(
    () => text.value.match(/[^.!?]+(?:[.!?]+["')”]*|$)\s*/g) ?? [],
);

const needsToggle = computed(
    () =>
        sentences.value.length > MAX_SENTENCES ||
        text.value.length > MAX_CHARACTERS,
);

const collapsedText = computed(() => {
    const preview = sentences.value.slice(0, MAX_SENTENCES).join("").trim();

    return preview.length > MAX_CHARACTERS
        ? `${preview.slice(0, MAX_CHARACTERS).trimEnd()}…`
        : preview;
});

const shownText = computed(() =>
    needsToggle.value && !expanded.value ? collapsedText.value : text.value,
);
</script>
