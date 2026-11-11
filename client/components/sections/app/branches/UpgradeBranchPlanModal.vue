<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="flex w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Upgrade branch plan"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <ArrowUpCircle class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold leading-tight text-gray-900 dark:text-white">
                                    Upgrade branch plan
                                </h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    Move
                                    <span class="font-medium text-secondary dark:text-white">{{ branchName }}</span>
                                    to a bigger plan. You only pay the difference for the months left.
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

                    <div class="grid grid-cols-1 gap-5 px-6 py-5 lg:grid-cols-[1fr_18rem]">
                        <PaymentForm
                            compact
                            v-model:card="card"
                            class="min-w-0"
                            :total-amount="selected?.amount ?? 0"
                            :processing="processing || !selected"
                            :onCardPay="payCard"
                            :onGCashPay="payGCash"
                            :enableGCash="true"
                            title="Branch payment"
                            description="Choose your payment method to upgrade this branch."
                            submit-label="Pay & upgrade"
                            terms-context="subscription"
                        />

                        <div class="order-first space-y-4 lg:order-none">
                            <div v-if="options.length > 1" class="space-y-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">New plan</p>
                                <label
                                    v-for="option in options"
                                    :key="option.plan_code"
                                    class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 transition"
                                    :class="
                                        selectedCode === option.plan_code
                                            ? 'border-primary bg-primary-50/60 dark:bg-primary-500/10'
                                            : 'border-slate-200 hover:border-primary-200 dark:border-white/10'
                                    "
                                >
                                    <input
                                        v-model="selectedCode"
                                        type="radio"
                                        :value="option.plan_code"
                                        class="h-4 w-4 shrink-0 border-slate-300 text-primary"
                                    />
                                    <span class="min-w-0 flex-1 text-sm font-medium text-slate-800 dark:text-white">
                                        {{ option.name }}
                                    </span>
                                    <span class="text-xs font-semibold text-primary">
                                        ₱{{ formatAmount(option.amount) }}
                                    </span>
                                </label>
                            </div>

                            <div
                                v-if="selected"
                                class="h-fit rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                            >
                                <p class="text-base font-semibold text-slate-900 dark:text-white">
                                    Order Details
                                </p>
                                <p class="mt-1 text-xs leading-5 text-muted dark:text-gray-400">
                                    {{ currentName }} → {{ selected.name }}. Prorated for
                                    {{ selected.months }} {{ selected.months === 1 ? "month" : "months" }}
                                    until {{ formatDate(activation.renews_on) }}.
                                </p>

                                <dl class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 text-xs dark:border-white/10">
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted dark:text-gray-400">{{ selected.name }} branch (yearly)</dt>
                                        <dd class="font-medium text-secondary dark:text-gray-200">
                                            ₱{{ formatAmount(selected.additional_branch_price) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted dark:text-gray-400">{{ currentName }} branch (yearly)</dt>
                                        <dd class="font-medium text-secondary dark:text-gray-200">
                                            −₱{{ formatAmount(selected.current_price) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted dark:text-gray-400">
                                            × {{ selected.months }}/12 months left
                                        </dt>
                                        <dd />
                                    </div>
                                    <div
                                        class="flex justify-between gap-3 border-t border-slate-100 pt-1.5 text-sm dark:border-white/10"
                                    >
                                        <dt class="font-semibold text-secondary dark:text-white">Due today</dt>
                                        <dd class="font-bold text-primary">₱{{ formatAmount(selected.amount) }}</dd>
                                    </div>
                                </dl>

                                <p class="mt-3 text-[11px] leading-4 text-slate-400 dark:text-gray-500">
                                    The new plan starts right away. At renewal this branch is billed at the
                                    {{ selected.name }} branch price.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { ArrowUpCircle, X } from "lucide-vue-next";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { rememberPaymentReturn } from "~/utils/paymentReturn";
import { useToast } from "~/composables/useToast";
import { formatAmount } from "~/utils/currency";
import type { CardDetails } from "~/types/payment";
import type { BranchActivation } from "~/types/branch";

const props = defineProps<{
    branchName: string;
    branchUuid: string;
    activation: BranchActivation;
}>();

const emit = defineEmits<{
    close: [];
    upgraded: [result: any];
}>();

const { success, error } = useToast();

const processing = ref(false);
const options = computed(() => props.activation.upgrade_options ?? []);
const selectedCode = ref(options.value[0]?.plan_code ?? null);
const selected = computed(() => options.value.find((option) => option.plan_code === selectedCode.value) ?? null);
const currentName = computed(() => props.activation.plan?.name ?? "Current plan");

const card = ref<CardDetails>({
    number: "",
    expMonth: "",
    expYear: "",
    cvc: "",
    firstName: "",
    lastName: "",
    email: "",
});

const formatDate = (date?: string | null) =>
    date
        ? new Date(date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })
        : "";

const payload = (paymentMethod: "CREDIT-CARD" | "GCASH") => ({
    branch_uuid: props.branchUuid,
    target_uuid: props.activation.uuid,
    plan_code: selectedCode.value,
    payment_method: paymentMethod,
});

const onDone = async (result: any) => {
    success(result?.message ?? `${props.branchName} was upgraded.`);
    emit("upgraded", result);
};

const payCard = async () => {
    if (processing.value || !selected.value) return;
    processing.value = true;

    try {
        await cardPayment({
            card: card.value,
            amount: selected.value.amount,
            onClose: () => {
                processing.value = false;
            },
            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.upgradeBranchWithPurchase({
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
    if (processing.value || !selected.value) return;
    processing.value = true;

    try {
        rememberPaymentReturn(window.location.pathname + window.location.search);

        await gcashPayment({
            createPayment: () => subscriptionService.upgradeBranchWithPurchase(payload("GCASH")),
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
