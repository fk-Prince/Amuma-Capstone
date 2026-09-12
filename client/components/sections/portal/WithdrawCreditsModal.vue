<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import { formatCurrency } from "~/utils/currency";

type WithdrawMethod = "GCash" | "CREDIT-CARD";

defineProps<{
    open: boolean;
    advanceBalance: number;
    availableCredit: number;
    creditReason: string;
    amount: number | null;
    method: WithdrawMethod;
    accountDetails: string;
    submitting?: boolean;
    errorMessage?: string;
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "submit"): void;
    (event: "update:amount", value: number | null): void;
    (event: "update:method", value: WithdrawMethod): void;
    (event: "update:accountDetails", value: string): void;
}>();

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
}
</script>

<template>
    <Transition name="modal">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="bg-gradient-to-br from-emerald-500 to-emerald-600 px-6 py-5 text-white"
                >
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"
                        >
                            <AppIcon name="arrow-down-circle" class="h-5 w-5" />
                        </div>

                        <button
                            class="flex h-8 w-8 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            <AppIcon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <p class="mt-4 text-lg font-bold">
                        Request Withdraw Credits
                    </p>

                    <p class="mt-1 text-xs leading-5 text-white/75">
                        The credit on this account will be sent using your
                        selected method.
                    </p>
                </div>

                <div class="space-y-5 p-6">
                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300"
                        >
                            Credit Available
                        </p>

                        <p
                            class="mt-1 text-3xl font-bold text-emerald-700 dark:text-emerald-300"
                        >
                            {{ peso(advanceBalance) }}
                        </p>

                        <div
                            class="mt-3 flex items-start gap-2 border-t border-emerald-100 pt-3 dark:border-emerald-500/20"
                        >
                            <AppIcon
                                name="info"
                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500 dark:text-emerald-300"
                            />

                            <p
                                class="text-[11px] leading-4 text-emerald-700/80 dark:text-emerald-300"
                            >
                                {{ creditReason }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                        >
                            How much would you like to withdraw?
                        </label>

                        <input
                            :value="amount"
                            type="number"
                            step="1"
                            min="1"
                            :max="availableCredit"
                            class="mt-1.5 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-sm text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                            @input="
                                emit(
                                    'update:amount',
                                    ($event.target as HTMLInputElement)
                                        .value === ''
                                        ? null
                                        : Number(
                                              (
                                                  $event.target as HTMLInputElement
                                              ).value,
                                          ),
                                )
                            "
                        />

                        <div
                            class="mt-1.5 flex items-center justify-between gap-3"
                        >
                            <button
                                type="button"
                                class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-300"
                                @click="emit('update:amount', availableCredit)"
                            >
                                Withdraw all
                            </button>

                            <p
                                v-if="Number(amount) > 0"
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ peso(availableCredit - Number(amount)) }}
                                stays on the account
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                        >
                            Withdraw Method
                        </label>

                        <div class="relative mt-1.5">
                            <select
                                :value="method"
                                class="w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-3 pr-10 text-sm text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                                @change="
                                    emit(
                                        'update:method',
                                        ($event.target as HTMLSelectElement)
                                            .value as WithdrawMethod,
                                    )
                                "
                            >
                                <option value="GCash">GCash</option>
                                <option value="CREDIT-CARD">Credit Card</option>
                            </select>

                            <AppIcon
                                name="chevron-down"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                            />
                        </div>
                    </div>

                    <BaseInput
                        :model-value="accountDetails"
                        :label="
                            method === 'GCash' ? 'GCash Number' : 'Card Number'
                        "
                        :placeholder="
                            method === 'GCash'
                                ? 'e.g. 0917 123 4567'
                                : 'e.g. 4000 0000 0000 2503'
                        "
                        @update:model-value="
                            emit('update:accountDetails', String($event ?? ''))
                        "
                    />

                    <div
                        v-if="errorMessage"
                        class="flex items-start gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                    >
                        <AppIcon
                            name="alert-circle"
                            class="mt-0.5 h-4 w-4 shrink-0"
                        />
                        <span>{{ errorMessage }}</span>
                    </div>

                    <div class="flex gap-2.5 pt-1">
                        <button
                            class="flex-1 rounded-full border border-gray-200 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>

                        <button
                            :disabled="submitting"
                            class="flex flex-1 items-center justify-center gap-2 rounded-full bg-emerald-500 py-3 text-sm font-semibold text-white shadow-sm shadow-emerald-500/20 transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                            @click="emit('submit')"
                        >
                            <span
                                v-if="submitting"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                            />
                            {{
                                submitting
                                    ? "Submitting..."
                                    : "Confirm Withdrawal"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

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

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    margin: 0;
    -webkit-appearance: none;
}

input[type="number"] {
    appearance: textfield;
}
</style>
