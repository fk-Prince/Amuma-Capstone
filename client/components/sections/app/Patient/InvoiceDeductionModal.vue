<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-black/40 backdrop-blur-sm"
                @click="close"
            />

            <div
                class="relative z-50 flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
            >
                <div
                    class="flex items-start justify-between border-b border-slate-100 px-6 py-5 dark:border-white/10"
                >
                    <div>
                        <p
                            class="text-xs uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Request Invoice Deduction
                        </p>

                        <h2
                            class="mt-1 text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ log?.schedule_code }}
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                        >
                            Total late/gap: {{ formatDurationShort(gapMinutes / 60) }}
                        </p>

                        <p
                            class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                        >
                            ADL rate: {{ peso(hourlyRate) }}/hr
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:text-gray-500 dark:hover:bg-white/10"
                        @click="close"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-4 p-6">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        This sends accounting a request to review and adjust
                        the invoice for this schedule — it does not change the
                        invoice by itself.
                    </p>

                    <div
                        v-if="hourlyRate > 0"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                    >
                        Calculated at {{ peso(hourlyRate) }}/hr, counting only
                        each full half-hour of late/gap time.
                    </div>

                    <div>
                        <label
                            class="text-xs font-semibold uppercase text-slate-500 dark:text-gray-400"
                        >
                            Deduction Amount
                        </label>

                        <div class="relative mt-1.5">
                            <span
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400 dark:text-gray-500"
                            >
                                ₱
                            </span>

                            <input
                                v-model.number="amount"
                                type="number"
                                min="0"
                                :max="maxAmount || undefined"
                                step="0.01"
                                class="w-full rounded-lg border border-slate-200 py-2 pl-7 pr-3 text-sm text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/25 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                @blur="clampAmount"
                            />
                        </div>

                        <p
                            v-if="maxAmount > 0"
                            class="mt-1.5 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            Cannot exceed the service amount of
                            {{ peso(maxAmount) }}.
                        </p>

                        <button
                            v-if="suggestedAmount > 0 && amount !== suggestedAmount"
                            type="button"
                            class="mt-1.5 text-xs font-medium text-primary hover:underline"
                            @click="amount = suggestedAmount"
                        >
                            Use suggested {{ peso(suggestedAmount) }}
                        </button>
                    </div>

                    <div>
                        <label
                            class="text-xs font-semibold uppercase text-slate-500 dark:text-gray-400"
                        >
                            Reason
                        </label>

                        <textarea
                            v-model="reason"
                            rows="3"
                            class="mt-1.5 w-full rounded-lg border border-slate-200 p-3 text-sm text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/25 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        />
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <ActionButton variant="outline" @click="close">
                        Cancel
                    </ActionButton>

                    <ActionButton
                        variant="primary"
                        :loading="isSaving"
                        :disabled="!(amount > 0)"
                        @click="submit"
                    >
                        Notify Accounting
                    </ActionButton>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { AuditRow } from "~/types/schedule";
import ActionButton from "~/components/ui/ActionButton.vue";
import { formatDurationShort } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";

const props = defineProps<{
    open: boolean;
    log?: AuditRow | null;
    gapMinutes: number;
    isSaving?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm", payload: { amount: number; reason: string }): void;
}>();

function peso(value: number) {
    return formatCurrency(value ?? 0);
}

const hourlyRate = computed(() => props.log?.price ?? 0);

const maxAmount = computed(
    () => hourlyRate.value * (props.log?.total_hours ?? 0),
);

const suggestedAmount = computed(() => {
    if (hourlyRate.value <= 0) return 0;

    const units = Math.floor((props.gapMinutes ?? 0) / 30);
    const suggested = Math.round(units * (hourlyRate.value / 2) * 100) / 100;

    return maxAmount.value > 0
        ? Math.min(suggested, maxAmount.value)
        : suggested;
});

const amount = ref(0);
const reason = ref("");

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        amount.value = suggestedAmount.value;
        reason.value = `late/gap of ${formatDurationShort((props.gapMinutes ?? 0) / 60)}`;
    },
);

function clampAmount() {
    if (maxAmount.value > 0 && amount.value > maxAmount.value) {
        amount.value = maxAmount.value;
    }

    if (amount.value < 0) {
        amount.value = 0;
    }
}

function close() {
    emit("close");
}

function submit() {
    clampAmount();

    if (!(amount.value > 0)) return;

    emit("confirm", { amount: amount.value, reason: reason.value.trim() });
}
</script>
