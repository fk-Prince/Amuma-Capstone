<template>
    <div class="flex flex-col h-screen overflow-hidden">
        <Filter
            :search-name="searchName"
            :search-location="searchLocation"
            :care-type="careType"
        />

        <div
            class="flex-1 overflow-hidden justify-center mx-auto w-full max-w-[95%] px-4 py-6 flex flex-col md:flex-row gap-6"
        >
            <div class="w-full md:w-2/5 overflow-y-auto">
                <SearchBooking />
            </div>

            <div class="w-full md:w-2/5 bg-secondary rounded-xl">
                <LocationPin
                    :locations="locations"
                    :center-lat="centerLat"
                    :center-lng="centerLng"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import Filter from "~/components/sections/booking/Filter.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import SearchBooking from "~/components/sections/booking/SearchBooking.vue";
import { branchService } from "~/api/branch/BranchService";
import type { BranchRetrieve } from "~/types/branch";
import { useGeo } from "~/composables/useGeo";
import { useRoute } from "vue-router";

useHead({ title: "Search" });

const route = useRoute();
const { centerLat, centerLng, geocodeLocation } = useGeo();
const branches = ref<BranchRetrieve[]>([]);

const searchName = computed(() => (route.query.service as string) ?? "");
const searchLocation = computed(() => (route.query.location as string) ?? "");
const careType = computed(() => (route.query.type as string) ?? "all");

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

onMounted(async () => {
    if (searchLocation.value?.trim()) {
        await geocodeLocation(searchLocation.value);
    }

    try {
        const res = await branchService.featured({ per_page: 9 });
        branches.value = res?.data ?? [];
    } catch (err) {
        console.error(err);
        branches.value = [];
    }
});
</script>
