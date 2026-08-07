<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-5">
        <ContractDashboard
            :overview="overview"
            :loading="loading"
            @action="handleDashboardAction"
        />

        <div
            class="bg-white rounded-2xl border border-[#E4EFED] overflow-hidden"
        >
            <div
                class="flex items-center gap-1 px-4 pt-4 border-b border-[#E4EFED] bg-[#F7FAF9]"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    @click="activeTab = tab.value"
                    class="relative px-5 py-3 text-sm font-semibold transition"
                    :class="
                        activeTab === tab.value
                            ? 'text-[#16302E]'
                            : 'text-[#9AB3AF] hover:text-[#16302E]'
                    "
                >
                    {{ tab.label }}

                    <span
                        class="absolute left-3 right-3 bottom-0 h-0.5 rounded-full transition"
                        :class="
                            activeTab === tab.value
                                ? 'bg-primary'
                                : 'bg-transparent'
                        "
                    />
                </button>
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
            @close="closeModal"
        />

        <CreateFacilityPlanModal
            :open="showFacilityModal"
            :data="selectedPlan"
            @close="closeModal"
        />

        <ViewModal
            :open="showViewModal"
            @close="showViewModal = false"
            @update="editPlan"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";

import HomecarePlan from "~/components/sections/app/Contract/HomecarePlan.vue";
import FacilityPlan from "~/components/sections/app/Contract/FacilityPlan.vue";
import ContractDashboard from "~/components/sections/app/Contract/ContractDashboard.vue";
import CreateHomecarePlanModal from "~/components/sections/app/Contract/CreateHomecarePlanModal.vue";
import CreateFacilityPlanModal from "~/components/sections/app/Contract/CreateFacilityPlanModal.vue";
import ViewModal from "~/components/sections/app/Contract/ViewModal.vue";

import { branchContractService } from "~/api/branch-contract/BranchContractService";

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
const activeTab = ref<"homecare" | "facility">("homecare");
const tabs = [
    {
        label: "Homecare Services",
        value: "homecare" as const,
    },
    {
        label: "Facility Plans",
        value: "facility" as const,
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

async function fetchOverview() {
    try {
        const res = await branchContractService.overview({
            branch_uuid: route.params.uuid as string,
        });

        overview.value = {
            ...overview.value,
            ...(res.data ?? res),
        };
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
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

onMounted(fetchOverview);
</script>
