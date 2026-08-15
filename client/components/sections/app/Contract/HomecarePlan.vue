<template>
    <div
        class="w-full rounded-3xl border border-muted-light bg-white overflow-hidden font-sans"
    >
        <div class="relative p-6 md:p-8">
            <div
                class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-primary-50 blur-3xl pointer-events-none"
            />

            <div
                class="relative grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-8"
            >
                <div class="flex flex-col gap-4">
                    <div
                        class="relative rounded-2xl overflow-hidden border border-muted-light aspect-[4/3]"
                    >
                        <img
                            :src="service.image"
                            :alt="service.name"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-secondary/80 via-secondary/0 to-transparent"
                        />
                        <span
                            class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/95 text-[10px] font-semibold uppercase tracking-wide text-secondary"
                        >
                            <HomeIcon class="w-3 h-3 text-primary" />
                            Homecare
                        </span>
                    </div>

                    <div>
                        <p
                            class="text-2xl font-bold text-secondary leading-tight"
                        >
                            {{ service.name }}
                        </p>
                        <p class="text-sm text-muted mt-1">
                            In-home caregiving program
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-muted mb-4"
                        >
                            Care Services
                        </p>

                        <div class="space-y-2">
                            <div
                                v-for="item in service.services"
                                :key="item.name"
                                class="flex items-start gap-3 p-3 rounded-2xl transition-colors hover:bg-light/60"
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
                                        class="text-sm font-semibold text-secondary"
                                    >
                                        {{ item.name }}
                                    </p>
                                    <p
                                        class="text-xs text-muted leading-relaxed mt-0.5"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-muted mb-4"
                        >
                            Contract Types
                        </p>

                        <div class="space-y-2">
                            <div
                                v-for="item in service.contracts"
                                :key="item.name"
                                class="flex items-start gap-3 p-3 rounded-2xl transition-colors hover:bg-light/60"
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
                                        class="text-sm font-semibold text-secondary"
                                    >
                                        {{ item.name }}
                                    </p>
                                    <p
                                        class="text-xs text-muted leading-relaxed mt-0.5"
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

        <div class="h-px bg-muted-light" />
        <!-- 
        <div
            class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-muted-light"
        >
            <div v-for="stat in stats" :key="stat.label" class="p-4 md:p-5">
                <p
                    class="text-[11px] font-semibold uppercase tracking-wide text-muted"
                >
                    {{ stat.label }}
                </p>
                <p class="text-2xl font-bold text-secondary mt-1">
                    {{ loading ? "—" : stat.value }}
                </p>
            </div>
        </div> -->
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { UserRound, Clock, FileSignature, HomeIcon } from "lucide-vue-next";

import Logo from "~/assets/logo/logo.png";
import { getBranchImage } from "~/types/branch.js";
import { useBranchStore } from "~/stores/branch";

// const props = defineProps<{
//     active_patient: string;
//     caregivers: string;
//     scheduled_visits: string;
//     homecare_retention: string;
//     loading: boolean;
// }>();

const branch = useBranchStore();

const service = computed(() => ({
    name: branch.activeBranch?.name ?? "",

    image: branch.activeBranch?.image
        ? getBranchImage(branch.activeBranch.image)
        : Logo,

    services: [
        {
            name: "Personal Care",
            description:
                "Assistance with daily personal needs such as hygiene, grooming, mobility, and essential activities.",
            icon: UserRound,
            iconBg: "bg-primary-50",
            iconColor: "text-primary-600",
        },
        {
            name: "Daily Assistance",
            description:
                "Support with meals, medication reminders, and daily routines.",
            icon: Clock,
            iconBg: "bg-accent-50",
            iconColor: "text-accent-600",
        },
    ],
    contracts: [
        {
            name: "Flexible Hours",
            description:
                "Care hours can be adjusted depending on patient requirements.",
            icon: FileSignature,
            iconBg: "bg-primary-50",
            iconColor: "text-primary-600",
        },
        {
            name: "Fixed Hours",
            description:
                "Care is provided for a predefined number of hours per schedule.",
            icon: FileSignature,
            iconBg: "bg-accent-50",
            iconColor: "text-accent-600",
        },
    ],
}));

// const stats = computed(() => [
//     { label: "Active Patients", value: props.active_patient },
//     { label: "Caregivers", value: props.caregivers },
//     { label: "Scheduled Visits", value: props.scheduled_visits },
//     { label: "Retention Rate", value: props.homecare_retention },
// ]);
</script>
