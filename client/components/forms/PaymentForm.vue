<script setup lang="ts">
import { computed, reactive } from "vue";
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

const clearError = (key: keyof typeof errors) => {
    errors[key] = "";
};

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

const digitsOnly = (val: unknown) => String(val ?? "").replace(/\D/g, "");

// Formats card number with spaces every 4 digits as the person types,
// e.g. 4000000000002503 -> 4000 0000 0000 2503
const formatCardNumber = (val: unknown) =>
    digitsOnly(val)
        .slice(0, 19)
        .replace(/(.{4})/g, "$1 ")
        .trim();

const handleCardNumberInput = (val: string | number) => {
    updateCard("number", formatCardNumber(val) as CardDetails["number"]);
};

const cardBrand = computed(() => {
    const digits = digitsOnly(props.card.number);
    if (/^4/.test(digits)) return "Visa";
    if (/^(5[1-5]|2[2-7])/.test(digits)) return "Mastercard";
    if (/^3[47]/.test(digits)) return "Amex";
    return null;
});

const validateField = (key: keyof typeof errors) => {
    const card = props.card;
    switch (key) {
        case "number": {
            const digits = digitsOnly(card.number);
            errors.number =
                digits.length >= 13 && digits.length <= 19
                    ? ""
                    : "Enter a valid card number.";
            break;
        }
        case "expMonth": {
            const m = Number(card.expMonth);
            errors.expMonth =
                m >= 1 && m <= 12 ? "" : "Enter a valid month (01–12).";
            break;
        }
        case "expYear": {
            const y = String(card.expYear ?? "");
            errors.expYear = /^\d{2,4}$/.test(y) ? "" : "Enter a valid year.";
            break;
        }
        case "cvc": {
            const c = digitsOnly(card.cvc);
            errors.cvc =
                c.length >= 3 && c.length <= 4 ? "" : "Enter a valid CVC.";
            break;
        }
        case "firstName":
            errors.firstName = card.firstName?.trim()
                ? ""
                : "First name is required.";
            break;
        case "lastName":
            errors.lastName = card.lastName?.trim()
                ? ""
                : "Last name is required.";
            break;
        case "email": {
            const e = String(card.email ?? "");
            errors.email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)
                ? ""
                : "Enter a valid email address.";
            break;
        }
    }
};

const validateCardForm = () => {
    (Object.keys(errors) as (keyof typeof errors)[]).forEach(validateField);
    return Object.values(errors).every((e) => e === "");
};

const handleCardPay = () => {
    if (props.processing) return;
    if (!validateCardForm()) return;
    props.onCardPay?.();
};
</script>

<template>
    <div
        class="w-full overflow-hidden rounded-2xl border border-muted-light bg-white p-5 shadow-sm"
    >
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Payment details</h2>
            <p class="text-sm text-gray-500">
                Select your preferred payment method.
            </p>
        </div>

        <div class="rounded-2xl border border-primary/20 p-4">
            <label class="mb-3 block text-sm font-medium text-gray-700">
                Payment method
            </label>

            <div class="grid grid-cols-2 gap-3">
                <button
                    v-for="method in methods"
                    :key="method.value"
                    type="button"
                    :aria-label="method.label"
                    :aria-pressed="checkout.payment_method === method.value"
                    @click="selectMethod(method.value)"
                    class="relative flex flex-col items-center gap-2 rounded-xl border p-3 transition-all duration-200"
                    :class="
                        checkout.payment_method === method.value
                            ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                            : 'border-gray-200 bg-white hover:border-primary/40 hover:bg-gray-50'
                    "
                >
                    <span
                        v-if="checkout.payment_method === method.value"
                        class="absolute right-2 top-2 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-white"
                    >
                        <svg
                            viewBox="0 0 16 16"
                            class="h-2.5 w-2.5"
                            fill="none"
                        >
                            <path
                                d="M3 8.5L6 11.5L13 4.5"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>

                    <img
                        :src="method.image"
                        :alt="method.label"
                        class="h-7 object-contain"
                    />
                    <span class="text-xs font-medium text-gray-600">
                        {{ method.label }}
                    </span>
                </button>
            </div>
        </div>

        <form
            v-if="isCard"
            class="mt-6 space-y-5"
            @submit.prevent="handleCardPay"
        >
            <div>
                <LabelInput
                    label="Card number"
                    placeholder="1234 5678 9012 3456"
                    :error="errors.number"
                    required
                    :text-max="23"
                    :model-value="card.number"
                    @update:model-value="handleCardNumberInput($event)"
                    @blur="validateField('number')"
                    @clear-error="clearError('number')"
                >
                    <template v-if="cardBrand" #suffix>
                        <span class="text-xs font-medium text-gray-400">
                            {{ cardBrand }}
                        </span>
                    </template>
                </LabelInput>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <LabelInput
                    label="MM"
                    placeholder="04"
                    input-class="text-center"
                    :error="errors.expMonth"
                    required
                    :text-max="2"
                    :model-value="card.expMonth"
                    @update:model-value="updateCard('expMonth', $event)"
                    @blur="validateField('expMonth')"
                    @clear-error="clearError('expMonth')"
                />

                <LabelInput
                    label="YYYY"
                    placeholder="2029"
                    input-class="text-center"
                    :error="errors.expYear"
                    required
                    :text-max="4"
                    :model-value="card.expYear"
                    @update:model-value="updateCard('expYear', $event)"
                    @blur="validateField('expYear')"
                    @clear-error="clearError('expYear')"
                />

                <LabelInput
                    label="CVC"
                    placeholder="123"
                    input-class="text-center"
                    :error="errors.cvc"
                    required
                    :text-max="4"
                    :model-value="card.cvc"
                    @update:model-value="updateCard('cvc', $event)"
                    @blur="validateField('cvc')"
                    @clear-error="clearError('cvc')"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <LabelInput
                    label="First name"
                    :error="errors.firstName"
                    required
                    :model-value="card.firstName"
                    @update:model-value="updateCard('firstName', $event)"
                    @blur="validateField('firstName')"
                    @clear-error="clearError('firstName')"
                />

                <LabelInput
                    label="Last name"
                    :error="errors.lastName"
                    required
                    :model-value="card.lastName"
                    @update:model-value="updateCard('lastName', $event)"
                    @blur="validateField('lastName')"
                    @clear-error="clearError('lastName')"
                />
            </div>

            <LabelInput
                label="Email"
                mode="email"
                placeholder="you@email.com"
                :error="errors.email"
                required
                :model-value="card.email"
                @update:model-value="updateCard('email', $event)"
                @blur="validateField('email')"
                @clear-error="clearError('email')"
            />

            <button
                type="submit"
                :disabled="processing"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg
                    v-if="processing"
                    class="h-4 w-4 animate-spin text-white"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                </svg>
                {{ processing ? "Processing…" : "Pay with card" }}
            </button>

            <p class="text-center text-xs text-gray-400">
                Payments are encrypted and processed securely.
            </p>
        </form>

        <div v-else-if="isGCash" class="mt-6 space-y-5">
            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-5">
                <h3 class="text-sm font-semibold text-gray-800">
                    Pay with GCash
                </h3>
                <p class="mt-1.5 text-sm text-gray-500">
                    You'll be redirected to GCash to complete this payment
                    securely, then brought back here automatically.
                </p>
            </div>

            <button
                type="button"
                :disabled="processing"
                @click="onGCashPay"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg
                    v-if="processing"
                    class="h-4 w-4 animate-spin text-white"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                </svg>
                {{ processing ? "Redirecting…" : "Continue to GCash" }}
            </button>

            <p class="text-center text-xs text-gray-400">
                Payments are encrypted and processed securely.
            </p>
        </div>
    </div>
</template>
