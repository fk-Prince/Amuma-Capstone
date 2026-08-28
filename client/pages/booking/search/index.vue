<template>
    <div
        class="flex flex-col h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-white to-slate-50"
    >
        <div
            class="w-full z-40 border-b border-slate-200/50 bg-white backdrop-blur-sm"
        >
            <div class="mx-auto max-w-[100rem] px-4 pt-4">
                <Breadcrumb :items="[{ label: 'Find a Provider' }]" />
            </div>

            <div class="mx-auto max-w-[100rem]">
                <Filter />
            </div>
        </div>

        <div class="flex-1 overflow-hidden">
            <div class="mx-auto max-w-[100rem] md:px-4 py-8 h-full">
                <div
                    class="flex flex-col lg:flex-row gap-8 h-full items-stretch"
                >
                    <div
                        class="w-full lg:w-[60%] flex flex-col overflow-hidden rounded-2xl bg-white"
                    >
                        <div class="flex-1 overflow-y-auto">
                            <SearchBooking
                                :branches="branches"
                                :loading="loading"
                            />

                            <div
                                v-if="!loading && hasMore"
                                class="flex justify-center py-8 px-4"
                            >
                                <button
                                    type="button"
                                    :disabled="loadingMore"
                                    @click="loadMore"
                                    class="inline-flex items-center gap-3 px-6 py-3 rounded-full border border-slate-300 bg-white text-sm font-medium text-slate-700 shadow-sm hover:shadow-md hover:border-primary hover:text-primary hover:bg-primary/5 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
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
                        class="relative w-full lg:w-[40%] z-20 flex flex-col rounded-2xl overflow-hidden bg-white shadow-sm border border-slate-200/50"
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
                                class="absolute inset-0 z-30 flex items-center justify-center bg-gradient-to-b from-white/80 to-white/60 backdrop-blur-md"
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
                                        <p
                                            class="text-sm font-semibold text-slate-700"
                                        >
                                            Finding care near you
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
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
import Breadcrumb from "~/components/ui/Breadcrumb.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import SearchBooking from "~/components/sections/booking/search/SearchBooking.vue";
import { branchService } from "~/api/branch/BranchService";
import type { BranchRetrieve } from "~/types/branch";
import { useGeo } from "~/composables/useGeo";

definePageMeta({
    layout: "default",
    navVariant: 4,
    navTheme: "dark",
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

const PER_PAGE = 6;

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
</style>
