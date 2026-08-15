<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-6 font-sans">
        <ContractDashboard
            :overview="overview"
            :loading="loading"
            @action="handleDashboardAction"
        />

        <div
            class="bg-white rounded-3xl border border-muted-light shadow-[0_1px_2px_rgba(15,22,35,0.04)] overflow-hidden"
        >
            <div
                class="flex items-center justify-between gap-4 px-5 pt-5 pb-0 border-b border-muted-light bg-gradient-to-b from-light/60 to-white"
            >
                <div
                    class="relative flex items-center p-1 bg-light rounded-2xl"
                >
                    <span
                        class="absolute top-1 bottom-1 w-[180px] rounded-xl bg-white shadow-[0_1px_3px_rgba(15,22,35,0.12)] transition-transform duration-300 ease-out"
                        :style="{
                            transform: `translateX(${activeIndex * 180}px)`,
                        }"
                    />

                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        @click="activeTab = tab.value"
                        class="relative z-10 flex w-[180px] items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors duration-200"
                        :class="
                            activeTab === tab.value
                                ? 'text-secondary'
                                : 'text-muted hover:text-secondary'
                        "
                    >
                        <component :is="tab.icon" class="w-4 h-4 shrink-0" />
                        {{ tab.label }}
                    </button>
                </div>

                <p class="hidden sm:block text-xs text-muted pb-4 shrink-0">
                    {{
                        activeTab === "homecare"
                            ? overview.active_patient
                            : overview.patient_with_plan
                    }}
                    patients enrolled
                </p>
            </div>

            <div class="overflow-hidden">
                <div
                    class="flex w-[200%] transition-transform duration-500 ease-out"
                    :style="{
                        transform: `translateX(-${activeIndex * 50}%)`,
                    }"
                >
                    <div class="w-1/2 shrink-0 p-6">
                        <HomecarePlan
                            :active_patient="overview.active_patient"
                            :caregivers="overview.caregivers"
                            :scheduled_visits="overview.scheduled_visits"
                            :homecare_retention="overview.homecare_retention"
                            :loading="loading"
                        />
                    </div>

                    <div class="w-1/2 shrink-0 p-6">
                        <FacilityPlan
                            :total_active_plans="overview.total_active_plans"
                            :patient_with_plan="overview.patient_with_plan"
                            :new_monthy_patients="overview.new_monthy_patients"
                            :patient_retention="overview.patient_retention"
                            :loading="loading"
                        />
                    </div>
                </div>
            </div>
        </div>

        <CreateHomecarePlanModal
            :open="showHomecareModal"
            :data="selectedPlan"
            :all-plans="plans"
            @close="closeModal"
            @saved="closeModal"
        />

        <CreateFacilityPlanModal
            :open="showFacilityModal"
            :data="selectedPlan"
            :all-plans="plans"
            @close="closeModal"
            @saved="closeModal"
        />

        <ViewModal
            :open="showViewModal"
            :plans="plans"
            :loading="plansLoading"
            @close="showViewModal = false"
            @update="editPlan"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { HomeIcon, Building2 } from "lucide-vue-next";

import HomecarePlan from "~/components/sections/app/Contract/HomecarePlan.vue";
import FacilityPlan from "~/components/sections/app/Contract/FacilityPlan.vue";
import ContractDashboard from "~/components/sections/app/Contract/ContractDashboard.vue";
import CreateHomecarePlanModal from "~/components/sections/app/Contract/CreateHomecarePlanModal.vue";
import CreateFacilityPlanModal from "~/components/sections/app/Contract/CreateFacilityPlanModal.vue";
import ViewModal from "~/components/sections/app/Contract/ViewModal.vue";

import { branchContractService } from "~/api/branch-contract/BranchContractService";

useHead({ title: "Contracts" });

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

const showHomecareModal = ref(false);
const showFacilityModal = ref(false);
const showViewModal = ref(false);

const selectedPlan = ref<any>(null);
const route = useRoute();
const loading = ref(true);
const plansLoading = ref(true);
const activeTab = ref<"homecare" | "facility">("homecare");
const plans = ref<any[]>([]);

const tabs = [
    {
        label: "Homecare Services",
        value: "homecare" as const,
        icon: HomeIcon,
    },
    {
        label: "Facility Plans",
        value: "facility" as const,
        icon: Building2,
    },
];

function editPlan(plan: any) {
    showViewModal.value = false;
    if (plan.category.toLowerCase() === "homecare") {
        showHomecareModal.value = true;
    } else {
        showFacilityModal.value = true;
    }
    selectedPlan.value = plan;
}

const activeIndex = computed(() =>
    tabs.findIndex((tab) => tab.value === activeTab.value),
);

function closeModal() {
    showHomecareModal.value = false;
    showFacilityModal.value = false;
    selectedPlan.value = null;
}

const overview = ref({
    active_patient: "0",
    caregivers: "0",
    scheduled_visits: "0",
    homecare_retention: "0%",

    total_active_plans: "0",
    patient_with_plan: "0",
    new_monthy_patients: "0",
    patient_retention: "0%",
});

async function fetchDashboardData() {
    const branch_uuid = route.params.uuid as string;

    try {
        const [overviewRes, plansRes] = await Promise.all([
            branchContractService.overview({ branch_uuid }),
            branchContractService.list({ branch_uuid }),
        ]);

        overview.value = {
            ...overview.value,
            ...(overviewRes.data ?? overviewRes),
        };

        plans.value = plansRes.data ?? plansRes;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
        plansLoading.value = false;
    }
}

function handleDashboardAction(action: string) {
    switch (action) {
        case "create-homecare":
            showHomecareModal.value = true;
            break;

        case "create-facility":
            showFacilityModal.value = true;
            break;

        case "view-plans":
            showViewModal.value = true;
            break;

        case "manage-contracts":
            break;
    }
}

onMounted(fetchDashboardData);
</script>
