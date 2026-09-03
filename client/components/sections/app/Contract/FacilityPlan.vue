<template>
    <div
        class="w-full rounded-3xl border border-muted-light bg-white overflow-hidden font-sans dark:bg-secondary dark:border-white/10"
    >
        <div class="relative p-6 md:p-8">
            <div
                class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-accent-50 blur-3xl pointer-events-none dark:bg-accent-500/15"
            />

            <div
                class="relative grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-8"
            >
                <div class="flex flex-col gap-4">
                    <div
                        class="relative rounded-2xl overflow-hidden border border-muted-light aspect-[4/3] dark:border-white/10"
                    >
                        <img
                            :src="plan.image"
                            :alt="plan.name"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-secondary/80 via-secondary/0 to-transparent"
                        />
                        <span
                            class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/95 dark:bg-secondary/95 text-[10px] font-semibold uppercase tracking-wide text-secondary dark:text-white"
                        >
                            <Building2 class="w-3 h-3 text-accent-600 dark:text-accent-300" />
                            Facility
                        </span>
                    </div>

                    <div>
                        <p
                            class="text-2xl font-bold text-secondary leading-tight dark:text-white"
                        >
                            {{ plan.name }}
                        </p>
                        <p class="text-sm text-muted mt-1 dark:text-gray-400">
                            In-facility residential program
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-muted mb-4 dark:text-gray-400"
                        >
                            Accommodation Types
                        </p>

                        <div class="space-y-2">
                            <div
                                v-for="item in plan.accommodationTypes"
                                :key="item.name"
                                class="flex items-start gap-3 p-3 rounded-2xl transition-colors hover:bg-light/60 dark:hover:bg-white/5"
                            >
                                <div
                                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
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
                                        class="text-sm font-semibold text-secondary dark:text-white"
                                    >
                                        {{ item.name }}
                                    </p>
                                    <p
                                        class="text-xs text-muted leading-relaxed mt-0.5 dark:text-gray-400"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-muted mb-4 dark:text-gray-400"
                        >
                            Contract Types
                        </p>

                        <div class="space-y-2">
                            <div
                                v-for="item in plan.contractTypes"
                                :key="item.name"
                                class="flex items-start gap-3 p-3 rounded-2xl transition-colors hover:bg-light/60 dark:hover:bg-white/5"
                            >
                                <div
                                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
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
                                        class="text-sm font-semibold text-secondary dark:text-white"
                                    >
                                        {{ item.name }}
                                    </p>
                                    <p
                                        class="text-xs text-muted leading-relaxed mt-0.5 dark:text-gray-400"
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

        <div class="h-px bg-muted-light dark:bg-white/10" />

        <!-- <div
            class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-muted-light"
        >
            <div v-for="stat in stats" :key="stat.label" class="p-4 md:p-5">
                <p
                    class="text-[11px] font-semibold uppercase tracking-wide text-muted dark:text-gray-400"
                >
                    {{ stat.label }}
                </p>
                <p class="text-2xl font-bold text-secondary mt-1 dark:text-white">
                    {{ loading ? "—" : stat.value }}
                </p>
            </div>
        </div> -->
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Crown, Bed, FileSignature, Building2 } from "lucide-vue-next";

import Logo from "~/assets/logo/logo.png";
import { getBranchImage } from "~/types/branch.js";
import { useBranchStore } from "~/stores/branch";

// const props = defineProps<{
//     total_active_plans: string;
//     patient_with_plan: string;
//     new_monthy_patients: string;
//     patient_retention: string;
//     loading: boolean;
// }>();

const branch = useBranchStore();

const plan = computed(() => ({
    name: branch.activeBranch?.name ?? "",

    image: branch.activeBranch?.image
        ? getBranchImage(branch.activeBranch.image)
        : Logo,

    accommodationTypes: [
        {
            name: "VIP Room",
            description:
                "Private accommodation providing comfort, privacy, and personalized living space.",
            icon: Crown,
            iconBg: "bg-accent-50 dark:bg-accent-500/15",
            iconColor: "text-accent-600 dark:text-accent-300",
        },
        {
            name: "Common Room",
            description:
                "Shared accommodation option providing affordable patient housing.",
            icon: Bed,
            iconBg: "bg-primary-50 dark:bg-primary-500/10",
            iconColor: "text-primary-600 dark:text-primary-300",
        },
    ],

    contractTypes: [
        {
            name: "Monthly Contract",
            description: "Recurring caregiving arrangement billed monthly.",
            icon: FileSignature,
            iconBg: "bg-primary-50 dark:bg-primary-500/10",
            iconColor: "text-primary-600 dark:text-primary-300",
        },
        {
            name: "Yearly Contract",
            description:
                "Annual caregiving agreement billed yearly with a defined duration and service scope.",
            icon: FileSignature,
            iconBg: "bg-accent-50 dark:bg-accent-500/15",
            iconColor: "text-accent-600 dark:text-accent-300",
        },
    ],
}));

// const stats = computed(() => [
//     { label: "Active Plans", value: props.total_active_plans },
//     { label: "Patients with Plan", value: props.patient_with_plan },
//     { label: "New Monthly Patients", value: props.new_monthy_patients },
//     { label: "Retention Rate", value: props.patient_retention },
// ]);
</script>
