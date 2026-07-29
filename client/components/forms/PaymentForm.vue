<script setup lang="ts">
import { Wallet } from "lucide-vue-next";
import { computed, reactive } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { type CardDetails } from "~/composables/useSubscriptionPayment";
import { paymentSchema } from "~/types/payment";

import visaIcon from "@/assets/icons/visa.png";
import gcashIcon from "@/assets/icons/gcash.png";
import LabelInput from "../ui/BaseInput.vue";

type PaymentMethod = "CREDIT-CARD" | "GCASH";

interface Props {
    card: CardDetails;
    processing: boolean;
    onCardPay?: () => void | Promise<void>;
    onGCashPay?: () => void | Promise<void>;
    title?: string;
    description?: string;
    submitLabel?: string;
    processingLabel?: string;
    gcashLabel?: string;
    gcashProcessingLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: "Payment",
    description: "Choose your payment method and complete your subscription.",
    submitLabel: "Complete subscription",
    processingLabel: "Processing payment...",
    gcashLabel: "Continue to GCash",
    gcashProcessingLabel: "Redirecting...",
});

const emit = defineEmits<{
    (e: "update:card", value: CardDetails): void;
}>();

const checkout = useSubscriptionCheckout();

const methods = [
    {
        value: "CREDIT-CARD" as PaymentMethod,
        label: "Credit / debit card",
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

const errors = reactive<Record<string, string>>({
    number: "",
    expMonth: "",
    expYear: "",
    cvc: "",
    firstName: "",
    lastName: "",
    email: "",
});

const updateCard = <K extends keyof CardDetails>(
    key: K,
    value: CardDetails[K],
) => {
    emit("update:card", {
        ...props.card,
        [key]: value,
    });

    if (errors[key]) {
        errors[key] = "";
    }
};

const selectMethod = (method: PaymentMethod) => {
    checkout.payment_method = method;
};

const digitsOnly = (value: unknown) => String(value ?? "").replace(/\D/g, "");

const formatCardNumber = (value: unknown) =>
    digitsOnly(value)
        .slice(0, 19)
        .replace(/(.{4})/g, "$1 ")
        .trim();

const handleCardNumberInput = (value: string | number) => {
    updateCard("number", formatCardNumber(value) as CardDetails["number"]);
};

const cardBrand = computed(() => {
    const digits = digitsOnly(props.card.number);

    if (/^4/.test(digits)) return "Visa";
    if (/^(5[1-5]|2[2-7])/.test(digits)) return "Mastercard";
    if (/^3[47]/.test(digits)) return "Amex";

    return null;
});

const validateCardForm = () => {
    Object.keys(errors).forEach((key) => {
        errors[key] = "";
    });

    const result = paymentSchema.safeParse({
        ...props.card,
        number: props.card.number ?? "",
        expMonth: String(props.card.expMonth ?? ""),
        expYear: String(props.card.expYear ?? ""),
        cvc: String(props.card.cvc ?? ""),
        firstName: props.card.firstName ?? "",
        lastName: props.card.lastName ?? "",
        email: props.card.email ?? "",
    });

    if (!result.success) {
        result.error.issues.forEach((issue) => {
            const field = issue.path[0] as keyof typeof errors;
            errors[field] = issue.message;
        });

        return false;
    }

    return true;
};

const handleCardPay = () => {
    if (props.processing) return;
    if (!validateCardForm()) return;

    props.onCardPay?.();
};
</script>

<template>
    <div class="w-full max-w-2xl mx-auto">
        <div
            class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
        >
            <div class="p-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center"
                    >
                        <Wallet />
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-slate-800">
                            {{ title }}
                        </h2>

                        <p class="text-sm text-slate-500">
                            {{ description }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-slate-100">
                <label class="text-sm font-semibold text-slate-700">
                    Payment method
                </label>

                <div class="grid grid-cols-2 gap-3 mt-3">
                    <button
                        v-for="method in methods"
                        :key="method.value"
                        type="button"
                        @click="selectMethod(method.value)"
                        class="relative flex items-center gap-3 rounded-xl border p-4 transition"
                        :class="
                            checkout.payment_method === method.value
                                ? 'border-primary bg-primary/5'
                                : 'border-slate-200 hover:border-slate-300'
                        "
                    >
                        <img
                            :src="method.image"
                            :alt="method.label"
                            class="h-7 w-32 object-contain"
                        />

                        <span
                            v-if="checkout.payment_method === method.value"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center text-xs"
                        >
                            ✓
                        </span>
                    </button>
                </div>
            </div>

            <form
                v-if="isCard"
                @submit.prevent="handleCardPay"
                class="p-6 space-y-5"
            >
                <div>
                    <h3 class="font-semibold text-slate-800">
                        Card information
                    </h3>
                    <p class="text-sm text-slate-500">
                        Your payment details are encrypted and secure.
                    </p>
                </div>

                <LabelInput
                    label="Card number"
                    placeholder="1234 5678 9012 3456"
                    :error="errors.number"
                    :text-max="23"
                    :model-value="card.number"
                    @update:model-value="handleCardNumberInput"
                >
                    <template #suffix v-if="cardBrand">
                        <span class="text-xs text-slate-400">{{
                            cardBrand
                        }}</span>
                    </template>
                </LabelInput>

                <div class="grid grid-cols-3 gap-3">
                    <LabelInput
                        label="MM"
                        placeholder="04"
                        input-class="text-center"
                        :text-max="2"
                        :error="errors.expMonth"
                        :model-value="card.expMonth"
                        @update:model-value="updateCard('expMonth', $event)"
                    />

                    <LabelInput
                        label="YYYY"
                        placeholder="2029"
                        input-class="text-center"
                        :text-max="4"
                        :error="errors.expYear"
                        :model-value="card.expYear"
                        @update:model-value="updateCard('expYear', $event)"
                    />

                    <LabelInput
                        label="CVC"
                        placeholder="123"
                        input-class="text-center"
                        :text-max="4"
                        :error="errors.cvc"
                        :model-value="card.cvc"
                        @update:model-value="updateCard('cvc', $event)"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <LabelInput
                        label="First name"
                        :error="errors.firstName"
                        :model-value="card.firstName"
                        @update:model-value="updateCard('firstName', $event)"
                    />

                    <LabelInput
                        label="Last name"
                        :error="errors.lastName"
                        :model-value="card.lastName"
                        @update:model-value="updateCard('lastName', $event)"
                    />
                </div>

                <LabelInput
                    label="Email"
                    mode="email"
                    placeholder="you@email.com"
                    :error="errors.email"
                    :model-value="card.email"
                    @update:model-value="updateCard('email', $event)"
                />

                <button
                    type="submit"
                    :disabled="processing"
                    class="w-full rounded-xl bg-primary py-3 text-white font-semibold hover:bg-primary/90 transition disabled:opacity-50"
                >
                    {{ processing ? processingLabel : submitLabel }}
                </button>

                <div
                    class="flex justify-center items-center gap-2 text-xs text-slate-400"
                >
                    <i class="ti ti-lock" />
                    Secure encrypted payment
                </div>
            </form>

            <div v-else-if="isGCash" class="p-6 space-y-5">
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <h3 class="font-semibold text-slate-800">
                        Pay using GCash
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        You will be redirected to GCash to complete payment.
                    </p>
                </div>

                <button
                    type="button"
                    @click="onGCashPay"
                    :disabled="processing"
                    class="w-full rounded-xl bg-primary py-3 text-white font-semibold disabled:opacity-50"
                >
                    {{ processing ? gcashProcessingLabel : gcashLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
