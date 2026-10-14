<template>
    <div>
        <textarea
            v-model="text"
            rows="3"
            maxlength="1000"
            :disabled="saving"
            :placeholder="placeholder"
            class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-secondary dark:text-gray-300 dark:placeholder:text-gray-500"
        />

        <p v-if="error" class="mt-1 text-xs text-rose-600 dark:text-rose-300">
            {{ error }}
        </p>

        <div class="mt-2 flex items-center justify-between gap-2">
            <span class="text-[11px] text-slate-400 dark:text-gray-500">
                {{ text.length }}/1000
            </span>

            <div class="flex items-center gap-2">
                <button
                    v-if="showCancel"
                    type="button"
                    :disabled="saving"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10"
                    @click="emit('cancel')"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    :disabled="saving || !text.trim()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="submit"
                >
                    <LoaderCircle v-if="saving" class="h-3.5 w-3.5 animate-spin" />
                    {{ saving ? "Saving..." : submitLabel }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { LoaderCircle } from "lucide-vue-next";

const props = withDefaults(
    defineProps<{
        initial?: string;
        saving?: boolean;
        submitLabel?: string;
        placeholder?: string;
        showCancel?: boolean;
    }>(),
    {
        initial: "",
        submitLabel: "Add note",
        placeholder: "Write what they did...",
    },
);

const emit = defineEmits<{
    (e: "submit", text: string): void;
    (e: "cancel"): void;
}>();

const text = ref(props.initial);
const error = ref("");

watch(text, () => (error.value = ""));

function submit() {
    const value = text.value.trim();

    if (value.length < 3) {
        error.value = "Write a note of at least 3 characters.";
        return;
    }

    emit("submit", value);
}

function clear() {
    text.value = "";
    error.value = "";
}

defineExpose({ clear });
</script>
