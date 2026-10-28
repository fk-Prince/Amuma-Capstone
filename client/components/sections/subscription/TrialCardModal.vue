<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @click.self="close"
        >
            <div
                class="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                aria-label="Add your card"
            >
                <div
                    class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <CreditCard class="h-5 w-5" />
                        </div>

                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Add your card
                            </h2>
                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                ₱{{ formatAmount(amount) }} is charged when
                                your trial ends
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        aria-label="Close dialog"
                        :disabled="processing"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="close"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <PaymentForm
                        v-model:card="card"
                        :total-amount="0"
                        :processing="processing"
                        :onCardPay="() => emit('submit')"
                        :enableGCash="false"
                        title="Card for after your trial"
                        description="We verify your card now. Nothing is charged today."
                        amount-label="Due today"
                        submit-label="Start free trial"
                        processing-label="Starting your trial…"
                        terms-context="subscription"
                        compact
                    />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { CreditCard, X } from "lucide-vue-next";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { formatAmount } from "~/utils/currency";
import type { CardDetails } from "~/types/payment";

const props = defineProps<{
    open: boolean;
    amount: number;
    processing: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit"): void;
}>();

const card = defineModel<CardDetails>("card", { required: true });
const close = () => {
    if (props.processing) return;
    emit("close");
};
</script>
