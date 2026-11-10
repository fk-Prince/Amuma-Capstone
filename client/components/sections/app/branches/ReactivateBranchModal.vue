<template>
    <ConfirmDialog
        v-if="amount <= 0"
        :open="true"
        :title="`Reactivate ${branchName}?`"
        :message="freeMessage"
        confirm-label="Reactivate"
        :loading="processing"
        @confirm="reactivateFree"
        @cancel="requestClose"
    />

    <Teleport v-else to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
        >
            <div
                class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                aria-label="Reactivate branch"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Power class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <h2
                                class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                            >
                                Reactivate branch
                            </h2>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                Pay for the time left to bring
                                <span class="font-medium text-secondary dark:text-white">
                                    {{ branchName }}
                                </span>
                                back.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        aria-label="Close dialog"
                        class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        :disabled="processing"
                        @click="requestClose"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="grid min-h-0 flex-1 grid-cols-1 gap-5 overflow-y-auto px-6 py-5 lg:grid-cols-[1fr_18rem]"
                >
                    <PaymentForm
                        compact
                        v-model:card="card"
                        class="min-w-0"
                        :total-amount="amount"
                        :processing="processing"
                        :onCardPay="payCard"
                        :onGCashPay="payGCash"
                        :enableGCash="true"
                        title="Branch payment"
                        description="Choose your payment method to reactivate this branch."
                        submit-label="Pay & reactivate"
                        terms-context="subscription"
                    />

                    <div
                        class="order-first h-fit rounded-2xl border border-slate-200 bg-white p-5 lg:order-none dark:border-white/10 dark:bg-secondary"
                    >
                        <p class="text-base font-semibold text-slate-900 dark:text-white">
                            Order Details
                        </p>
                        <p class="mt-1 text-xs leading-5 text-muted dark:text-gray-400">
                            Prorated until {{ formatDate(activation.renews_on) }}.
                        </p>

                        <dl
                            class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 text-xs dark:border-white/10"
                        >
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted dark:text-gray-400">
                                    Branch price (yearly)
                                </dt>
                                <dd class="font-medium text-secondary dark:text-gray-200">
                                    ₱{{ formatAmount(activation.additional_branch_price) }}
                                </dd>
                            </div>
                            <div
                                class="flex justify-between gap-3 border-t border-slate-100 pt-1.5 text-sm dark:border-white/10"
                            >
                                <dt class="font-semibold text-secondary dark:text-white">
                                    Due today
                                </dt>
                                <dd class="font-bold text-primary">
                                    ₱{{ formatAmount(amount) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { Power, X } from "lucide-vue-next";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { rememberPaymentReturn } from "~/utils/paymentReturn";
import { useToast } from "~/composables/useToast";
import { formatAmount } from "~/utils/currency";
import type { CardDetails } from "~/types/payment";
import type { BranchActivation } from "./BranchCard.vue";

const props = defineProps<{
    branchName: string;
    branchUuid: string;
    activation: BranchActivation;
}>();

const emit = defineEmits<{
    close: [];
    reactivated: [result: any];
}>();

const { success, error } = useToast();

const processing = ref(false);
const amount = computed(() => props.activation.reactivation_amount ?? 0);

const card = ref<CardDetails>({
    number: "",
    expMonth: "",
    expYear: "",
    cvc: "",
    firstName: "",
    lastName: "",
    email: "",
});

const freeMessage = computed(() =>
    props.activation.activity_state === "ending"
        ? "It's still within its paid period, so it's free to reactivate."
        : "It uses a free slot in your plan, so it's free to reactivate.",
);

const formatDate = (date?: string | null) =>
    date
        ? new Date(date).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
          })
        : "";

const payload = (paymentMethod?: "CREDIT-CARD" | "GCASH") => ({
    branch_uuid: props.branchUuid,
    target_uuid: props.activation.uuid,
    ...(paymentMethod ? { payment_method: paymentMethod } : {}),
});

const onDone = (result: any) => {
    success(result?.message ?? `${props.branchName} is active again.`);
    emit("reactivated", result);
};

const reactivateFree = async () => {
    if (processing.value) return;
    processing.value = true;

    try {
        onDone(await subscriptionService.reactivateBranch(payload()));
    } catch (err: any) {
        error(err?.message ?? "Couldn't reactivate this branch.");
    } finally {
        processing.value = false;
    }
};

const payCard = async () => {
    if (processing.value) return;
    processing.value = true;

    try {
        await cardPayment({
            card: card.value,
            amount: amount.value,
            onClose: () => {
                processing.value = false;
            },
            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.reactivateBranchWithPurchase({
                    ...payload("CREDIT-CARD"),
                    token_id,
                    authentication_id,
                }),
            onSuccess: onDone,
        });
    } catch (err: any) {
        error(err?.message ?? "Payment failed.");
    } finally {
        processing.value = false;
    }
};

const payGCash = async () => {
    if (processing.value) return;
    processing.value = true;

    try {
        rememberPaymentReturn(window.location.pathname + window.location.search);

        await gcashPayment({
            createPayment: () =>
                subscriptionService.reactivateBranchWithPurchase(payload("GCASH")),
            onClose: () => {
                processing.value = false;
            },
            onSuccess: onDone,
        });
    } catch (err: any) {
        error(err?.message ?? "Payment failed.");
    } finally {
        processing.value = false;
    }
};

const requestClose = () => {
    if (processing.value) return;
    emit("close");
};
</script>
