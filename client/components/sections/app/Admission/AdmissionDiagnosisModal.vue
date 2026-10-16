<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-5"
            >
                <div
                    class="flex w-full max-w-xl max-h-[88dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight">
                                Diagnosis Cases
                            </h3>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                Pick the case each diagnosis of
                                {{ patientName || "the patient" }} is billed
                                under. The price is added to this admission's
                                invoice, and can be changed until the patient
                                is admitted.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                            aria-label="Close"
                            :disabled="saving"
                            @click="$emit('close')"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 pb-5">
                        <DiagnosisFeePicker
                            :open="open"
                            :patient-uuid="patientUuid"
                            :admission-id="admissionId"
                            section-class="mt-5"
                            hint="Leave a diagnosis on “No diagnosis fee” to bill nothing for it."
                            empty-text="This patient has no recorded diagnoses, or the branch has no diagnosis cases yet."
                            @update:selection="selection = $event"
                        />
                    </div>

                    <div
                        class="border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div
                            class="mb-3 flex items-center justify-between text-sm"
                        >
                            <span class="text-slate-500 dark:text-gray-400">
                                Diagnosis fees
                            </span>
                            <span class="text-lg font-bold tracking-tight">
                                {{ formatCurrency(selection.fee) }}
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                :disabled="saving"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                class="flex flex-[2] items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="saving"
                                @click="$emit('save', selection.diagnoses)"
                            >
                                {{ saving ? "Saving..." : "Save" }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref } from "vue";

import DiagnosisFeePicker from "~/components/sections/app/Admission/DiagnosisFeePicker.vue";
import type { DiagnosisFeeSelection } from "~/types/additional-charge";
import { formatCurrency } from "~/utils/currency";

defineProps<{
    open: boolean;
    patientUuid: string;
    patientName?: string | null;
    admissionId?: number;
    saving?: boolean;
}>();

defineEmits<{
    (e: "close"): void;
    (e: "save", diagnoses: DiagnosisFeeSelection["diagnoses"]): void;
}>();

const selection = ref<DiagnosisFeeSelection>({ diagnoses: [], fee: 0 });
</script>
