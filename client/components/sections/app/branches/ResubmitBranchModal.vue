<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
        >
            <Transition
                appear
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
            >
                <div
                    class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Resubmit branch"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <RotateCw class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <h2
                                    class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                >
                                    {{ agency ? "Reapply" : "Resubmit branch" }}
                                </h2>

                                <p
                                    class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        step === "agency"
                                            ? "Fix your agency details, then send"
                                            : step === "details"
                                              ? "Fix the details below and send"
                                              : step === "review"
                                                ? "Check everything, then send"
                                                : "Pay for an additional branch to send"
                                    }}
                                    <span
                                        class="font-medium text-secondary dark:text-white"
                                    >
                                        {{ branch.name }}
                                    </span>
                                    back for review.
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

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <p
                            v-if="formError"
                            class="mb-4 rounded-lg border border-danger/20 bg-danger/5 px-4 py-2 text-sm text-danger dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
                        >
                            {{ formError }}
                        </p>

                        <template v-if="step === firstStep">
                            <div
                                class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-500/30 dark:bg-rose-500/10"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-wide text-rose-500 dark:text-rose-300"
                                >
                                    Why it was rejected
                                </p>
                                <p
                                    class="mt-1 text-sm leading-6 text-rose-700 dark:text-rose-300"
                                >
                                    {{
                                        rejectionReason ||
                                        "No reason was given."
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="purchaseNotice"
                                class="mb-5 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/60 px-4 py-3 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                <CreditCard class="mt-0.5 h-4 w-4 shrink-0" />
                                <div>
                                    <p class="font-semibold">
                                        Additional branch payment required
                                    </p>
                                    <p class="mt-0.5">
                                        {{ purchaseNotice }} Update the details
                                        below, then pay to send this branch back
                                        for review.
                                    </p>
                                </div>
                            </div>
                        </template>

                        <AgencyForm
                            v-if="step === 'agency' && agencyForm"
                            v-model:agency="agencyForm"
                            v-model:errors="agencyErrors"
                        />

                        <BranchForm
                            v-if="step === 'details'"
                            v-model:branch="form"
                            v-model:errors="errors"
                        />

                        <div
                            v-if="step === 'review'"
                            :class="
                                resubmitPlan
                                    ? 'grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]'
                                    : 'mx-auto w-full max-w-3xl'
                            "
                        >
                        <div class="min-w-0 space-y-4">
                            <ResubmitReview
                                :agency="agencyForm"
                                :branch="form"
                            />

                            <div
                                v-if="isTrial && !resubmitPlan"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                            >
                                <p class="font-semibold">
                                    {{ planName ? `${planName} · ` : "" }}2-week
                                    free trial
                                </p>
                                <p class="mt-0.5 text-xs">
                                    Your trial starts once an admin approves
                                    your branch. Nothing is charged now.
                                </p>
                            </div>
                        </div>

                        <aside v-if="resubmitPlan" class="space-y-4">
                            <section
                                class="rounded-xl border border-slate-200 dark:border-white/10"
                            >
                                <h3
                                    class="border-b border-slate-100 px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:border-white/10 dark:text-gray-500"
                                >
                                    Plan
                                </h3>

                                <div class="space-y-3 px-4 py-3.5">
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-base font-semibold text-secondary dark:text-white"
                                            >
                                                {{ resubmitPlan.name }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                                            >
                                                {{ planTypeLabel(resubmitPlan.type) }}
                                                ·
                                                {{ branchLimitText(resubmitPlan.type) }}
                                            </p>
                                        </div>

                                        <span
                                            v-if="isTrial"
                                            class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                        >
                                            {{ resubmitPlan.trial_weeks }}-week
                                            free trial
                                        </span>
                                    </div>

                                    <div
                                        class="space-y-1.5 border-t border-slate-100 pt-3 text-xs dark:border-white/10"
                                    >
                                        <div class="flex justify-between gap-3">
                                            <span
                                                class="text-slate-500 dark:text-gray-400"
                                            >
                                                {{
                                                    isTrial
                                                        ? "12-month period (after trial)"
                                                        : "12-month period"
                                                }}
                                            </span>
                                            <span
                                                class="font-medium text-secondary dark:text-white"
                                            >
                                                ₱{{ formatAmount(resubmitPlan.subtotal) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-3">
                                            <span
                                                class="text-slate-500 dark:text-gray-400"
                                            >
                                                Taxes (VAT
                                                {{ resubmitPlan.vat_rate }}%)
                                            </span>
                                            <span
                                                class="font-medium text-secondary dark:text-white"
                                            >
                                                ₱{{ formatAmount(resubmitPlan.vat_amount) }}
                                            </span>
                                        </div>

                                        <div
                                            class="flex items-baseline justify-between gap-3 border-t border-slate-100 pt-2 dark:border-white/10"
                                        >
                                            <span
                                                class="text-sm font-semibold text-secondary dark:text-white"
                                            >
                                                {{
                                                    isTrial
                                                        ? "Total after trial"
                                                        : "Total"
                                                }}
                                            </span>
                                            <span
                                                class="text-base font-bold text-primary"
                                            >
                                                ₱{{ formatAmount(resubmitPlan.amount) }}
                                                <span
                                                    class="text-xs font-medium text-slate-400 dark:text-gray-500"
                                                >
                                                    / year
                                                </span>
                                            </span>
                                        </div>

                                        <div
                                            v-if="isTrial"
                                            class="flex justify-between gap-3"
                                        >
                                            <span
                                                class="text-slate-500 dark:text-gray-400"
                                            >
                                                Due today
                                            </span>
                                            <span
                                                class="font-semibold text-emerald-600 dark:text-emerald-400"
                                            >
                                                ₱0.00
                                            </span>
                                        </div>
                                    </div>

                                    <p
                                        v-if="isTrial"
                                        class="rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200"
                                    >
                                        Your trial starts once an admin
                                        approves your branch. Nothing is
                                        charged now.
                                    </p>
                                </div>
                            </section>

                            <section
                                class="rounded-xl border border-slate-200 dark:border-white/10"
                            >
                                <h3
                                    class="border-b border-slate-100 px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:border-white/10 dark:text-gray-500"
                                >
                                    Saved card
                                </h3>

                                <div
                                    v-if="resubmitPlan.card"
                                    class="flex items-center gap-3 px-4 py-3.5"
                                >
                                    <div
                                        class="flex h-9 w-12 shrink-0 items-center justify-center rounded-lg bg-white ring-1 ring-slate-200 dark:bg-secondary dark:ring-white/10"
                                    >
                                        <img
                                            v-if="resubmitPlan.card.brand === 'VISA'"
                                            :src="visaIcon"
                                            alt="Visa"
                                            class="h-3.5 w-auto object-contain"
                                        />
                                        <CreditCard
                                            v-else
                                            class="h-4 w-4 text-slate-500 dark:text-gray-400"
                                        />
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            {{ cardBrandLabel(resubmitPlan.card.brand) }}
                                            ••••
                                            {{ resubmitPlan.card.last4 }}
                                        </p>
                                        <p
                                            class="text-xs leading-5 text-slate-500 dark:text-gray-400"
                                        >
                                            <template v-if="isTrial">
                                                Charged ₱{{
                                                    formatAmount(resubmitPlan.amount)
                                                }}
                                                when the trial ends<template
                                                    v-if="resubmitPlan.card.auto_renew"
                                                    >, then yearly</template
                                                >.
                                            </template>
                                            <template
                                                v-else-if="resubmitPlan.card.auto_renew"
                                            >
                                                Auto-renew is on.
                                            </template>
                                            <template v-else>
                                                Auto-renew is off.
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="px-4 py-3.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    No card saved.
                                </p>
                            </section>
                        </aside>
                        </div>

                        <div
                            v-if="step === 'payment' && usesAdditional"
                            class="mx-auto grid w-full max-w-5xl items-start gap-5 lg:grid-cols-2"
                        >
                            <div class="min-w-0 space-y-4">
                            <template v-if="additionalOptions.length > 1">
                                <button
                                    v-for="option in additionalOptions"
                                    :key="option.uuid"
                                    type="button"
                                    class="flex w-full items-start gap-4 rounded-xl border p-5 text-left transition dark:border-white/10"
                                    :class="
                                        selectedSubscriptionUuid === option.uuid
                                            ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                            : 'border-slate-200 hover:border-primary hover:bg-primary-50/40 dark:hover:border-primary-500/40'
                                    "
                                    @click="selectedSubscriptionUuid = option.uuid"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                    >
                                        <Building2 class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            Add an additional branch to my
                                            {{ option.plan_name }}
                                            {{ planTypeLabel(option.plan_type) }}
                                            subscription
                                        </p>
                                        <p
                                            class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                                        >
                                            Runs until
                                            {{ formatDate(option.end_date) }}.
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary"
                                    >
                                        ₱{{ formatAmount(option.amount) }}
                                    </span>
                                </button>
                            </template>

                            <PaymentForm
                                v-if="additionalOption"
                                compact
                                v-model:card="card"
                                :total-amount="amountDue"
                                :processing="processing"
                                :onCardPay="payCard"
                                :onGCashPay="payGCash"
                                :enableGCash="true"
                                title="Additional branch payment"
                                description="Pay for the additional branch to send it back for review."
                                submit-label="Pay & resubmit"
                                terms-context="subscription"
                            />
                            </div>

                            <div
                                v-if="additionalOption"
                                class="order-first rounded-2xl border border-slate-200 bg-white p-5 lg:sticky lg:top-0 lg:order-none dark:border-white/10 dark:bg-secondary"
                            >
                                <p
                                    class="text-base font-semibold text-slate-900 dark:text-white"
                                >
                                    Order Details
                                </p>
                                <p
                                    class="mt-3 text-[13px] font-semibold text-secondary dark:text-gray-200"
                                >
                                    {{ additionalOption.plan_name }} —
                                    additional branch
                                </p>
                                <p
                                    class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                                >
                                    This branch joins your existing
                                    subscription and expires with it on
                                    {{ formatDate(additionalOption.end_date) }}.
                                    You pay
                                    ₱{{ formatAmount(total) }}, prorated for
                                    the time left. It renews with
                                    the subscription at
                                    ₱{{
                                        formatAmount(
                                            additionalOption.additional_branch_price,
                                        )
                                    }}
                                    / year.
                                </p>

                                <div
                                    class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 text-xs dark:border-white/10"
                                >
                                    <div class="flex justify-between">
                                        <span
                                            class="text-muted dark:text-gray-400"
                                        >
                                            Additional branch (yearly)
                                        </span>
                                        <span
                                            class="font-medium text-secondary dark:text-white"
                                        >
                                            ₱{{
                                                formatAmount(
                                                    additionalOption.additional_branch_price,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span
                                            class="text-muted dark:text-gray-400"
                                        >
                                            Months left on subscription
                                        </span>
                                        <span
                                            class="font-medium text-secondary dark:text-white"
                                        >
                                            {{ additionalOption.months }} of 12
                                        </span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span
                                            class="flex items-center gap-1.5 text-muted dark:text-gray-400"
                                        >
                                            Subtotal (prorated)
                                            <InfoTooltip
                                                :text="proratedHelp(additionalOption.additional_branch_price, additionalOption.months)"
                                            />
                                        </span>
                                        <span
                                            class="font-medium text-secondary dark:text-white"
                                        >
                                            ₱{{ formatAmount(total) }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span
                                            class="text-muted dark:text-gray-400"
                                            :title="`VAT: ${VAT_RATE}%`"
                                        >
                                            Taxes
                                        </span>
                                        <span
                                            class="font-medium text-secondary dark:text-white"
                                        >
                                            ₱{{ formatAmount(withVat(total).vat) }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex justify-between border-t border-slate-100 pt-1.5 dark:border-white/10"
                                    >
                                        <span
                                            class="font-semibold text-secondary dark:text-white"
                                        >
                                            You pay today
                                        </span>
                                        <span class="font-bold text-primary">
                                            ₱{{ formatAmount(amountDue) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div
                        class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            v-if="step !== firstStep"
                            type="button"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-white/20 dark:hover:bg-white/10"
                            @click="goBack"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Back
                        </button>

                        <button
                            v-else
                            type="button"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-white/20 dark:hover:bg-white/10"
                            @click="requestClose"
                        >
                            Cancel
                        </button>

                        <button
                            v-if="step === 'agency'"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600"
                            @click="continueFromAgency"
                        >
                            Continue
                            <ChevronRight class="h-4 w-4" />
                        </button>

                        <button
                            v-if="step === 'details'"
                            type="button"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submit"
                        >
                            <LoaderCircle
                                v-if="processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <template v-if="purchaseMode">
                                Continue to payment
                            </template>
                            <template v-else>Review</template>
                            <ChevronRight class="h-4 w-4" />
                        </button>

                        <button
                            v-if="step === 'review'"
                            type="button"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="send"
                        >
                            <LoaderCircle
                                v-if="processing"
                                class="h-4 w-4 animate-spin"
                            />
                            {{
                                processing
                                    ? "Resubmitting..."
                                    : "Resubmit for review"
                            }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>

        <ConfirmDialog
            :open="!!slotPrompt"
            title="No free slot"
            :message="slotPrompt ?? ''"
            description="Pay for an additional branch to send this branch back for review. Your changes to the details are kept."
            confirm-label="Continue"
            @confirm="switchToPurchase"
            @cancel="slotPrompt = null"
        />
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import {
    Building2,
    ChevronLeft,
    ChevronRight,
    CreditCard,
    LoaderCircle,
    RotateCw,
    X,
} from "lucide-vue-next";

import AgencyForm from "~/components/forms/AgencyForm.vue";
import BranchForm from "~/components/forms/BranchForm.vue";
import { agencySchema } from "~/schema/agency-schema";
import type { Agency } from "~/types/agency";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { VAT_RATE, withVat } from "~/utils/vat";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import InfoTooltip from "~/components/ui/InfoTooltip.vue";
import { proratedHelp } from "~/utils/prorated";
import ResubmitReview from "~/components/sections/app/branches/ResubmitReview.vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { useToast } from "~/composables/useToast";
import { branchSchema } from "~/schema/branch-schema";
import { formatAmount } from "~/utils/currency";
import visaIcon from "@/assets/icons/visa.png";
import {
    branchLimitText,
    planTypeLabel,
    type PlanType,
} from "~/utils/planType";
import type { Branch } from "~/types/branch";
import type { CardDetails } from "~/types/payment";

interface AdditionalOption {
    uuid: string;
    plan_name: string | null;
    plan_code: string | null;
    plan_type: PlanType | null;
    end_date: string | null;
    additional_branch_price: number;
    months: number;
    amount: number;
}

const props = defineProps<{
    branch: Branch;
    actingBranchUuid: string;
    rejectionReason?: string | null;
    requiresPurchase?: boolean;
    purchaseReason?: string | null;
    agency?: Agency | null;
    showPlan?: boolean;
    capacity?: {
        additional_options?: AdditionalOption[];
        is_testing?: boolean;
    } | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "resubmitted", result: any): void;
}>();

const { success, error } = useToast();

const purchaseMode = ref(!!props.requiresPurchase);
const purchaseNotice = ref(props.purchaseReason ?? null);

const toNumber = (value: unknown) =>
    value === null || value === undefined || value === ""
        ? undefined
        : Number(value);

const form = ref<Branch>({
    ...props.branch,
    tin: props.branch.settings?.tin ?? props.branch.tin ?? "",
    location: {
        street: props.branch.location?.street ?? "",
        city: props.branch.location?.city ?? "",
        province: props.branch.location?.province ?? "",
        country: props.branch.location?.country ?? "",
        full_address:
            props.branch.location?.full_address ??
            props.branch.location?.address ??
            "",
        latitude: toNumber(props.branch.location?.latitude),
        longitude: toNumber(props.branch.location?.longitude),
    },
});

type Step = "agency" | "details" | "review" | "payment";

const firstStep: Step = props.agency ? "agency" : "details";
const step = ref<Step>(firstStep);
const errors = ref<Record<string, string>>({});
const agencyErrors = ref<Record<string, string>>({});

const agencyForm = ref<Agency | null>(
    props.agency
        ? {
              ...props.agency,
              location: {
                  street: props.agency.location?.street ?? "",
                  city: props.agency.location?.city ?? "",
                  province: props.agency.location?.province ?? "",
                  country: props.agency.location?.country ?? "",
                  full_address: props.agency.location?.full_address ?? "",
                  latitude: toNumber(props.agency.location?.latitude),
                  longitude: toNumber(props.agency.location?.longitude),
              },
          }
        : null,
);

const agencyClientKeys: Record<string, string> = {
    name: "agency_name",
    description: "agency_description",
    email: "agency_email",
    image: "agency_image",
    id_front: "agency_id_front",
    id_back: "agency_id_back",
    document: "agency_document",
};

const agencyServerKeys: Record<string, string> = {
    agency_street: "location.street",
    agency_city: "location.city",
    agency_province: "location.province",
    agency_country: "location.country",
};

const scrollToField = async (key?: string) => {
    await nextTick();

    if (!key) return;

    document
        .querySelector(`[data-field~="${key}"]`)
        ?.scrollIntoView({ behavior: "smooth", block: "center" });
};

const validateAgency = (): boolean => {
    if (!agencyForm.value) return true;

    const result = agencySchema.safeParse(agencyForm.value);

    if (result.success) return true;

    const mapped: Record<string, string> = {};

    result.error.issues.forEach((issue) => {
        const path = issue.path.join(".");
        mapped[agencyClientKeys[path] ?? path] = issue.message;
    });

    agencyErrors.value = mapped;

    return false;
};

const continueFromAgency = async () => {
    formError.value = null;
    agencyErrors.value = {};

    if (!validateAgency()) {
        await scrollToField(Object.keys(agencyErrors.value)[0]);
        return;
    }

    step.value = "details";
};

const goBack = () => {
    formError.value = null;
    step.value =
        step.value === "payment" || step.value === "review"
            ? "details"
            : step.value === "details" && props.agency
              ? "agency"
              : firstStep;
};

const agencyPayload = () => {
    const agency = agencyForm.value;

    if (!agency) return {};

    const fileOrUndefined = (value: unknown) =>
        value instanceof File ? value : undefined;

    return {
        agency_name: agency.name,
        agency_email: agency.email,
        agency_description: agency.description,
        agency_street: agency.location.street,
        agency_city: agency.location.city,
        agency_province: agency.location.province,
        agency_country: agency.location.country,
        agency_full_address: agency.location.full_address ?? "",
        agency_latitude: agency.location.latitude,
        agency_longitude: agency.location.longitude,
        agency_image: fileOrUndefined(agency.image),
        agency_id_front: fileOrUndefined(agency.id_front),
        agency_id_back: fileOrUndefined(agency.id_back),
        agency_document: fileOrUndefined(agency.document),
    };
};
const formError = ref<string | null>(null);
const processing = ref(false);

const card = ref<CardDetails>({
    number: "4000000000001000",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const additionalOptions = computed<AdditionalOption[]>(
    () => props.capacity?.additional_options ?? [],
);

const hasAdditionalOptions = computed(() => additionalOptions.value.length > 0);

const selectedSubscriptionUuid = ref<string | null>(
    additionalOptions.value[0]?.uuid ?? null,
);

const additionalOption = computed(
    () =>
        additionalOptions.value.find(
            (option) => option.uuid === selectedSubscriptionUuid.value,
        ) ?? null,
);

const usesAdditional = computed(
    () => purchaseMode.value && hasAdditionalOptions.value,
);

const total = computed(() => Number(additionalOption.value?.amount) || 0);

const noSlotMessage = computed(() =>
    props.capacity?.is_testing
        ? "There's no free slot left on your plan. You can resubmit this branch once your free trial ends and your plan is paid."
        : props.branch.resubmit_subscription_status === "expired"
          ? "Your subscription has expired. Renew it in Manage Subscription, then resubmit this branch as an additional branch."
          : "Your subscription ends this month, so it can't take an additional branch. Renew it in Manage Subscription, then resubmit this branch.",
);

const amountDue = computed(() => withVat(total.value).total);

const formatDate = (date?: string | null) => {
    if (!date) return "—";

    return new Date(date).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
};

const clientKeys: Record<string, string> = {
    name: "branch_name",
    description: "branch_description",
    contact_number: "branch_contact_number",
    image: "branch_image",
    email: "branch_email",
    document: "branch_document",
    tin: "branch_tin",
};

const serverKeys: Record<string, string> = {
    branch_street: "location.street",
    branch_city: "location.city",
    branch_province: "location.province",
    branch_country: "location.country",
};

const scrollToFirstError = async () => {
    await nextTick();

    const firstKey = Object.keys(errors.value)[0];

    if (!firstKey) return;

    document
        .querySelector(`[data-field~="${firstKey}"]`)
        ?.scrollIntoView({ behavior: "smooth", block: "center" });
};

const validate = (): boolean => {
    const result = branchSchema.safeParse(form.value);

    if (result.success) return true;

    const mapped: Record<string, string> = {};

    result.error.issues.forEach((issue) => {
        const path = issue.path.join(".");
        mapped[clientKeys[path] ?? path] ??= issue.message;
    });

    errors.value = mapped;

    return false;
};

const buildPayload = () => {
    const branch = form.value;
    const location = branch.location;

    return {
        branch_uuid: props.actingBranchUuid,
        target_branch_uuid: props.branch.uuid,
        branch_name: branch.name,
        branch_email: branch.email,
        branch_contact_number: branch.contact_number,
        branch_description: branch.description,
        branch_tin: branch.tin || null,
        branch_image: branch.image instanceof File ? branch.image : undefined,
        branch_document:
            branch.document instanceof File ? branch.document : undefined,
        branch_street: location.street,
        branch_city: location.city,
        branch_province: location.province,
        branch_country: location.country,
        branch_full_address: location.full_address ?? "",
        branch_latitude: location.latitude,
        branch_longitude: location.longitude,
        ...agencyPayload(),
    };
};

const handleServerError = async (err: any, fallback: string) => {
    const raw: Record<string, any> = err?.errors ?? {};

    if (!Object.keys(raw).length) {
        formError.value = err?.message ?? fallback;
        return;
    }

    const first = (value: any) => (Array.isArray(value) ? value[0] : value);
    const agencyEntries = Object.entries(raw).filter(([key]) =>
        key.startsWith("agency_"),
    );
    const branchEntries = Object.entries(raw).filter(
        ([key]) => !key.startsWith("agency_"),
    );

    agencyErrors.value = Object.fromEntries(
        agencyEntries.map(([key, value]) => [
            agencyServerKeys[key] ?? key,
            first(value),
        ]),
    );
    errors.value = Object.fromEntries(
        branchEntries.map(([key, value]) => [
            serverKeys[key] ?? key,
            first(value),
        ]),
    );

    if (agencyEntries.length && props.agency) {
        step.value = "agency";
        await scrollToField(Object.keys(agencyErrors.value)[0]);
        return;
    }

    step.value = "details";
    await scrollToFirstError();
};

const submit = async () => {
    if (processing.value) return;

    formError.value = null;
    errors.value = {};

    if (!validate()) {
        await scrollToFirstError();
        return;
    }

    if (purchaseMode.value) {
        if (!hasAdditionalOptions.value) {
            formError.value = noSlotMessage.value;
            return;
        }

        step.value = "payment";
        return;
    }

    step.value = "review";
};

const send = async () => {
    if (processing.value) return;

    formError.value = null;
    processing.value = true;

    try {
        const result = await subscriptionService.resubmitBranch(buildPayload());

        success(result?.message ?? "Branch resubmitted for review.");
        emit("resubmitted", result);
    } catch (err: any) {
        if (err?.status === 409) {
            if (!hasAdditionalOptions.value) {
                formError.value = noSlotMessage.value;
                return;
            }

            slotPrompt.value =
                err?.message ?? "This branch's subscription has no free slot.";
            return;
        }

        await handleServerError(
            err,
            "Failed to resubmit the branch. Please try again.",
        );
    } finally {
        processing.value = false;
    }
};

const planName = computed(() => props.branch.plan?.[0]?.name ?? null);

const resubmitPlan = computed(() =>
    props.showPlan ? (props.branch.resubmit_plan ?? null) : null,
);

const CARD_BRANDS: Record<string, string> = {
    VISA: "VISA",
    MASTERCARD: "Mastercard",
    AMEX: "Amex",
    JCB: "JCB",
};

const cardBrandLabel = (brand?: string | null) =>
    CARD_BRANDS[brand ?? ""] ?? "Card";

const isTrial = computed(
    () => !!props.agency && props.branch.subscription_mode !== "live",
);

const slotPrompt = ref<string | null>(null);

const switchToPurchase = () => {
    purchaseNotice.value = "There's no free branch slot left on your subscription.";
    purchaseMode.value = true;
    slotPrompt.value = null;
    step.value = "payment";
};

const purchasePayload = (paymentMethod: "CREDIT-CARD" | "GCASH") => ({
    ...buildPayload(),
    subscription_uuid: additionalOption.value?.uuid,
    payment_method: paymentMethod,
});

const canPay = () => !!additionalOption.value;

const onPaid = async (result: any) => {
    success(
        result?.message ?? "Payment received. Branch resubmitted for review.",
    );
    emit("resubmitted", {
        ...result,
        purchased: false,
        additional: true,
    });
};

const payCard = async () => {
    if (processing.value || !canPay()) return;

    processing.value = true;
    formError.value = null;

    try {
        await cardPayment({
            card: card.value,
            amount: total.value,
            onClose: () => {
                processing.value = false;
            },
            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.resubmitBranchWithPurchase({
                    ...purchasePayload("CREDIT-CARD"),
                    token_id,
                    authentication_id,
                }),
            onSuccess: onPaid,
        });
    } catch (err: any) {
        await handleServerError(err, "Payment failed.");
        error(err?.message ?? "Payment failed.");
    } finally {
        processing.value = false;
    }
};

const payGCash = async () => {
    if (processing.value || !canPay()) return;

    processing.value = true;
    formError.value = null;

    try {
        await gcashPayment({
            createPayment: () =>
                subscriptionService.resubmitBranchWithPurchase(
                    purchasePayload("GCASH"),
                ),
            onClose: () => {
                processing.value = false;
            },
            onSuccess: onPaid,
        });
    } catch (err: any) {
        await handleServerError(err, "Payment failed.");
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
