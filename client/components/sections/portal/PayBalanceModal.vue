<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import type { CardDetails } from "~/types/payment";
import { formatCurrency } from "~/utils/currency";

defineProps<{
    open: boolean;
    patientName?: string;
    currentBalance: number;
    unpaidInvoiceCount: number;
    amount: number;
    card: CardDetails;
    processing?: boolean;
    onCardPay: () => void | Promise<void>;
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "update:amount", value: number): void;
    (event: "update:card", value: CardDetails): void;
}>();

function peso(value: number) {
    return formatCurrency(value, { treatMissingAsZero: true });
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
                class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                aria-label="Pay balance"
            >
                <div
                    class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <AppIcon name="credit-card" class="h-5 w-5" />
                        </div>

                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Pay balance
                            </h2>

                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ patientName || "This resident" }}
                                ·
                                {{ peso(currentBalance) }} outstanding
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        aria-label="Close dialog"
                        :disabled="processing"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="emit('close')"
                    >
                        <AppIcon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="grid min-h-0 flex-1 gap-6 overflow-y-auto px-6 py-5 lg:grid-cols-2 lg:items-start"
                >
                    <div class="space-y-5">
                        <div
                            class="rounded-2xl border border-rose-100 bg-rose-50 p-5 dark:border-rose-500/20 dark:bg-rose-500/10"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs font-bold uppercase tracking-wide text-rose-500 dark:text-rose-300"
                                >
                                    Amount to charge
                                </p>

                                <AppIcon
                                    name="alert-circle"
                                    class="h-4 w-4 text-rose-400"
                                />
                            </div>

                            <p
                                class="mt-1 text-4xl font-bold text-rose-600 dark:text-rose-300"
                            >
                                {{ peso(amount) }}
                            </p>

                            <p
                                class="mt-3 border-t border-rose-100 pt-3 text-xs text-rose-500/80 dark:border-rose-500/20 dark:text-rose-300/70"
                            >
                                {{ peso(currentBalance) }} outstanding in total
                                across {{ unpaidInvoiceCount }} bill{{
                                    unpaidInvoiceCount === 1 ? "" : "s"
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                            >
                                How much would you like to pay?
                            </label>

                            <input
                                :value="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="currentBalance"
                                class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-base font-semibold text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                                @input="
                                    emit(
                                        'update:amount',
                                        Number(
                                            ($event.target as HTMLInputElement)
                                                .value,
                                        ) || 0,
                                    )
                                "
                            />

                            <div
                                class="mt-2 flex items-center justify-between gap-3"
                            >
                                <button
                                    type="button"
                                    class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-300"
                                    @click="
                                        emit('update:amount', currentBalance)
                                    "
                                >
                                    Pay the full balance
                                </button>

                                <span
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Max {{ peso(currentBalance) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <PaymentForm
                            :card="card"
                            :total-amount="amount"
                            :processing="processing"
                            :on-card-pay="onCardPay"
                            gcash-label="GCash is not available yet"
                            gcash-description="GCash payments aren't available yet. Please use a card for now."
                            title="Card details"
                            description="Your card is charged securely through Xendit."
                            submit-label="Pay now"
                            @update:card="emit('update:card', $event)"
                        />
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
