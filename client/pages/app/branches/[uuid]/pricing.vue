<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-5">
        <PageHeader
            title="Homecare & Facility Plans"
            subtitle="Care Coordination"
            description="Configure homecare service offerings and inhouse facility plans for your branches."
        />

        <div
            class="bg-white rounded-2xl border border-[#E4EFED] overflow-hidden"
        >
            <div
                class="flex items-center gap-1 px-4 pt-4 border-b border-[#E4EFED] bg-[#F7FAF9]"
            >
                <button
                    v-for="section in sections"
                    :key="section.value"
                    type="button"
                    @click="activeSection = section.value"
                    class="relative flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-all duration-200"
                    :class="
                        activeSection === section.value
                            ? 'text-[#16302E]'
                            : 'text-[#6B8A87] hover:text-[#16302E]'
                    "
                >
                    <component
                        :is="section.icon"
                        class="w-4 h-4"
                        :class="
                            activeSection === section.value
                                ? 'text-primary'
                                : 'text-[#9AB3AF]'
                        "
                    />
                    {{ section.label }}

                    <span
                        class="absolute left-3 right-3 -bottom-px h-0.5 rounded-full"
                        :class="
                            activeSection === section.value
                                ? 'bg-primary'
                                : 'bg-transparent'
                        "
                    />
                </button>
            </div>
            <div class="relative overflow-hidden">
                <div
                    class="flex w-[200%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                    :style="{ transform: `translateX(-${sectionIndex * 50}%)` }"
                >
                    <HomecarePlan
                        :active_patient="overview.active_patient"
                        :caregivers="overview.caregivers"
                        :scheduled_visits="overview.caregivers"
                        :homecare_retention="overview.homecare_retention"
                        :loading="loading"
                    />

                    <FacilityPlan
                        :total_active_plans="overview.total_active_plans"
                        :patient_with_plan="overview.patient_with_plan"
                        :patient_retention="overview.patient_retention"
                        :new_monthy_patients="overview.new_monthy_patients"
                        :loading="loading"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import FacilityPlan from "~/components/sections/app/Contract/FacilityPlan.vue";
import HomecarePlan from "~/components/sections/app/Contract/HomecarePlan.vue";
import { Building2, HeartHandshake } from "lucide-vue-next";
import { useRoute } from "vue-router";

import PageHeader from "~/components/ui/PageHeader.vue";
import { branchContractService } from "~/api/branch-contract/BranchContractService";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

const route = useRoute();
const loading = ref(true);

const activeSection = ref<"homecare" | "facility">("homecare");

const sections = [
    {
        label: "Homecare Services",
        value: "homecare" as const,
        icon: HeartHandshake,
    },
    {
        label: "Inhouse Facility Plans",
        value: "facility" as const,
        icon: Building2,
    },
];

const sectionIndex = computed(() =>
    sections.findIndex((section) => section.value === activeSection.value),
);
const overview = ref({
    total_active_plans: "0",
    patient_with_plan: "0",
    new_monthy_patients: "0",
    patient_retention: "0%",
    active_patient: "0",
    caregivers: "0",
    scheduled_visits: "0",
    homecare_retention: "0",
});
onMounted(async () => {
    try {
        const res = await branchContractService.overview({
            branch_uuid: route.params.uuid as string,
        });
        overview.value = res.data ?? res;
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
});
</script>
