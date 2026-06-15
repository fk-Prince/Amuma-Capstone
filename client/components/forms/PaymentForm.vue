<script setup lang="ts">
import { computed } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { type CardDetails } from "~/composables/useSubscriptionPayment";

import visaIcon from "@/assets/icons/visa.png";
import gcashIcon from "@/assets/icons/gcash.png";
import LabelInput from "../ui/BaseInput.vue";

type PaymentMethod = "CREDIT-CARD" | "GCASH";

interface Props {
    card: CardDetails;
    processing: boolean;
    onCardPay?: () => void | Promise<void>;
    onGCashPay?: () => void | Promise<void>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: "update:card", value: CardDetails): void;
}>();

const checkout = useSubscriptionCheckout();

const methods = [
    {
        value: "CREDIT-CARD" as PaymentMethod,
        label: "Credit Card",
        image: visaIcon,
    },
    {
        value: "GCASH" as PaymentMethod,
        label: "GCash",
        image: gcashIcon,
    },
];

const isCard = computed(() => checkout.payment_method === "CREDIT-CARD");

const isGCash = computed(() => checkout.payment_method === "GCASH");

const updateCard = <K extends keyof CardDetails>(
    key: K,
    value: CardDetails[K],
) => {
    emit("update:card", {
        ...props.card,
        [key]: value,
    });
};

const selectMethod = (method: PaymentMethod) => {
    checkout.payment_method = method;
};
</script>

<template>
    <div
        class="mt-5 w-[25%] overflow-hidden rounded-2xl border border-muted-light bg-white p-5 shadow-sm"
    >
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Payment Details</h2>
            <p class="text-sm text-gray-500">
                Select your preferred payment method.
            </p>
        </div>

        <!-- <div
            class="flex items-center justify-between rounded-2xl border border-primary/20 p-3"
        >
            <span class="text-sm font-medium text-gray-700">
                Payment Method
            </span>

            <div class="flex gap-2">
                <button
                    v-for="method in methods"
                    :key="method.value"
                    type="button"
                    :aria-label="method.label"
                    @click="selectMethod(method.value)"
                    class="relative flex h-11 w-16 items-center justify-center overflow-hidden rounded-xl border-2 bg-white transition-all"
                    :class="
                        checkout.payment_method === method.value
                            ? 'border-primary shadow-sm'
                            : 'border-transparent hover:border-gray-200'
                    "
                >
                    <img
                        :src="method.image"
                        :alt="method.label"
                        class="h-full w-full object-contain p-2"
                    />
                </button>
            </div>
        </div> -->

        <div class="rounded-2xl border border-primary/20 p-4">
            <div class="mb-3">
                <label class="text-sm font-medium text-gray-700">
                    Payment Method
                </label>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button
                    v-for="method in methods"
                    :key="method.value"
                    type="button"
                    :aria-label="method.label"
                    @click="selectMethod(method.value)"
                    class="flex items-center justify-center rounded-xl border p-3 transition-all duration-200"
                    :class="
                        checkout.payment_method === method.value
                            ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                            : 'border-gray-200 bg-white hover:border-primary/40 hover:bg-gray-50'
                    "
                >
                    <img
                        :src="method.image"
                        :alt="method.label"
                        class="h-8 object-contain"
                    />
                </button>
            </div>
        </div>
        <div v-if="isCard" class="mt-6 space-y-5">
            <LabelInput
                label="Card Number"
                :model-value="card.number"
                @update:model-value="updateCard('number', $event)"
            />

            <div class="grid grid-cols-3 gap-4">
                <LabelInput
                    label="MM"
                    input-class="text-center"
                    :model-value="card.expMonth"
                    @update:model-value="updateCard('expMonth', $event)"
                />

                <LabelInput
                    label="YYYY"
                    input-class="text-center"
                    :model-value="card.expYear"
                    @update:model-value="updateCard('expYear', $event)"
                />

                <LabelInput
                    label="CVC"
                    input-class="text-center"
                    :model-value="card.cvc"
                    @update:model-value="updateCard('cvc', $event)"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <LabelInput
                    label="First Name"
                    :model-value="card.firstName"
                    @update:model-value="updateCard('firstName', $event)"
                />

                <LabelInput
                    label="Last Name"
                    :model-value="card.lastName"
                    @update:model-value="updateCard('lastName', $event)"
                />
            </div>

            <LabelInput
                label="Email"
                :model-value="card.email"
                @update:model-value="updateCard('email', $event)"
            />

            <button
                type="button"
                :disabled="processing"
                @click="onCardPay"
                class="w-full rounded-xl bg-primary py-3 font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ processing ? "Processing..." : "Pay with Card" }}
            </button>
        </div>

        <div v-else-if="isGCash" class="mt-6">
            <div
                class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-center"
            >
                <h3 class="text-lg font-semibold text-primary">
                    GCash Payment
                </h3>

                <p class="mt-2 text-sm text-gray-600">
                    You will be redirected to GCash to complete your payment
                    securely.
                </p>

                <button
                    type="button"
                    :disabled="processing"
                    @click="onGCashPay"
                    class="mt-4 w-full rounded-xl bg-primary py-3 font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ processing ? "Processing..." : "Pay with GCash" }}
                </button>
            </div>
        </div>
    </div>
</template>
