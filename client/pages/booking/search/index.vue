<template>
    <div class="flex flex-col h-screen overflow-hidden">
        <Filter />

        <div
            class="flex-1 overflow-hidden justify-center mx-auto w-full max-w-[95%] px-4 py-6 flex flex-col md:flex-row gap-6"
        >
            <div class="w-full md:w-2/5 overflow-y-auto">
                <SearchBooking :branches="branches" :loading="loading" />
            </div>

            <div class="w-full md:w-2/5 bg-secondary rounded-xl">
                <LocationPin
                    :key="`map-${locations.length}-${centerLat}-${centerLng}`"
                    :locations="locations"
                    :center-lat="centerLat"
                    :center-lng="centerLng"
                />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, computed } from "vue";
import Filter from "~/components/sections/booking/Filter.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import SearchBooking from "~/components/sections/booking/SearchBooking.vue";
import { branchService } from "~/api/branch/BranchService";
import type { BranchRetrieve } from "~/types/branch";
import { useGeo } from "~/composables/useGeo";
import { useRoute } from "vue-router";

useHead({ title: "Search Homecare" });

const route = useRoute();
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
        const res = await branchService.filtered({
            provider_name: route.query.provider_name ?? "",
            location: route.query.location ?? DEFAULT_LOCATION.label,
            lat: route.query.lat ?? DEFAULT_LOCATION.lat,
            long: route.query.long ?? DEFAULT_LOCATION.long,
            plan_code: route.query.plan_code ?? "",
            per_page: route.query.per_page ?? 6,
        });

        branches.value = res?.data ?? [];
        loading.value = false;
    } catch (err) {
        console.error(err);
        branches.value = [];
        return [];
    } finally {
        loading.value = false;
    }
};

watch(
    () => route.query,
    () => l(),
    { immediate: true },
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
