<template>
    <!-- w-[95%] sm:w-[92%] lg:w-[90%] xl:w-[88%] 2xl:w-[85%]  -->
    <div class="w-full mx-auto py-6">
        <SubscriptionSkeleton v-if="loading" />

        <div v-else class="flex items-start gap-3 sm:gap-8">
            <aside class="sticky top-32 shrink-0 self-start sm:w-60">
                <ol class="flex flex-col">
                    <li
                        v-for="(step, index) in STEPS"
                        :key="step"
                        class="flex gap-3"
                    >
                        <div class="flex flex-col items-center self-stretch">
                            <SubscriptionStepDot
                                :number="index + 1"
                                :current-step="currentStep"
                                :has-error="stepsWithErrors.has(index + 1)"
                            />

                            <div
                                v-if="index < STEPS.length - 1"
                                class="my-1 h-8 w-px flex-1 transition-colors duration-200 sm:min-h-10"
                                :class="
                                    currentStep > index + 1
                                        ? 'bg-primary'
                                        : 'bg-slate-200 dark:bg-white/10'
                                "
                            ></div>
                        </div>

                        <div class="hidden pt-1 sm:block">
                            <p
                                class="text-sm font-semibold leading-tight transition-colors"
                                :class="
                                    stepsWithErrors.has(index + 1)
                                        ? 'text-danger'
                                        : currentStep >= index + 1
                                          ? 'text-slate-800 dark:text-white'
                                          : 'text-slate-400 dark:text-gray-500'
                                "
                            >
                                {{ step }}
                            </p>
                            <p
                                class="mt-0.5 text-xs leading-snug text-slate-400 dark:text-gray-500"
                            >
                                {{ STEP_SUBTITLES[index] }}
                            </p>
                        </div>
                    </li>
                </ol>
            </aside>

            <div class="min-w-0 flex-1 space-y-3 rounded-2xl">
                <p class="pb-1 text-xs font-semibold text-primary sm:hidden">
                    Step {{ currentStep }} of {{ STEPS.length }} ·
                    {{ STEPS[currentStep - 1] }}
                </p>

                <div v-if="currentStep === 1">
                    <p
                        v-if="stepError"
                        class="text-sm text-danger bg-danger-50 border border-danger-100 px-4 py-2 rounded-lg mb-4"
                    >
                        {{ stepError }}
                    </p>

                    <div class="mb-8">
                        <div class="flex flex-col items-center justify-center">
                            <div
                                class="relative inline-flex border border-primary-200 dark:border-white/10 items-center rounded-full bg-muted-light/40 dark:bg-white/5 py-1"
                            >
                                <span
                                    class="absolute top-1 bottom-1 left-1 w-[calc(50%-6px)] rounded-full bg-primary shadow-sm transition-all duration-300 ease-in-out"
                                    :class="
                                        checkout.selectedInterval === 'yearly'
                                            ? 'translate-x-[calc(100%+3px)]'
                                            : 'translate-x-0'
                                    "
                                />

                                <button
                                    type="button"
                                    class="relative z-10 min-w-[110px] rounded-full px-5 py-2 text-sm font-semibold transition-colors duration-300"
                                    :class="
                                        checkout.selectedInterval === 'monthly'
                                            ? 'text-white'
                                            : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                    "
                                    @click="
                                        checkout.selectedInterval = 'monthly'
                                    "
                                >
                                    Monthly
                                </button>

                                <button
                                    type="button"
                                    class="relative z-10 flex min-w-[130px] items-center justify-center gap-2 rounded-full px-5 py-2 text-sm font-semibold transition-colors duration-300"
                                    :class="
                                        checkout.selectedInterval === 'yearly'
                                            ? 'text-white'
                                            : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                    "
                                    @click="
                                        checkout.selectedInterval = 'yearly'
                                    "
                                >
                                    Yearly
                                </button>
                            </div>

                            <p
                                class="mt-2 text-xs text-muted dark:text-gray-400"
                            >
                                {{
                                    checkout.selectedInterval === "yearly"
                                        ? "Billed annually — save more compared to monthly billing."
                                        : "Billed monthly. Switch to yearly to save more."
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8 items-stretch"
                    >
                        <label
                            v-for="plan in checkout.plans"
                            :key="plan.plan_id"
                            class="relative flex flex-col h-full gap-3 border border-primary/20 p-5 sm:p-6 rounded-xl cursor-pointer transition-all"
                            :class="
                                checkout.selectedPlan?.plan_id === plan.plan_id
                                    ? 'border-primary bg-primary-50/60 dark:bg-primary-500/10 ring-1 ring-primary/20'
                                    : 'border-muted-light dark:border-white/10 hover:border-primary-200'
                            "
                        >
                            <input
                                type="radio"
                                class="absolute opacity-0 w-0 h-0 pointer-events-none"
                                :checked="
                                    checkout.selectedPlan?.plan_id ===
                                    plan.plan_id
                                "
                                @change="checkout.selectedPlan = plan"
                            />

                            <!-- Radio -->
                            <span
                                class="absolute top-4 right-4 h-5 w-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="
                                    checkout.selectedPlan?.plan_id ===
                                    plan.plan_id
                                        ? 'border-primary'
                                        : 'border-slate-300 dark:border-white/20'
                                "
                            >
                                <span
                                    v-if="
                                        checkout.selectedPlan?.plan_id ===
                                        plan.plan_id
                                    "
                                    class="h-2.5 w-2.5 rounded-full bg-primary"
                                />
                            </span>

                            <!-- Icon -->
                            <div
                                class="h-10 w-10 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center shrink-0 dark:bg-primary-500/10"
                            >
                                <component
                                    :is="plan.icon ?? Home"
                                    class="h-5 w-5 text-primary"
                                />
                            </div>

                            <!-- Plan information -->
                            <div>
                                <p
                                    class="font-semibold text-base text-secondary leading-tight dark:text-white"
                                >
                                    {{ plan.name }}
                                </p>

                                <p
                                    class="text-sm text-muted mt-1.5 leading-relaxed dark:text-gray-400"
                                >
                                    {{ plan.description }}
                                </p>
                            </div>

                            <!-- Pricing -->
                            <div
                                class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1 pt-4 mt-auto border-t border-muted-light/70 dark:border-white/10"
                            >
                                <span
                                    class="text-sm font-medium text-muted whitespace-nowrap dark:text-gray-400"
                                >
                                    {{
                                        checkout.selectedInterval === "yearly"
                                            ? "/ year"
                                            : "/ month"
                                    }}
                                </span>

                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="
                                            checkout.selectedInterval ===
                                            'yearly'
                                        "
                                        class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700"
                                    >
                                        Save
                                        {{
                                            Math.round(
                                                ((Number(plan.monthly_price) *
                                                    12 -
                                                    Number(plan.yearly_price)) /
                                                    (Number(
                                                        plan.monthly_price,
                                                    ) *
                                                        12)) *
                                                    100,
                                            )
                                        }}%
                                    </span>

                                    <span
                                        class="font-bold text-lg text-primary whitespace-nowrap"
                                    >
                                        {{
                                            formatCurrency(
                                                checkout.selectedInterval ===
                                                    "yearly"
                                                    ? plan.yearly_price
                                                    : plan.monthly_price,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="mb-10 flex justify-end">
                        <button
                            type="button"
                            @click="nextStep"
                            :disabled="
                                !checkout.selectedPlan ||
                                !checkout.selectedInterval
                            "
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition-all hover:bg-primary-600 hover:shadow-md hover:shadow-primary/20 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                        >
                            Continue
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>

                    <ComparableTable />
                </div>

                <div v-if="currentStep === 2">
                    <AgencyForm
                        v-model:agency="checkout.agency"
                        v-model:errors="checkout.errors"
                        mode="new"
                    />
                </div>

                <div v-if="currentStep === 3">
                    <BranchForm
                        v-model:branch="checkout.branch"
                        v-model:errors="checkout.errors"
                        mode="new"
                    />
                </div>

                <div v-if="currentStep === 4">
                    <SubcriptionConfigure
                        :setting="checkout.settings"
                        v-model:errors="checkout.errors"
                    />
                </div>

                <div v-if="currentStep === 5">
                    <SubscriptionPayment
                        v-model:busy="paymentBusy"
                        @back="currentStep--"
                    />
                </div>

                <div
                    v-if="currentStep > 1"
                    class="flex items-center justify-between border-t border-slate-200 pt-6 dark:border-white/10"
                >
                    <button
                        type="button"
                        @click="currentStep--"
                        :disabled="isLoading || paymentBusy"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-secondary dark:border-white/10 dark:text-white dark:hover:bg-white/5"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Previous
                    </button>

                    <button
                        v-if="currentStep < STEPS.length"
                        type="button"
                        @click="nextStep"
                        :disabled="isLoading"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition-all hover:bg-primary-600 hover:shadow-md hover:shadow-primary/20 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                    >
                        <template v-if="isLoading">
                            <LoaderCircle class="h-4 w-4 animate-spin" />
                            Validating...
                        </template>
                        <template v-else>
                            {{
                                currentStep === 4 ? "Review & Pay" : "Continue"
                            }}
                            <ChevronRight class="h-4 w-4" />
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    Check,
    ChevronLeft,
    ChevronRight,
    Home,
    LoaderCircle,
} from "lucide-vue-next";
import { ref, onMounted, nextTick, watch } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { planService } from "@/api/plan/PlanService";
import { agencySchema } from "~/schema/agency-schema";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { useToast } from "~/composables/useToast";
import { type SubscriptionRequest } from "~/types/subscription";
import BranchForm from "~/components/forms/BranchForm.vue";
import { formatCurrency } from "~/utils/currency";
import AgencyForm from "~/components/forms/AgencyForm.vue";
import SubcriptionConfigure from "~/components/forms/SubcriptionConfigure.vue";
import ComparableTable from "~/components/ui/ComparableTable.vue";
import SubscriptionStepDot from "~/components/sections/subscription/SubscriptionStepDot.vue";
import SubscriptionSkeleton from "~/components/sections/subscription/SubscriptionSkeleton.vue";
import SubscriptionPayment from "~/components/sections/subscription/SubscriptionPayment.vue";
import { branchSchema } from "~/schema/branch-schema";
const checkout = useSubscriptionCheckout();
const { error } = useToast();

const loading = ref(true);
const currentStep = ref(1);
const paymentBusy = ref(false);
const route = useRoute();
const stepError = ref<string | null>(null);

const scrollToTop = async () => {
    await nextTick();
    window.scrollTo({ top: 0, behavior: "smooth" });
};

watch(currentStep, scrollToTop);

const STEPS = [
    "Subscription Plan",
    "Agency Information",
    "Branch Information",
    "Configuration",
    "Review & Payment",
];

const STEP_SUBTITLES = [
    "Choose your plan and billing cycle",
    "Your agency and verification documents",
    "Branch details, tax info and location",
    "Operating hours and booking preferences",
    "Review details and complete payment",
];

const scrollToFirstError = async () => {
    await nextTick();

    const firstKey = Object.keys(checkout.errors ?? {})[0];
    if (!firstKey) return;

    const el = document.querySelector(`[data-field~="${firstKey}"]`);
    el?.scrollIntoView({ behavior: "smooth", block: "center" });
};

const nextStep = async () => {
    stepError.value = null;

    if (currentStep.value === 1) {
        if (!checkout.selectedPlan || !checkout.selectedInterval) {
            stepError.value = "Please select a plan and billing cycle.";
            return;
        }
    }
    if (currentStep.value === 2) {
        const isValid =
            (await validateAgency()) &&
            (await checkUnique(
                {
                    agency_id: checkout.agency.agency_id,
                    agency_name: checkout.agency.name,
                    agency_email: checkout.agency.email,
                },
                isAgencyField,
            ));
        if (!isValid) {
            await scrollToFirstError();
            return;
        }
    }

    if (currentStep.value === 3) {
        const isValid =
            (await validateBranch()) &&
            (await checkUnique(
                {
                    agency_id: checkout.agency.agency_id,
                    branch_name: checkout.branch.name,
                    branch_email: checkout.branch.email,
                },
                isBranchField,
            ));
        if (!isValid) {
            await scrollToFirstError();
            return;
        }
    }

    if (currentStep.value === 4) {
        await submitDetails();
        return;
    }

    if (currentStep.value < STEPS.length) {
        currentStep.value++;
    }
};

const checkUnique = async (
    payload: Record<string, any>,
    scope: (key: string) => boolean,
): Promise<boolean> => {
    isLoading.value = true;
    try {
        await subscriptionService.checkUnique(payload);
        mergeStepErrors(scope, {});
        return true;
    } catch (err: any) {
        const errors = err?.errors || err?.response?.data?.errors;
        if (!errors) {
            error(
                err?.message ??
                    "Could not verify your details. Please try again.",
            );
            return false;
        }

        mergeStepErrors(
            scope,
            Object.fromEntries(
                Object.entries(errors).map(([key, value]: any) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            ),
        );
        return false;
    } finally {
        isLoading.value = false;
    }
};

const validateAgency = async (): Promise<boolean> => {
    const result = agencySchema.safeParse(checkout.agency);

    if (!result.success) {
        const errors: Record<string, string> = {};
        const keyMap: Record<string, string> = {
            name: "agency_name",
            description: "agency_description",
            email: "agency_email",
            image: "agency_image",
            id_front: "agency_id_front",
            id_back: "agency_id_back",
            document: "agency_document",
        };
        result.error.issues.forEach((issue: any) => {
            const path = issue.path.join(".");
            errors[keyMap[path] ?? path] = issue.message;
        });
        claimErrors(errors, 2);
        mergeStepErrors(isAgencyField, errors);
        return false;
    }
    mergeStepErrors(isAgencyField, {});
    return true;
};

const validateBranch = async (): Promise<boolean> => {
    const result = branchSchema.safeParse(checkout.branch);

    if (!result.success) {
        const errors: Record<string, string> = {};
        const keyMap: Record<string, string> = {
            name: "branch_name",
            description: "branch_description",
            contact_number: "branch_contact_number",
            image: "branch_image",
            email: "branch_email",
            document: "branch_document",
            tin: "branch_tin",
        };

        result.error.issues.forEach((issue) => {
            const path = issue.path.join(".");

            errors[keyMap[path] ?? path] = issue.message;
        });
        claimErrors(errors, 3);
        mergeStepErrors(isBranchField, errors);
        return false;
    }
    mergeStepErrors(isBranchField, {});
    return true;
};

const validateConfiguration = (): boolean => {
    const opening = checkout.settings?.opening;
    const closing = checkout.settings?.closing;

    if (opening && closing && closing < opening) {
        const errors = {
            closing: "Closing time must be later than opening time.",
        };
        claimErrors(errors, 4);
        mergeStepErrors(isConfigField, errors);
        return false;
    }

    mergeStepErrors(isConfigField, {});
    return true;
};

const SERVER_FIELD_ALIASES: Record<string, string> = {
    "branch_settings.tin": "branch_tin",
};

const errorOwners = ref<Record<string, number>>({});

function claimErrors(errors: Record<string, string>, step: number) {
    Object.keys(errors).forEach((key) => {
        errorOwners.value[key] = step;
    });
}

watch(
    () => checkout.errors,
    (errors) => {
        const keys = Object.keys(errors ?? {});

        Object.keys(errorOwners.value).forEach((key) => {
            if (!keys.includes(key)) delete errorOwners.value[key];
        });

        keys.forEach((key) => {
            errorOwners.value[key] ??= stepForField(key);
        });
    },
    { deep: true },
);

function stepForField(field: string) {
    if (errorOwners.value[field]) return errorOwners.value[field];

    if (field.startsWith("branch_settings")) return 4;

    if (field.startsWith("agency_") || field.startsWith("agency.")) return 2;

    if (field.startsWith("branch_") || field.startsWith("branch.")) return 3;

    return currentStep.value;
}

// Every step that currently has at least one error in checkout.errors, so
// the stepper can flag steps the user hasn't revisited yet instead of only
// showing whichever step they happen to be looking at.
const stepsWithErrors = computed(() => {
    const steps = new Set<number>();

    Object.entries(checkout.errors ?? {}).forEach(([key, message]) => {
        if (message) steps.add(stepForField(key));
    });

    return steps;
});

const isAgencyField = (key: string) =>
    key.startsWith("agency_") ||
    key.startsWith("agency.") ||
    errorOwners.value[key] === 2;

const isBranchField = (key: string) =>
    (key.startsWith("branch_") && !key.startsWith("branch_settings")) ||
    key.startsWith("branch.") ||
    errorOwners.value[key] === 3;

const isConfigField = (key: string) =>
    key.startsWith("branch_settings") || errorOwners.value[key] === 4;

// Re-validating one step must only touch that step's own errors — replacing
// the whole checkout.errors object here was wiping out server-reported
// errors on OTHER steps the user hadn't revisited yet, which made it look
// like only one step ever had a problem.
function mergeStepErrors(
    scope: (key: string) => boolean,
    newErrors: Record<string, string>,
) {
    const preserved = Object.fromEntries(
        Object.entries(checkout.errors ?? {}).filter(([key]) => !scope(key)),
    );

    checkout.errors = { ...preserved, ...newErrors };
}

const isLoading = ref(false);

const submitDetails = async () => {
    if (isLoading.value) return;

    if (!validateConfiguration()) {
        await scrollToFirstError();
        return;
    }

    isLoading.value = true;
    try {
        const payload: SubscriptionRequest = {
            plan_code: checkout.selectedPlan.plan_code,
            payment_method: checkout.payment_method,
            billing_interval: checkout.selectedInterval,

            //BRANCH DATA
            branch_name: checkout.branch.name,
            branch_contact_number: checkout.branch.contact_number,
            branch_image: checkout.branch.image,
            branch_description: checkout.branch.description,
            branch_settings: checkout.branchSettingsPayload,
            branch_street: checkout.branch.location.street,
            branch_city: checkout.branch.location.city,
            branch_province: checkout.branch.location.province,
            branch_country: checkout.branch.location.country,
            branch_full_address: checkout.branch.location.full_address ?? "",
            branch_latitude: checkout.branch.location.latitude,
            branch_longitude: checkout.branch.location.longitude,
            branch_email: checkout.branch.email ?? "",
            branch_document: checkout.agency.document ?? "",

            // AGENCY DATA
            agency_id: checkout.agency.agency_id,
            agency_name: checkout.agency.name,
            agency_description: checkout.agency.description,
            agency_street: checkout.agency.location.street ?? "",
            agency_city: checkout.agency.location.city ?? "",
            agency_province: checkout.agency.location.province ?? "",
            agency_country: checkout.agency.location.country ?? "",
            agency_full_address: checkout.agency.location.full_address ?? "",
            agency_latitude: checkout.agency.location.latitude ?? undefined,
            agency_longitude: checkout.agency.location.longitude ?? undefined,
            agency_email: checkout.agency.email ?? "",
            agency_image: checkout.agency.image,
            agency_id_front: checkout.agency.id_front ?? "",
            agency_id_back: checkout.agency.id_back ?? "",
            agency_document: checkout.agency.document ?? "",
        };
        await subscriptionService.validateSubscription(payload);
        checkout.subscriptionPayload = payload;

        currentStep.value = 5;
        await scrollToTop();
    } catch (err: any) {
        const errors = err?.errors || err?.response?.data?.errors;
        if (errors) {
            const owned: Record<number, Record<string, string>> = {
                2: {},
                3: {},
                4: {},
            };
            const formattedErrors = Object.fromEntries(
                Object.entries(errors).map(([key, value]: any) => {
                    const message = Array.isArray(value) ? value[0] : value;
                    const alias = SERVER_FIELD_ALIASES[key];

                    if (!alias && key.startsWith("branch_settings.")) {
                        const field = key.slice("branch_settings.".length);
                        owned[4]![field] = message;
                        return [field, message];
                    }

                    const location = key.match(
                        /^(agency|branch)_(street|city|province|country|latitude|longitude)$/,
                    );

                    if (location) {
                        const field = `location.${location[2]}`;
                        owned[location[1] === "agency" ? 2 : 3]![field] =
                            message;
                        return [field, message];
                    }

                    return [alias ?? key, message];
                }),
            );

            Object.entries(owned).forEach(([step, fields]) =>
                claimErrors(fields, Number(step)),
            );
            checkout.errors = formattedErrors;

            const firstError = Object.keys(formattedErrors)[0];

            if (!firstError) return;

            currentStep.value = stepForField(firstError);
            await scrollToTop();
        }
    } finally {
        isLoading.value = false;
    }
};

onMounted(async () => {
    try {
        const plans = await planService.list();
        checkout.setPlans(plans);

        if (route.query.step === "payment" && checkout.subscriptionPayload) {
            currentStep.value = 5;
        } else {
            checkout.selectedInterval = "monthly";
        }
    } finally {
        loading.value = false;
    }
});
</script>
