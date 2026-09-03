<template>
    <Transition name="fade">
        <Teleport to="body">
            <div
                v-if="props.open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-primary-900/50 backdrop-blur-sm p-4"
                @click.self="closeModal"
            >
                <div
                    class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 dark:bg-secondary dark:ring-primary-500/20"
                >
                    <div
                        class="flex items-center justify-between border-b border-primary-100 px-6 py-4 dark:border-primary-500/20"
                    >
                        <div>
                            <h2
                                class="text-base font-semibold text-primary-900 dark:text-primary-300"
                            >
                                Room Transfer History
                            </h2>

                            <p class="mt-1 text-xs text-muted dark:text-gray-400">
                                Patient room and bed transfer history
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            @click="closeModal"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto p-6">
                        <div v-if="isLoadingTransfers" class="space-y-4">
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="animate-pulse rounded-xl border border-primary-100 p-4 dark:border-primary-500/20"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="space-y-2">
                                        <div
                                            class="h-4 w-32 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>

                                        <div
                                            class="h-3 w-48 rounded bg-slate-100 dark:bg-white/10"
                                        ></div>
                                    </div>

                                    <div
                                        class="h-3 w-24 rounded bg-slate-100 dark:bg-white/10"
                                    ></div>
                                </div>

                                <div class="mt-4 flex items-center gap-3">
                                    <div
                                        class="flex-1 rounded-lg bg-slate-50 p-4 dark:bg-white/5"
                                    >
                                        <div
                                            class="h-3 w-16 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>

                                        <div
                                            class="mt-2 h-5 w-28 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>

                                        <div
                                            class="mt-2 h-3 w-20 rounded bg-slate-100 dark:bg-white/10"
                                        ></div>
                                    </div>

                                    <div
                                        class="h-8 w-8 shrink-0 rounded-full bg-slate-200 dark:bg-white/15"
                                    ></div>

                                    <div
                                        class="flex-1 rounded-lg bg-slate-50 p-4 dark:bg-white/5"
                                    >
                                        <div
                                            class="h-3 w-16 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>

                                        <div
                                            class="mt-2 h-5 w-28 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>

                                        <div
                                            class="mt-2 h-3 w-20 rounded bg-slate-100 dark:bg-white/10"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="!transfers.length"
                            class="flex flex-col items-center justify-center py-12 text-center"
                        >
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-white/10"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6 text-slate-400 dark:text-gray-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7h12m0 0l-3-3m3 3l-3 3M16 17H4m0 0l3 3m-3-3l3-3"
                                    />
                                </svg>
                            </div>

                            <h3 class="text-sm font-semibold text-primary-900 dark:text-primary-300">
                                No transfer history
                            </h3>

                            <p class="mt-1 text-sm text-muted dark:text-gray-400">
                                No room or bed transfers have been recorded.
                            </p>
                        </div>

                        <TransferHistoryList v-else :transfers="transfers" />
                    </div>

                    <div
                        class="flex justify-end border-t border-primary-100 bg-slate-50 px-6 py-4 dark:border-primary-500/20 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition hover:opacity-90"
                            @click="closeModal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </Transition>
</template>
<script setup lang="ts">
import { ref, watch } from "vue";
import { useRoute } from "vue-router";
import TransferHistoryList from "./TransferHistoryList.vue";
import { admissionService } from "~/api/admission/AdmissionService";
import type { Admission } from "~/types/patient";

const props = defineProps<{
    open: boolean;
    admission: Admission | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const route = useRoute();

const isLoadingTransfers = ref(false);
const transfers = ref<any[]>([]);

const closeModal = () => {
    emit("close");
};

const loadTransferHistory = async () => {
    const admissionId = props.admission?.patient_admission_id;

    if (!admissionId) {
        transfers.value = [];
        return;
    }

    isLoadingTransfers.value = true;

    try {
        const res = await admissionService.list({
            type: "room_transfers",
            patient_admission_id: admissionId,
            branch_uuid: route.params.uuid,
        });

        transfers.value = res?.data ?? res ?? [];
    } catch (error) {
        console.error("Failed to load transfer history:", error);
        transfers.value = [];
    } finally {
        isLoadingTransfers.value = false;
    }
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            loadTransferHistory();
        }
    },
);

watch(
    () => props.admission?.patient_admission_id,
    (admissionId) => {
        if (props.open && admissionId) {
            loadTransferHistory();
        }
    },
);
</script>
