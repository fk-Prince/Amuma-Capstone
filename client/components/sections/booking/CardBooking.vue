<template>
    <div class="w-full min-h-screen">
        <div class="space-y-4">
            <div
                v-for="item in facilities"
                :key="item.id"
                class="flex overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
            >
                <div class="relative w-64 flex-shrink-0">
                    <img
                        :src="item.image || '/images/facility-placeholder.jpg'"
                        :alt="item.name"
                        class="h-full w-full object-cover"
                    />

                    <button
                        class="absolute top-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-white shadow"
                    >
                        <Icon
                            name="heroicons:heart"
                            class="h-5 w-5 text-gray-500"
                        />
                    </button>

                    <div
                        v-if="item.topRated"
                        class="absolute bottom-3 left-3 rounded-md bg-primary px-2 py-1 text-xs font-medium text-white"
                    >
                        Top Rated
                    </div>
                </div>

                <!-- Content -->
                <div class="flex flex-1 justify-between">
                    <div class="p-5">
                        <h3 class="text-xl font-semibold text-secondary">
                            {{ item.name }}
                        </h3>

                        <div class="mt-2 flex items-center gap-2">
                            <span
                                class="rounded bg-accent px-2 py-1 text-xs font-semibold text-white"
                            >
                                {{ item.rating }}
                            </span>

                            <span class="text-sm text-gray-600">
                                Excellent ({{ item.reviews }} reviews)
                            </span>
                        </div>

                        <div
                            class="mt-3 flex items-center gap-2 text-sm text-muted"
                        >
                            <Icon name="heroicons:map-pin" class="h-4 w-4" />
                            <span>{{ item.location }}</span>
                        </div>

                        <div class="mt-2 text-sm text-gray-600">
                            {{ item.type }}
                        </div>

                        <div
                            class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-medium"
                            :class="
                                item.availability === 'available'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-orange-100 text-orange-700'
                            "
                        >
                            {{ item.availability }}
                        </div>
                    </div>

                    <div
                        class="flex min-w-[220px] flex-col justify-center bg-light p-5"
                    >
                        <p class="text-sm text-gray-500">Starting from</p>

                        <h3 class="text-3xl font-bold text-secondary">
                            ₱4,234
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">Per month</p>

                        <NuxtLink
                            :to="`/facilities/${item.id}`"
                            class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 font-medium text-white transition hover:opacity-90"
                        >
                            View Details
                            <Icon
                                name="heroicons:chevron-right"
                                class="h-4 w-4"
                            />
                        </NuxtLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Filter from "~/components/sections/booking/Filter.vue";

const facilities = [
    {
        id: 1,
        name: "ENJ USDI Inc. Corp.",
        location: "Davao City",
        type: "Home Care Services",
        rating: 4.8,
        reviews: 150,
        availability: "available",
        topRated: false,
        image: null,
    },
    {
        id: 2,
        name: "Davao Viel Geriatric HomeCare Center",
        location: "Davao City",
        type: "Geriatric Care Facility",
        rating: 4.9,
        reviews: 180,
        availability: "available",
        topRated: false,
        image: null,
    },
    {
        id: 3,
        name: "Tahanan Caregiving Care Services",
        location: "Davao City",
        type: "Caregiving Services",
        rating: 4.7,
        reviews: 95,
        availability: "limited",
        topRated: false,
        image: null,
    },
    {
        id: 4,
        name: "Riemon Haven's Homecare Nursing Home",
        location: "Davao City",
        type: "Nursing Home",
        rating: 4.8,
        reviews: 130,
        availability: "available",
        topRated: false,
        image: null,
    },
    {
        id: 5,
        name: "Sunshine Care Home",
        location: "Davao City",
        type: "Elderly Care Facility",
        rating: 4.8,
        reviews: 120,
        availability: "available",
        topRated: false,
        image: null,
    },
    {
        id: 6,
        name: "Grace Elderly Home",
        location: "Davao City",
        type: "Nursing Care",
        rating: 4.6,
        reviews: 89,
        availability: "limited",
        topRated: false,
        image: null,
    },
    {
        id: 7,
        name: "Comfort Haven Care Center",
        location: "Davao City",
        type: "Home & Facility Care",
        rating: 4.9,
        reviews: 210,
        availability: "available",
        topRated: false,
        image: null,
    },
];
</script>
