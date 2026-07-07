<template>
    <div
        v-if="variant === 1"
        class="rounded-2xl border bg-white overflow-hidden cursor-pointer transition hover:shadow-md"
        @click="$emit('select', branch)"
    >
        <div class="h-32 bg-slate-100">
            <img
                v-if="branch?.image"
                :src="branch.image"
                class="w-full h-full object-cover"
                alt="branch image"
            />
            <img
                v-else
                :src="Logo"
                class="w-16 h-16 object-contain opacity-60"
                alt="default logo"
            />
        </div>

        <div class="p-4">
            <h3 class="font-semibold text-slate-900">
                {{ branch.name }}
            </h3>

            <p class="text-xs text-slate-500 mt-1 flex gap-1">
                <Location />
                {{ branch.location.street }}, {{ branch.location.city }}
            </p>

            <div class="mt-3 flex items-center justify-between">
                <span
                    class="text-xs px-2 py-1 rounded-full font-semibold"
                    :class="
                        branch.availability.is_open
                            ? 'bg-green-100 text-green-700'
                            : 'bg-red-100 text-red-600'
                    "
                >
                    {{ branch.availability.is_open ? "Open" : "Closed" }}
                </span>

                <span class="text-xs text-slate-400">
                    {{ getTime(branch.availability).label }}
                </span>
            </div>

            <div class="mt-3 text-xs text-slate-500">
                ⭐
                <span class="font-semibold text-slate-700">
                    {{ branch.averageRating ?? "No rating" }}
                </span>
                ({{ branch.reviewCount }})
            </div>
        </div>

        <div class="p-4 border-t bg-slate-50 flex justify-end items-center">
            <button
                class="text-xs font-semibold text-white bg-primary px-5 py-1.5 rounded-lg hover:bg-blue-700"
            >
                Book Now
            </button>
        </div>
    </div>

    <div
        v-else-if="variant === 2"
        class="group flex overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-md hover:border-slate-300 cursor-pointer"
    >
        <div class="relative w-64 shrink-0 overflow-hidden bg-slate-100">
            <img
                v-if="branch?.image"
                :src="branch.image"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                alt="branch image"
            />

            <img
                v-else
                :src="Logo"
                class="h-full w-full object-contain opacity-40 p-8"
                alt="default logo"
            />

            <div
                class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/50 to-transparent"
            />

            <span
                class="absolute left-3 top-3 rounded-md border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide backdrop-blur-sm"
                :class="
                    branch.availability.is_open
                        ? 'bg-white/90 text-emerald-700 border-emerald-200'
                        : 'bg-white/90 text-slate-500 border-slate-200'
                "
            >
                {{ branch.availability.is_open ? "Open Now" : "Closed" }}
            </span>

            <div
                class="absolute right-3 top-3 flex items-center gap-1 rounded-md bg-white/90 px-2.5 py-1 shadow-sm backdrop-blur-sm"
            >
                <span class="text-amber-500 text-xs">★</span>

                <span class="text-xs font-semibold text-slate-700">
                    {{ branch.averageRating ?? "0.0" }}
                </span>

                <span
                    v-if="branch.reviewCount > 0"
                    class="text-xs text-slate-500"
                >
                    ({{ branch.reviewCount }})
                </span>
            </div>
        </div>

        <div class="flex flex-1 flex-col p-6">
            <div>
                <h3
                    class="line-clamp-1 text-lg font-semibold tracking-tight text-slate-900 transition group-hover:text-primary"
                >
                    {{ branch.name }}
                </h3>

                <div
                    class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-500"
                >
                    <Location class="h-4 w-4 shrink-0" />
                    <span class="line-clamp-1">
                        {{ branch.location.street }},
                        {{ branch.location.city }},
                        {{ branch.location.province }}
                    </span>
                </div>

                <p
                    class="mt-3 w-[90%] mx-auto text-sm leading-6 text-slate-600 line-clamp-3"
                >
                    {{ branch.description }}
                </p>
            </div>

            <div class="mt-4 flex flex-wrap gap-1.5">
                <template
                    v-for="p in branch.subscriptions"
                    :key="p.plans.plan_code ?? ''"
                >
                    <template v-if="p.plans.name === 'Hybrid'">
                        <span
                            class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
                        >
                            Homecare Service
                        </span>
                        <span
                            class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
                        >
                            Inhouse Facility
                        </span>
                    </template>

                    <span
                        v-else
                        class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
                    >
                        {{ p.plans.name }}
                    </span>
                </template>
            </div>

            <div class="mt-4 flex items-center gap-2 text-sm">
                <svg
                    class="h-4 w-4 shrink-0"
                    :class="
                        getTime(branch.availability).is24Hours
                            ? 'text-emerald-600'
                            : 'text-slate-400'
                    "
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>

                <span
                    v-if="getTime(branch.availability).is24Hours"
                    class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700"
                >
                    Open 24 Hours
                </span>

                <span v-else class="font-medium text-slate-600">
                    {{ getTime(branch.availability).label }}
                </span>
            </div>

            <div class="mt-auto pt-5">
                <button
                    @click="$emit('select', branch)"
                    class="w-full rounded-lg border border-slate-900 bg-slate-900 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800"
                >
                    View Provider
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { BranchRetrieve } from "~/types/branch";
import Location from "~/components/icons/location.vue";
import Logo from "~/assets/logo/logo.png";
import { getBranchTimeDisplay } from "~/utils/time";

const props = defineProps<{
    branch: BranchRetrieve;
    variant: 1 | 2;
}>();

defineEmits(["select"]);

const getTime = (availability: BranchRetrieve["availability"]) =>
    getBranchTimeDisplay(availability);
</script>
