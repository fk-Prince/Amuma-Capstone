<template>
    <div
        class="w-full grid grid-cols-1 lg:grid-cols-[1fr_420px] gap-10 items-start"
    >
        <div class="w-full">
            <CheckoutSummary
                :total-amount="total"
                :disabled="busy || processing"
                @back="emit('back')"
            />
        </div>

        <div>
            <PaymentForm
                v-model:card="card"
                :total-amount="total"
                :processing="processing || loadingTotal"
                :onCardPay="payCard"
                :onGCashPay="payGCash"
                :enableGCash="true"
                terms-context="subscription"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import CheckoutSummary from "~/components/sections/subscription/CheckoutSummary.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { useToast } from "~/composables/useToast";
import { fetchAuthUser } from "~/composables/useAuthUser";
import type { CardDetails } from "~/types/payment";

const busy = defineModel<boolean>("busy", { default: false });

const emit = defineEmits<{
    (e: "back"): void;
}>();

const checkout = useSubscriptionCheckout();
const { success, error } = useToast();

const card = ref<CardDetails>({
    number: "4000000000001000",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const processing = ref(false);
const xenditProcessing = ref(false);
const loadingTotal = ref(true);
const total = ref(0);

watch([processing, xenditProcessing], ([p, x]) => {
    busy.value = p || x;
});

const payCard = async () => {
    if (processing.value || loadingTotal.value || xenditProcessing.value) {
        return;
    }

    processing.value = true;

    try {
        const payload = checkout.subscriptionPayload;
        if (!payload) throw new Error("Subscription details are missing.");

        await cardPayment({
            card: card.value,
            amount: total.value,

            onClose: () => {
                xenditProcessing.value = false;
                processing.value = false;
            },

            on3DSProcessingChange: (value: any) => {
                xenditProcessing.value = value;
            },

            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.createSubscription({
                    ...payload,
                    token_id,
                    authentication_id,
                    payment_method: "CREDIT-CARD",
                    payment_type: "SUBSCRIPTION",
                }),

            onSuccess: async (result) => {
                xenditProcessing.value = false;

                success("Subscription Request Submitted", result.message);

                await fetchAuthUser();

                await navigateTo({
                    path: "/product/subscription-summary?status=success",
                    query: {
                        status: result.status,
                    },
                });
            },
        });
    } catch (err: any) {
        xenditProcessing.value = false;
        error(err.message);
    } finally {
        processing.value = false;
    }
};

const payGCash = async () => {
    if (processing.value || loadingTotal.value) return;

    processing.value = true;

    try {
        const payload = checkout.subscriptionPayload;
        if (!payload) throw new Error("Subscription details are missing.");

        await gcashPayment({
            createPayment: () =>
                subscriptionService.createSubscription({
                    ...payload,
                    payment_method: "GCASH",
                    payment_type: "SUBSCRIPTION",
                }),

            onClose: () => {
                processing.value = false;
            },

            onSuccess: async () => {
                await navigateTo({
                    path: "/product/subscription-summary",
                    query: { status: "true" },
                });
            },
        });
    } catch (err: any) {
        error(err?.message ?? "GCash payment failed.");
    } finally {
        processing.value = false;
    }
};

onMounted(async () => {
    try {
        const payload = checkout.subscriptionPayload;
        if (!payload) throw new Error("Subscription details are missing.");

        const res =
            await subscriptionService.retrieveSubscriptionDetail(payload);
        total.value = Number(res.total_amount);
    } catch (err: any) {
        error(err.message ?? "Failed to load subscription total.");
    } finally {
        loadingTotal.value = false;
    }
});
</script>
