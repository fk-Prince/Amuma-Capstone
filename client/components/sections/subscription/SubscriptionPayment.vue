<template>
    <div
        class="w-full grid grid-cols-1 lg:grid-cols-[1fr_420px] gap-10 items-start"
    >
        <div class="w-full">
            <CheckoutSummary
                :disabled="busy || processing"
                @back="emit('back')"
            />
        </div>

        <div
            v-if="!loadingTotal"
            class="rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary"
        >
            <div
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                <Gift class="h-5 w-5" />
            </div>

            <h3
                class="mt-4 text-base font-semibold text-slate-900 dark:text-white"
            >
                Start your 2-week free trial
            </h3>

            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400">
                No payment today. An admin reviews your subscription and branch
                first, and your 2-week trial starts once they're approved. Your
                card is charged only when the trial ends, unless you cancel
                before then.
            </p>

            <VatBreakdown
                :subtotal="subtotal"
                title="Order summary"
                :heading="planName"
                item-label="12-month period (after trial)"
                total-label="Total after trial"
                class="mt-5"
            />

            <button
                type="button"
                :disabled="processing"
                class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-60"
                @click="showCardModal = true"
            >
                <CreditCard class="h-4 w-4" />
                Start free trial
            </button>

            <p
                class="mt-2 text-center text-xs text-slate-500 dark:text-gray-400"
            >
                ₱0.00 due today · card required
            </p>

            <TrialCardModal
                v-model:card="card"
                :open="showCardModal"
                :amount="total"
                :processing="processing || xenditProcessing"
                @close="showCardModal = false"
                @submit="startTrial"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { CreditCard, Gift } from "lucide-vue-next";
import CheckoutSummary from "~/components/sections/subscription/CheckoutSummary.vue";
import TrialCardModal from "~/components/sections/subscription/TrialCardModal.vue";
import VatBreakdown from "~/components/billing/VatBreakdown.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { cardPayment } from "~/composables/usePayment";
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

const PLAN_NAMES: Record<string, string> = {
    A: "Homecare Services",
    B: "Inhouse Facility",
    C: "Hybrid Plan",
};

const planName = computed(
    () => PLAN_NAMES[checkout.selectedPlan?.plan_code ?? ""] ?? "",
);

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
const subtotal = ref(0);
const showCardModal = ref(false);

watch([processing, xenditProcessing], ([p, x]) => {
    busy.value = p || x;
});

const startTrial = async () => {
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
            reusable: true,

            onClose: () => {
                xenditProcessing.value = false;
                processing.value = false;
            },

            on3DSProcessingChange: (value: any) => {
                xenditProcessing.value = value;
            },

            createPayment: (saved) =>
                subscriptionService.startTrial({
                    ...payload,
                    ...saved,
                    payment_method: null,
                    payment_type: "SUBSCRIPTION",
                }),

            onSuccess: async (result) => {
                xenditProcessing.value = false;

                success("Free trial started", result.message);

                await fetchAuthUser();

                await navigateTo({
                    path: "/product/subscription-summary",
                    query: {
                        status: "true",
                        trial: "1",
                    },
                });
            },
        });
    } catch (err: any) {
        xenditProcessing.value = false;
        error(err?.message ?? "Could not start your free trial.");
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
        subtotal.value = Number(res.subtotal ?? res.total_amount);
    } catch (err: any) {
        error(err.message ?? "Failed to load subscription total.");
    } finally {
        loadingTotal.value = false;
    }
});
</script>
