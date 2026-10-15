<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { X, CreditCard } from "lucide-vue-next";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { paymentService } from "~/api/payment/PaymentService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { useToast } from "~/composables/useToast";
import { formatCurrency } from "~/utils/currency";
import type { CardDetails } from "~/types/payment";

const props = defineProps<{
    open: boolean;
    referenceId: string;
    patientName: string;
    total: number;
    amountDue: number;
    percent: number | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "paid"): void;
}>();

const { success, error: toastError } = useToast();

const paying = ref(false);

function emptyCard(): CardDetails {
    return {
        number: "",
        expMonth: "",
        expYear: "",
        cvc: "",
        firstName: "",
        lastName: "",
        email: "",
    };
}

const card = reactive<CardDetails>(emptyCard());

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) Object.assign(card, emptyCard());
    },
);

function errorMessage(err: any, fallback: string) {
    return err?.response?.data?.message ?? err?.message ?? fallback;
}

function onPaid(message?: string) {
    paying.value = false;

    success(message ?? "Payment received.");

    emit("paid");
}

// The GCash popup only reports that the customer finished; the webhook is
// what records the payment, so wait for it before refreshing the booking.
async function waitForGCash(reference?: string) {
    for (let attempt = 0; reference && attempt < 8; attempt++) {
        const res = await paymentService.checkStatus(reference);

        if (res.status === "submitted") return;

        if (res.status === "failed" || res.status === "unknown") {
            throw new Error(
                res.message ?? "We couldn't apply your GCash payment.",
            );
        }

        await new Promise((resolve) => setTimeout(resolve, 1500));
    }
}

async function handleCardPay() {
    if (paying.value) return;

    paying.value = true;

    try {
        await cardPayment({
            card,
            amount: props.amountDue,
            onClose: () => {
                paying.value = false;
            },
            createPayment: ({ token_id, authentication_id }) =>
                patientAccessService.executeAction({
                    action: "pay_booking",
                    reference_id: props.referenceId,
                    payment_method: "CREDIT-CARD",
                    token_id,
                    authentication_id,
                }),
            onSuccess: async (res) => onPaid(res?.message),
        });
    } catch (err: any) {
        toastError(errorMessage(err, "Payment failed."));
    } finally {
        paying.value = false;
    }
}

async function handleGCashPay() {
    if (paying.value) return;

    paying.value = true;

    try {
        await gcashPayment({
            createPayment: () =>
                patientAccessService.executeAction({
                    action: "pay_booking",
                    reference_id: props.referenceId,
                    payment_method: "GCASH",
                }),
            onSuccess: async (res) => {
                await waitForGCash(res?.external_id);
                onPaid();
            },
            onClose: () => {
                paying.value = false;
            },
        });
    } catch (err: any) {
        toastError(errorMessage(err, "GCash payment failed."));
    } finally {
        paying.value = false;
    }
}

function close() {
    if (paying.value) return;

    emit("close");
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="close"
        >
            <div
                class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                aria-label="Pay booking"
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
                                Pay booking
                            </h2>

                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ referenceId }} · {{ patientName }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        aria-label="Close dialog"
                        :disabled="paying"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="close"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="grid min-h-0 flex-1 gap-6 overflow-y-auto px-6 py-5 md:grid-cols-2 md:items-start"
                >
                    <div
                        class="rounded-2xl border border-primary/15 bg-primary/5 p-5 dark:border-primary-500/20 dark:bg-primary-500/10"
                    >
                        <p
                            class="text-xs font-bold uppercase tracking-wide text-primary"
                        >
                            Amount to pay now
                        </p>

                        <p
                            class="mt-1 text-4xl font-bold text-primary dark:text-primary-300"
                        >
                            {{ formatCurrency(amountDue) }}
                        </p>

                        <div
                            class="mt-4 space-y-1.5 border-t border-primary/15 pt-3 text-xs text-gray-600 dark:border-primary-500/20 dark:text-gray-400"
                        >
                            <div class="flex justify-between gap-3">
                                <span>Booking total</span>
                                <span class="font-semibold">
                                    {{ formatCurrency(total) }}
                                </span>
                            </div>

                            <div class="flex justify-between gap-3">
                                <span>
                                    Downpayment<template v-if="percent">
                                        ({{ percent }}%)</template
                                    >
                                </span>
                                <span class="font-semibold">
                                    {{ formatCurrency(amountDue) }}
                                </span>
                            </div>

                            <div
                                v-if="total - amountDue > 0"
                                class="flex justify-between gap-3"
                            >
                                <span>Balance after payment</span>
                                <span class="font-semibold">
                                    {{ formatCurrency(total - amountDue) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <PaymentForm
                        compact
                        :card="card"
                        :total-amount="amountDue"
                        :processing="paying"
                        :on-card-pay="handleCardPay"
                        :on-g-cash-pay="handleGCashPay"
                        terms-context="booking"
                        title="Payment method"
                        description="Pay by card or GCash. Payments are processed securely through Xendit."
                        submit-label="Pay now"
                        gcash-label="Pay with GCash"
                        gcash-processing-label="Redirecting to GCash..."
                        @update:card="(value) => Object.assign(card, value)"
                    />
                </div>
            </div>
        </div>
    </Transition>
</template>
