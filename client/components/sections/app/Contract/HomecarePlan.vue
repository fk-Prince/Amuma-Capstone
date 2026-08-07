<template>
    <div class="w-full space-y-5">
        <div class="rounded-2xl border border-[#E4EFED] p-6">
            <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-8">
                <div class="flex flex-col gap-3">
                    <p class="text-3xl font-semibold text-[#16302E]">
                        {{ service.name }}
                    </p>

                    <img
                        :src="service.image"
                        :alt="service.name"
                        class="w-full h-32 object-cover rounded-xl border border-[#E4EFED]"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Care Services
                        </p>

                        <div class="space-y-4">
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
                                        class="text-xs text-[#9AB3AF] leading-relaxed"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contract Types -->
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Contract Types
                        </p>

                        <div class="space-y-4">
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
                                        class="text-xs text-[#9AB3AF] leading-relaxed"
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
</template>

<script setup lang="ts">
import { computed } from "vue";

import { UserRound, Clock, FileSignature } from "lucide-vue-next";

import Logo from "~/assets/logo/logo.png";
import { getBranchImage } from "~/types/branch.js";
import { useBranchStore } from "~/stores/branch";

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
            iconBg: "bg-blue-50",
            iconColor: "text-blue-500",
        },

        {
            name: "Daily Assistance",
            description:
                "Support with meals, medication reminders, and daily routines.",
            icon: Clock,
            iconBg: "bg-green-50",
            iconColor: "text-green-500",
        },
    ],

    contracts: [
        {
            name: "Monthly Contract",
            description: "Recurring caregiving arrangement billed monthly.",
            icon: FileSignature,
            iconBg: "bg-orange-50",
            iconColor: "text-orange-500",
        },

        {
            name: "Yearly Contract",
            description:
                "Annual caregiving agreement billed yearly with a defined duration and service scope.",
            icon: FileSignature,
            iconBg: "bg-purple-50",
            iconColor: "text-purple-500",
        },
    ],
}));
</script>
