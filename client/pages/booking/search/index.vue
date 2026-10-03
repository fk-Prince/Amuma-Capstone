<template>
    <div
        class="flex flex-col h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-surface dark:via-surface dark:to-surface"
    >
        <div class="relative w-full z-30 shrink-0 overflow-hidden bg-[#EEF3FB] pt-[130px] pb-6 dark:bg-secondary">
            <img
                :src="finderBg"
                class="pointer-events-none select-none absolute inset-0 h-full w-full object-cover"
                alt=""
            />
            <div
                class="absolute inset-0 bg-gradient-to-b from-[#EEF3FB]/85 via-[#EEF3FB]/80 to-[#EEF3FB] dark:from-secondary/85 dark:via-secondary/80 dark:to-secondary"
            />

            <div class="relative z-10 mx-auto max-w-[100rem] px-6">
                <NuxtLink
                    to="/"
                    class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:text-primary-600 dark:text-white/80 dark:hover:text-white"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    Back to Home
                </NuxtLink>

                <h1 class="text-2xl font-bold text-secondary sm:text-3xl dark:text-white">
                    Find a
                    <span class="text-primary">Provider</span>
                </h1>
                <p class="mt-1 text-sm text-muted dark:text-white/80">
                    Browse trusted caregivers and care services near you.
                </p>

                <div class="mt-6">
                    <Filter />
                </div>
            </div>
        </div>

        <div class="relative flex-1 min-h-0 overflow-hidden bg-[#EEF3FB] dark:bg-secondary">
            <img
                :src="finderBg"
                class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-10 dark:opacity-20"
                alt=""
            />
            <div
                class="absolute inset-0 bg-gradient-to-b from-[#EEF3FB]/95 via-[#EEF3FB] to-[#EEF3FB] dark:from-secondary/95 dark:via-secondary dark:to-secondary"
            />

            <div
                class="relative z-10 mx-auto flex h-full max-w-[100rem] flex-col px-6 py-8"
            >
                <div
                    v-if="!loading"
                    class="flex items-center justify-between gap-3 pb-4"
                >
                    <p class="text-sm text-muted dark:text-gray-400">
                        <span class="font-semibold text-secondary dark:text-white">{{
                            branches.length
                        }}</span>
                        of
                        <span class="font-semibold text-secondary dark:text-white">{{
                            totalCount
                        }}</span>
                        providers found in
                        <span class="font-semibold text-secondary dark:text-white">{{
                            (route.query.location as string) ??
                            DEFAULT_LOCATION.label
                        }}</span>
                    </p>

                    <div
                        class="flex shrink-0 gap-1 rounded-lg bg-slate-100 p-1 dark:bg-white/10"
                    >
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                viewMode === 'list'
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white/90'
                            "
                            @click="viewMode = 'list'"
                        >
                            <List class="h-3.5 w-3.5" />
                            List
                        </button>
                        <button
                            type="button"
                            class="hidden lg:flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                viewMode === 'both'
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white/90'
                            "
                            @click="viewMode = 'both'"
                        >
                            <Columns2 class="h-3.5 w-3.5" />
                            Split
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                viewMode === 'map'
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white/90'
                            "
                            @click="viewMode = 'map'"
                        >
                            <MapIcon class="h-3.5 w-3.5" />
                            Map
                        </button>
                    </div>
                </div>

                <div
                    class="flex min-h-0 flex-1 flex-col gap-6 lg:flex-row items-stretch"
                >
                    <div
                        class="flex-col min-h-0 overflow-hidden rounded-2xl lg:shrink-0"
                        :class="[
                            viewMode === 'map' ? 'hidden' : 'flex w-full',
                            viewMode === 'both' ? 'lg:w-[58%]' : 'lg:w-full',
                        ]"
                    >
                        <div class="flex-1 overflow-y-auto">
                            <SearchBooking
                                :branches="branches"
                                :loading="loading"
                                :compact="viewMode === 'both'"
                                @reset="resetFilters"
                            />

                            <div
                                v-if="!loading && hasMore"
                                class="flex justify-center py-8 px-4"
                            >
                                <button
                                    type="button"
                                    :disabled="loadingMore"
                                    @click="loadMore"
                                    class="inline-flex items-center gap-3 px-6 py-3 rounded-full border border-slate-300 bg-white text-sm font-medium text-slate-700 shadow-sm hover:shadow-md hover:border-primary hover:text-primary hover:bg-primary/5 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-secondary dark:text-gray-300 dark:border-white/10"
                                >
                                    <svg
                                        v-if="loadingMore"
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        class="animate-spin"
                                    >
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                                    </svg>
                                    {{
                                        loadingMore
                                            ? "Loading..."
                                            : "Load more results"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="relative w-full z-20 flex-1 min-h-0 flex-col rounded-2xl overflow-hidden shadow-sm border border-slate-200/50 bg-white dark:border-white/10 dark:bg-secondary"
                        :class="viewMode === 'list' ? 'hidden' : 'flex'"
                    >
                        <LocationPin
                            class="flex-1 h-full w-full z-20"
                            :locations="locations"
                            :center-lat="centerLat"
                            :center-lng="centerLng"
                        />

                        <Transition name="fade">
                            <div
                                v-if="loading && locations.length === 0"
                                class="absolute inset-0 z-30 flex items-center justify-center bg-white/80 backdrop-blur-md dark:bg-secondary/80"
                            >
                                <div class="flex flex-col items-center gap-4">
                                    <div
                                        class="relative w-12 h-12 flex items-center justify-center"
                                    >
                                        <div
                                            class="absolute inset-0 bg-primary/10 rounded-full animate-pulse"
                                        />
                                        <svg
                                            width="28"
                                            height="28"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            class="animate-spin text-primary relative z-10"
                                        >
                                            <path
                                                d="M21 12a9 9 0 1 1-6.219-8.56"
                                            />
                                        </svg>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-slate-700 dark:text-gray-300">
                                            Finding care near you
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1 dark:text-gray-400">
                                            Please wait while we search
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import Filter from "~/components/sections/booking/search/Filter.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import SearchBooking from "~/components/sections/booking/search/SearchBooking.vue";
import { branchService } from "~/api/branch/BranchService";
import type { BranchRetrieve } from "~/types/branch";
import { ArrowLeft, List, Map as MapIcon, Columns2 } from "lucide-vue-next";
import { useGeo } from "~/composables/useGeo";
import finderBg from "~/assets/images/finder-bg.png";

definePageMeta({
    layout: "default",
    navVariant: 3,
    middleware: ["prevent-staff-booking"],
});
useHead({ title: "Search Homecare" });

const route = useRoute();
const router = useRouter();
const { centerLat, centerLng, geocodeLocation } = useGeo();

const branches = ref<BranchRetrieve[]>([]);
const loading = ref(false);
const loadingMore = ref(false);
const page = ref(1);
const lastPage = ref(1);
const totalCount = ref(0);
const viewMode = ref<"list" | "map" | "both">("both");

const PER_PAGE = 15;

const DEFAULT_LOCATION = {
    label: "Davao City",
    lat: 7.1907,
    long: 125.4553,
};

const hasMore = computed(() => page.value < lastPage.value);

let requestId = 0;
const l = async (opts: { append?: boolean } = {}) => {
    const currentRequest = ++requestId;
    const append = opts.append ?? false;

    if (append) {
        loadingMore.value = true;
    } else {
        loading.value = true;
        page.value = 1;
    }

    try {
        if (!append && route.query.location) {
            await geocodeLocation(route.query.location as string);
        }

        const payload = {
            provider_name: route.query.provider_name ?? "",
            location: route.query.location ?? DEFAULT_LOCATION.label,
            lat: route.query.lat ?? DEFAULT_LOCATION.lat,
            long: route.query.long ?? DEFAULT_LOCATION.long,
            plan_code: route.query.plan_code ?? "",
            sort: route.query.sort ?? "recommended",
            per_page: PER_PAGE,
            page: page.value,
        };

        const res = await branchService.filtered(payload);

        if (currentRequest !== requestId) return;

        const newBranches = res?.data ?? [];
        branches.value = append
            ? [...branches.value, ...newBranches]
            : newBranches;

        lastPage.value = res?.meta?.last_page ?? 1;
        totalCount.value = res?.meta?.total ?? newBranches.length;
    } catch (err) {
        if (currentRequest !== requestId) return;

        console.error(err);
        if (!append) branches.value = [];
    } finally {
        if (currentRequest === requestId) {
            loading.value = false;
            loadingMore.value = false;
        }
    }
};

const loadMore = () => {
    if (loadingMore.value || !hasMore.value) return;
    page.value += 1;
    l({ append: true });
};

const resetFilters = () => {
    router.replace({
        query: {
            location: DEFAULT_LOCATION.label,
            lat: DEFAULT_LOCATION.lat,
            long: DEFAULT_LOCATION.long,
            plan_code: "",
            sort: "recommended",
        },
    });
};

onMounted(async () => {
    if (Object.keys(route.query).length === 0) {
        loading.value = true;
        await router.replace({
            query: {
                location: DEFAULT_LOCATION.label,
                lat: DEFAULT_LOCATION.lat,
                long: DEFAULT_LOCATION.long,
                plan_code: "C",
                sort: "recommended",
            },
        });
        return;
    }

    l();
});

watch(
    () => route.query,
    () => l(),
);

const locations = computed(() =>
    branches.value
        .filter((branch) => branch.location)
        .map((branch) => ({
            latitude: Number(branch.location.latitude),
            longitude: Number(branch.location.longitude),
            label: branch.name,
            street: branch.location.street,
            city: branch.location.city,
            province: branch.location.province,
            country: branch.location.country,
        })),
);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>a