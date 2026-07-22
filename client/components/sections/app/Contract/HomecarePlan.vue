<template>
    <div class="w-1/2 shrink-0 p-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="relative bg-white rounded-xl border border-[#E4EFED] p-5 overflow-hidden"
            >
                <div
                    class="absolute top-0 left-0 right-0 h-1"
                    :class="stat.barClass"
                />

                <div class="flex items-center gap-3">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center"
                        :class="stat.iconBg"
                    >
                        <component
                            :is="stat.icon"
                            class="w-5 h-5"
                            :class="stat.iconColor"
                        />
                    </div>

                    <p
                        class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF]"
                    >
                        {{ stat.label }}
                    </p>
                </div>

                <p class="text-3xl font-bold text-[#16302E] mt-4">
                    {{ stat.value }}
                </p>

                <p class="text-xs text-[#9AB3AF] mt-1">
                    {{ stat.caption }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <button
                v-for="action in actions"
                :key="action.label"
                class="flex items-center justify-between rounded-xl border bg-white px-5 py-4 hover:shadow-md transition"
                @click="action.onClick?.()"
            >
                <div class="flex items-center gap-3">
                    <component
                        :is="action.icon"
                        class="w-5 h-5"
                        :class="action.iconColor"
                    />

                    <span
                        class="text-sm font-semibold"
                        :class="action.textColor"
                    >
                        {{ action.label }}
                    </span>
                </div>

                <ChevronRight class="w-4 h-4" :class="action.textColor" />
            </button>
        </div>

        <div class="rounded-2xl border border-[#E4EFED] p-6">
            <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-8">
                <ClientOnly>
                    <div class="flex flex-col gap-3">
                        <div>
                            <p class="text-3xl font-semibold text-[#16302E]">
                                {{ service.name }}
                            </p>
                        </div>

                        <img
                            :src="service.image"
                            :alt="service.name ?? ''"
                            class="w-full h-32 object-cover rounded-xl border border-[#E4EFED]"
                        />
                    </div>

                    <template #fallback>
                        <div class="flex flex-col gap-3">
                            <div class="space-y-2">
                                <div
                                    class="h-8 w-40 rounded-lg bg-gray-200 animate-pulse"
                                ></div>
                                <div
                                    class="h-3 w-56 rounded bg-gray-200 animate-pulse"
                                ></div>
                            </div>

                            <div
                                class="w-full h-32 rounded-xl bg-gray-200 animate-pulse"
                            ></div>
                        </div>
                    </template>
                </ClientOnly>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Care Services
                        </p>

                        <div class="space-y-3">
                            <div
                                v-for="item in service.services"
                                :key="item.name"
                                class="flex items-start gap-3"
                            >
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="item.iconBg"
                                >
                                    <component
                                        :is="item.icon"
                                        class="w-4 h-4"
                                        :class="item.iconColor"
                                    />
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-medium text-[#16302E]"
                                    >
                                        {{ item.name }}
                                    </p>

                                    <p
                                        class="text-xs max-w-xs text-[#9AB3AF] leading-relaxed"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Contract Types
                        </p>

                        <div class="space-y-3">
                            <div
                                v-for="item in service.contracts"
                                :key="item.name"
                                class="flex items-start gap-3"
                            >
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="item.iconBg"
                                >
                                    <component
                                        :is="item.icon"
                                        class="w-4 h-4"
                                        :class="item.iconColor"
                                    />
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-medium text-[#16302E]"
                                    >
                                        {{ item.name }}
                                    </p>

                                    <p
                                        class="text-xs max-w-xs text-[#9AB3AF] leading-relaxed"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <CreateHomecarePlanModal
        :open="showCreateModal"
        @close="showCreateModal = false"
    />

    <ViewModal
        :open="showViewModal"
        @close="showViewModal = false"
        @update="onUpdatePlan"
    />
</template>

<script setup lang="ts">
import CreateHomecarePlanModal from "./CreateHomecarePlanModal.vue";
import Logo from "~/assets/logo/logo.png";
import { getBranchImage } from "~/types/branch.js";
import ViewModal from "./ViewModal.vue";

import {
    Users,
    HeartHandshake,
    FileText,
    ChevronRight,
    UserRound,
    CalendarDays,
    Clock,
    FileSignature,
} from "lucide-vue-next";
import { homecarePlanForm } from "~/types/contract.js";

const props = defineProps<{
    active_patient: string;
    caregivers: string;
    scheduled_visits: string;
    homecare_retention: string;
    loading: boolean;
}>();

const branch = useBranchStore();
const showCreateModal = ref(false);
const showViewModal = ref(false);

const modalData = ref<any>(homecarePlanForm());
function onUpdatePlan(plan: any) {
    showViewModal.value = false;
    openEditModal(plan);
}
function openEditModal(existingPlan: any) {
    modalData.value = { ...existingPlan };
    showCreateModal.value = true;
}
function openCreateModal() {
    modalData.value = homecarePlanForm();
    showCreateModal.value = true;
}

const stats = [
    {
        label: "Active Patients",
        value: props.loading ? "0" : props.active_patient,
        caption: "Currently receiving care",
        icon: Users,
        barClass: "bg-[#0E7C7B]",
        iconBg: "bg-[#E8F5F3]",
        iconColor: "text-[#0E7C7B]",
    },
    {
        label: "Caregivers",
        value: props.loading ? "0" : props.caregivers,
        caption: "Available caregivers",
        icon: UserRound,
        barClass: "bg-[#2563EB]",
        iconBg: "bg-[#EFF6FF]",
        iconColor: "text-[#2563EB]",
    },
    {
        label: "Scheduled Visits",
        value: props.loading ? "0" : props.scheduled_visits,
        caption: "This week",
        icon: CalendarDays,
        barClass: "bg-[#16A34A]",
        iconBg: "bg-[#F0FDF4]",
        iconColor: "text-[#16A34A]",
    },
    {
        label: "Retention",
        value: props.loading ? "0" : props.homecare_retention,
        caption: "Active clients retained",
        icon: HeartHandshake,
        barClass: "bg-[#F59E0B]",
        iconBg: "bg-[#FFFBEB]",
        iconColor: "text-[#F59E0B]",
    },
];

const actions = [
    {
        label: "Create Care Package",
        icon: HeartHandshake,
        iconColor: "text-[#0E7C7B]",
        textColor: "text-[#0E7C7B]",
        onClick: openCreateModal,
    },
    {
        label: "View Care Plans",
        icon: FileText,
        iconColor: "text-[#2563EB]",
        textColor: "text-[#2563EB]",
        onClick: () => {
            showViewModal.value = true;
        },
    },
    {
        label: "Caregiver List",
        icon: Users,
        iconColor: "text-[#16A34A]",
        textColor: "text-[#16A34A]",
    },
    {
        label: "Renewals",
        icon: FileText,
        iconColor: "text-[#F59E0B]",
        textColor: "text-[#F59E0B]",
    },
];

const service = {
    name: branch.activeBranch?.name ?? "",
    image: branch.activeBranch?.image
        ? getBranchImage(branch.activeBranch?.image)
        : Logo,
    services: [
        {
            name: "Personal Care",
            description:
                "Assistance with daily personal needs such as hygiene, grooming, mobility, and other essential activities.",
            icon: UserRound,
            iconBg: "bg-blue-50",
            iconColor: "text-blue-500",
        },
        {
            name: "Daily Assistance",
            description:
                "Support with everyday routines including meal preparation, medication reminders, and household activities.",
            icon: Clock,
            iconBg: "bg-green-50",
            iconColor: "text-green-500",
        },
    ],

    contracts: [
        {
            name: "Monthly Contract",
            description:
                "A recurring caregiving arrangement billed monthly based on the selected care package and service needs.",
            icon: FileSignature,
            iconBg: "bg-orange-50",
            iconColor: "text-orange-500",
        },
        {
            name: "Fixed Contract",
            description:
                "A caregiving agreement with a defined duration and scope of service based on the client's requirements.",
            icon: FileSignature,
            iconBg: "bg-purple-50",
            iconColor: "text-purple-500",
        },
    ],
};
</script>
