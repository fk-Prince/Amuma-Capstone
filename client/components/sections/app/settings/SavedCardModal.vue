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
                aria-label="Replace card"
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
                                Replace card
                            </h2>
                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{
                                    chargesOn
                                        ? `Next charge on ${formatDate(chargesOn)}`
                                        : "Used when you turn on auto-renew"
                                }}
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
                        :total-amount="amount"
                        :processing="processing"
                        :onCardPay="save"
                        :enableGCash="false"
                        title="New card"
                        description="We verify your card now. Nothing is charged today."
                        :amount-label="chargesOn ? 'Next charge' : undefined"
                        submit-label="Save card"
                        processing-label="Saving card…"
                        compact
                    />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { CreditCard, X } from "lucide-vue-next";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { cardPayment } from "~/composables/usePayment";
import { useToast } from "~/composables/useToast";
import type { CardDetails } from "~/types/payment";

const props = defineProps<{
    open: boolean;
    branchUuid: string;
    amount: number;
    chargesOn?: string | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "saved", card: any): void;
}>();

const { success, error } = useToast();

const processing = ref(false);

const card = ref<CardDetails>({
    number: "4000000000001000",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const close = () => {
    if (processing.value) return;
    emit("close");
};

const save = async () => {
    if (processing.value) return;

    processing.value = true;

    try {
        await cardPayment({
            card: card.value,
            amount: props.amount,
            reusable: true,

            onClose: () => {
                processing.value = false;
            },

            createPayment: (saved) =>
                subscriptionService.updateCard({
                    branch_uuid: props.branchUuid,
                    ...saved,
                }),

            onSuccess: async (result: any) => {
                success(result?.message ?? "Card saved.");
                emit("saved", result?.card ?? null);
                emit("close");
            },
        });
    } catch (err: any) {
        error(err?.message ?? "Could not save your card.");
    } finally {
        processing.value = false;
    }
};

const formatDate = (date?: string | null) =>
    date
        ? new Date(date).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
          })
        : "—";
</script>
