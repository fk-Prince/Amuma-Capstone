<template>
    <div class="min-h-screen bg-slate-50 w-full">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-800">
                    Confirm your subscription
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Review your branch and plan details, then choose how you'd
                    like to pay.
                </p>
            </div>

            <div
                class="grid grid-cols-1 lg:grid-cols-[1fr_420px] gap-10 items-start"
            >
                <div class="w-full">
                    <CheckoutSummary />
                </div>
                <div class="lg:sticky lg:top-6">
                    <PaymentForm
                        v-model:card="card"
                        :processing="processing"
                        :onCardPay="payCard"
                        :onGCashPay="payGCash"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { reactive, ref } from "vue";
import CheckoutSummary from "~/components/sections/subscription/CheckoutSummary.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
// import {
//     gcashPayment,
//     cardPayment,
// } from "~/composables/useSubscriptionPayment";

import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { type SubscriptionRequest } from "~/types/subscription";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { useToast } from "~/composables/useToast";
import { fetchAuthUser } from "~/composables/useAuthUser";

const checkout = useSubscriptionCheckout();
useHead({ title: "Subscription Checkout" });

definePageMeta({
    middleware: ["auth-client", "subscription-guard"],
    navVariant: 1,
});
// const card = reactive({
//     number: "",
//     expMonth: "",
//     expYear: "",
//     cvc: "",
//     firstName: "",
//     lastName: "",
//     email: "",
// });
const card = reactive({
    number: "4000000000002503",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const processing = ref(false);
const isModalOpen = ref(false);
const closeModal = ref<(() => void) | null>(null);
closeModal.value = () => {
    isModalOpen.value = false;
};

const { success, error } = useToast();

const buildSubscriptionPayload = (): SubscriptionRequest => ({
    plan_code: checkout.selectedPlan?.plan_code,
    payment_method: checkout.payment_method,
    billing_interval: checkout.selectedInterval,

    branch_name: checkout.branch?.name,
    branch_street: checkout.branch?.location?.street,
    branch_city: checkout.branch?.location?.city,
    branch_province: checkout.branch?.location?.province,
    branch_country: checkout.branch?.location?.country,
    branch_contact_number: checkout.branch?.contact_number,
    branch_image: checkout.branch?.image,
    branch_description: checkout.branch?.description,
    branch_settings: checkout.settings,
    branch_latitude: checkout.branch?.location?.latitude,
    branch_longitude: checkout.branch?.location?.longitude,

    agency_id: checkout.agency?.id,
    agency_name: checkout.agency?.agency_name,
    agency_description: checkout.agency?.agency_description,
    agency_street: checkout.agency?.location?.street,
    agency_city: checkout.agency?.location?.city,
    agency_province: checkout.agency?.location?.province,
    agency_country: checkout.agency?.location?.country,
    agency_latitude: checkout.agency?.location?.latitude,
    agency_longitude: checkout.agency?.location?.longitude,
});

const payCard = async () => {
    if (processing.value) return;
    processing.value = true;
    try {
        const payload = buildSubscriptionPayload();
        const res =
            await subscriptionService.retrieveSubscriptionDetail(payload);

        await cardPayment({
            card,
            amount: Number(res.total_amount),
            onClose: () => {
                processing.value = false;
            },

            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.createSubscription({
                    ...payload,
                    token_id: token_id,
                    authentication_id: authentication_id,
                    payment_method: "CREDIT-CARD",
                    payment_type: "SUBSCRIPTION",
                }),

            onSuccess: async (result) => {
                success(result.message);
                await fetchAuthUser();

                await navigateTo({
                    path: `/product/subscription-summary?status=success`,
                    query: {
                        status: result.status,
                    },
                });
            },
        });
    } catch (err: any) {
        error(err.message);
    } finally {
        processing.value = false;
    }
};

const payGCash = async () => {
    const payload = buildSubscriptionPayload();
    if (processing.value) return;
    processing.value = true;
    try {
        await gcashPayment({
            closeModal,
            createPayment: () =>
                subscriptionService.createSubscription({
                    ...payload,
                    payment_method: "GCASH",
                    payment_type: "SUBSCRIPTION",
                }),

            onSuccess: async (result) => {
                success(result.message);
                await fetchAuthUser();
                await navigateTo({
                    path: `/product/subscription-summary`,
                    query: {
                        status: result.status,
                    },
                });
            },
            onClose: () => {
                processing.value = false;
            },
        });
    } catch (err: any) {
        console.error(err);
    } finally {
        processing.value = false;
    }
};
</script>
