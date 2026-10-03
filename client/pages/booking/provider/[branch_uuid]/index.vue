<template>
    <div class="mx-auto w-full max-w-[100rem] px-4 pb-28 pt-[144px] sm:px-6 lg:pb-16">
        <ProviderBanner
            ref="bannerRef"
            :branch="branch"
            :loading="loading"
            @back="goBack"
            @favorite="toggleFavorite"
        />

        <!-- Tabs -->
        <div
            class="mt-5 flex overflow-x-auto border-b border-gray-200 dark:border-white/10"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="relative shrink-0 px-5 pb-3.5 pt-2 text-sm font-medium transition-colors"
                :class="
                    activeTab === tab.key
                        ? 'text-primary'
                        : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'
                "
                @click="goToTab(tab.key)"
            >
                {{ tab.label }}
                <span
                    v-if="activeTab === tab.key"
                    class="absolute inset-x-0 -bottom-px h-[2px] rounded-full bg-primary"
                />
            </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left column -->
            <div class="flex flex-col gap-5 lg:col-span-2">
                <!-- About -->
                <section ref="overviewRef" :class="[cardClass, 'scroll-mt-40']">
                    <template v-if="loading">
                        <div class="flex animate-pulse flex-col gap-3">
                            <div class="h-6 w-56 rounded-md bg-gray-200 dark:bg-white/10" />
                            <div class="h-4 w-full rounded bg-gray-200 dark:bg-white/10" />
                            <div class="h-4 w-2/3 rounded bg-gray-200 dark:bg-white/10" />
                            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div
                                    v-for="n in 4"
                                    :key="n"
                                    class="h-10 rounded-xl bg-gray-200 dark:bg-white/10"
                                />
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-xl font-bold text-secondary dark:text-white">
                                About {{ branch?.name }}
                            </h2>

                            <span
                                v-if="isVerified"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-300"
                            >
                                <ShieldCheck class="h-4 w-4" />
                                Verified Provider
                            </span>
                        </div>

                        <p
                            v-if="branch?.description"
                            class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300"
                        >
                            {{ branch.description }}
                        </p>

                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div
                                v-for="item in highlights"
                                :key="item.label"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary/15"
                                >
                                    <component :is="item.icon" class="h-5 w-5" />
                                </span>
                                <span class="text-xs font-medium leading-tight text-gray-600 dark:text-gray-300">
                                    {{ item.label }}
                                </span>
                            </div>
                        </div>
                    </template>
                </section>

                <!-- Location -->
                <section ref="locationRef" :class="[cardClass, 'scroll-mt-40']">
                    <div v-if="loading" class="flex animate-pulse flex-col gap-4">
                        <div class="h-[220px] w-full rounded-2xl bg-gray-200 dark:bg-white/10" />
                        <div class="h-5 w-48 rounded-md bg-gray-200 dark:bg-white/10" />
                        <div class="h-4 w-full rounded bg-gray-200 dark:bg-white/10" />
                        <div class="h-4 w-3/5 rounded bg-gray-200 dark:bg-white/10" />
                    </div>

                    <template v-else-if="branch?.location">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary/15"
                            >
                                <Location class="h-5 w-5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-bold text-secondary dark:text-white">
                                    Visit Our Location
                                </h3>

                                <p class="mt-1.5 text-sm leading-7 text-gray-600 dark:text-gray-300">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ branch.name }}
                                    </span>
                                    is conveniently located at
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ branch.location.street }},
                                        {{ branch.location.city }},
                                        {{ branch.location.province }},
                                        {{ branch.location.country }} </span
                                    >. Whether you're visiting for a
                                    consultation, treatment, or scheduled care,
                                    our location is easily accessible and ready
                                    to welcome you.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-2xl">
                            <LocationPin
                                :center-lat="Number(branch.location.latitude)"
                                :center-lng="Number(branch.location.longitude)"
                            />
                        </div>

                        <a
                            :href="mapsUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-primary/20 bg-primary-50/60 px-4 py-3 text-sm font-medium text-primary transition hover:bg-primary-50 dark:border-primary/30 dark:bg-primary/10 dark:hover:bg-primary/15"
                        >
                            <span class="inline-flex items-center gap-2">
                                <MapPin class="h-4 w-4" />
                                View on Google Maps
                            </span>
                            <ExternalLink class="h-4 w-4" />
                        </a>
                    </template>
                </section>

                <!-- Reviews -->
                <div v-if="loading" :class="cardClass">
                    <div class="flex animate-pulse flex-col gap-5">
                        <div class="h-6 w-40 rounded-md bg-gray-200 dark:bg-white/10" />

                        <div class="h-32 w-full rounded-2xl bg-gray-200 dark:bg-white/10" />

                        <div
                            v-for="n in 2"
                            :key="n"
                            class="flex gap-3 border-t border-gray-100 pt-5 dark:border-white/10"
                        >
                            <div class="h-10 w-10 shrink-0 rounded-full bg-gray-200 dark:bg-white/10" />
                            <div class="flex-1 space-y-2">
                                <div class="h-4 w-28 rounded bg-gray-200 dark:bg-white/10" />
                                <div class="h-3 w-full rounded bg-gray-200 dark:bg-white/10" />
                                <div class="h-3 w-4/5 rounded bg-gray-200 dark:bg-white/10" />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else ref="reviewsRef" class="scroll-mt-40">
                    <ReviewSection :branch-uuid="uuid" />
                </div>
            </div>

            <!-- Right column -->
            <div class="lg:col-span-1">
                <div ref="servicesRef" class="flex scroll-mt-40 flex-col gap-4 lg:sticky lg:top-40">
                    <div
                        v-if="loading"
                        class="animate-pulse rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
                    >
                        <div class="h-7 w-40 rounded-md bg-gray-200 dark:bg-white/10" />

                        <div class="mt-6 space-y-3">
                            <div class="h-[72px] w-full rounded-xl bg-gray-200 dark:bg-white/10" />
                            <div class="h-[72px] w-full rounded-xl bg-gray-200 dark:bg-white/10" />
                        </div>

                        <div class="mt-6 h-11 w-full rounded-xl bg-gray-200 dark:bg-white/10" />
                    </div>

                    <BookingCard
                        v-else-if="branch"
                        ref="bookingCardRef"
                        :has-homecare="hasHomecare"
                        :has-facility="hasFacility"
                        @homecare="homecare"
                        @facility="facility"
                    />

                    <div
                        v-if="!loading"
                        class="hidden rounded-2xl border border-primary/15 bg-primary-50/50 p-5 lg:block dark:border-primary/25 dark:bg-primary/10"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-white"
                            >
                                <Info class="h-5 w-5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-bold text-secondary dark:text-white">
                                    Need Help?
                                </h3>
                                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                    Have questions or need assistance? Our team
                                    is here to help you.
                                </p>

                                <NuxtLink
                                    to="/company"
                                    class="mt-3 inline-flex items-center gap-2 rounded-lg border border-primary/30 bg-white px-4 py-2 text-xs font-semibold text-primary transition hover:bg-primary-50 dark:bg-transparent dark:hover:bg-white/5"
                                >
                                    <Phone class="h-3.5 w-3.5" />
                                    Contact Us
                                </NuxtLink>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import {
    ExternalLink,
    HeartHandshake,
    Info,
    Clock,
    MapPin,
    Phone,
    ShieldCheck,
    Users,
} from "lucide-vue-next";
import ProviderBanner from "~/components/sections/booking/provider/ProviderBanner.vue";
import BookingCard from "~/components/sections/booking/provider/BookingCard.vue";
import ReviewSection from "~/components/sections/booking/provider/Review.vue";
import LocationPin from "~/components/ui/LocationPin.vue";
import Location from "~/components/icons/location.vue";
import { providerNavList } from "~/config/publicMenu";
import { getBranchTimeDisplay } from "~/utils/time";
import { useBranch } from "~/composables/useBranchProvider";

useHead({ title: "Search Homecare" });
definePageMeta({
    navVariant: 7,
    navTheme: "light",
    navList: providerNavList,
    middleware: ["prevent-staff-booking"],
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

const cardClass =
    "rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary";

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

// The provider endpoint doesn't return `status` yet, so the badge stays
// hidden until BranchResource sends it (see notes).
const isVerified = computed(
    () => (branch.value as any)?.status === "verified",
);

const highlights = computed(() => {
    const time = getBranchTimeDisplay(branch.value?.settings);

    return [
        { label: "Professional Caregivers", icon: Users },
        { label: "Personalized Care", icon: HeartHandshake },
        { label: "Safe & Trusted", icon: ShieldCheck },
        {
            label: time.is24Hours ? "24/7 Support" : `Open ${time.label}`,
            icon: Clock,
        },
    ];
});

const mapsUrl = computed(() => {
    const loc = branch.value?.location;
    if (!loc) return "#";
    return `https://www.google.com/maps/search/?api=1&query=${loc.latitude},${loc.longitude}`;
});

const homecare = () => {
    navigateTo(`/booking/provider/${uuid.value}/details?category=homecare`);
};

const facility = () => {
    navigateTo(`/booking/provider/${uuid.value}/details?category=facility`);
};

const goBack = () => {
    navigateTo("/booking/search");
};

const toggleFavorite = () => {
    // FOVITE API ?>?
};

/* ---------- tabs ---------- */
const bannerRef = ref<any>(null);
const bookingCardRef = ref<any>(null);
const overviewRef = ref<HTMLElement | null>(null);
const servicesRef = ref<HTMLElement | null>(null);
const reviewsRef = ref<HTMLElement | null>(null);
const locationRef = ref<HTMLElement | null>(null);

type TabKey = "overview" | "services" | "reviews" | "location" | "gallery";

const tabs: { key: TabKey; label: string }[] = [
    { key: "overview", label: "Overview" },
    { key: "services", label: "Services" },
    { key: "reviews", label: "Reviews" },
    { key: "location", label: "Location" },
    { key: "gallery", label: "Gallery" },
];

const activeTab = ref<TabKey>("overview");

const scrollTo = (el: HTMLElement | null) =>
    el?.scrollIntoView({ behavior: "smooth", block: "start" });

const goToTab = (key: TabKey) => {
    if (key === "gallery") {
        bannerRef.value?.openGallery?.();
        return;
    }

    activeTab.value = key;

    if (key === "overview") scrollTo(overviewRef.value);
    if (key === "reviews") scrollTo(reviewsRef.value);
    if (key === "location") scrollTo(locationRef.value);

    if (key === "services") {
        // The service card is a bottom sheet on small screens.
        if (window.innerWidth < 1024) bookingCardRef.value?.openSheet?.();
        else scrollTo(servicesRef.value);
    }
};

// Keeps the highlighted tab in step with the section being read.
let ticking = false;
const onScroll = () => {
    if (ticking) return;
    ticking = true;

    requestAnimationFrame(() => {
        ticking = false;

        const sections: [TabKey, HTMLElement | null][] = [
            ["overview", overviewRef.value],
            ["location", locationRef.value],
            ["reviews", reviewsRef.value],
        ];

        let current: TabKey = "overview";
        for (const [key, el] of sections) {
            if (el && el.getBoundingClientRect().top <= 180) current = key;
        }

        if (activeTab.value !== "services") activeTab.value = current;
        else if (current !== "overview") activeTab.value = current;
    });
};

onMounted(() => window.addEventListener("scroll", onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener("scroll", onScroll));
</script>