<template>
    <div class="max-w-6xl mx-auto p-6">
        <div
            v-if="loading"
            class="min-h-[500px] flex items-center justify-center"
        >
            <div
                class="rounded-2xl bg-white px-10 py-12 flex flex-col items-center gap-5"
            >
                <div class="relative">
                    <div
                        class="h-12 w-12 rounded-full border-4 border-gray-200"
                    ></div>
                    <div
                        class="absolute inset-0 h-12 w-12 rounded-full border-4 border-primary border-t-transparent animate-spin"
                    ></div>
                </div>

                <div class="text-center">
                    <p class="text-sm font-semibold text-gray-700">
                        Loading subscription setup
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Preparing plans and configuration options...
                    </p>
                </div>
            </div>
        </div>

        <template v-else>
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div
                        v-for="(step, index) in STEPS"
                        :key="step"
                        class="flex items-center flex-1"
                    >
                        <div class="flex items-center">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold"
                                :class="
                                    currentStep > index + 1
                                        ? 'bg-green-500 text-white'
                                        : currentStep === index + 1
                                          ? 'bg-primary text-white'
                                          : 'bg-gray-200 text-gray-500'
                                "
                            >
                                {{ index + 1 }}
                            </div>

                            <span
                                class="ml-3 text-sm font-medium hidden md:block"
                            >
                                {{ step }}
                            </span>
                        </div>

                        <div
                            v-if="index !== STEPS.length - 1"
                            class="flex-1 h-[2px] mx-4"
                            :class="
                                currentStep > index + 1
                                    ? 'bg-green-500'
                                    : 'bg-gray-200'
                            "
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl p-6 space-y-8">
                <div v-if="currentStep === 1">
                    <h2 class="text-xl font-bold mb-4">
                        Subscription Details
                        <p class="text-sm text-slate-500 font-normal mt-1">
                            Manage your AMUMA subscription plan and billing
                            preferences.
                        </p>
                    </h2>

                    <p
                        v-if="stepError"
                        class="text-sm text-red-500 bg-red-50 border border-red-200 px-4 py-2 rounded-lg mb-4"
                    >
                        {{ stepError }}
                    </p>

                    <div class="space-y-3 mb-6">
                        <label
                            v-for="plan in checkout.plans"
                            :key="plan.plan_id"
                            class="flex items-center justify-between border p-4 rounded-xl cursor-pointer"
                            :class="
                                checkout.selectedPlan?.plan_id === plan.plan_id
                                    ? 'border-primary bg-blue-50'
                                    : 'border-gray-200'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    class="accent-primary"
                                    :checked="
                                        checkout.selectedPlan?.plan_id ===
                                        plan.plan_id
                                    "
                                    @change="checkout.selectedPlan = plan"
                                />
                                <div>
                                    <p class="font-semibold">
                                        {{ plan.name }}
                                    </p>
                                    <p
                                        class="text-sm text-gray-500 max-w-[80%]"
                                    >
                                        {{ plan.description }}
                                    </p>
                                </div>
                            </div>

                            <div class="font-bold text-primary">
                                ₱{{
                                    checkout.selectedInterval === "yearly"
                                        ? plan.yearly_price
                                        : plan.monthly_price
                                }}
                            </div>
                        </label>
                    </div>

                    <div>
                        <h3 class="font-semibold mb-3">
                            Billing Cycle
                            <p class="text-sm text-slate-500 font-normal mt-1">
                                Choose how your subscription is billed (monthly
                                or yearly).
                            </p>
                        </h3>

                        <div class="grid grid-cols-2 gap-3">
                            <label
                                v-for="opt in intervalOptions"
                                :key="opt.value"
                                class="border rounded-xl p-4 cursor-pointer"
                                :class="
                                    checkout.selectedInterval === opt.value
                                        ? 'border-primary bg-blue-50'
                                        : 'border-gray-200'
                                "
                            >
                                <input
                                    type="radio"
                                    v-model="checkout.selectedInterval"
                                    :value="opt.value"
                                    class="accent-primary"
                                />

                                <div class="mt-2">
                                    <p class="font-semibold text-sm">
                                        {{ opt.label }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ opt.description }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div v-if="currentStep === 2">
                    <!-- <h2 class="text-xl font-bold mb-4">
                        Agency Information
                        <p class="text-sm text-gray-500 font-normal">
                            Configure your agency profile, branding, and agency
                            details.
                        </p>
                    </h2> -->
                    <AgencyForm
                        v-model:agency="checkout.agency"
                        v-model:errors="checkout.errors"
                        mode="new"
                    />
                </div>

                <div v-if="currentStep === 3">
                    <!-- <h2 class="text-xl font-bold mb-4">Branch Information</h2> -->
                    <BranchForm
                        v-model:branch="checkout.branch"
                        v-model:errors="checkout.errors"
                    />
                </div>

                <div v-if="currentStep === 4">
                    <h2 class="text-xl font-bold mb-4">Branch Configuration</h2>
                    <SubcriptionConfigure
                        :setting="checkout.settings"
                        :errors="checkout.errors"
                    />
                </div>

                <div class="flex justify-between border-t pt-6">
                    <button
                        v-if="currentStep > 1"
                        @click="
                            currentStep--;
                            emit('update:stepCompleted', false);
                        "
                        class="px-5 py-2 border rounded-xl"
                    >
                        Previous
                    </button>

                    <div v-else class="w-[110px]"></div>

                    <button
                        v-if="currentStep < STEPS.length"
                        @click="nextStep"
                        :disabled="
                            currentStep === 1 &&
                            (!checkout.selectedPlan ||
                                !checkout.selectedInterval)
                        "
                        class="px-6 py-2 bg-primary text-white rounded-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Next
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { planService } from "@/api/plan/PlanService";
import { agencyService } from "~/api/agency/AgencyService";
import { agencySchema, agencySchema2 } from "~/types/agency";
import { branchService } from "~/api/branch/BranchService";

import BranchForm from "~/components/forms/BranchForm.vue";
import AgencyForm from "~/components/forms/AgencyForm.vue";
import SubcriptionConfigure from "~/components/forms/SubcriptionConfigure.vue";
const props = defineProps<{
    stepCompleted: boolean;
}>();
const emit = defineEmits(["update:stepCompleted"]);
import { branchSchema } from "~/types/branch";
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
const fieldKeyMap: Record<string, string> = {
    agency_name: "agency_name",
    agency_description: "agency_description",
};

const validateAgency = async (): Promise<boolean> => {
    // try {
    const result = agencySchema2.safeParse(checkout.agency);

    if (!result.success) {
        const validationErrors: Record<string, string> = {};

        result.error.issues.forEach((issue: any) => {
            const path = issue.path.join(".");
            validationErrors[fieldKeyMap[path] ?? path] = issue.message;
        });

        checkout.errors = validationErrors;
        return false;
    }
    // await agencyService.validate(checkout.agency);
    return true;
    // } catch (err: any) {
    //     const errors = err?.errors || err?.response?.data?.errors;
    //     console.error(err);
    //     if (errors) {
    //         checkout.errors = Object.fromEntries(
    //             Object.entries(errors).map(([key, value]: any) => [
    //                 key,
    //                 Array.isArray(value) ? value[0] : value,
    //             ]),
    //         );
    //     }
    //     return false;
    // }
};

const validateBranch = async (): Promise<boolean> => {
    const result = branchSchema.safeParse(checkout.branch);

    if (!result.success) {
        const keyMap: Record<string, string> = {
            name: "branch_name",
            description: "branch_description",
            contact_number: "branch_contact_number",
            image: "branch_image",
        };

        const errors: Record<string, string> = {};

        result.error.issues.forEach((issue) => {
            const path = issue.path.join(".");

            errors[keyMap[path] ?? path] = issue.message;
        });

        checkout.setErrors(errors);
        return false;
    }
    return true;
    // try {
    //     // await branchService.validate(checkout.branch);
    //     return true;
    // } catch (err: any) {
    //     const errors = err?.errors || err?.response?.data?.errors;

    //     const keyMap: Record<string, string> = {
    //         name: "branch_name",
    //         description: "branch_description",
    //         contact_number: "branch_contact_number",
    //         image: "branch_image",
    //     };

    //     if (errors) {
    //         checkout.setErrors(
    //             Object.fromEntries(
    //                 Object.entries(errors).map(([key, value]: any) => [
    //                     keyMap[key] ?? key,
    //                     Array.isArray(value) ? value[0] : value,
    //                 ]),
    //             ),
    //         );
    //     }

    //     return false;
    // }
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
