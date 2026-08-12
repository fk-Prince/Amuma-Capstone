<!-- <template>
    <div class="p-6 max-w-md">
        <LocationInput
            v-model="locationId"
            label="Location"
            placeholder="Search for a location..."
            @select="handleLocationSelect"
        />

        <div
            v-if="selectedLocation"
            class="mt-4 rounded-lg bg-slate-50 p-4 text-sm"
        >
            <p class="font-semibold text-slate-700">Selected Location</p>

            <p class="mt-1 text-slate-500">
                {{ selectedLocation.name }}
            </p>

            <p class="text-slate-400">
                {{ selectedLocation.province }},
                {{ selectedLocation.country }}
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref } from "vue";
import LocationInput from "~/components/ui/LocationInput.vue";

const locationId = ref<number | string>("");

const selectedLocation = ref<any>(null);

const handleLocationSelect = (location: any) => {
    selectedLocation.value = location;

    console.log("Selected location:", location);
};
</script> -->

<template>
    <div></div>
</template>
