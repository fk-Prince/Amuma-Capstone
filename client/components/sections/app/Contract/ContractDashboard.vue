<template>
    <div
        class="border rounded-t-lg border-muted-light bg-white overflow-hidden font-sans dark:bg-secondary dark:border-white/10"
    >
        <div class="p-5 md:p-6">
            <div class="flex items-center gap-2 mb-4">
                <div
                    class="w-7 h-7 rounded-lg bg-primary-50 flex items-center justify-center dark:bg-primary-500/10"
                >
                    <LayoutDashboard class="w-3.5 h-3.5 text-primary" />
                </div>
                <p class="text-sm font-semibold text-secondary dark:text-white">
                    Overview
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <StatCard
                    title="Patients"
                    :value="overview.total_patients"
                    subtitle="All patients"
                    :icon="Users"
                    tone="primary"
                    :loading="loading"
                />

                <StatCard
                    title="Caregivers"
                    :value="overview.caregivers"
                    subtitle="All caregivers"
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

                <StatCard
                    title="Active Plans"
                    :value="overview.total_active_plans"
                    subtitle="Plans"
                    :icon="ClipboardList"
                    tone="secondary"
                    :loading="loading"
                />

                <StatCard
                    title="Patients Admitted"
                    :value="overview.patient_with_plan"
                    subtitle="Admitted to facility"
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

            </div>
        </div>

        <div class="h-px bg-muted-light dark:bg-white/10" />

        <div
            v-if="visibleActions.length"
            class="p-5 md:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
        >
            <button
                v-for="action in visibleActions"
                :key="action.action"
                type="button"
                :disabled="isLocked(action.action)"
                :title="
                    isLocked(action.action)
                        ? lockedTitle(action.action)
                        : undefined
                "
                @click="handleAction(action.action)"
                class="group rounded-2xl px-4 py-3.5 flex items-center justify-between text-left transition-colors hover:bg-light/70 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent dark:hover:bg-white/5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                        :class="action.iconBg"
                    >
                        <component
                            :is="isLocked(action.action) ? Lock : action.icon"
                            class="w-4 h-4"
                            :class="action.iconColor"
                        />
                    </div>

                    <span
                        class="font-semibold text-sm text-secondary dark:text-white"
                    >
                        {{ action.label }}
                    </span>
                </div>

                <ChevronRight
                    class="w-4 h-4 text-muted transition-transform group-hover:translate-x-0.5 group-hover:text-primary dark:text-gray-400"
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
    LayoutDashboard,
    Lock,
    Stethoscope,
    UserPlus,
    Users,
} from "lucide-vue-next";

import { computed } from "vue";

import StatCard from "./StatCard.vue";
import { Modules } from "~/types/module";

type ActionType =
    | "create-homecare"
    | "create-facility"
    | "view-plans"
    | "diagnosis-cases";

interface Overview {
    total_patients: string;
    caregivers: string;
    scheduled_visits: string;
    homecare_retention: string;
    total_active_plans: string;
    patient_with_plan: string;
    new_monthy_patients: string;
    patient_retention: string;
}

const props = defineProps<{
    overview: Overview;
    loading: boolean;
    homecareLocked?: boolean;
    facilityLocked?: boolean;
}>();

const isLocked = (action: ActionType) =>
    (action === "create-homecare" && !!props.homecareLocked) ||
    (action === "create-facility" && !!props.facilityLocked);

const lockedTitle = (action: ActionType) =>
    action === "create-facility"
        ? "Locked — this branch has no In-house Facility plan."
        : "Locked — this branch has no Homecare Services plan.";

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
        iconBg: "bg-primary-50 dark:bg-primary-500/10",
        iconColor: "text-primary",
    },
    {
        label: "Create Facility Plan",
        action: "create-facility",
        icon: Building2,
        iconBg: "bg-accent-50 dark:bg-accent-500/15",
        iconColor: "text-accent-600 dark:text-accent-300",
    },
    {
        label: "View Care Plans",
        action: "view-plans",
        icon: FileText,
        iconBg: "bg-light dark:bg-white/10",
        iconColor: "text-primary-700 dark:text-primary-300",
    },
    {
        label: "Diagnosis Cases & Prices",
        action: "diagnosis-cases",
        icon: Stethoscope,
        iconBg: "bg-light dark:bg-white/10",
        iconColor: "text-primary-700 dark:text-primary-300",
    },
];

const { canCreate } = usePermissions();

const visibleActions = computed(() =>
    actions.filter(
        (action) =>
            action.action === "view-plans" ||
            action.action === "diagnosis-cases" ||
            canCreate(Modules.Contracts),
    ),
);

function handleAction(type: ActionType) {
    emit("action", type);
}
</script>
