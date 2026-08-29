<template>
    <div
        v-if="variant === 1"
        class="group rounded-2xl border border-primary-200 bg-white overflow-hidden cursor-pointer shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:bg-secondary"
        @click="$emit('select', branch)"
    >
        <div class="relative h-40 overflow-hidden bg-muted-light dark:bg-white/10">
            <img
                v-if="branch?.image"
                :src="branch.image"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                alt="branch image"
            />

            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-50 via-white to-accent-50 dark:from-primary-500/10 dark:via-secondary dark:to-accent-500/10"
            >
                <img
                    :src="Logo"
                    class="h-16 w-16 object-contain opacity-70"
                    alt="default logo"
                />
            </div>

            <div
                v-if="branch?.image"
                class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/40 to-transparent"
            />

            <span
                class="absolute left-3 top-3 rounded-md bg-white/95 px-2.5 py-1 text-xs font-semibold text-accent-700 shadow-sm backdrop-blur-sm dark:bg-secondary/90 dark:text-accent-400"
            >
                {{ getTime(branch.settings).label }}
            </span>

            <div
                class="absolute right-3 top-3 flex items-center gap-1 rounded-md bg-white/95 px-2.5 py-1 shadow-sm backdrop-blur-sm dark:bg-secondary/90"
            >
                <Star class="h-3 w-3 text-amber-400 fill-amber-400" />

                <span class="text-xs font-semibold text-secondary dark:text-white">
                    {{ branch.averageRating ?? "0.0" }}
                </span>

                <span v-if="branch.reviewCount > 0" class="text-xs text-muted dark:text-gray-400">
                    ({{ branch.reviewCount }})
                </span>
            </div>
        </div>

        <div class="p-4">
            <h3
                class="truncate font-semibold text-secondary transition-colors group-hover:text-primary dark:text-white"
            >
                {{ branch.name }}
            </h3>

            <p class="mt-1 flex items-center gap-1 text-xs text-muted dark:text-gray-400">
                <Location class="h-3.5 w-3.5 shrink-0" />
                <span class="truncate"
                    >{{ branch.location.street }},
                    {{ branch.location.city }}</span
                >
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                <template
                    v-for="subscription in branch.subscriptions"
                    :key="subscription.subscription_id"
                >
                    <template v-if="subscription.plans.name === 'Hybrid'">
                        <span
                            class="text-xs px-2 py-1 rounded-full font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/10"
                        >
                            Homecare Services
                        </span>

                        <span
                            class="text-xs px-2 py-1 rounded-full font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/10"
                        >
                            In-House Facility
                        </span>
                    </template>

                    <span
                        v-else
                        class="text-xs px-2 py-1 rounded-full font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/10"
                    >
                        {{ subscription.plans.name }}
                    </span>
                </template>
            </div>
        </div>

        <div
            class="flex items-center justify-between gap-2 border-t border-muted-light bg-light px-4 py-3 dark:bg-white/5 dark:border-white/10"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                :class="
                    branch.settings.is_open
                        ? 'bg-accent-50 text-accent-700'
                        : 'bg-danger/10 text-danger'
                "
            >
                <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="
                        branch.settings.is_open ? 'bg-accent-500' : 'bg-danger'
                    "
                />
                {{ branch.settings.is_open ? "Open" : "Closed" }}
            </span>

            <button
                @click.stop="$emit('select', branch)"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-primary-600"
            >
                Book Now
                <ArrowRight class="h-3 w-3" />
            </button>
        </div>
    </div>

    <div
        v-else-if="variant === 2"
        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-md hover:border-slate-300 cursor-pointer sm:flex-row dark:bg-secondary dark:border-white/10"
    >
        <div
            class="relative h-48 w-full shrink-0 overflow-hidden bg-slate-100 sm:h-auto sm:w-64 dark:bg-white/10"
        >
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
                class="absolute left-3 bg-white/90 top-3 rounded-md border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide backdrop-blur-sm dark:bg-secondary/90"
                :class="
                    branch.settings.is_open
                        ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400'
                        : 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-400'
                "
            >
                {{ branch.settings.is_open ? "Open Now" : "Closed" }}
            </span>

            <div
                class="absolute right-3 top-3 flex items-center gap-1 rounded-md bg-white/90 px-2.5 py-1 shadow-sm backdrop-blur-sm dark:bg-secondary/90"
            >
                <!-- <span class="text-amber-500 text-xs">★</span> -->
                <Star class="h-3 w-3 text-orange-400 fill-orange-400" />

                <span class="text-xs font-semibold text-slate-700 dark:text-gray-300">
                    {{ branch.averageRating ?? "0.0" }}
                </span>

                <span
                    v-if="branch.reviewCount > 0"
                    class="text-xs text-slate-500 dark:text-gray-400"
                >
                    ({{ branch.reviewCount }})
                </span>
            </div>
        </div>

        <div class="flex flex-1 flex-col p-6">
            <div>
                <h3
                    class="line-clamp-1 text-lg font-semibold tracking-tight text-slate-900 transition group-hover:text-primary dark:text-white"
                >
                    {{ branch.name }}
                </h3>

                <div
                    class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-500 dark:text-gray-400"
                >
                    <Location class="h-4 w-4 shrink-0" />
                    <span class="line-clamp-1">
                        {{ branch.location.street }},
                        {{ branch.location.city }},
                        {{ branch.location.province }}
                    </span>
                </div>

                <p
                    class="mt-3 w-[90%] mx-auto text-sm leading-6 text-slate-600 line-clamp-3 dark:text-gray-300"
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
                            class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-secondary dark:border-white/10 dark:text-gray-300"
                        >
                            Homecare Service
                        </span>
                        <span
                            class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-secondary dark:border-white/10 dark:text-gray-300"
                        >
                            Inhouse Facility
                        </span>
                    </template>

                    <span
                        v-else
                        class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-secondary dark:border-white/10 dark:text-gray-300"
                    >
                        {{ p.plans.name }}
                    </span>
                </template>
            </div>

            <div class="mt-4 flex items-center gap-2 text-sm">
                <svg
                    class="h-4 w-4 shrink-0"
                    :class="
                        getTime(branch.settings).is24Hours
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
                    v-if="getTime(branch.settings).is24Hours"
                    class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400"
                >
                    Open 24 Hours
                </span>

                <span v-else class="font-medium text-slate-600 dark:text-gray-300">
                    {{ getTime(branch.settings).label }}
                </span>
            </div>

            <div class="mt-auto pt-5">
                <button
                    @click="$emit('select', branch)"
                    class="w-full rounded-lg border border-slate-900 bg-slate-900 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800 dark:border-primary dark:bg-primary dark:hover:bg-primary-600"
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
import { Star, ArrowRight } from "lucide-vue-next";
import { getBranchTimeDisplay } from "~/utils/time";

const props = defineProps<{
    branch: BranchRetrieve;
    variant: 1 | 2;
}>();

defineEmits(["select"]);

const getTime = (settings: BranchRetrieve["settings"]) =>
    getBranchTimeDisplay(settings);
</script>
