<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatCard
                title="Active Patients"
                :value="overview.active_patient"
                subtitle="Homecare patients"
                color="teal"
                :loading="loading"
            />

            <StatCard
                title="Caregivers"
                :value="overview.caregivers"
                subtitle="Available caregivers"
                color="blue"
                :loading="loading"
            />

            <StatCard
                title="Facility Plans"
                :value="overview.total_active_plans"
                subtitle="Active facility plans"
                color="purple"
                :loading="loading"
            />

            <StatCard
                title="Patient Retention"
                :value="overview.patient_retention"
                subtitle="Current retention"
                color="orange"
                :loading="loading"
            />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <StatCard
                title="Scheduled Visits"
                :value="overview.scheduled_visits"
                subtitle="This week"
                color="green"
                :loading="loading"
            />

            <StatCard
                title="Patients With Plans"
                :value="overview.patient_with_plan"
                subtitle="Facility enrollment"
                color="blue"
                :loading="loading"
            />

            <StatCard
                title="New Patients"
                :value="overview.new_monthy_patients"
                subtitle="Added this month"
                color="teal"
                :loading="loading"
            />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <button
                v-for="action in actions"
                :key="action.label"
                @click="handleAction(action.action)"
                class="rounded-xl border border-[#E4EFED] bg-white px-5 py-4 flex justify-between hover:shadow-md"
            >
                <span class="font-semibold text-sm">
                    {{ action.label }}
                </span>

                <ChevronRight class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ChevronRight } from "lucide-vue-next";
import StatCard from "./StatCard.vue";

defineProps<{
    overview: {
        active_patient: string;
        caregivers: string;
        scheduled_visits: string;
        homecare_retention: string;

        total_active_plans: string;
        patient_with_plan: string;
        new_monthy_patients: string;
        patient_retention: string;
    };

    loading: boolean;
}>();

const emit = defineEmits<{
    action: [
        type:
            | "create-homecare"
            | "create-facility"
            | "view-plans"
            | "manage-contracts",
    ];
}>();
const actions = [
    {
        label: "Create Homecare Plan",
        action: "create-homecare",
    },
    {
        label: "Create Facility Plan",
        action: "create-facility",
    },
    {
        label: "View Care Plans",
        action: "view-plans",
    },
    {
        label: "Manage Contracts",
        action: "manage-contracts",
    },
];

function handleAction(type: any) {
    emit("action", type);
}
</script>
