<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                            >
                                Rejected request
                            </p>

                            <h3
                                class="mt-1 truncate text-base font-semibold text-secondary dark:text-white"
                            >
                                {{ branchName || "This branch" }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            <AppIcon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="space-y-3 px-6 py-5">
                        <p
                            class="text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Reason for rejection
                        </p>

                        <div
                            class="flex items-start gap-2 rounded-xl bg-red-50 p-3 text-sm leading-6 text-red-700 dark:bg-red-500/10 dark:text-red-300"
                        >
                            <AppIcon
                                name="alert-circle"
                                class="mt-1 h-4 w-4 shrink-0"
                            />

                            <p class="whitespace-pre-line break-words">
                                {{ reason || "No reason was given." }}
                            </p>
                        </div>

                        <p
                            v-if="rejectedAt"
                            class="text-[11px] text-muted dark:text-gray-500"
                        >
                            Rejected on {{ formatDate(rejectedAt) }}
                        </p>
                    </div>

                    <div
                        class="flex justify-end border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-slate-100 hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import { formatDate } from "~/utils/time";

defineProps<{
    open: boolean;
    branchName?: string | null;
    reason?: string | null;
    rejectedAt?: string | null;
}>();

const emit = defineEmits<{
    (event: "close"): void;
}>();
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(12px) scale(0.98);
}
</style>
