<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @click.self="requestClose"
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
                    aria-label="Add branch"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <Building2 class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <h2
                                    class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                >
                                    Add a new branch
                                </h2>

                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    Connecting to
                                    <span class="font-medium text-secondary dark:text-white">
                                        {{ agencyName }}
                                    </span>
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

                    <div class="shrink-0 border-b border-gray-100 px-6 py-4 dark:border-white/10">
                        <ol class="flex w-full items-start">
                            <li
                                v-for="(step, index) in STEPS"
                                :key="step"
                                :class="[
                                    'flex items-start',
                                    index < STEPS.length - 1
                                        ? 'flex-1'
                                        : 'shrink-0',
                                ]"
                            >
                                <div
                                    class="flex shrink-0 flex-col items-center"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-semibold transition-all"
                                        :class="
                                            currentStep > index + 1
                                                ? 'border-primary bg-primary text-white'
                                                : currentStep === index + 1
                                                  ? 'border-primary bg-white text-primary ring-4 ring-primary/10 dark:bg-secondary'
                                                  : 'border-slate-200 bg-white text-slate-400 dark:border-white/10 dark:bg-secondary dark:text-gray-500'
                                        "
                                    >
                                        <Check
                                            v-if="currentStep > index + 1"
                                            class="h-3.5 w-3.5 stroke-[2.5]"
                                        />
                                        <span v-else>{{ index + 1 }}</span>
                                    </div>

                                    <span
                                        class="mt-1.5 max-w-[6rem] text-center text-[11px] font-medium leading-tight"
                                        :class="
                                            currentStep >= index + 1
                                                ? 'text-slate-800 dark:text-white'
                                                : 'text-slate-400 dark:text-gray-500'
                                        "
                                    >
                                        {{ step }}
                                    </span>
                                </div>

                                <div
                                    v-if="index < STEPS.length - 1"
                                    class="mx-3 mt-[15px] h-px flex-1 transition-colors"
                                    :class="
                                        currentStep > index + 1
                                            ? 'bg-primary'
                                            : 'bg-slate-200 dark:bg-white/10'
                                    "
                                />
                            </li>
                        </ol>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <p
                            v-if="stepError"
                            class="mb-4 rounded-lg border border-danger/20 bg-danger/5 px-4 py-2 text-sm text-danger dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
                        >
                            {{ stepError }}
                        </p>

                        <!-- Step 1 — plan & billing -->
                        <div v-if="currentStep === 1">
                            <div v-if="loadingPlans" class="space-y-4">
                                <div
                                    class="mx-auto h-10 w-56 animate-pulse rounded-full bg-slate-100 dark:bg-white/10"
                                />
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div
                                        v-for="n in 3"
                                        :key="n"
                                        class="h-56 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                                    />
                                </div>
                            </div>

                            <template v-else>
                                <div
                                    class="mb-6 flex flex-col items-center justify-center"
                                >
                                    <div
                                        class="relative inline-flex items-center rounded-full border border-primary-200 bg-muted-light/40 py-1 dark:border-primary-500/30 dark:bg-white/5"
                                    >
                                        <span
                                            class="absolute bottom-1 left-1 top-1 w-[calc(50%-6px)] rounded-full bg-primary shadow-sm transition-all duration-300"
                                            :class="
                                                form.interval === 'yearly'
                                                    ? 'translate-x-[calc(100%+3px)]'
                                                    : 'translate-x-0'
                                            "
                                        />

                                        <button
                                            v-for="option in INTERVALS"
                                            :key="option"
                                            type="button"
                                            class="relative z-10 min-w-[110px] rounded-full px-5 py-2 text-sm font-semibold capitalize transition-colors duration-300"
                                            :class="
                                                form.interval === option
                                                    ? 'text-white'
                                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                            "
                                            @click="form.interval = option"
                                        >
                                            {{ option }}
                                        </button>
                                    </div>

                                    <p class="mt-2 text-xs text-muted dark:text-gray-400">
                                        {{
                                            form.interval === "yearly"
                                                ? "Billed annually — save more compared to monthly billing."
                                                : "Billed monthly. Switch to yearly to save more."
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-3"
                                >
                                    <label
                                        v-for="plan in plans"
                                        :key="plan.plan_id"
                                        class="relative flex h-full cursor-pointer flex-col gap-3 rounded-xl border p-5 transition-all"
                                        :class="
                                            form.plan?.plan_id === plan.plan_id
                                                ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                                : 'border-muted-light hover:border-primary-200 dark:border-white/10 dark:hover:border-primary-500/40'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            class="pointer-events-none absolute h-0 w-0 opacity-0"
                                            :checked="
                                                form.plan?.plan_id ===
                                                plan.plan_id
                                            "
                                            @change="form.plan = plan"
                                        />

                                        <span
                                            class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border-2 transition-colors"
                                            :class="
                                                form.plan?.plan_id ===
                                                plan.plan_id
                                                    ? 'border-primary'
                                                    : 'border-slate-300 dark:border-white/20'
                                            "
                                        >
                                            <span
                                                v-if="
                                                    form.plan?.plan_id ===
                                                    plan.plan_id
                                                "
                                                class="h-2.5 w-2.5 rounded-full bg-primary"
                                            />
                                        </span>

                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-primary-100 bg-primary-50 dark:border-primary-500/20 dark:bg-primary-500/10"
                                        >
                                            <Home
                                                class="h-4 w-4 text-primary"
                                            />
                                        </div>

                                        <div class="pr-6">
                                            <p
                                                class="text-sm font-semibold leading-tight text-secondary dark:text-white"
                                            >
                                                {{ plan.name }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs leading-relaxed text-muted dark:text-gray-400"
                                            >
                                                {{ plan.description }}
                                            </p>
                                        </div>

                                        <div
                                            class="mt-auto flex items-center justify-between border-t border-muted-light/70 pt-3 dark:border-white/10"
                                        >
                                            <span
                                                class="text-xs font-medium text-muted dark:text-gray-400"
                                            >
                                                {{
                                                    form.interval === "yearly"
                                                        ? "/ year"
                                                        : "/ month"
                                                }}
                                            </span>

                                            <span
                                                class="whitespace-nowrap text-base font-bold text-primary"
                                            >
                                                ₱{{
                                                    form.interval === "yearly"
                                                        ? plan.yearly_price
                                                        : plan.monthly_price
                                                }}
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            </template>
                        </div>

                        <!-- Step 2 — branch details (no agency form, it is inherited) -->
                        <div v-else-if="currentStep === 2">
                            <div
                                class="mb-5 flex items-start gap-2.5 rounded-lg border border-primary/10 bg-primary/5 px-4 py-3 text-[13px] text-primary"
                            >
                                <Link2 class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>
                                    This branch will be created under
                                    <strong>{{ agencyName }}</strong> — your
                                    agency details carry over automatically, so
                                    you only need the branch information.
                                </span>
                            </div>

                            <BranchForm
                                v-model:branch="form.branch"
                                v-model:errors="errors"
                            />
                        </div>

                        <!-- Step 3 — operational configuration -->
                        <div v-else-if="currentStep === 3">
                            <div class="mb-5">
                                <h3
                                    class="text-lg font-semibold text-slate-900 dark:text-white"
                                >
                                    Branch configuration
                                </h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                                    Set up the operational preferences for this
                                    branch.
                                </p>
                            </div>

                            <SubcriptionConfigure
                                :setting="form.settings"
                                :errors="errors"
                                @update:errors="errors = $event"
                            />
                        </div>

                        <!-- Step 4 — plan recap + payment -->
                        <div v-else-if="currentStep === 4">
                            <div
                                class="mx-auto mb-5 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgba(15,23,42,0.06)] dark:border-white/10 dark:bg-secondary"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                    >
                                        <Home class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-base font-bold text-secondary dark:text-white"
                                        >
                                            {{ form.plan?.name }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-xs capitalize text-muted dark:text-gray-400"
                                        >
                                            Billed {{ form.interval }}
                                        </p>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-lg font-bold text-primary"
                                        >
                                            <span
                                                v-if="loadingTotal"
                                                class="inline-block h-5 w-20 animate-pulse rounded bg-slate-200 dark:bg-white/10 align-middle"
                                            />
                                            <template v-else>
                                                ₱{{ formatMoney(total) }}
                                            </template>
                                        </p>

                                        <p class="text-[11px] text-muted dark:text-gray-400">
                                            {{
                                                form.interval === "yearly"
                                                    ? "/ year"
                                                    : "/ month"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="form.plan?.description"
                                    class="mt-3 border-t border-slate-100 pt-3 text-xs leading-relaxed text-muted dark:border-white/10 dark:text-gray-400"
                                >
                                    {{ form.plan.description }}
                                </p>
                            </div>

                            <PaymentForm
                                v-model:card="card"
                                :total-amount="total"
                                :processing="processing || loadingTotal"
                                :onCardPay="payCard"
                                :onGCashPay="payGCash"
                                :enableGCash="true"
                                title="Branch payment"
                                description="Choose your payment method to activate this branch."
                                submit-label="Confirm & add branch"
                            />
                        </div>
                    </div>

                    <div
                        v-if="currentStep < STEPS.length"
                        class="flex shrink-0 items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-white/20 dark:hover:bg-white/10"
                            @click="previousStep"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Previous
                        </button>

                        <div v-else />

                        <button
                            type="button"
                            :disabled="continueDisabled"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="nextStep"
                        >
                            <LoaderCircle
                                v-if="validating"
                                class="h-4 w-4 animate-spin"
                            />
                            {{ validating ? "Validating..." : "Continue" }}
                            <ChevronRight v-if="!validating" class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        v-else
                        class="flex shrink-0 items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            type="button"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-white/20 dark:hover:bg-white/10 disabled:opacity-40"
                            @click="previousStep"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Back to details
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import {
    Building2,
    Check,
    ChevronLeft,
    ChevronRight,
    Home,
    Link2,
    LoaderCircle,
    X,
} from "lucide-vue-next";

import BranchForm from "~/components/forms/BranchForm.vue";
import { formatAmount } from "~/utils/currency";
import SubcriptionConfigure from "~/components/forms/SubcriptionConfigure.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";

import { planService } from "~/api/plan/PlanService";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { useToast } from "~/composables/useToast";
import { branchSchema } from "~/schema/branch-schema";
import type { Branch, BranchSettings } from "~/types/branch";
import type { CardDetails } from "~/types/payment";

const props = defineProps<{
    agencyId: number | string;
    agencyName: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "created", result: any): void;
}>();

const { success, error } = useToast();

// PaymentForm reads the selected method straight off this store, so the modal
// shares it rather than duplicating the method picker.
const checkout = useSubscriptionCheckout();

const STEPS = ["Plan", "Branch Details", "Configuration", "Payment"];
const INTERVALS = ["monthly", "yearly"] as const;

const currentStep = ref(1);
const stepError = ref<string | null>(null);
const errors = ref<Record<string, string>>({});

const plans = ref<any[]>([]);
const loadingPlans = ref(true);

const validating = ref(false);
const processing = ref(false);
const loadingTotal = ref(false);
const total = ref(0);

const emptyBranch = (): Branch =>
    ({
        name: "",
        contact_number: "",
        description: "",
        image: null,
        email: "",
        document: "",
        status: "active",
        location: {
            street: "",
            city: "",
            province: "",
            country: "",
            latitude: 0,
            longitude: 0,
        },
    }) as unknown as Branch;

const form = reactive({
    plan: null as any,
    interval: "monthly" as (typeof INTERVALS)[number],
    branch: emptyBranch(),
    settings: {
        opening: "00:00",
        closing: "00:00",
        currency: "PHP",
        time_zone: "Asia/Manila",
        reserved_walkin_slots: 3,
        enable_booking_pre_admission: true,
        enable_booking_complete_admission: true,
        minimum_adl_hours: 8,
        is_open: true,
    } as BranchSettings,
});

// A ref rather than reactive(): PaymentForm's `update:card` emits a fresh
// object, which v-model has to be able to assign back.
// Prefilled with the Xendit test card, matching the subscription checkout page.
const card = ref<CardDetails>({
    number: "4000000000002503",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const continueDisabled = computed(
    () =>
        validating.value ||
        (currentStep.value === 1 && (loadingPlans.value || !form.plan)),
);

const buildPayload = () => ({
    plan_code: form.plan?.plan_code,
    billing_interval: form.interval,
    payment_method: checkout.payment_method,

    // BRANCH DATA
    branch_name: form.branch.name,
    branch_contact_number: form.branch.contact_number,
    branch_description: form.branch.description,
    branch_email: form.branch.email,
    branch_image: form.branch.image,
    branch_document: (form.branch as any).document,
    branch_settings: form.settings,
    branch_street: form.branch.location.street,
    branch_city: form.branch.location.city,
    branch_province: form.branch.location.province,
    branch_country: form.branch.location.country,
    branch_latitude: form.branch.location.latitude,
    branch_longitude: form.branch.location.longitude,

    // AGENCY — only the id: the backend resolves and reuses the existing
    // agency, and every agency_* rule is nullable unless agency_name is sent.
    agency_id: props.agencyId,
});

const validateBranch = (): boolean => {
    const result = branchSchema.safeParse(form.branch);

    if (result.success) return true;

    const keyMap: Record<string, string> = {
        name: "branch_name",
        description: "branch_description",
        contact_number: "branch_contact_number",
        image: "branch_image",
        email: "branch_email",
        document: "branch_document",
    };

    const mapped: Record<string, string> = {};

    result.error.issues.forEach((issue) => {
        const path = issue.path.join(".");
        mapped[keyMap[path] ?? path] = issue.message;
    });

    errors.value = mapped;

    return false;
};

const stepForField = (field: string): number => {
    if (field.startsWith("branch_settings")) return 3;
    if (field.startsWith("branch_")) return 2;
    if (field.startsWith("plan") || field.startsWith("billing")) return 1;

    return 2;
};

const nextStep = async () => {
    stepError.value = null;

    if (currentStep.value === 1) {
        if (!form.plan) {
            stepError.value = "Please select a plan.";
            return;
        }
    }

    if (currentStep.value === 2) {
        errors.value = {};
        if (!validateBranch()) return;
    }

    if (currentStep.value === 3) {
        const passed = await validateOnServer();
        if (!passed) return;

        await loadTotal();
    }

    currentStep.value++;
};

const previousStep = () => {
    stepError.value = null;
    if (currentStep.value > 1) currentStep.value--;
};

// The same validate endpoint the public subscription flow uses, so branch
// uniqueness (email/name) is caught before the card is ever charged.
const validateOnServer = async (): Promise<boolean> => {
    validating.value = true;
    errors.value = {};

    try {
        await subscriptionService.validateSubscription(buildPayload());
        return true;
    } catch (err: any) {
        const raw = err?.errors ?? {};

        const mapped = Object.fromEntries(
            Object.entries(raw).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        errors.value = mapped;

        const first = Object.keys(mapped)[0];

        if (first) {
            currentStep.value = stepForField(first);
            stepError.value = mapped[first];
        } else {
            stepError.value = err?.message ?? "Validation failed.";
        }

        return false;
    } finally {
        validating.value = false;
    }
};

const loadTotal = async () => {
    loadingTotal.value = true;

    try {
        const payload: Record<string, any> = { ...buildPayload() };

        // GET can't carry the uploads, and the total only depends on the plan.
        delete payload.branch_image;
        delete payload.branch_document;
        delete payload.branch_settings;

        const res = await subscriptionService.retrieveSubscriptionDetail(
            payload as any,
        );

        total.value = Number(res.total_amount) || 0;
    } catch (err: any) {
        error(err?.message ?? "Failed to load the branch total.");
    } finally {
        loadingTotal.value = false;
    }
};

const onCreated = async (result: any) => {
    success(result?.message ?? "Branch added successfully.");
    emit("created", result);
};

const payCard = async () => {
    if (processing.value || loadingTotal.value) return;

    processing.value = true;

    try {
        const payload = buildPayload();

        await cardPayment({
            card: card.value,
            amount: total.value,

            onClose: () => {
                processing.value = false;
            },

            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.createSubscription({
                    ...payload,
                    token_id,
                    authentication_id,
                    payment_method: "CREDIT-CARD",
                    payment_type: "SUBSCRIPTION",
                }),

            onSuccess: onCreated,
        });
    } catch (err: any) {
        error(err?.message ?? "Payment failed.");
    } finally {
        processing.value = false;
    }
};

const payGCash = async () => {
    if (processing.value || loadingTotal.value) return;

    processing.value = true;

    try {
        const payload = buildPayload();

        await gcashPayment({
            createPayment: () =>
                subscriptionService.createSubscription({
                    ...payload,
                    payment_method: "GCASH",
                    payment_type: "SUBSCRIPTION",
                }),

            onClose: () => {
                processing.value = false;
            },

            onSuccess: onCreated,
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

const formatMoney = (value: number) => formatAmount(value);

// Switching the billing cycle changes what is owed, so refresh the total when
// the user goes back and flips it after it has already been fetched.
watch(
    () => [form.plan?.plan_id, form.interval],
    () => {
        if (currentStep.value === 4) loadTotal();
    },
);

onMounted(async () => {
    try {
        const res = await planService.list();
        plans.value = res ?? [];
        form.plan = plans.value[0] ?? null;
    } catch (err: any) {
        stepError.value = err?.message ?? "Failed to load plans.";
    } finally {
        loadingPlans.value = false;
    }
});
</script>
