<template>
    <div class="text-secondary py-5">
        <template v-if="loading">
            <div class="flex items-start justify-between gap-6">
                <div class="flex flex-col gap-6 flex-1 animate-pulse">
                    <div class="flex justify-between items-center">
                        <div class="h-6 w-48 bg-gray-200 rounded"></div>
                        <div class="h-9 w-32 bg-gray-200 rounded-md"></div>
                    </div>

                    <div class="flex items-center gap-2 ml-2">
                        <div class="h-4 w-4 bg-gray-200 rounded"></div>
                        <div class="h-4 w-12 bg-gray-200 rounded"></div>
                        <div class="h-4 w-24 bg-gray-200 rounded"></div>
                    </div>

                    <div class="space-y-2 mt-3 w-[90%] mx-auto">
                        <div class="h-4 bg-gray-200 rounded w-full"></div>
                        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                        <div class="h-4 bg-gray-200 rounded w-4/6"></div>
                    </div>

                    <div class="flex flex-col gap-4 mt-10">
                        <div class="h-24 bg-gray-200 rounded-2xl"></div>

                        <div class="h-24 bg-gray-200 rounded-2xl"></div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="flex items-start justify-center gap-6 px-5">
                <div class="flex flex-col justify-between min-h-[270px] flex-1">
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <h1
                                class="text-lg sm:text-xl font-semibold text-gray-900"
                            >
                                {{ branch?.name }}
                            </h1>

                            <div
                                class="flex md:flex-col flex-row md:items-end items-start gap-3 md:pt-0 pt-2"
                            >
                                <button
                                    @click="emit('favorite')"
                                    class="flex items-center gap-2 text-sm text-gray-500 border border-transparent p-2 rounded-md hover:text-red-500 hover:border-red-500 transition-all duration-300"
                                >
                                    <Heart class="w-5 h-5" />
                                    <span class="hidden sm:inline">
                                        Add to Favorites
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            class="ml-2 flex items-center gap-2 text-sm w-full text-gray-600"
                        >
                            <Star
                                class="h-4 w-4 text-orange-400 fill-orange-400"
                            />

                            <span class="font-medium text-gray-800">
                                {{ branch?.averageRating || 0 }}
                            </span>

                            <span class="text-gray-400">
                                ({{ branch?.reviewCount || 0 }} reviews)
                            </span>
                        </div>

                        <p
                            class="text-sm mt-3 text-gray-500 leading-relaxed mx-auto w-[90%]"
                        >
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            {{ branch?.description }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 mt-10">
                        <button
                            v-if="
                                branch?.subscriptions.some((s) =>
                                    ['A', 'C'].includes(s.plans.plan_code),
                                )
                            "
                            @click="emit('homecare')"
                            class="group flex items-center justify-between rounded-2xl border border-primary/20 bg-primary/5 p-4 text-left transition-all hover:bg-primary hover:border-primary hover:text-white hover:shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-white transition group-hover:bg-white group-hover:text-primary"
                                >
                                    <House class="h-6 w-6" />
                                </div>

                                <div>
                                    <h3 class="font-semibold text-base">
                                        Homecare Services
                                    </h3>

                                    <p class="text-sm opacity-70">
                                        Professional care delivered at your
                                        home.
                                    </p>
                                </div>
                            </div>

                            <ArrowRight
                                class="h-5 w-5 transition group-hover:translate-x-1"
                            />
                        </button>
                        <button
                            v-if="
                                branch?.subscriptions.some((s) =>
                                    ['B', 'C'].includes(s.plans.plan_code),
                                )
                            "
                            @click="emit('facility')"
                            class="group flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-left transition-all hover:bg-emerald-600 hover:border-emerald-600 hover:text-white hover:shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 text-white transition group-hover:bg-white group-hover:text-emerald-600"
                                >
                                    <Building2 class="h-6 w-6" />
                                </div>

                                <div>
                                    <h3 class="font-semibold text-base">
                                        In-House Facility Admission
                                    </h3>

                                    <p class="text-sm opacity-70">
                                        Get admitted and receive facility-based
                                        care.
                                    </p>
                                </div>
                            </div>

                            <ArrowRight
                                class="h-5 w-5 transition group-hover:translate-x-1"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Heart, House, Building2, ArrowRight, Star } from "lucide-vue-next";
import type { BranchRetrieve } from "~/types/branch";

defineProps<{
    branch: BranchRetrieve | null;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "homecare"): void;
    (e: "facility"): void;
    (e: "favorite"): void;
}>();
</script>
