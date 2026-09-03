<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Subscription &amp; Renewal
            </h2>

            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                Review this branch's plan and extend it before it lapses.
            </p>
        </div>

        <div v-if="loading" class="space-y-4">
            <div
                class="h-36 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/10"
            />
            <div
                class="h-24 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/10"
            />
        </div>

        <div
            v-else-if="!subscription"
            class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-slate-50/60 px-6 py-12 text-center dark:border-white/10 dark:bg-white/5"
        >
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
            >
                <CalendarX class="h-5 w-5" />
            </div>

            <p
                class="mt-2 text-sm font-semibold text-slate-800 dark:text-white"
            >
                No subscription found
            </p>
            <p
                class="mt-0.5 max-w-sm text-xs text-slate-500 dark:text-gray-400"
            >
                This branch has no subscription record to renew yet.
            </p>
        </div>

        <template v-else>
            <div
                class="overflow-hidden rounded-2xl border shadow-sm dark:border-white/10"
                :class="statusTone.border"
            >
                <div class="p-5" :class="statusTone.bg">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-secondary"
                                :class="statusTone.icon"
                            >
                                <component
                                    :is="statusTone.glyph"
                                    class="h-5 w-5"
                                />
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-base font-bold text-slate-900 dark:text-white"
                                >
                                    {{ subscription.plan?.name ?? "—" }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs capitalize text-slate-500 dark:text-gray-400"
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
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                Started
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-white"
                            >
                                {{ formatDate(subscription.start_date) }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                {{ isExpired ? "Expired on" : "Renews on" }}
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-white"
                            >
                                {{ formatDate(subscription.end_date) }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                {{ isExpired ? "Overdue by" : "Time left" }}
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold"
                                :class="statusTone.text"
                            >
                                {{ Math.abs(daysRemaining) }}
                                {{
                                    Math.abs(daysRemaining) === 1
                                        ? "day"
                                        : "days"
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div>
                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        {{
                            coveredBranches.length > 1
                                ? "Renew this subscription"
                                : "Renew this branch"
                        }}
                    </h3>

                    <p
                        class="mt-1 max-w-lg text-xs leading-5 text-slate-500 dark:text-gray-400"
                    >
                        <template v-if="hasPendingUpgrade">
                            You've already paid ahead — renewing again is
                            available once {{ pendingPlan.name }} takes over.
                        </template>
                        <template v-else>
                            Renewing early adds time on top of what's left — you
                            never lose the days you've already paid for.
                        </template>
                    </p>
                </div>

                <!-- Only worth showing when the renewal reaches beyond the
                     branch whose settings page this already is. -->
                <div
                    v-if="coveredBranches.length > 1"
                    class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/5"
                >
                    <p
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                    >
                        Branches on this subscription
                    </p>

                    <ul class="mt-3 flex flex-col gap-2">
                        <li
                            v-for="covered in coveredBranches"
                            :key="covered.uuid"
                            class="flex items-center gap-2 text-sm text-slate-700 dark:text-gray-300"
                        >
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full"
                                :class="
                                    covered.status === 'approved'
                                        ? 'bg-emerald-500'
                                        : 'bg-amber-500'
                                "
                            />
                            {{ covered.name }}
                            <span
                                v-if="covered.status !== 'approved'"
                                class="text-[10px] uppercase text-amber-600 dark:text-amber-400"
                            >
                                pending
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Already paid for, just waiting its turn -->
                <div
                    v-if="pendingPlan && !pendingPlan.is_due"
                    class="mt-4 rounded-xl border border-primary/20 bg-primary/5 p-4"
                >
                    <div class="flex items-start gap-3 text-xs text-primary">
                        <RefreshCw class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                            Upgrade to {{ pendingPlan.name }} starts on
                            {{ formatDate(pendingPlan.starts_at) }}, when the
                            current period ends.
                        </span>
                    </div>

                    <div
                        v-if="!confirmApply"
                        class="mt-3 flex flex-wrap items-center justify-between gap-3 pl-7"
                    >
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Don't want to wait?
                        </p>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-primary/30 bg-white px-1.5 py-1.5 text-xs font-semibold text-primary shadow-sm transition hover:border-primary/50 hover:bg-primary/5 dark:bg-secondary"
                            @click="confirmApply = true"
                        >
                            <span class="px-2.5"> Start it today </span>

                            <template v-if="forfeitedDays > 0">
                                <span
                                    class="mx-1 h-4 w-px bg-gray-200 dark:bg-gray-700"
                                    aria-hidden="true"
                                />

                                <span
                                    class="rounded-md bg-amber-50 px-2 py-1 text-[11px] font-bold text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                                >
                                    {{ forfeitedDays }}
                                    {{
                                        forfeitedDays === 1 ? "day" : "days"
                                    }}
                                    lost
                                </span>
                            </template>
                        </button>
                    </div>

                    <div
                        v-else
                        class="mt-3 rounded-lg border border-primary/20 bg-white p-3 dark:bg-white/5"
                    >
                        <p class="text-xs text-slate-600 dark:text-gray-300">
                            {{ pendingPlan.name }} starts today and
                            <span class="font-semibold">
                                {{ currentPlan?.name ?? "your current plan" }}
                            </span>
                            ends now — you give up its
                            {{ forfeitedDays }}
                            remaining
                            {{ forfeitedDays === 1 ? "day" : "days" }}, so
                            coverage runs to
                            {{ formatDate(earlyUpgradeEndDate) }} instead of
                            {{ formatDate(subscription?.end_date) }}.
                        </p>

                        <div class="mt-3 flex flex-wrap justify-end gap-2">
                            <button
                                type="button"
                                :disabled="applying"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10"
                                @click="confirmApply = false"
                            >
                                Keep waiting
                            </button>

                            <button
                                type="button"
                                :disabled="applying"
                                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary/90 disabled:opacity-50"
                                @click="applyUpgradeNow"
                            >
                                {{
                                    applying ? "Applying…" : "Yes, start today"
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="upgradePlan" class="mt-5">
                    <p
                        class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Plan
                    </p>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition dark:border-white/10"
                        :class="
                            wantsUpgrade
                                ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                : 'border-slate-200 hover:border-primary-200 dark:border-white/10 dark:hover:border-primary-500/40'
                        "
                    >
                        <input
                            v-model="wantsUpgrade"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                        />

                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-900 dark:text-white"
                                >
                                    Upgrade to {{ upgradePlan.name }}
                                </span>

                                <span
                                    v-if="upgradeDelta > 0"
                                    class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-bold text-primary"
                                >
                                    +₱{{ formatMoney(upgradeDelta) }}
                                    {{
                                        renewInterval === "yearly"
                                            ? "/ year"
                                            : "/ month"
                                    }}
                                </span>
                            </span>

                            <span
                                class="mt-1 block text-xs leading-5 text-slate-500 dark:text-gray-400"
                            >
                                Currently on
                                {{ subscription?.plan?.name }} — upgrading adds
                                both homecare and in-house facility to
                                {{
                                    coveredBranches.length > 1
                                        ? `all ${coveredBranches.length} branches on this subscription`
                                        : "this branch"
                                }}.
                            </span>
                        </span>
                    </label>

                    <div
                        v-if="wantsUpgrade"
                        class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2"
                    >
                        <label
                            v-for="timing in UPGRADE_TIMINGS"
                            :key="timing.value"
                            class="flex cursor-pointer flex-col rounded-xl border px-4 py-3 transition dark:border-white/10"
                            :class="
                                upgradeTiming === timing.value
                                    ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                    : 'border-slate-200 hover:border-primary-200 dark:border-white/10 dark:hover:border-primary-500/40'
                            "
                        >
                            <span class="flex items-center gap-2.5">
                                <span
                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2"
                                    :class="
                                        upgradeTiming === timing.value
                                            ? 'border-primary'
                                            : 'border-slate-300 dark:border-white/20'
                                    "
                                >
                                    <span
                                        v-if="upgradeTiming === timing.value"
                                        class="h-2 w-2 rounded-full bg-primary"
                                    />
                                </span>

                                <span
                                    class="text-sm font-medium text-slate-800 dark:text-white"
                                >
                                    {{ timing.label }}
                                </span>

                                <span
                                    v-if="
                                        timing.value === 'now' &&
                                        daysRemaining > 0
                                    "
                                    class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                                >
                                    {{ daysRemaining }}
                                    {{ daysRemaining === 1 ? "day" : "days" }}
                                    lost
                                </span>

                                <span
                                    v-else-if="timing.value === 'after'"
                                    class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                >
                                    Nothing lost
                                </span>
                            </span>

                            <span
                                class="mt-1.5 pl-6.5 text-xs leading-5"
                                :class="
                                    timing.value === 'now' && daysRemaining > 0
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-slate-500 dark:text-gray-400'
                                "
                            >
                                {{
                                    timing.value === "now"
                                        ? `Starts today. You give up the ${Math.max(daysRemaining, 0)} day${Math.max(daysRemaining, 0) === 1 ? "" : "s"} left on the current plan.`
                                        : `Starts ${formatDate(subscription?.end_date)}, keeping the days you already paid for.`
                                }}
                            </span>

                            <input
                                type="radio"
                                class="sr-only"
                                :value="timing.value"
                                :checked="upgradeTiming === timing.value"
                                @change="upgradeTiming = timing.value"
                            />
                        </label>
                    </div>
                </div>

                <template v-if="!hasPendingUpgrade">
                    <p
                        class="mb-2 mt-5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Billing cycle
                    </p>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label
                            v-for="option in INTERVALS"
                            :key="option.value"
                            class="flex cursor-pointer items-center justify-between rounded-xl border px-4 py-3 transition dark:border-white/10"
                            :class="
                                renewInterval === option.value
                                    ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                    : 'border-slate-200 hover:border-primary-200 dark:border-white/10 dark:hover:border-primary-500/40'
                            "
                        >
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="flex h-4 w-4 items-center justify-center rounded-full border-2"
                                    :class="
                                        renewInterval === option.value
                                            ? 'border-primary'
                                            : 'border-slate-300 dark:border-white/20'
                                    "
                                >
                                    <span
                                        v-if="renewInterval === option.value"
                                        class="h-2 w-2 rounded-full bg-primary"
                                    />
                                </span>

                                <span
                                    class="text-sm font-medium text-slate-800 dark:text-white"
                                >
                                    {{ option.label }}
                                </span>

                                <span
                                    v-if="
                                        option.value === 'yearly' &&
                                        yearlySavings > 0
                                    "
                                    class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                >
                                    Save {{ yearlySavings }}%
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

                    <div
                        class="mt-5 flex flex-col gap-4 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p class="flex items-baseline gap-2">
                                <span
                                    class="text-lg font-bold text-slate-900 dark:text-white"
                                >
                                    ₱{{ formatMoney(renewTotal) }}
                                </span>
                            </p>

                            <p
                                class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                            >
                                {{ chargedPlan?.name }} · covered until
                                {{ formatDate(projectedEndDate) }}
                                <template v-if="isUpgrading">
                                    ·
                                    {{
                                        upgradeTiming === "now"
                                            ? "upgrade starts today"
                                            : `upgrade starts ${formatDate(subscription?.end_date)}`
                                    }}
                                </template>
                            </p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                            @click="openRenew"
                        >
                            <RefreshCw class="h-4 w-4" />
                            {{
                                isUpgrading
                                    ? "Upgrade & renew"
                                    : "Renew subscription"
                            }}
                        </button>
                    </div>
                </template>
            </div>

            <div
                v-if="payments.length"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="border-b border-slate-100 px-5 py-3 dark:border-white/10"
                >
                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        Payment history
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px]">
                        <thead>
                            <tr class="bg-slate-50/60 dark:bg-white/5">
                                <th
                                    v-for="head in [
                                        'Reference',
                                        'Plan',
                                        'Card',
                                        'Amount',
                                        'Date',
                                        'Status',
                                    ]"
                                    :key="head"
                                    class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    {{ head }}
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100 dark:divide-white/10"
                        >
                            <tr
                                v-for="payment in payments"
                                :key="payment.subscription_payment_id"
                                class="transition hover:bg-slate-50/60 dark:hover:bg-white/5"
                            >
                                <td
                                    class="px-4 py-2.5 text-xs font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{ payment.payment_reference_id ?? "—" }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ payment.plan_name ?? "—" }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ payment.masked_card_number ?? "—" }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold text-slate-800 dark:text-white"
                                >
                                    ₱{{ formatMoney(payment.price) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ formatDate(payment.created_at) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
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
                    class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Renew subscription"
                >
                    <div
                        class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <RefreshCw class="h-5 w-5" />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    Renew subscription
                                </h2>
                                <p
                                    class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Extends
                                    {{
                                        coveredBranches.length > 1
                                            ? `${coveredBranches.length} branches`
                                            : "this branch"
                                    }}
                                    to {{ formatDate(projectedEndDate) }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Close dialog"
                            :disabled="processing"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
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

const UPGRADE_TIMINGS = [
    { value: "now", label: "Start today" },
    { value: "after", label: "Start when current period ends" },
] as const;

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

const coveredBranches = computed<
    { uuid: string; name: string; status: string }[]
>(() => subscription.value?.subscription?.covered_branches ?? []);

const currentPlan = computed(() =>
    plans.value.find(
        (plan) => plan.plan_code === subscription.value?.plan?.plan_code,
    ),
);

const upgradePlan = computed(() => {
    const code = subscription.value?.plan?.plan_code;

    if (!code || code === "C") return null;

    return plans.value.find((plan) => plan.plan_code === "C") ?? null;
});

const pendingPlan = computed(() => subscription.value?.pending_plan ?? null);

const hasPendingUpgrade = computed(
    () => Boolean(pendingPlan.value) && !pendingPlan.value.is_due,
);

const confirmApply = ref(false);
const applying = ref(false);

const forfeitedDays = computed(() => {
    if (!pendingPlan.value?.starts_at) return 0;

    const starts = new Date(pendingPlan.value.starts_at).setHours(0, 0, 0, 0);
    const today = new Date().setHours(0, 0, 0, 0);

    return Math.max(0, Math.round((starts - today) / 86_400_000));
});

const earlyUpgradeEndDate = computed(() => {
    if (!subscription.value?.end_date) return null;

    const end = new Date(subscription.value.end_date);
    end.setDate(end.getDate() - forfeitedDays.value);

    return end.toISOString();
});

const wantsUpgrade = ref(false);
const upgradeTiming = ref<"now" | "after">("after");

const isUpgrading = computed(
    () => wantsUpgrade.value && Boolean(upgradePlan.value),
);

const chargedPlan = computed(() =>
    isUpgrading.value ? upgradePlan.value : currentPlan.value,
);

const priceFor = (interval: "monthly" | "yearly") =>
    Number(
        interval === "yearly"
            ? chargedPlan.value?.yearly_price
            : chargedPlan.value?.monthly_price,
    ) || 0;

const renewTotal = computed(() => priceFor(renewInterval.value));

// What the upgrade costs on top of simply renewing the current plan.
const upgradeDelta = computed(() => {
    if (!upgradePlan.value || !currentPlan.value) return 0;

    const key =
        renewInterval.value === "yearly" ? "yearly_price" : "monthly_price";

    return (
        (Number(upgradePlan.value[key]) || 0) -
        (Number(currentPlan.value[key]) || 0)
    );
});

const yearlySavings = computed(() => {
    const monthly = Number(chargedPlan.value?.monthly_price) || 0;
    const yearly = Number(chargedPlan.value?.yearly_price) || 0;

    if (!monthly || !yearly) return 0;

    return Math.max(
        0,
        Math.round(((monthly * 12 - yearly) / (monthly * 12)) * 100),
    );
});

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
            border: "border-rose-200 dark:border-rose-500/30",
            bg: "bg-rose-50/60 dark:bg-rose-500/10",
            divider: "border-rose-200/70 dark:border-rose-500/20",
            icon: "text-rose-600 dark:text-rose-300",
            glyph: TriangleAlert,
            badge: "bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300",
            dot: "bg-rose-500",
            text: "text-rose-600 dark:text-rose-400",
        };
    }

    if (isExpiringSoon.value) {
        return {
            border: "border-amber-200 dark:border-amber-500/30",
            bg: "bg-amber-50/60 dark:bg-amber-500/10",
            divider: "border-amber-200/70 dark:border-amber-500/20",
            icon: "text-amber-600 dark:text-amber-300",
            glyph: Clock,
            badge: "bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300",
            dot: "bg-amber-500",
            text: "text-amber-600 dark:text-amber-400",
        };
    }

    return {
        border: "border-emerald-200 dark:border-emerald-500/30",
        bg: "bg-emerald-50/60 dark:bg-emerald-500/10",
        divider: "border-emerald-200/70 dark:border-emerald-500/20",
        icon: "text-emerald-600 dark:text-emerald-300",
        glyph: CheckCircle2,
        badge: "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300",
        dot: "bg-emerald-500",
        text: "text-emerald-600 dark:text-emerald-400",
    };
});

const projectedEndDate = computed(() => {
    const end = subscription.value?.end_date
        ? new Date(subscription.value.end_date)
        : new Date();

    const startsToday = isUpgrading.value && upgradeTiming.value === "now";

    const base = !startsToday && end.getTime() > Date.now() ? end : new Date();

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
                    plan_code: chargedPlan.value?.plan_code,
                    upgrade_timing: isUpgrading.value
                        ? upgradeTiming.value
                        : undefined,
                    token_id,
                    authentication_id,
                }),

            onSuccess: async (result: any) => {
                success(result?.message ?? "Subscription renewed.");

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

const applyUpgradeNow = async () => {
    if (applying.value) return;

    applying.value = true;

    try {
        const result = await subscriptionService.applyUpgrade({
            branch_uuid: props.uuid,
        });

        subscription.value = {
            ...subscription.value,
            ...result.subscription,
        };

        confirmApply.value = false;
        success(result?.message ?? "Upgrade applied.");
    } catch (err: any) {
        error(err?.message ?? "Could not start the upgrade.");
    } finally {
        applying.value = false;
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
