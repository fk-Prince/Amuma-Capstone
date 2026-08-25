<template>
    <div class="min-h-screen flex flex-col bg-slate-50 relative">
        <div class="mx-auto max-w-[100rem] bg-white">
            <!-- <div class="px-6 sm:px-12">
                <Navbar @change="scrollToSection" />
            </div> -->

            <div class="px-6 sm:px-12 pt-5">
                <Breadcrumb
                    :items="[
                        { label: 'Find a Provider', to: '/booking/search' },
                        { label: branch?.name ?? 'Provider' },
                    ]"
                />
            </div>

            <div class="px-6 sm:px-12 scroll-mt-20" ref="overviewRef">
                <ProviderImage
                    :loading="loading"
                    :primaryImage="branch?.image"
                    :secondaryImage="branch?.images"
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
                        <!-- <div
                            class="py-8 scroll-mt-20"
                            v-if="!loading"
                            ref="servicesRef"
                        >
                            <ServiceSection />
                        </div> -->

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
                            v-if="branch"
                            :has-homecare="hasHomecare"
                            :has-facility="hasFacility"
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
import ReviewSection from "~/components/sections/booking/provider/Review.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import Location from "~/components/icons/location.vue";
import Breadcrumb from "~/components/ui/Breadcrumb.vue";
import { useBranch } from "~/composables/useBranchProvider";
useHead({ title: "Search Homecare" });
definePageMeta({
    navVariant: 4,
    navTheme: "dark",
});

const route = useRoute();
const uuid = computed(() => route.params.branch_uuid as string);

const { loading, branch, fetchBranch } = useBranch();

watch(
    uuid,
    (id) => {
        if (id) fetchBranch(id);
    },
    { immediate: true },
);

const hasHomecare = computed(
    () =>
        branch.value?.subscriptions?.some((s) =>
            ["A", "C"].includes(s.plans.plan_code),
        ) ?? false,
);

const hasFacility = computed(
    () =>
        branch.value?.subscriptions?.some((s) =>
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
    // FOVITE API ?>?
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
