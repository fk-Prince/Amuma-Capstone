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
                                              : usesAdditional
                                                ? "Pay for an additional branch to send"
                                                : "Choose a plan and pay to send"
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
                                        {{
                                            hasAdditionalOptions
                                                ? "Additional branch payment required"
                                                : "New subscription required"
                                        }}
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
                            class="mx-auto w-full max-w-2xl space-y-4"
                        >
                            <section
                                v-for="group in reviewGroups"
                                :key="group.title"
                                class="rounded-xl border border-slate-200 dark:border-white/10"
                            >
                                <h3
                                    class="border-b border-slate-100 px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:border-white/10 dark:text-gray-500"
                                >
                                    {{ group.title }}
                                </h3>

                                <dl
                                    class="divide-y divide-slate-100 dark:divide-white/10"
                                >
                                    <div
                                        v-for="row in group.rows"
                                        :key="row.label"
                                        class="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:gap-4"
                                    >
                                        <dt
                                            class="w-40 shrink-0 text-xs text-slate-500 dark:text-gray-400"
                                        >
                                            {{ row.label }}
                                        </dt>
                                        <dd
                                            class="min-w-0 break-words text-sm font-medium text-secondary dark:text-white"
                                        >
                                            {{ row.value }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <div
                                v-if="isTrial"
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

                        <div
                            v-if="step === 'payment' && usesAdditional"
                            class="mx-auto w-full max-w-2xl space-y-4"
                        >
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

                            <div
                                v-if="additionalOption"
                                class="rounded-xl border border-slate-200 p-5 dark:border-white/10"
                            >
                                <p
                                    class="text-sm font-semibold text-secondary dark:text-white"
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
                                    ₱{{ formatAmount(additionalOption.amount) }},
                                    prorated for the time left. It renews with
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

                                    <div
                                        class="flex justify-between border-t border-slate-100 pt-1.5 dark:border-white/10"
                                    >
                                        <span
                                            class="font-semibold text-secondary dark:text-white"
                                        >
                                            You pay today
                                        </span>
                                        <span class="font-bold text-primary">
                                            ₱{{
                                                formatAmount(
                                                    additionalOption.amount,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <PaymentForm
                                v-if="additionalOption"
                                compact
                                v-model:card="card"
                                :total-amount="total"
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

                        <template v-else-if="step === 'payment'">
                            <div v-if="loadingPlans" class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div
                                        v-for="n in 3"
                                        :key="n"
                                        class="h-40 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                                    />
                                </div>
                            </div>

                            <template v-else>
                                <div class="mb-4 flex justify-center">
                                    <PlanTypeToggle v-model="planType" />
                                </div>

                                <div
                                    class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-3"
                                >
                                    <button
                                        v-for="plan in typedPlans"
                                        :key="plan.plan_id"
                                        type="button"
                                        class="flex h-full flex-col gap-2 rounded-xl border p-5 text-left transition-all dark:border-white/10"
                                        :class="
                                            selectedPlan?.plan_id ===
                                            plan.plan_id
                                                ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                                : 'border-muted-light hover:border-primary-200 dark:hover:border-primary-500/40'
                                        "
                                        @click="selectedPlan = plan"
                                    >
                                        <p
                                            class="text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            {{ plan.name }}
                                        </p>
                                        <p
                                            class="text-xs leading-relaxed text-muted dark:text-gray-400"
                                        >
                                            {{ plan.description }}
                                        </p>
                                        <p
                                            class="mt-auto border-t border-muted-light/70 pt-3 text-base font-bold text-primary dark:border-white/10"
                                        >
                                            ₱{{ formatAmount(priceOf(plan)) }}
                                            <span
                                                class="text-xs font-medium text-muted dark:text-gray-400"
                                            >
                                                / year
                                            </span>
                                        </p>
                                    </button>
                                </div>

                                <PaymentForm
                                    v-if="selectedPlan"
                                    compact
                                    v-model:card="card"
                                    class="mt-6"
                                    :total-amount="total"
                                    :processing="processing"
                                    :onCardPay="payCard"
                                    :onGCashPay="payGCash"
                                    :enableGCash="true"
                                    title="Subscription payment"
                                    description="Pay for a new subscription to send this branch back for review."
                                    submit-label="Pay & resubmit"
                                    terms-context="subscription"
                                />
                            </template>
                        </template>
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
            :description="
                hasAdditionalOptions
                    ? 'Pay for an additional branch to send this branch back for review. Your changes to the details are kept.'
                    : 'Buy a new subscription to send this branch back for review. Your changes to the details are kept.'
            "
            confirm-label="Continue"
            @confirm="switchToPurchase"
            @cancel="slotPrompt = null"
        />
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
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
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import { planService } from "~/api/plan/PlanService";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { useToast } from "~/composables/useToast";
import { branchSchema } from "~/schema/branch-schema";
import { formatAmount } from "~/utils/currency";
import PlanTypeToggle from "~/components/ui/PlanTypeToggle.vue";
import {
    DEFAULT_PLAN_TYPE,
    planPrice,
    plansOfType,
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
    capacity?: {
        additional_options?: AdditionalOption[];
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

const plans = ref<any[]>([]);
const loadingPlans = ref(false);
const selectedPlan = ref<any | null>(null);

const card = ref<CardDetails>({
    number: "4000000000001000",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const planType = ref<PlanType>(DEFAULT_PLAN_TYPE);

const priceOf = (plan: any) => planPrice(plan);

const typedPlans = computed(() => plansOfType(plans.value, planType.value));

watch(planType, () => {
    selectedPlan.value =
        typedPlans.value.find((plan) => plan.plan_code === selectedPlan.value?.plan_code) ??
        typedPlans.value[0] ??
        null;
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

const total = computed(() =>
    usesAdditional.value
        ? Number(additionalOption.value?.amount) || 0
        : priceOf(selectedPlan.value),
);

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

const loadPlans = async () => {
    if (usesAdditional.value || plans.value.length) return;

    loadingPlans.value = true;

    try {
        plans.value = (await planService.list()) ?? [];
        selectedPlan.value = typedPlans.value[0] ?? null;
    } catch (err: any) {
        formError.value = err?.message ?? "Failed to load plans.";
    } finally {
        loadingPlans.value = false;
    }
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
        step.value = "payment";
        loadPlans();
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

const isTrial = computed(
    () => !!props.agency && props.branch.subscription_mode !== "live",
);

const addressOf = (location?: {
    full_address?: string | null;
    street?: string | null;
    city?: string | null;
    province?: string | null;
}) =>
    location?.full_address ||
    [location?.street, location?.city, location?.province]
        .filter(Boolean)
        .join(", ") ||
    "—";

const reviewGroups = computed(() => {
    const groups: { title: string; rows: { label: string; value: string }[] }[] =
        [];
    const agency = agencyForm.value;
    const branch = form.value;

    if (agency) {
        groups.push({
            title: "Agency",
            rows: [
                { label: "Name", value: agency.name || "—" },
                { label: "Email", value: agency.email || "—" },
                { label: "Address", value: addressOf(agency.location) },
                ...(agency.id_front instanceof File ||
                agency.id_back instanceof File ||
                agency.document instanceof File
                    ? [{ label: "Documents", value: "New files attached" }]
                    : []),
            ],
        });
    }

    groups.push({
        title: "Branch",
        rows: [
            { label: "Name", value: branch.name || "—" },
            { label: "Email", value: branch.email || "—" },
            { label: "Contact number", value: branch.contact_number || "—" },
            { label: "TIN", value: branch.tin || "—" },
            { label: "Address", value: addressOf(branch.location) },
            ...(branch.document instanceof File
                ? [{ label: "Document", value: "New file attached" }]
                : []),
        ],
    });

    return groups;
});

const slotPrompt = ref<string | null>(null);

const switchToPurchase = () => {
    purchaseNotice.value = "There's no free branch slot left on your subscription.";
    purchaseMode.value = true;
    slotPrompt.value = null;
    step.value = "payment";
    loadPlans();
};

const purchasePayload = (paymentMethod: "CREDIT-CARD" | "GCASH") => ({
    ...buildPayload(),
    ...(usesAdditional.value
        ? { subscription_uuid: additionalOption.value?.uuid }
        : {
              plan_code: selectedPlan.value?.plan_code,
              plan_type: selectedPlan.value?.type,
          }),
    payment_method: paymentMethod,
});

const canPay = () =>
    usesAdditional.value ? !!additionalOption.value : !!selectedPlan.value;

const onPaid = async (result: any) => {
    success(
        result?.message ?? "Payment received. Branch resubmitted for review.",
    );
    emit("resubmitted", {
        ...result,
        purchased: !usesAdditional.value,
        additional: usesAdditional.value,
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

onMounted(() => {
    if (purchaseMode.value) loadPlans();
});
</script>
