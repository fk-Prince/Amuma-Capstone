<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-primary-900/50 backdrop-blur-sm p-4"
            @click.self="handleClose"
        >
            <div
                class="w-full max-w-md rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60"
            >
                <div class="border-b border-primary-100 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3
                                class="text-base font-semibold text-primary-900"
                            >
                                Cancel Admission
                            </h3>

                            <p class="text-xs text-muted mt-1">
                                Please provide a reason for cancelling this
                                admission.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600 transition"
                            :disabled="loading"
                            @click="handleClose"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-5 h-5"
                            >
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div>
                        <label
                            for="cancellation-reason"
                            class="block text-xs font-medium text-primary-900 mb-2"
                        >
                            Cancellation reason
                        </label>

                        <textarea
                            id="cancellation-reason"
                            v-model="cancellationReason"
                            rows="4"
                            :disabled="loading"
                            placeholder="Enter the reason for cancelling this admission..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-primary-900 placeholder:text-slate-400 outline-none resize-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-slate-50 disabled:cursor-not-allowed"
                        />

                        <p
                            v-if="cancellationReasonError"
                            class="mt-1.5 text-xs text-rose-600"
                        >
                            {{ cancellationReasonError }}
                        </p>
                    </div>

                    <div
                        class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2"
                    >
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition disabled:opacity-40"
                            :disabled="loading"
                            @click="handleClose"
                        >
                            Keep Admission
                        </button>

                        <button
                            type="button"
                            class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-rose-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="loading || !cancellationReason.trim()"
                            @click="handleConfirm"
                        >
                            {{ loading ? "Cancelling..." : "Cancel Admission" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

const props = defineProps<{
    open: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "confirm", reason: string): void;
    (e: "cancel"): void;
}>();

const cancellationReason = ref("");
const cancellationReasonError = ref("");

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            cancellationReason.value = "";
            cancellationReasonError.value = "";
        }
    },
);

function handleClose() {
    if (props.loading) return;
    emit("cancel");
}

function handleConfirm() {
    const reason = cancellationReason.value.trim();

    if (!reason) {
        cancellationReasonError.value =
            "Please provide a reason for cancelling this admission.";
        return;
    }

    if (reason.length < 3) {
        cancellationReasonError.value =
            "Cancellation reason must be at least 3 characters.";
        return;
    }

    cancellationReasonError.value = "";
    emit("confirm", reason);
}
</script>
