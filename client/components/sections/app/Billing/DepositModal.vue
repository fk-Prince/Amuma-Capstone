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
                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary-600 dark:text-primary-300"
                    >
                        Credit on account
                    </p>

                    <h3
                        class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                    >
                        Record deposit
                    </h3>

                    <p class="mt-1 text-xs text-muted dark:text-gray-400">
                        Cash received at the counter is added to the credit on
                        this account.
                    </p>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-secondary dark:text-gray-300"
                        >
                            Amount received
                        </label>

                        <input
                            :value="amount ?? ''"
                            type="number"
                            step="0.01"
                            min="1"
                            placeholder="0.00"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                            @input="
                                emit(
                                    'update:amount',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />

                        <p
                            v-if="errorMessage"
                            class="mt-2 text-xs text-rose-600 dark:text-rose-300"
                        >
                            {{ errorMessage }}
                        </p>

                        <p
                            v-else-if="Number(amount) > 0"
                            class="mt-2 text-xs text-slate-500 dark:text-gray-400"
                        >
                            {{ formatMoney(available + Number(amount)) }} credit
                            on the account after this deposit
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-secondary dark:text-gray-300"
                        >
                            Deposited by
                        </label>

                        <input
                            :value="depositedBy"
                            type="text"
                            maxlength="255"
                            placeholder="Name of the person paying"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                            @input="
                                emit(
                                    'update:depositedBy',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-secondary dark:text-gray-300"
                        >
                            Note
                        </label>

                        <input
                            :value="note"
                            type="text"
                            maxlength="255"
                            placeholder="Optional"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                            @input="
                                emit(
                                    'update:note',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
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
                        :disabled="processing || !!errorMessage || !(Number(amount) > 0)"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="emit('confirm')"
                    >
                        {{ processing ? "Recording…" : "Record deposit" }}
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
    amount: number | string | null;
    depositedBy: string;
    note: string;
    processing: boolean;
    errorMessage?: string | null;
}>();

const emit = defineEmits<{
    (event: "update:amount", amount: number | string | null): void;
    (event: "update:depositedBy", value: string): void;
    (event: "update:note", value: string): void;
    (event: "confirm"): void;
    (event: "close"): void;
}>();

function formatMoney(value: number | string | null | undefined) {
    return formatAmount(value, { treatMissingAsZero: true });
}
</script>
