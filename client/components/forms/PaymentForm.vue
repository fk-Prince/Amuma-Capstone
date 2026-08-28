<script setup lang="ts">
import {
    Wallet,
    Banknote,
    ShieldCheck,
    Check,
    LoaderCircle,
} from "lucide-vue-next";
import { computed, reactive, ref, watch } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { paymentSchema } from "~/types/payment";

export interface CardDetails {
    number: string;
    expMonth: string;
    expYear: string;
    cvc: string;
    firstName: string;
    lastName: string;
    email: string;
}

import visaIcon from "@/assets/icons/visa.png";
import gcashIcon from "@/assets/icons/gcash.png";
import LabelInput from "../ui/BaseInput.vue";
import { formatAmount } from "~/utils/currency";

type PaymentMethod = "CREDIT-CARD" | "GCASH" | "CASH";

interface Props {
    card?: CardDetails;
    processing: boolean;
    onCardPay?: () => void | Promise<void>;
    onGCashPay?: () => void | Promise<void>;
    onCashPay?: (cashReceived: number) => void | Promise<void>;
    title?: string;
    description?: string;
    submitLabel?: string;
    processingLabel?: string;
    gcashLabel?: string;
    gcashProcessingLabel?: string;
    cashLabel?: string;
    cashProcessingLabel?: string;
    cashDescription?: string;
    enableCard?: boolean;
    enableGCash?: boolean;
    enableCash?: boolean;
    totalAmount: number;
    currency?: string;
    allowShortCash?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: "Payment",
    description: "Choose your payment method and complete your subscription.",
    submitLabel: "Complete subscription",
    processingLabel: "Processing payment...",
    gcashLabel: "Continue to GCash",
    gcashProcessingLabel: "Redirecting...",
    cashLabel: "Confirm cash payment",
    cashProcessingLabel: "Processing...",
    cashDescription:
        "Enter the cash amount received. Your subscription will be activated once payment is confirmed.",
    enableCard: true,
    enableGCash: true,
    enableCash: false,
    currency: "₱",
    allowShortCash: true,
});

const emit = defineEmits<{
    (e: "update:card", value: CardDetails): void;
}>();

const checkout = useSubscriptionCheckout();

const allMethods: {
    value: PaymentMethod;
    label: string;
    image?: string;
    icon?: unknown;
    enabled: boolean;
}[] = [
    {
        value: "CREDIT-CARD",
        label: "Credit / Debit card",
        image: visaIcon,
        enabled: props.enableCard,
    },
    {
        value: "GCASH",
        label: "GCash",
        image: gcashIcon,
        enabled: props.enableGCash,
    },
    {
        value: "CASH",
        label: "Cash",
        icon: Banknote,
        enabled: props.enableCash,
    },
];

const methods = computed(() => allMethods.filter((m) => m.enabled));

const isCard = computed(() => checkout.payment_method === "CREDIT-CARD");
const isGCash = computed(() => checkout.payment_method === "GCASH");
const isCash = computed(() => checkout.payment_method === "CASH");

watch(
    methods,
    (list) => {
        const first = list[0];
        if (!first) return;

        const stillValid = list.some(
            (m) => m.value === checkout.payment_method,
        );

        if (!stillValid) {
            checkout.payment_method = first.value;
        }
    },
    { immediate: true },
);

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
    if (!props.card) return;

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
    const digits = digitsOnly(props.card?.number);

    if (/^4/.test(digits)) return "Visa";
    if (/^(5[1-5]|2[2-7])/.test(digits)) return "Mastercard";
    if (/^3[47]/.test(digits)) return "Amex";

    return null;
});

const validateCardForm = () => {
    if (!props.card) return;

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

const cashReceivedInput = ref("");
const cashError = ref("");

const formatMoney = (value: number) => formatAmount(value);

const handleCashAmountInput = (value: string | number) => {
    const raw = String(value ?? "");
    const cleaned = raw.replace(/[^\d.]/g, "").replace(/(\..*)\./g, "$1");

    cashReceivedInput.value = cleaned;

    if (cashError.value) {
        cashError.value = "";
    }
};

const cashReceivedAmount = computed(() => {
    const parsed = parseFloat(cashReceivedInput.value);
    return Number.isFinite(parsed) ? parsed : 0;
});

const changeDue = computed(() =>
    Math.max(cashReceivedAmount.value - props.totalAmount, 0),
);

const isCashSufficient = computed(() =>
    props.allowShortCash
        ? cashReceivedAmount.value > 0
        : cashReceivedAmount.value >= props.totalAmount,
);

const handleCashPay = () => {
    if (props.processing) return;

    if (!isCashSufficient.value) {
        cashError.value = props.allowShortCash
            ? "Please enter the cash amount received."
            : `Amount must be at least ${props.currency}${formatMoney(props.totalAmount)}`;

        return;
    }

    props.onCashPay?.(cashReceivedAmount.value);
};

watch(isCash, (active) => {
    if (active) {
        cashReceivedInput.value = "";
        cashError.value = "";
    }
});
</script>

<template>
    <div class="w-full max-w-2xl mx-auto">
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_8px_30px_rgba(15,23,42,0.06)]"
        >
            <!-- Header -->
            <div
                class="border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white px-6 py-5 sm:px-7"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <Wallet class="h-5 w-5" />
                    </div>

                    <div class="min-w-0">
                        <h2
                            class="text-lg font-bold tracking-tight text-slate-900"
                        >
                            {{ title }}
                        </h2>

                        <p class="mt-1 text-sm leading-5 text-slate-500">
                            {{ description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div
                v-if="methods.length > 1"
                class="border-b border-slate-100 px-6 py-5"
            >
                <div class="mb-4">
                    <p class="text-sm font-semibold text-slate-800">
                        Payment method
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Choose how you would like to pay.
                    </p>
                </div>

                <div class="space-y-3">
                    <button
                        v-for="method in methods"
                        :key="method.value"
                        type="button"
                        @click="selectMethod(method.value)"
                        class="relative flex w-full items-center gap-4 rounded-xl border px-4 py-3.5 text-left transition-all duration-200"
                        :class="
                            checkout.payment_method === method.value
                                ? 'border-primary bg-primary/[0.04] ring-1 ring-primary/20'
                                : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                        "
                    >
                        <div
                            class="flex h-12 w-16 shrink-0 items-center justify-center rounded-xl transition-all duration-200"
                        >
                            <img
                                v-if="method.image"
                                :src="method.image"
                                :alt="method.label"
                                class="h-auto w-auto max-h-6 max-w-[3.5rem] object-contain"
                            />

                            <component
                                v-else-if="method.icon"
                                :is="method.icon"
                                class="h-5 w-5 text-slate-600"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-sm font-semibold"
                                :class="
                                    checkout.payment_method === method.value
                                        ? 'text-primary'
                                        : 'text-slate-800'
                                "
                            >
                                {{ method.label }}
                            </p>

                            <p class="mt-0.5 text-xs text-slate-500">
                                <template v-if="method.value === 'CREDIT-CARD'">
                                    Visa, Mastercard and other cards
                                </template>

                                <template v-else-if="method.value === 'GCASH'">
                                    Pay securely using your GCash account
                                </template>

                                <template v-else-if="method.value === 'CASH'">
                                    Pay with cash at the facility
                                </template>
                            </p>
                        </div>

                        <div
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-all"
                            :class="
                                checkout.payment_method === method.value
                                    ? 'border-primary bg-primary'
                                    : 'border-slate-300 bg-white'
                            "
                        >
                            <svg
                                v-if="checkout.payment_method === method.value"
                                class="h-3 w-3 text-white"
                                viewBox="0 0 20 20"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="m5 10 3 3 7-7" />
                            </svg>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Card -->
            <form
                v-if="isCard && card"
                @submit.prevent="handleCardPay"
                class="space-y-6 px-6 py-6 sm:px-7 sm:py-7"
            >
                <div>
                    <h3 class="text-sm font-bold text-slate-800">
                        Card information
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Enter the card details used for this payment.
                    </p>
                </div>

                <div class="space-y-4">
                    <LabelInput
                        label="Card number"
                        placeholder="1234 5678 9012 3456"
                        :error="errors.number"
                        :text-max="23"
                        :model-value="card.number"
                        inputClass="pr-20 tracking-[0.08em] font-medium"
                        @update:model-value="handleCardNumberInput"
                    >
                        <template #suffix v-if="cardBrand">
                            <!-- :class="
                                    cardBrand === 'Visa'
                                        ? 'border-blue-100 bg-blue-50 text-[#1A1F71]'
                                        : cardBrand === 'Mastercard'
                                          ? 'border-red-100 bg-red-50 text-[#EB001B]'
                                          : 'border-slate-100 bg-slate-50 text-slate-600' border   shadow-sm
                                " -->
                            <div
                                class="flex h-full py-1 items-center justify-center rounded-md px-2 uppercase mr-2"
                            >
                                <img
                                    v-if="cardBrand === 'Visa'"
                                    :src="visaIcon"
                                    alt="Visa"
                                    class="h-3 w-5 max-w-8 object-contain"
                                />
                            </div>
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

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <LabelInput
                            label="First name"
                            placeholder="Juan"
                            :error="errors.firstName"
                            :model-value="card.firstName"
                            @update:model-value="
                                updateCard('firstName', $event)
                            "
                        />

                        <LabelInput
                            label="Last name"
                            placeholder="Dela Cruz"
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
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />

                    {{ processing ? processingLabel : submitLabel }}
                </button>

                <div
                    class="flex items-center justify-center gap-2 text-[11px] text-slate-400"
                >
                    <ShieldCheck class="h-4 w-4" />
                    <span>Secure encrypted payment</span>
                </div>
            </form>

            <!-- GCash -->
            <div
                v-else-if="isGCash"
                class="space-y-6 px-6 py-6 sm:px-7 sm:py-7"
            >
                <div
                    class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-blue-100"
                        >
                            <img
                                :src="gcashIcon"
                                alt="GCash"
                                class="h-6 w-auto object-contain"
                            />
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-slate-800">
                                Pay using GCash
                            </h3>

                            <p class="mt-1 text-sm leading-5 text-slate-500">
                                You will be redirected to GCash to securely
                                complete your payment.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3"
                >
                    <span class="text-sm text-slate-500"> Amount to pay </span>

                    <span class="text-base font-bold text-slate-900">
                        {{ currency }}{{ formatMoney(totalAmount) }}
                    </span>
                </div>

                <button
                    type="button"
                    @click="onGCashPay"
                    :disabled="processing"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />

                    {{ processing ? gcashProcessingLabel : gcashLabel }}
                </button>

                <div
                    class="flex items-center justify-center gap-2 text-[11px] text-slate-400"
                >
                    <ShieldCheck class="h-4 w-4" />
                    Secure payment via GCash
                </div>
            </div>

            <!-- Cash -->
            <div v-else-if="isCash" class="space-y-6 px-6 py-6 sm:px-7 sm:py-7">
                <div
                    class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-100"
                        >
                            <Banknote class="h-5 w-5" />
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-slate-800">
                                Pay with cash
                            </h3>

                            <p class="mt-1 text-sm leading-5 text-slate-500">
                                {{ cashDescription }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-white divide-y divide-slate-100"
                >
                    <div class="flex items-center justify-between px-4 py-3.5">
                        <span class="text-sm text-slate-500">
                            Total to pay
                        </span>

                        <span class="text-base font-bold text-slate-900">
                            {{ currency }}{{ formatMoney(totalAmount) }}
                        </span>
                    </div>

                    <div
                        v-if="cashReceivedAmount > 0"
                        class="flex items-center justify-between px-4 py-3.5"
                    >
                        <span class="text-sm text-slate-500">
                            {{
                                isCashSufficient ? "Change due" : "Amount short"
                            }}
                        </span>

                        <span
                            class="text-sm font-bold"
                            :class="
                                isCashSufficient
                                    ? 'text-emerald-600'
                                    : 'text-red-500'
                            "
                        >
                            {{ currency
                            }}{{
                                formatMoney(
                                    isCashSufficient
                                        ? changeDue
                                        : totalAmount - cashReceivedAmount,
                                )
                            }}
                        </span>
                    </div>
                </div>

                <LabelInput
                    label="Cash received"
                    :placeholder="`${currency}0.00`"
                    :error="cashError"
                    :model-value="cashReceivedInput"
                    @update:model-value="handleCashAmountInput"
                />

                <button
                    type="button"
                    @click="handleCashPay"
                    :disabled="processing || !cashReceivedAmount"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />

                    {{ processing ? cashProcessingLabel : cashLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
