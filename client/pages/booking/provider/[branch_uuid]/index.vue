<!-- <template>
    <div class="min-h-screen flex flex-col bg-slate-100 relative">
        <div class="w-full md:max-w-[70%] max-w-[90%] mx-auto bg-white px-12">
            <Navbar @change="scrollToSection" />
            <div class="flex flex-col gap-8">
                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-5 scroll-mt-20"
                    ref="overviewRef"
                >
                    <div class="order-1 md:order-1 flex flex-col py-4 gap-2">
                        <ProviderImage
                            :loading="loading"
                            :primaryImage="branch?.image"
                            :secondaryImage="branch?.secondaryImage"
                        />
                    </div>

                    <div class="order-2 md:order-2">
                        <HeroSection
                            :branch="branch"
                            :loading="loading"
                            @homecare="homecare"
                            @facility="facility"
                        />
                    </div>
                </div>
            </div>

            <div
                class="max-w-6xl flex flex-col py-8 scroll-mt-20"
                v-if="!loading"
                ref="servicesRef"
            >
                <ServiceSection />
            </div>

            <div
                class="max-w-6xl flex flex-col py-8 scroll-mt-20"
                v-if="!loading"
                ref="reviewsRef"
            >
                <ReviewSection :branch-uuid="uuid" />
            </div>

            <div class="flex flex-col gap-2 w-full py-10" ref="locationRef">
                <LocationPin
                    v-if="branch?.location"
                    :center-lat="Number(branch.location.latitude)"
                    :center-lng="Number(branch.location.longitude)"
                />

                <div v-if="branch?.location" class="bg-white p-5">
                    <div class="flex items-start gap-3">
                        <div
                            class="rounded-full bg-primary/10 p-3 text-primary"
                        >
                            <Location class="h-5 w-5" />
                        </div>

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Visit Our Location
                            </h3>

                            <p class="mt-2 text-sm leading-7 text-gray-600">
                                <span class="font-medium text-gray-900">
                                    {{ branch.name }}
                                </span>
                                is conveniently located at
                                <span class="font-medium text-gray-900">
                                    {{ branch.location.street }},
                                    {{ branch.location.city }},
                                    {{ branch.location.province }},
                                    {{ branch.location.country }} </span
                                >. Whether you're visiting for a consultation,
                                treatment, or scheduled care, our location is
                                easily accessible and ready to welcome you.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import ProviderImage from "~/components/sections/booking/provider/ProviderImage.vue";
import Navbar from "~/components/sections/booking/provider/Navbar.vue";
import HeroSection from "~/components/sections/booking/provider/HeroSection.vue";
import ServiceSection from "~/components/sections/booking/provider/ServiceSection.vue";
import ReviewSection from "~/components/sections/booking/provider/Review.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import Location from "~/components/icons/location.vue";

import { branchService } from "~/api/branch/BranchService";
import type { BranchRetrieve } from "~/types/branch";

useHead({ title: "Search Homecare" });
definePageMeta({ navVariant: 3 });

const route = useRoute();
const uuid = computed(() => route.params.branch_uuid as string);

const branch = ref<BranchRetrieve | null>(null);
const loading = ref(true);

const fetchBranch = async (id: string) => {
    loading.value = true;
    try {
        const res: any = await branchService.get(id);
        branch.value = res;
    } finally {
        loading.value = false;
    }
};

watch(
    uuid,
    (id) => {
        if (id) fetchBranch(id);
    },
    { immediate: true },
);

const homecare = () => {
    navigateTo(`/booking/provider/${uuid.value}/details?category=homecare`);
};

const facility = () => {
    navigateTo(`/booking/provider/${uuid.value}/details?category=facility`);
};

const overviewRef = ref<HTMLElement | null>(null);
const servicesRef = ref<HTMLElement | null>(null);
const reviewsRef = ref<HTMLElement | null>(null);
const locationRef = ref<HTMLElement | null>(null);
const sections = [overviewRef, servicesRef, reviewsRef, locationRef];

const scrollToSection = (index: number) => {
    sections[index]?.value?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
};
</script> -->

<template>
    <div class="min-h-screen flex flex-col bg-slate-50 relative">
        <div class="w-full md:max-w-[70%] max-w-[90%] mx-auto bg-white">
            <div class="px-6 sm:px-12">
                <Navbar @change="scrollToSection" />
            </div>

            <div class="px-6 sm:px-12 scroll-mt-20" ref="overviewRef">
                <ProviderImage
                    :loading="loading"
                    :primaryImage="branch?.image"
                    :secondaryImage="branch?.secondaryImage"
                    @favorite="toggleFavorite"
                />
            </div>

            <div class="px-6 sm:px-12 pb-28 lg:pb-16">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mt-2">
                    <div
                        class="lg:col-span-2 flex flex-col divide-y divide-gray-100"
                    >
                        <HeroSection :branch="branch" :loading="loading" />
                        <div
                            class="flex flex-col gap-2 w-full py-8"
                            ref="locationRef"
                        >
                            <LocationPin
                                v-if="branch?.location"
                                :center-lat="Number(branch.location.latitude)"
                                :center-lng="Number(branch.location.longitude)"
                            />

                            <div v-if="branch?.location" class="pt-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-full bg-primary/10 p-3 text-primary"
                                    >
                                        <Location class="h-5 w-5" />
                                    </div>

                                    <div class="flex-1">
                                        <h3
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            Visit Our Location
                                        </h3>

                                        <p
                                            class="mt-2 text-sm leading-7 text-gray-600"
                                        >
                                            <span
                                                class="font-medium text-gray-900"
                                            >
                                                {{ branch.name }}
                                            </span>
                                            is conveniently located at
                                            <span
                                                class="font-medium text-gray-900"
                                            >
                                                {{ branch.location.street }},
                                                {{ branch.location.city }},
                                                {{ branch.location.province }},
                                                {{
                                                    branch.location.country
                                                }} </span
                                            >. Whether you're visiting for a
                                            consultation, treatment, or
                                            scheduled care, our location is
                                            easily accessible and ready to
                                            welcome you.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="py-8 scroll-mt-20"
                            v-if="!loading"
                            ref="servicesRef"
                        >
                            <ServiceSection />
                        </div>

                        <div
                            class="py-8 scroll-mt-20"
                            v-if="!loading"
                            ref="reviewsRef"
                        >
                            <ReviewSection :branch-uuid="uuid" />
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <BookingCard
                            :has-homecare="hasHomecare"
                            :has-facility="hasFacility"
                            :loading="loading"
                            @homecare="homecare"
                            @facility="facility"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ProviderImage from "~/components/sections/booking/provider/ProviderImage.vue";
import Navbar from "~/components/sections/booking/provider/Navbar.vue";
import HeroSection from "~/components/sections/booking/provider/HeroSection.vue";
import BookingCard from "~/components/sections/booking/provider/BookingCard.vue";
import ServiceSection from "~/components/sections/booking/provider/ServiceSection.vue";
import ReviewSection from "~/components/sections/booking/provider/Review.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import Location from "~/components/icons/location.vue";

import { branchService } from "~/api/branch/BranchService";
import type { BranchRetrieve } from "~/types/branch";

useHead({ title: "Search Homecare" });
definePageMeta({ navVariant: 1 });

const route = useRoute();
const uuid = computed(() => route.params.branch_uuid as string);

const branch = ref<BranchRetrieve | null>(null);
const loading = ref(true);

const fetchBranch = async (id: string) => {
    loading.value = true;
    try {
        const res: any = await branchService.get(id);
        branch.value = res;
    } finally {
        loading.value = false;
    }
};

watch(
    uuid,
    (id) => {
        if (id) fetchBranch(id);
    },
    { immediate: true },
);

const hasHomecare = computed(
    () =>
        branch.value?.subscriptions.some((s) =>
            ["A", "C"].includes(s.plans.plan_code),
        ) ?? false,
);
const hasFacility = computed(
    () =>
        branch.value?.subscriptions.some((s) =>
            ["B", "C"].includes(s.plans.plan_code),
        ) ?? false,
);

const homecare = () => {
    navigateTo(`/booking/provider/${uuid.value}/details?category=homecare`);
};

const facility = () => {
    navigateTo(`/booking/provider/${uuid.value}/details?category=facility`);
};

const toggleFavorite = () => {
    // wire up to favorites API
};

const overviewRef = ref<HTMLElement | null>(null);
const servicesRef = ref<HTMLElement | null>(null);
const reviewsRef = ref<HTMLElement | null>(null);
const locationRef = ref<HTMLElement | null>(null);
const sections = [overviewRef, servicesRef, reviewsRef, locationRef];

const scrollToSection = (index: number) => {
    sections[index]?.value?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
};
</script>
