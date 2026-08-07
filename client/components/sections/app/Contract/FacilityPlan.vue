<template>
    <div class="w-full space-y-5">
        <div class="rounded-2xl border border-[#E4EFED] p-6">
            <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-8">
                <div class="flex flex-col gap-3">
                    <p class="text-3xl font-semibold text-[#16302E]">
                        {{ plan.name }}
                    </p>

                    <img
                        :src="plan.image"
                        :alt="plan.name"
                        class="w-full h-32 object-cover rounded-xl border border-[#E4EFED]"
                    />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Included Accommodation Types
                        </p>

                        <div class="space-y-4">
                            <div
                                v-for="item in plan.accommodationTypes"
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

                    <!-- Contracts -->
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] mb-3"
                        >
                            Included Contract Types
                        </p>

                        <div class="space-y-4">
                            <div
                                v-for="item in plan.contractTypes"
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

import { Crown, Bed, FileSignature } from "lucide-vue-next";

import Logo from "~/assets/logo/logo.png";
import { getBranchImage } from "~/types/branch.js";
import { useBranchStore } from "~/stores/branch";
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

            iconBg: "bg-amber-50",

            iconColor: "text-amber-500",
        },

        {
            name: "Common Room",

            description:
                "Shared accommodation option providing affordable patient housing.",

            icon: Bed,

            iconBg: "bg-violet-50",

            iconColor: "text-violet-500",
        },
    ],

    contractTypes: [
        {
            name: "Flexible Hours",
            description:
                "Care hours can be adjusted depending on patient requirements.",
            icon: FileSignature,
            iconBg: "bg-blue-50",
            iconColor: "text-blue-500",
        },
        {
            name: "Fixed Hours",
            description:
                "Care is provided for a predefined number of hours per schedule.",
            icon: FileSignature,
            iconBg: "bg-orange-50",
            iconColor: "text-orange-500",
        },
    ],
}));
</script>
