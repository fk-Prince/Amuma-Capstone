<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
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
                        Extend stay
                    </p>

                    <h3
                        class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                    >
                        Collect payment
                    </h3>

                    <p class="mt-1 text-xs text-muted dark:text-gray-400">
                        The extension is only recorded once this is paid.
                    </p>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div
                        class="rounded-xl border border-primary-100 bg-slate-50/70 p-4 dark:border-primary-500/20 dark:bg-white/5"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-muted dark:text-gray-400">
                                {{ planLabel }}
                            </span>

                            <span
                                class="text-lg font-bold text-secondary dark:text-white"
                            >
                                ₱{{ formatMoney(amountDue) }}
                            </span>
                        </div>

                        <p
                            v-if="coverageLabel"
                            class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                        >
                            {{ coverageLabel }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-secondary dark:text-gray-300"
                        >
                            Cash received
                        </label>

                        <input
                            :value="cash"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="formatMoney(amountDue)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                            @input="
                                emit(
                                    'update:cash',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />

                        <div class="mt-2 flex items-center justify-between gap-3">
                            <button
                                type="button"
                                class="text-xs font-medium text-primary hover:underline"
                                @click="emit('update:cash', amountDue)"
                            >
                                Exact amount
                            </button>

                            <p
                                v-if="isShort"
                                class="text-xs text-rose-600 dark:text-rose-300"
                            >
                                Short by ₱{{ formatMoney(amountDue - tendered) }}
                            </p>

                            <p
                                v-else-if="change > 0"
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Change ₱{{ formatMoney(change) }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-secondary dark:text-gray-300"
                        >
                            Received from
                            <span class="text-muted dark:text-gray-500">
                                (optional)
                            </span>
                        </label>

                        <input
                            :value="payorName"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                            @input="
                                emit(
                                    'update:payorName',
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
                        :disabled="processing"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 disabled:opacity-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="processing || isShort"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="emit('confirm')"
                    >
                        {{ processing ? "Processing…" : "Pay and extend" }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";

import { formatAmount } from "~/utils/currency";

const props = defineProps<{
    open: boolean;
    amountDue: number;
    planLabel: string;
    coverageLabel?: string | null;
    cash: number | string | null;
    payorName: string;
    processing: boolean;
}>();

const emit = defineEmits<{
    (event: "update:cash", cash: number | string | null): void;
    (event: "update:payorName", name: string): void;
    (event: "confirm"): void;
    (event: "close"): void;
}>();

const tendered = computed(() => Number(props.cash ?? 0));

const isShort = computed(() => tendered.value < props.amountDue);

const change = computed(() =>
    Math.max(0, tendered.value - props.amountDue),
);

function formatMoney(value: number | string | null | undefined) {
    return formatAmount(value, { treatMissingAsZero: true });
}
</script>
