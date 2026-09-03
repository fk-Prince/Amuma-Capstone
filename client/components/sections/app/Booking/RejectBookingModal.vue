<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @click.self="requestClose"
        >
            <div
                class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                role="dialog"
                aria-modal="true"
                aria-label="Reject booking"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            <TriangleAlert class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <h2
                                class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                            >
                                Reject booking
                            </h2>

                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                {{ referenceId }} — this can't be undone.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        aria-label="Close dialog"
                        :disabled="loading"
                        class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                        @click="requestClose"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="px-6 py-5">
                    <p
                        v-if="willRefund"
                        class="mb-4 flex items-start gap-2 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-[13px] leading-relaxed text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                            This booking has been paid. Rejecting it will
                            request a refund of the amount charged.
                        </span>
                    </p>

                    <label
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-400"
                    >
                        Reason for rejection
                        <span class="text-rose-500 dark:text-rose-300">*</span>
                    </label>

                    <textarea
                        v-model="reason"
                        rows="4"
                        maxlength="500"
                        placeholder="Let the client know why this booking can't be accepted — e.g. patient requires extensive assistance."
                        class="w-full resize-none rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:text-white dark:placeholder:text-gray-500"
                        :class="showError ? 'border-rose-300' : ''"
                        @input="showError = false"
                    />

                    <div class="mt-1.5 flex items-center justify-between">
                        <span class="text-xs text-slate-400 dark:text-gray-500">
                            {{ reason.length }}/500
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in PRESETS"
                            :key="preset"
                            type="button"
                            class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-400"
                            @click="applyPreset(preset)"
                        >
                            {{ preset }}
                        </button>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        :disabled="loading"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-40 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/5"
                        @click="requestClose"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="loading"
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="submit"
                    >
                        <LoaderCircle
                            v-if="loading"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ loading ? "Rejecting..." : "Reject booking" }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Info, LoaderCircle, TriangleAlert, X } from "lucide-vue-next";

const props = defineProps<{
    referenceId: string;
    loading?: boolean;
    willRefund?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm", reason: string): void;
}>();

const PRESETS = [
    "Incomplete or unclear patient information",
    "Patient Requires Extensive Assistance",
    "Outside our service area",
    "Patient Location Difficult to Identify",
];

const reason = ref("");
const showError = ref(false);

const applyPreset = (preset: string) => {
    reason.value = preset;
    showError.value = false;
};

const submit = () => {
    if (props.loading) return;

    if (!reason.value.trim()) {
        showError.value = true;
        return;
    }

    emit("confirm", reason.value.trim());
};

const requestClose = () => {
    if (props.loading) return;
    emit("close");
};
</script>
