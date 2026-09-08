<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
            >
                <div
                    class="border-b border-primary-100 px-6 py-5 dark:border-primary-500/20"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                            >
                                Refund
                            </p>

                            <h3
                                class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                            >
                                Process Refund
                            </h3>

                            <p
                                class="mt-1 text-xs text-muted dark:text-gray-400"
                            >
                                Please confirm the refund amount below.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:hover:bg-white/10 dark:text-gray-400 dark:hover:text-white"
                            :disabled="processing"
                            @click="emit('close')"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                            >
                                <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-5 p-6">
                    <div class="rounded-xl border border-danger/20 bg-danger/5 p-5">
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                        >
                            Amount to Refund
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold tracking-tight text-danger"
                        >
                            ₱{{ formatMoney(amount) }}
                        </p>

                        <p
                            v-if="hasPendingRequest"
                            class="mt-2 text-xs leading-5 text-muted dark:text-gray-400"
                        >
                            This amount is currently available for refund.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-primary-100 bg-slate-50/70 px-4 py-3 dark:border-primary-500/20 dark:bg-white/5"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-muted dark:text-gray-400">
                                Refundable Amount
                            </span>

                            <span
                                class="text-sm font-bold text-secondary dark:text-white"
                            >
                                ₱{{ formatMoney(amount) }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="errorMessage"
                        class="rounded-xl bg-danger/10 px-3 py-2.5 text-xs text-danger"
                    >
                        {{ errorMessage }}
                    </p>
                </div>

                <div
                    class="flex justify-end gap-2 border-t border-primary-100 bg-slate-50/60 px-6 py-4 dark:border-primary-500/20 dark:bg-white/5"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-white hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        :disabled="processing"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-danger/90 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="emit('confirm')"
                    >
                        {{ processing ? "Processing..." : "Confirm Refund" }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { formatAmount } from "~/utils/currency";

defineProps<{
    open: boolean;
    amount: number;
    hasPendingRequest: boolean;
    processing: boolean;
    errorMessage?: string | null;
}>();

const emit = defineEmits<{
    (event: "confirm"): void;
    (event: "close"): void;
}>();

function formatMoney(value: number | string | null | undefined) {
    return formatAmount(value, { treatMissingAsZero: true });
}
</script>
