<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm"
            @keydown.esc="close"
        >
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative w-full max-w-md rounded-xl shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Add your card"
                >
                    <button
                        type="button"
                        aria-label="Close dialog"
                        :disabled="processing"
                        class="absolute right-2.5 top-2.5 z-20 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <PaymentForm
                        v-model:card="card"
                        :total-amount="0"
                        :processing="processing"
                        :onCardPay="() => emit('submit')"
                        :enableGCash="false"
                        title="Add your card"
                        :description="`Nothing today. ₱${formatAmount(amount)} is charged when your trial ends.`"
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
import { X } from "lucide-vue-next";
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
