<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">
                Subscription &amp; Renewal
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Review this branch's plan and extend it before it lapses.
            </p>
        </div>

        <div v-if="loading" class="space-y-4">
            <div class="h-36 animate-pulse rounded-2xl bg-slate-100" />
            <div class="h-24 animate-pulse rounded-2xl bg-slate-100" />
        </div>

        <div
            v-else-if="!subscription"
            class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-slate-50/60 px-6 py-12 text-center"
        >
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400"
            >
                <CalendarX class="h-5 w-5" />
            </div>

            <p class="mt-2 text-sm font-semibold text-slate-800">
                No subscription found
            </p>
            <p class="mt-0.5 max-w-sm text-xs text-slate-500">
                This branch has no subscription record to renew yet.
            </p>
        </div>

        <template v-else>
            <div
                class="overflow-hidden rounded-2xl border shadow-sm"
                :class="statusTone.border"
            >
                <div class="p-5" :class="statusTone.bg">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm"
                                :class="statusTone.icon"
                            >
                                <component :is="statusTone.glyph" class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-base font-bold text-slate-900"
                                >
                                    {{ subscription.plan?.name ?? "—" }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs capitalize text-slate-500"
                                >
                                    Billed
                                    {{
                                        (
                                            subscription.billing_interval ?? ""
                                        ).toLowerCase()
                                    }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide"
                            :class="statusTone.badge"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="statusTone.dot"
                            />
                            {{ statusLabel }}
                        </span>
                    </div>

                    <div
                        class="mt-5 grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-3"
                        :class="statusTone.divider"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                            >
                                Started
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-slate-800"
                            >
                                {{ formatDate(subscription.start_date) }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                            >
                                {{ isExpired ? "Expired on" : "Renews on" }}
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-slate-800"
                            >
                                {{ formatDate(subscription.end_date) }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                            >
                                {{ isExpired ? "Overdue by" : "Time left" }}
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold"
                                :class="statusTone.text"
                            >
                                {{ Math.abs(daysRemaining) }}
                                {{ Math.abs(daysRemaining) === 1 ? "day" : "days" }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Renew this branch
                        </h3>

                        <p class="mt-1 max-w-lg text-xs leading-5 text-slate-500">
                            Renewing early adds time on top of what's left — you
                            never lose the days you've already paid for.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                        @click="openRenew"
                    >
                        <RefreshCw class="h-4 w-4" />
                        Renew subscription
                    </button>
                </div>

                <div
                    class="mt-4 grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2"
                >
                    <label
                        v-for="option in INTERVALS"
                        :key="option.value"
                        class="flex cursor-pointer items-center justify-between rounded-xl border px-4 py-3 transition"
                        :class="
                            renewInterval === option.value
                                ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20'
                                : 'border-slate-200 hover:border-primary-200'
                        "
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="flex h-4 w-4 items-center justify-center rounded-full border-2"
                                :class="
                                    renewInterval === option.value
                                        ? 'border-primary'
                                        : 'border-slate-300'
                                "
                            >
                                <span
                                    v-if="renewInterval === option.value"
                                    class="h-2 w-2 rounded-full bg-primary"
                                />
                            </span>

                            <span
                                class="text-sm font-medium text-slate-800"
                            >
                                {{ option.label }}
                            </span>
                        </div>

                        <span class="text-sm font-bold text-primary">
                            ₱{{ formatMoney(priceFor(option.value)) }}
                        </span>

                        <input
                            type="radio"
                            class="sr-only"
                            :value="option.value"
                            :checked="renewInterval === option.value"
                            @change="renewInterval = option.value"
                        />
                    </label>
                </div>
            </div>

            <div
                v-if="payments.length"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 px-5 py-3">
                    <h3 class="text-sm font-bold text-slate-900">
                        Payment history
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px]">
                        <thead>
                            <tr class="bg-slate-50/60">
                                <th
                                    v-for="head in [
                                        'Reference',
                                        'Card',
                                        'Amount',
                                        'Date',
                                        'Status',
                                    ]"
                                    :key="head"
                                    class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                >
                                    {{ head }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="payment in payments"
                                :key="payment.subscription_payment_id"
                                class="transition hover:bg-slate-50/60"
                            >
                                <td
                                    class="px-4 py-2.5 text-xs font-medium text-slate-700"
                                >
                                    {{ payment.payment_reference_id ?? "—" }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-500">
                                    {{ payment.masked_card_number ?? "—" }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold text-slate-800"
                                >
                                    ₱{{ formatMoney(payment.price) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500"
                                >
                                    {{ formatDate(payment.created_at) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-600"
                                    >
                                        {{ payment.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <Teleport to="body">
            <div
                v-if="showRenew"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
                @click.self="closeRenew"
            >
                <div
                    class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Renew subscription"
                >
                    <div
                        class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-100 px-6 py-5"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <RefreshCw class="h-5 w-5" />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-semibold text-gray-900"
                                >
                                    Renew subscription
                                </h2>
                                <p class="mt-0.5 text-sm text-gray-500">
                                    Extends to
                                    {{ formatDate(projectedEndDate) }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Close dialog"
                            :disabled="processing"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40"
                            @click="closeRenew"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <PaymentForm
                            v-model:card="card"
                            :total-amount="renewTotal"
                            :processing="processing"
                            :onCardPay="payCard"
                            :enableGCash="false"
                            title="Renewal payment"
                            description="Confirm your card to extend this branch's subscription."
                            submit-label="Confirm renewal"
                        />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
    CalendarX,
    CheckCircle2,
    Clock,
    RefreshCw,
    TriangleAlert,
    X,
} from "lucide-vue-next";

import PaymentForm from "~/components/forms/PaymentForm.vue";
import { formatAmount } from "~/utils/currency";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { planService } from "~/api/plan/PlanService";
import { cardPayment } from "~/composables/usePayment";
import { useToast } from "~/composables/useToast";
import type { CardDetails } from "~/types/payment";

const props = defineProps<{
    uuid: string;
}>();

const { success, error } = useToast();

const INTERVALS = [
    { value: "monthly", label: "Monthly" },
    { value: "yearly", label: "Yearly" },
] as const;

const loading = ref(true);
const processing = ref(false);
const showRenew = ref(false);

const subscription = ref<any>(null);
const plans = ref<any[]>([]);
const renewInterval = ref<"monthly" | "yearly">("monthly");

const card = ref<CardDetails>({
    number: "4000000000002503",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const payments = computed(() => subscription.value?.payments ?? []);

const currentPlan = computed(() =>
    plans.value.find(
        (plan) => plan.plan_code === subscription.value?.plan?.plan_code,
    ),
);

const priceFor = (interval: "monthly" | "yearly") =>
    Number(
        interval === "yearly"
            ? currentPlan.value?.yearly_price
            : currentPlan.value?.monthly_price,
    ) || 0;

const renewTotal = computed(() => priceFor(renewInterval.value));

const daysRemaining = computed(() => {
    if (!subscription.value?.end_date) return 0;

    const end = new Date(subscription.value.end_date).getTime();
    const diff = end - Date.now();

    return Math.ceil(diff / 86_400_000);
});

const isExpired = computed(() => daysRemaining.value < 0);
const isExpiringSoon = computed(
    () => !isExpired.value && daysRemaining.value <= 14,
);

const statusLabel = computed(() => {
    if (isExpired.value) return "Expired";
    if (isExpiringSoon.value) return "Expiring soon";

    return subscription.value?.status ?? "Active";
});

const statusTone = computed(() => {
    if (isExpired.value) {
        return {
            border: "border-rose-200",
            bg: "bg-rose-50/60",
            divider: "border-rose-200/70",
            icon: "text-rose-600",
            glyph: TriangleAlert,
            badge: "bg-rose-100 text-rose-700",
            dot: "bg-rose-500",
            text: "text-rose-600",
        };
    }

    if (isExpiringSoon.value) {
        return {
            border: "border-amber-200",
            bg: "bg-amber-50/60",
            divider: "border-amber-200/70",
            icon: "text-amber-600",
            glyph: Clock,
            badge: "bg-amber-100 text-amber-700",
            dot: "bg-amber-500",
            text: "text-amber-600",
        };
    }

    return {
        border: "border-emerald-200",
        bg: "bg-emerald-50/60",
        divider: "border-emerald-200/70",
        icon: "text-emerald-600",
        glyph: CheckCircle2,
        badge: "bg-emerald-100 text-emerald-700",
        dot: "bg-emerald-500",
        text: "text-emerald-600",
    };
});

// Mirrors the server: early renewals extend from the current end date, lapsed
// ones restart from today.
const projectedEndDate = computed(() => {
    const end = subscription.value?.end_date
        ? new Date(subscription.value.end_date)
        : new Date();

    const base = end.getTime() > Date.now() ? end : new Date();
    const projected = new Date(base);

    if (renewInterval.value === "yearly") {
        projected.setFullYear(projected.getFullYear() + 1);
    } else {
        projected.setMonth(projected.getMonth() + 1);
    }

    return projected.toISOString();
});

const fetchSubscription = async () => {
    loading.value = true;

    try {
        const [subRes, planRes] = await Promise.all([
            subscriptionService.list({
                branch_uuid: props.uuid,
                per_page: 1,
            }),
            planService.list(),
        ]);

        subscription.value = subRes?.data?.[0] ?? null;
        plans.value = planRes ?? [];

        const interval = String(
            subscription.value?.billing_interval ?? "monthly",
        ).toLowerCase();

        renewInterval.value = interval === "yearly" ? "yearly" : "monthly";
    } catch (err: any) {
        error(err?.message ?? "Failed to load subscription.");
    } finally {
        loading.value = false;
    }
};

const openRenew = () => {
    if (!renewTotal.value) {
        error("This plan has no price for the selected billing cycle.");
        return;
    }

    showRenew.value = true;
};

const closeRenew = () => {
    if (processing.value) return;
    showRenew.value = false;
};

const payCard = async () => {
    if (processing.value) return;

    processing.value = true;

    try {
        await cardPayment({
            card: card.value,
            amount: renewTotal.value,

            onClose: () => {
                processing.value = false;
            },

            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.renew({
                    branch_uuid: props.uuid,
                    billing_interval: renewInterval.value,
                    payment_method: "CREDIT-CARD",
                    token_id,
                    authentication_id,
                }),

            onSuccess: async (result: any) => {
                success(result?.message ?? "Subscription renewed.");

                // Patch the dates in place rather than refetching the tab.
                if (result?.subscription) {
                    subscription.value = {
                        ...subscription.value,
                        ...result.subscription,
                    };
                }

                showRenew.value = false;
            },
        });
    } catch (err: any) {
        error(err?.message ?? "Renewal payment failed.");
    } finally {
        processing.value = false;
    }
};

const formatDate = (date?: string | null) => {
    if (!date) return "—";

    try {
        return new Date(date).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    } catch {
        return String(date);
    }
};

const formatMoney = (value: number | string) =>
    formatAmount(value, { treatMissingAsZero: true });

onMounted(fetchSubscription);
</script>
