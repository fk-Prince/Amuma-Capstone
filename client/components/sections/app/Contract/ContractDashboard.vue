<template>
    <div
        class="rounded-3xl border border-muted-light bg-white overflow-hidden font-sans"
    >
        <div class="p-5 md:p-6">
            <div class="flex items-center gap-2 mb-4">
                <div
                    class="w-7 h-7 rounded-lg bg-primary-50 flex items-center justify-center"
                >
                    <HomeIcon class="w-3.5 h-3.5 text-primary" />
                </div>
                <p class="text-sm font-semibold text-secondary">
                    Homecare Overview
                </p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="Active Patients"
                    :value="overview.active_patient"
                    subtitle="Homecare patients"
                    :icon="Users"
                    tone="primary"
                    :loading="loading"
                />

                <StatCard
                    title="Caregivers"
                    :value="overview.caregivers"
                    subtitle="Available caregivers"
                    :icon="HeartHandshake"
                    tone="accent"
                    :loading="loading"
                />

                <StatCard
                    title="Scheduled Visits"
                    :value="overview.scheduled_visits"
                    subtitle="This week"
                    :icon="CalendarCheck"
                    tone="secondary"
                    :loading="loading"
                />
            </div>
        </div>

        <div class="h-px bg-muted-light" />

        <div class="p-5 md:p-6">
            <div class="flex items-center gap-2 mb-4">
                <div
                    class="w-7 h-7 rounded-lg bg-accent-50 flex items-center justify-center"
                >
                    <Building2 class="w-3.5 h-3.5 text-accent-600" />
                </div>
                <p class="text-sm font-semibold text-secondary">
                    Facility Overview
                </p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="Active Plans"
                    :value="overview.total_active_plans"
                    subtitle="Facility plans"
                    :icon="ClipboardList"
                    tone="secondary"
                    :loading="loading"
                />

                <StatCard
                    title="Patients with Plan"
                    :value="overview.patient_with_plan"
                    subtitle="Facility enrollment"
                    :icon="Users"
                    tone="primary"
                    :loading="loading"
                />

                <StatCard
                    title="New Patients"
                    :value="overview.new_monthy_patients"
                    subtitle="Added this month"
                    :icon="UserPlus"
                    tone="primary"
                    :loading="loading"
                />

                <StatCard
                    title="Retention Rate"
                    :value="overview.patient_retention"
                    subtitle="Current retention"
                    :icon="TrendingUp"
                    tone="accent"
                    :loading="loading"
                />
            </div>
        </div>

        <div class="h-px bg-muted-light" />

        <div
            class="p-5 md:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
        >
            <button
                v-for="action in actions"
                :key="action.action"
                type="button"
                @click="handleAction(action.action)"
                class="group rounded-2xl px-4 py-3.5 flex items-center justify-between text-left transition-colors hover:bg-light/70"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                        :class="action.iconBg"
                    >
                        <component
                            :is="action.icon"
                            class="w-4 h-4"
                            :class="action.iconColor"
                        />
                    </div>

                    <span class="font-semibold text-sm text-secondary">
                        {{ action.label }}
                    </span>
                </div>

                <ChevronRight
                    class="w-4 h-4 text-muted transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    Building2,
    CalendarCheck,
    ChevronRight,
    ClipboardList,
    FileText,
    HeartHandshake,
    HomeIcon,
    TrendingUp,
    UserPlus,
    Users,
} from "lucide-vue-next";

import StatCard from "./StatCard.vue";

type ActionType =
    | "create-homecare"
    | "create-facility"
    | "view-plans"
    | "manage-contracts";

interface Overview {
    active_patient: string;
    caregivers: string;
    scheduled_visits: string;
    homecare_retention: string;
    total_active_plans: string;
    patient_with_plan: string;
    new_monthy_patients: string;
    patient_retention: string;
}

defineProps<{
    overview: Overview;
    loading: boolean;
}>();

const emit = defineEmits<{
    action: [type: ActionType];
}>();

const actions: {
    label: string;
    action: ActionType;
    icon: typeof HomeIcon;
    iconBg: string;
    iconColor: string;
}[] = [
    {
        label: "Create Homecare Plan",
        action: "create-homecare",
        icon: HomeIcon,
        iconBg: "bg-primary-50",
        iconColor: "text-primary",
    },
    {
        label: "Create Facility Plan",
        action: "create-facility",
        icon: Building2,
        iconBg: "bg-accent-50",
        iconColor: "text-accent-600",
    },
    {
        label: "View Care Plans",
        action: "view-plans",
        icon: FileText,
        iconBg: "bg-light",
        iconColor: "text-secondary",
    },
    {
        label: "Manage Contracts",
        action: "manage-contracts",
        icon: FileText,
        iconBg: "bg-muted-light",
        iconColor: "text-muted-dark",
    },
];

function handleAction(type: ActionType) {
    emit("action", type);
}
</script>
