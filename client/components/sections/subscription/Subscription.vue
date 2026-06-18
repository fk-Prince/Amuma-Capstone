<template>
    <div class="max-w-4xl mx-auto p-6">
        <div
            v-if="loading"
            class="bg-white rounded-2xl shadow p-10 flex justify-center"
        >
            <div
                class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"
            />
        </div>

        <template v-else>
            <div
                class="bg-white border rounded-2xl shadow-sm divide-y divide-gray-100"
            >
                <section id="section-plan">
                    <button
                        class="w-full flex items-center justify-between p-5 text-left"
                        @click="isPlanOpen = !isPlanOpen"
                    >
                        <h2 class="font-semibold text-lg text-gray-900">
                            Subscription Plan
                            <p class="text-sm text-slate-500 font-normal">
                                Manage your current AMUMA plan.
                            </p>
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="text-red-500 text-sm font-medium"
                                >Required</span
                            >
                            <Dropdown :isOpen="isPlanOpen" />
                        </div>
                    </button>

                    <Transition name="accordion">
                        <div v-if="isPlanOpen" class="px-5 pb-5 space-y-3">
                            <label
                                v-for="plan in checkout.plans"
                                :key="plan.plan_id"
                                class="flex items-center justify-between rounded-xl border p-4 cursor-pointer transition-colors"
                                :class="
                                    checkout.selectedPlan?.plan_id ===
                                    plan.plan_id
                                        ? 'border-primary bg-blue-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        class="mt-0.5 accent-primary"
                                        :checked="
                                            checkout.selectedPlan?.plan_id ===
                                            plan.plan_id
                                        "
                                        @change="checkout.selectedPlan = plan"
                                    />
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            {{ plan.name }}
                                        </p>
                                        <p class="text-sm text-gray-500 mx-3">
                                            {{ plan.description }}
                                        </p>
                                    </div>
                                </div>
                                <div class="font-bold text-primary">
                                    ₱{{
                                        checkout.selectedInterval === "yearly"
                                            ? plan.yearly_price?.price
                                            : plan.monthly_price?.price
                                    }}
                                </div>
                            </label>
                        </div>
                    </Transition>
                </section>

                <section id="section-billing">
                    <button
                        class="w-full flex items-center justify-between p-5 text-left"
                        @click="isBillingOpen = !isBillingOpen"
                    >
                        <h2 class="font-semibold text-lg text-gray-900">
                            Billing Interval
                            <p class="text-sm text-gray-500 font-normal">
                                Choose how your subscription is billed (monthly
                                or yearly).
                            </p>
                        </h2>
                        <div class="flex gap-2">
                            <span class="text-red-500 text-sm font-medium"
                                >Required</span
                            >
                            <Dropdown :isOpen="isBillingOpen" />
                        </div>
                    </button>

                    <Transition name="accordion">
                        <div v-if="isBillingOpen" class="px-5 pb-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label
                                    v-for="opt in intervalOptions"
                                    :key="opt.value"
                                    class="border rounded-xl p-4 cursor-pointer transition-colors"
                                    :class="
                                        checkout.selectedInterval === opt.value
                                            ? 'border-primary bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    "
                                >
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="radio"
                                            class="mt-0.5 accent-primary"
                                            v-model="checkout.selectedInterval"
                                            :value="opt.value"
                                        />
                                        <div>
                                            <p
                                                class="font-semibold text-sm text-gray-900"
                                            >
                                                {{ opt.label }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ opt.description }}
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </Transition>
                </section>

                <section id="section-branch">
                    <button
                        class="w-full flex items-center justify-between p-5 text-left"
                        @click="isBranchOpen = !isBranchOpen"
                    >
                        <h2 class="font-semibold text-lg text-gray-900">
                            Branch Information
                            <p class="text-sm text-slate-500 font-normal">
                                Configure your branch profile and including
                                location.
                            </p>
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="text-red-500 text-sm font-medium"
                                >Required</span
                            >
                            <Dropdown :isOpen="isBranchOpen" />
                        </div>
                    </button>

                    <Transition name="accordion">
                        <div v-if="isBranchOpen" class="px-5 pb-5">
                            <BranchForm :branch="checkout.branch" />
                        </div>
                    </Transition>
                </section>

                <section id="section-branch-config">
                    <button
                        class="w-full flex items-center justify-between p-5 text-left"
                        @click="isBranchConfigureOpen = !isBranchConfigureOpen"
                    >
                        <h2 class="font-semibold text-lg text-gray-900">
                            Branch Operational Setting
                            <p class="text-sm text-slate-500 font-normal">
                                Configure branch settings.
                            </p>
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="text-danger text-sm font-medium"
                                >Required</span
                            >
                            <Dropdown :isOpen="isBranchConfigureOpen" />
                        </div>
                    </button>

                    <Transition name="accordion">
                        <div v-if="isBranchConfigureOpen" class="px-5 pb-5">
                            <SubcriptionConfigure
                                :setting="checkout.settings"
                            />
                        </div>
                    </Transition>
                </section>

                <section id="section-agency">
                    <button
                        class="w-full flex items-center justify-between p-5 text-left"
                        @click="isAgencyOpen = !isAgencyOpen"
                    >
                        <h2 class="font-semibold text-lg text-gray-900">
                            Agency Information
                            <p class="text-sm text-gray-500 font-normal">
                                Configure your agency profile, branding, and
                                agency details.
                            </p>
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="text-muted text-sm font-medium"
                                >Optional</span
                            >
                            <Dropdown :isOpen="isAgencyOpen" />
                        </div>
                    </button>
                    <Transition name="accordion">
                        <div v-if="isAgencyOpen" class="px-5 pb-5">
                            <AgencyForm :agency="checkout.agency" />
                        </div>
                    </Transition>
                </section>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import Dropdown from "~/components/icons/dropdown.vue";
import { ref, onMounted, watch, nextTick } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { planService } from "@/api/plan/PlanService";
import BranchForm from "~/components/forms/BranchForm.vue";
import AgencyForm from "~/components/forms/AgencyForm.vue";
import SubcriptionConfigure from "~/components/forms/SubcriptionConfigure.vue";

const checkout = useSubscriptionCheckout();
const loading = ref(true);

const isPlanOpen = ref(true);
const isBillingOpen = ref(true);
const isBranchOpen = ref(true);
const isBranchConfigureOpen = ref(true);
const isAgencyOpen = ref(false);

const errorSectionMap: Record<string, { open: typeof isPlanOpen; id: string }> =
    {
        plan: { open: isPlanOpen, id: "section-plan" },
        billing_interval: { open: isBillingOpen, id: "section-billing" },
        branch_name: { open: isBranchOpen, id: "section-branch" },
        branch_description: { open: isBranchOpen, id: "section-branch" },
        branch_contact_number: { open: isBranchOpen, id: "section-branch" },
        branch_image: { open: isBranchOpen, id: "section-branch" },
        branch_lat: { open: isBranchOpen, id: "section-branch" },
        branch_config: {
            open: isBranchConfigureOpen,
            id: "section-branch-config",
        },
        agency_name: { open: isAgencyOpen, id: "section-agency" },
        agency_street: { open: isAgencyOpen, id: "section-agency" },
        agency_city: { open: isAgencyOpen, id: "section-agency" },
        agency_province: { open: isAgencyOpen, id: "section-agency" },
        agency_country: { open: isAgencyOpen, id: "section-agency" },
    };
const sectionOrder = [
    "section-plan",
    "section-billing",
    "section-branch",
    "section-branch-config",
    "section-agency",
];

watch(
    () => checkout.errors,
    async (errors) => {
        if (!errors || Object.keys(errors).length === 0) return;

        const firstErrorSection = sectionOrder.find((sectionId) =>
            Object.keys(errors).some(
                (key) => errorSectionMap[key]?.id === sectionId,
            ),
        );

        if (!firstErrorSection) return;

        Object.keys(errors).forEach((key) => {
            if (errorSectionMap[key]) {
                errorSectionMap[key].open.value = true;
            }
        });

        await nextTick();

        document.getElementById(firstErrorSection)?.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
    },
    { deep: true },
);

const intervalOptions = [
    { value: "monthly", label: "Monthly", description: "Billed monthly" },
    { value: "yearly", label: "Yearly", description: "Save more yearly" },
];

onMounted(async () => {
    try {
        const plans = await planService.list();
        checkout.setPlans(plans);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.accordion-enter-active,
.accordion-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}
.accordion-enter-from,
.accordion-leave-to {
    opacity: 0;
    max-height: 0;
}
.accordion-enter-to,
.accordion-leave-from {
    opacity: 1;
    max-height: 600px;
}
</style>
