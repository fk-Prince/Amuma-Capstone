<template>
    <div class="flex flex-col h-screen overflow-hidden bg-slate-100">
        <Filter />

        <div class="flex-1 overflow-hidden">
            <div class="mx-auto max-w-[100rem] px-4 py-6 h-full">
                <div
                    class="flex flex-col lg:flex-row gap-6 h-full items-stretch"
                >
                    <div
                        class="w-full lg:w-[60%] flex flex-col overflow-hidden rounded-xl"
                    >
                        <div class="flex-1 overflow-y-auto">
                            <SearchBooking
                                :branches="branches"
                                :loading="loading"
                            />
                        </div>
                    </div>

                    <div
                        class="w-full lg:w-[40%] flex flex-col rounded-xl overflow-hidden"
                    >
                        <LocationPin
                            class="flex-1 h-full w-full"
                            :locations="locations"
                            :center-lat="centerLat"
                            :center-lng="centerLng"
                        />
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
import { useGeo } from "~/composables/useGeo";

useHead({ title: "Search Homecare" });

const route = useRoute();
const router = useRouter();
const { centerLat, centerLng, geocodeLocation } = useGeo();

const branches = ref<BranchRetrieve[]>([]);
const loading = ref(false);

const DEFAULT_LOCATION = {
    label: "Davao City",
    lat: 7.1907,
    long: 125.4553,
};

const l = async () => {
    loading.value = true;

    try {
        if (route.query.location) {
            await geocodeLocation(route.query.location as string);
        }

        const payload = {
            provider_name: route.query.provider_name ?? "",
            location: route.query.location ?? DEFAULT_LOCATION.label,
            lat: route.query.lat ?? DEFAULT_LOCATION.lat,
            long: route.query.long ?? DEFAULT_LOCATION.long,
            plan_code: route.query.plan_code ?? "",
            per_page: route.query.per_page ?? 6,
        };

        const res = await branchService.filtered(payload);
        branches.value = res?.data ?? [];
        console.log(branches.value);
    } catch (err) {
        console.error(err);
        branches.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    if (Object.keys(route.query).length === 0) {
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
