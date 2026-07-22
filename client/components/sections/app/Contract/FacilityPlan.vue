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
                type="button"
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
                                {{ plan.name }}
                            </p>
                        </div>

                        <img
                            :src="plan.image"
                            :alt="plan.name ?? ''"
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
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Included Accommodation Types
                        </p>

                        <div class="space-y-3">
                            <div
                                v-for="type in plan.accommodationTypes"
                                :key="type.name"
                                class="flex items-start gap-3"
                            >
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="type.iconBg"
                                >
                                    <component
                                        :is="type.icon"
                                        class="w-4 h-4"
                                        :class="type.iconColor"
                                    />
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-medium text-[#16302E]"
                                    >
                                        {{ type.name }}
                                    </p>

                                    <p
                                        class="text-xs max-w-xs text-[#9AB3AF] leading-relaxed"
                                    >
                                        {{ type.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Included Contract Types
                        </p>

                        <div
                            v-for="contract in plan.contractTypes"
                            :key="contract.name"
                            class="flex items-start gap-3"
                        >
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                :class="contract.iconBg"
                            >
                                <component
                                    :is="contract.icon"
                                    class="w-4 h-4"
                                    :class="contract.iconColor"
                                />
                            </div>

                            <div>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ contract.name }}
                                </p>

                                <p
                                    class="text-xs max-w-xs text-[#9AB3AF] leading-relaxed"
                                >
                                    {{ contract.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <CreateFacilityPlanModal
        :open="showCreateModal"
        @close="showCreateModal = false"
        :data="modalData"
    />

    <ViewModal
        :open="showViewModal"
        @close="showViewModal = false"
        @update="onUpdatePlan"
    />
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import ViewModal from "./ViewModal.vue";
import { useBranchStore } from "~/stores/branch";
import {
    Building2,
    FileText,
    Users,
    ChevronRight,
    Crown,
    Bed,
    FileSignature,
} from "lucide-vue-next";
import { useRoute } from "vue-router";
import { getBranchImage } from "~/types/branch.js";
import CreateFacilityPlanModal from "~/components/sections/app/Contract/CreateFacilityPlanModal.vue";
import { branchContractService } from "~/api/branch-contract/BranchContractService";
import Logo from "~/assets/logo/logo.png";
import { facilityPlanForm } from "~/types/contract";

const branch = useBranchStore();
const props = defineProps<{
    total_active_plans: string;
    patient_with_plan: string;
    new_monthy_patients: string;
    patient_retention: string;
    loading: boolean;
}>();

const showCreateModal = ref(false);
const showViewModal = ref(false);

const modalData = ref<any>(facilityPlanForm());

function openCreateModal() {
    modalData.value = facilityPlanForm();
    showCreateModal.value = true;
}

function openEditModal(existingPlan: any) {
    modalData.value = { ...existingPlan };
    showCreateModal.value = true;
}

function onUpdatePlan(plan: any) {
    showViewModal.value = false;
    openEditModal(plan);
}

const stats = computed(() => [
    {
        label: "Patients with a Plan",
        value: props.loading ? "—" : props.patient_with_plan,
        caption: "Currently enrolled",
        icon: Users,
        barClass: "bg-[#0E7C7B]",
        iconBg: "bg-[#E8F5F3]",
        iconColor: "text-[#0E7C7B]",
    },
    {
        label: "Facility Plans",
        value: props.loading ? "—" : props.total_active_plans,
        caption: "Active care plans",
        icon: Building2,
        barClass: "bg-[#2563EB]",
        iconBg: "bg-[#EFF6FF]",
        iconColor: "text-[#2563EB]",
    },
    {
        label: "New Patients",
        value: props.loading ? "—" : props.new_monthy_patients,
        caption: "Added this month",
        icon: Users,
        barClass: "bg-[#16A34A]",
        iconBg: "bg-[#F0FDF4]",
        iconColor: "text-[#16A34A]",
    },
    {
        label: "Patient Retention",
        value: props.loading ? "—" : props.patient_retention,
        caption: "Active retention rate",
        icon: Users,
        barClass: "bg-[#F59E0B]",
        iconBg: "bg-[#FFFBEB]",
        iconColor: "text-[#F59E0B]",
    },
]);

const actions = [
    {
        label: "Create Facility Plan",
        icon: Building2,
        iconColor: "text-[#0E7C7B]",
        textColor: "text-[#0E7C7B]",
        bgColor: "bg-[#E8F5F3]",
        onClick: openCreateModal,
    },
    {
        label: "View All Plans",
        icon: FileText,
        iconColor: "text-[#2563EB]",
        textColor: "text-[#2563EB]",
        bgColor: "bg-[#EFF6FF]",
        onClick: () => {
            showViewModal.value = true;
        },
    },
    {
        label: "Active Subscribers",
        icon: Users,
        iconColor: "text-[#16A34A]",
        textColor: "text-[#16A34A]",
        bgColor: "bg-[#F0FDF4]",
        onClick: () => {},
    },
    {
        label: "Recently Renewed",
        icon: FileText,
        iconColor: "text-[#F59E0B]",
        textColor: "text-[#F59E0B]",
        bgColor: "bg-[#FFFBEB]",
        onClick: () => {},
    },
];

const plan = computed(() => ({
    name: branch.activeBranch?.name ?? "",
    image: branch.activeBranch?.image
        ? getBranchImage(branch.activeBranch?.image)
        : Logo,

    accommodationTypes: [
        {
            name: "VIP Room",
            description:
                "A private room exclusively assigned to the patient, providing more privacy, comfort, and a personalized living environment.",
            icon: Crown,
            iconBg: "bg-amber-50",
            iconColor: "text-amber-500",
        },
        {
            name: "Common Room",
            description:
                "A shared room arrangement where the patient stays with other patients, offering a more affordable accommodation option.",
            icon: Bed,
            iconBg: "bg-violet-50",
            iconColor: "text-violet-500",
        },
    ],

    contractTypes: [
        {
            name: "Open Contract",
            description:
                "A flexible contract arrangement based on the patient's or guardian's preferred duration and care needs.",
            icon: FileSignature,
            iconBg: "bg-blue-50",
            iconColor: "text-blue-500",
        },
        {
            name: "Fixed Contract",
            description:
                "A contract with a defined duration, available on monthly or yearly terms depending on the selected care plan.",
            icon: FileSignature,
            iconBg: "bg-orange-50",
            iconColor: "text-orange-500",
        },
    ],
}));
</script>
