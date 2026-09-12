<template>
    <Transition name="fade">
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
                    <p
                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-600 dark:text-emerald-300"
                    >
                        Credit on account
                    </p>

                    <h3
                        class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                    >
                        Withdraw credits
                    </h3>

                    <p class="mt-1 text-xs text-muted dark:text-gray-400">
                        {{ formatMoney(available) }} is available. Withdraw part
                        of it and the rest stays on the account.
                    </p>
                </div>

                <div class="px-6 py-5">
                    <label
                        class="mb-1.5 block text-xs font-medium text-secondary dark:text-gray-300"
                    >
                        Amount to withdraw
                    </label>

                    <input
                        :value="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :max="available"
                        :placeholder="formatMoney(available)"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                        @input="
                            emit(
                                'update:amount',
                                ($event.target as HTMLInputElement).value,
                            )
                        "
                    />

                    <div class="mt-2 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="text-xs font-medium text-primary hover:underline"
                            @click="emit('update:amount', available)"
                        >
                            Withdraw all
                        </button>

                        <p
                            v-if="errorMessage"
                            class="text-xs text-rose-600 dark:text-rose-300"
                        >
                            {{ errorMessage }}
                        </p>

                        <p
                            v-else-if="Number(amount) > 0"
                            class="text-xs text-slate-500 dark:text-gray-400"
                        >
                            {{ formatMoney(available - Number(amount)) }}
                            left on the account
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-primary-100 bg-slate-50/60 px-6 py-4 dark:border-primary-500/20 dark:bg-white/5"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="processing || !!errorMessage"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="emit('confirm')"
                    >
                        {{ processing ? "Withdrawing…" : "Withdraw" }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { formatAmount } from "~/utils/currency";

defineProps<{
    open: boolean;
    available: number;
    // Null while the field is empty, which shows the placeholder rather than a
    // typed zero.
    amount: number | string | null;
    processing: boolean;
    errorMessage?: string | null;
}>();

const emit = defineEmits<{
    (event: "update:amount", amount: number | string | null): void;
    (event: "confirm"): void;
    (event: "close"): void;
}>();

function formatMoney(value: number | string | null | undefined) {
    return formatAmount(value, { treatMissingAsZero: true });
}
</script>
