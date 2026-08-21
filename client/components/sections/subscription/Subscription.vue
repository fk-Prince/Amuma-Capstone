<template>
    <div
        class="w-[95%] sm:w-[92%] lg:w-[90%] xl:w-[88%] 2xl:w-[70%] mx-auto py-6"
    >
        <div v-if="loading" class="min-h-[500px]">
            <div class="animate-pulse space-y-8">
                <ol class="flex items-start w-full">
                    <li
                        v-for="index in 4"
                        :key="index"
                        class="flex items-start flex-1"
                    >
                        <div class="flex flex-col items-center shrink-0">
                            <div
                                class="h-9 w-9 rounded-full"
                                :class="
                                    index === 1
                                        ? 'bg-primary-200'
                                        : 'bg-muted-light'
                                "
                            ></div>
                            <div
                                class="h-3 w-16 rounded bg-muted-light mt-2"
                            ></div>
                        </div>

                        <div
                            v-if="index < 4"
                            class="flex-1 h-px bg-muted-light mx-3 mt-[18px]"
                        ></div>
                    </li>
                </ol>

                <div class="rounded-2xl p-6 space-y-8">
                    <div class="space-y-3">
                        <div class="h-6 w-48 rounded-lg bg-primary-100"></div>
                        <div
                            class="h-4 w-80 max-w-full rounded bg-muted-light/60"
                        ></div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="index in 3"
                            :key="index"
                            class="flex items-center justify-between rounded-xl border p-4"
                            :class="
                                index === 1
                                    ? 'border-primary-100 bg-primary-50'
                                    : 'border-muted-light'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-4 w-4 rounded-full"
                                    :class="
                                        index === 1
                                            ? 'bg-primary-100'
                                            : 'bg-muted-light'
                                    "
                                ></div>

                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-28 rounded bg-muted-light"
                                    ></div>
                                    <div
                                        class="h-3 w-56 max-w-[40vw] rounded bg-muted-light/60"
                                    ></div>
                                </div>
                            </div>

                            <div
                                class="h-5 w-20 rounded"
                                :class="
                                    index === 1
                                        ? 'bg-primary-200'
                                        : 'bg-muted-light'
                                "
                            ></div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="h-4 w-28 rounded bg-muted-light"></div>
                        <div
                            class="h-3 w-72 max-w-full rounded bg-muted-light/60"
                        ></div>

                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-for="index in 2"
                                :key="index"
                                class="h-24 rounded-xl border"
                                :class="
                                    index === 1
                                        ? 'border-primary-200 bg-primary-50'
                                        : 'border-muted-light bg-muted-light/40'
                                "
                            ></div>
                        </div>
                    </div>

                    <div
                        class="flex justify-between border-t border-muted-light pt-6"
                    >
                        <div
                            class="h-10 w-24 rounded-xl bg-muted-light/60"
                        ></div>
                        <div class="h-10 w-28 rounded-xl bg-accent-200"></div>
                    </div>
                </div>
            </div>
        </div>

        <template v-else>
            <div class="flex w-full justify-center">
                <ol class="flex w-full max-w-6xl items-start justify-center">
                    <li
                        v-for="(step, index) in STEPS"
                        :key="step"
                        :class="[
                            'flex items-start',
                            index < STEPS.length - 1 ? 'flex-1' : 'shrink-0',
                        ]"
                    >
                        <div class="flex shrink-0 flex-col items-center">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-semibold transition-all duration-200"
                                :class="
                                    currentStep > index + 1
                                        ? 'border-primary bg-primary text-white'
                                        : currentStep === index + 1
                                          ? 'border-primary bg-white text-primary shadow-sm ring-4 ring-primary/10'
                                          : 'border-slate-200 bg-white text-slate-400'
                                "
                            >
                                <Check
                                    v-if="currentStep > index + 1"
                                    class="h-4 w-4 stroke-[2.5]"
                                />

                                <span v-else>
                                    {{ index + 1 }}
                                </span>
                            </div>

                            <span
                                class="mt-2 max-w-[7rem] text-center text-xs font-medium leading-tight transition-colors"
                                :class="
                                    currentStep >= index + 1
                                        ? 'text-slate-800'
                                        : 'text-slate-400'
                                "
                            >
                                {{ step }}
                            </span>
                        </div>

                        <!-- Connector -->
                        <div
                            v-if="index < STEPS.length - 1"
                            class="mx-4 mt-[18px] h-px flex-1 transition-colors duration-200"
                            :class="
                                currentStep > index + 1
                                    ? 'bg-primary'
                                    : 'bg-slate-200'
                            "
                        ></div>
                    </li>
                </ol>
            </div>
            <div class="rounded-2xl p-6 space-y-3">
                <div v-if="currentStep === 1">
                    <!-- <div class="mb-6">
                        <h2 class="text-xl font-bold text-secondary">
                            Subscription details
                        </h2>
                        <p class="text-sm text-muted mt-1">
                            Manage your AMUMA subscription plan and billing
                            preferences.
                        </p>
                    </div> -->

                    <p
                        v-if="stepError"
                        class="text-sm text-danger bg-danger-50 border border-danger-100 px-4 py-2 rounded-lg mb-4"
                    >
                        {{ stepError }}
                    </p>

                    <div class="mb-8">
                        <div class="flex flex-col items-center justify-center">
                            <div
                                class="relative inline-flex border border-primary-200 items-center rounded-full bg-muted-light/40 py-1"
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
                                            : 'text-muted hover:text-secondary'
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
                                            : 'text-muted hover:text-secondary'
                                    "
                                    @click="
                                        checkout.selectedInterval = 'yearly'
                                    "
                                >
                                    Yearly
                                </button>
                            </div>

                            <p class="mt-2 text-xs text-muted">
                                {{
                                    checkout.selectedInterval === "yearly"
                                        ? "Billed annually — save more compared to monthly billing."
                                        : "Billed monthly. Switch to yearly to save more."
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 items-stretch"
                    >
                        <label
                            v-for="plan in checkout.plans"
                            :key="plan.plan_id"
                            class="relative flex flex-col h-full gap-3 border border-primary/20 p-8 rounded-xl cursor-pointer transition-all"
                            :class="
                                checkout.selectedPlan?.plan_id === plan.plan_id
                                    ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20'
                                    : 'border-muted-light hover:border-primary-200'
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
                                        : 'border-slate-300'
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
                                class="h-10 w-10 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center shrink-0"
                            >
                                <component
                                    :is="plan.icon ?? Home"
                                    class="h-5 w-5 text-primary"
                                />
                            </div>

                            <!-- Plan information -->
                            <div class="pr-6">
                                <p
                                    class="font-semibold text-base text-secondary leading-tight"
                                >
                                    {{ plan.name }}
                                </p>

                                <p
                                    class="text-sm text-muted mt-1.5 leading-relaxed"
                                >
                                    {{ plan.description }}
                                </p>
                            </div>

                            <!-- Pricing -->
                            <div
                                class="flex items-center justify-between pt-4 mt-auto border-t border-muted-light/70"
                            >
                                <span class="text-sm font-medium text-muted">
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
                                        ₱{{
                                            checkout.selectedInterval ===
                                            "yearly"
                                                ? plan.yearly_price
                                                : plan.monthly_price
                                        }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <!-- 
                    <div>
                        <h3 class="font-semibold text-secondary mb-1">
                            Billing cycle
                        </h3>
                        <p class="text-sm text-muted mb-3">
                            Choose how your subscription is billed, monthly or
                            yearly.
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            <label
                                v-for="opt in intervalOptions"
                                :key="opt.value"
                                class="border rounded-xl p-4 cursor-pointer transition-colors"
                                :class="
                                    checkout.selectedInterval === opt.value
                                        ? 'border-primary bg-primary-50'
                                        : 'border-muted-light hover:border-primary-200'
                                "
                            >
                                <input
                                    type="radio"
                                    v-model="checkout.selectedInterval"
                                    :value="opt.value"
                                    class="accent-primary"
                                />

                                <div class="mt-2">
                                    <p
                                        class="font-semibold text-sm text-secondary"
                                    >
                                        {{ opt.label }}
                                    </p>
                                    <p class="text-xs text-muted">
                                        {{ opt.description }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div> -->
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
                    />
                </div>

                <div v-if="currentStep === 4">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-secondary">
                            Branch configuration
                        </h2>
                        <p class="text-sm text-muted mt-1">
                            Set up the operational preferences for this branch.
                        </p>
                    </div>

                    <SubcriptionConfigure
                        :setting="checkout.settings"
                        :errors="checkout.errors"
                    />
                </div>

                <div
                    class="flex items-center justify-between border-t border-slate-200 pt-6"
                >
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        @click="
                            currentStep--;
                            emit('update:stepCompleted', false);
                        "
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 active:scale-[0.98]"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Previous
                    </button>

                    <div v-else class="w-[110px]"></div>

                    <button
                        v-if="currentStep < STEPS.length"
                        type="button"
                        @click="nextStep"
                        :disabled="
                            currentStep === 1 &&
                            (!checkout.selectedPlan ||
                                !checkout.selectedInterval)
                        "
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition-all hover:bg-primary-600 hover:shadow-md hover:shadow-primary/20 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                    >
                        Continue
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
                <button
                    v-if="stepCompleted && currentStep === 4"
                    @click="send"
                    :disabled="isLoading"
                    class="w-full rounded-xl bg-primary hover:bg-primary/90 disabled:opacity-50 text-white py-3 font-semibold transition flex items-center justify-center gap-2"
                >
                    <svg
                        v-if="isLoading"
                        class="w-5 h-5 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        />
                    </svg>

                    {{ isLoading ? "Validating..." : "Confirm & Pay" }}
                </button>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Check, ChevronLeft, ChevronRight, Home } from "lucide-vue-next";
import { ref, onMounted } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { planService } from "@/api/plan/PlanService";
import { agencySchema } from "~/schema/agency-schema";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { type SubscriptionRequest } from "~/types/subscription";
import BranchForm from "~/components/forms/BranchForm.vue";
import AgencyForm from "~/components/forms/AgencyForm.vue";
import SubcriptionConfigure from "~/components/forms/SubcriptionConfigure.vue";
const props = defineProps<{
    stepCompleted: boolean;
}>();
const emit = defineEmits(["update:stepCompleted"]);
import { branchSchema } from "~/schema/branch-schema";
const checkout = useSubscriptionCheckout();

const loading = ref(true);
const currentStep = ref(1);
const stepError = ref<string | null>(null);

const STEPS = [
    "Subscription",
    "Agency Information",
    "Branch Information",
    "Configuration",
];

const intervalOptions = [
    { value: "monthly", label: "Monthly", description: "Billed monthly" },
    { value: "yearly", label: "Yearly", description: "Save more yearly" },
];

const nextStep = async () => {
    stepError.value = null;
    checkout.clearAllErrors();

    if (currentStep.value === 1) {
        if (!checkout.selectedPlan || !checkout.selectedInterval) {
            stepError.value = "Please select a plan and billing cycle.";
            return;
        }
    }
    if (currentStep.value === 2) {
        const isValid = await validateAgency();
        if (!isValid) return;
    }

    if (currentStep.value === 3) {
        const isValid = await validateBranch();
        if (!isValid) return;
        emit("update:stepCompleted", isValid && currentStep.value === 3);
    }

    if (currentStep.value < STEPS.length) {
        currentStep.value++;
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
        checkout.errors = errors;
        return false;
    }
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
        };

        result.error.issues.forEach((issue) => {
            const path = issue.path.join(".");

            errors[keyMap[path] ?? path] = issue.message;
        });
        checkout.setErrors(errors);
        return false;
    }
    return true;
};

const isLoading = ref(false);
const send = async () => {
    console.log(checkout.settings);
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
            branch_settings: checkout.settings,
            branch_street: checkout.branch.location.street,
            branch_city: checkout.branch.location.city,
            branch_province: checkout.branch.location.province,
            branch_country: checkout.branch.location.country,
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
            agency_latitude: checkout.agency.location.latitude ?? undefined,
            agency_longitude: checkout.agency.location.longitude ?? undefined,
            agency_email: checkout.agency.email ?? "",
            agency_image: checkout.agency.image,
            agency_id_front: checkout.agency.id_front ?? "",
            agency_id_back: checkout.agency.id_back ?? "",
            agency_document: checkout.agency.document ?? "",
        };
        console.log(payload);
        await subscriptionService.validateSubscription(payload);
        checkout.subscriptionPayload = payload;
        await navigateTo({
            path: "/product/subscription-details/checkout",
            query: {
                code: checkout.selectedPlan?.plan_id,
                interval: checkout.selectedInterval,
            },
        });
    } catch (err: any) {
        const errors = err?.errors || err?.response?.data?.errors;
        if (errors) {
            const formattedErrors = Object.fromEntries(
                Object.entries(errors).map(([key, value]: any) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            );

            checkout.errors = formattedErrors;

            const firstError = Object.keys(formattedErrors)[0];

            if (!firstError) return;

            if (firstError === "agency_email") {
                currentStep.value = 2;
            } else if (firstError === "branch_email") {
                currentStep.value = 3;
            } else if (
                firstError.startsWith("agency_") ||
                firstError.startsWith("agency.")
            ) {
                currentStep.value = 2;
            } else if (
                firstError.startsWith("branch_") ||
                firstError.startsWith("branch.")
            ) {
                currentStep.value = 3;
            } else if (firstError.startsWith("branch_settings")) {
                currentStep.value = 4;
            }
        }
    } finally {
        isLoading.value = false;
    }
};

onMounted(async () => {
    try {
        const plans = await planService.list();
        checkout.setPlans(plans);
    } finally {
        loading.value = false;
    }
});
</script>
