<template>
    <section class="pt-[50px] relative overflow-hidden bg-secondary">
        <img
            :src="logo"
            class="pointer-events-none select-none absolute inset-0 w-full h-full object-cover"
            alt=""
        />
        <div
            class="absolute inset-0 bg-gradient-to-r from-secondary via-secondary/90 to-secondary/70"
        ></div>

        <div class="relative z-10 max-w-[90rem] mx-auto px-6 py-20 lg:py-28">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="max-w-xl">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-1.5 h-1.5 rounded-full bg-accent-400"
                        ></span>
                        <span
                            class="text-xs font-medium tracking-[0.16em] uppercase text-white/60"
                        >
                            Trusted home care services
                        </span>
                    </div>

                    <h1
                        class="mt-5 text-4xl md:text-5xl lg:text-6xl font-medium text-white leading-[1.08] tracking-tight"
                    >
                        Find quality
                        <br />
                        <span class="text-primary-300">care near you</span>
                    </h1>

                    <p
                        class="mt-6 text-base md:text-lg text-white/60 leading-relaxed max-w-md"
                    >
                        Book professional caregivers, home-care services, and
                        trusted care facilities. Compare options, check
                        availability, and schedule care with confidence, all in
                        one place.
                    </p>

                    <!-- feature strip: a label pattern, not badge chips -->
                    <ul
                        class="mt-9 flex flex-wrap gap-x-6 gap-y-4 text-sm text-white/70"
                    >
                        <li
                            v-for="feature in features"
                            :key="feature.label"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="text-primary-300"
                                v-html="feature.icon"
                            ></span>
                            {{ feature.label }}
                        </li>
                    </ul>

                    <!-- stats -->
                    <div class="mt-10 flex divide-x divide-white/10">
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="px-6 first:pl-0"
                        >
                            <p
                                class="text-2xl md:text-3xl font-medium text-white tabular-nums"
                            >
                                {{ stat.value }}
                            </p>
                            <p class="mt-1 text-xs text-white/50">
                                {{ stat.label }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- search card -->
                <div
                    class="bg-white rounded-3xl shadow-xl border border-black/5 p-6 md:p-8 w-full max-w-lg lg:ml-auto"
                >
                    <div class="mb-6">
                        <div class="flex items-center gap-2">
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-accent"
                            ></span>
                            <span
                                class="text-xs font-medium tracking-[0.14em] uppercase text-muted"
                            >
                                Trusted care services
                            </span>
                        </div>
                        <h2 class="mt-3 text-2xl font-medium text-secondary">
                            Find care near you
                        </h2>
                        <p class="mt-2 text-sm text-muted">
                            Search verified caregivers, home-care services, and
                            residential care facilities tailored to your needs.
                        </p>
                    </div>

                    <div class="grid gap-5">
                        <div>
                            <label
                                class="mb-2 block text-xs font-medium text-muted uppercase tracking-wide"
                            >
                                Search
                            </label>
                            <BaseInput
                                v-model="searchName"
                                :is-search="true"
                                placeholder="Provider name"
                                input-class="px-4 py-3"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-xs font-medium text-muted uppercase tracking-wide"
                            >
                                Location
                            </label>
                            <BaseInput
                                v-model="searchLocation"
                                :placeholder="
                                    locating
                                        ? 'Locating...'
                                        : 'Enter your city.'
                                "
                                input-class="px-4 py-3"
                                :readonly="locating"
                            >
                                <template #suffix>
                                    <span class="pr-3 flex items-center">
                                        <Location
                                            clickable
                                            @get-location="handleLocation"
                                            @loading="locating = $event"
                                        />
                                    </span>
                                </template>
                            </BaseInput>
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-xs font-medium text-muted uppercase tracking-wide"
                            >
                                Care type
                            </label>
                            <Combobox
                                v-model="planCode"
                                :items="planCodeList"
                                input-class="px-4 py-3"
                                :searchBar="false"
                            />
                        </div>
                    </div>

                    <BaseButton
                        @click="searchClick"
                        class="mt-6 w-full py-3 rounded-xl bg-primary hover:bg-primary-600 text-white font-medium shadow-sm transition-colors flex items-center justify-center gap-2"
                    >
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <path d="M21 21l-4.35-4.35" />
                        </svg>
                        Search available homecare
                    </BaseButton>

                    <div
                        class="mt-6 border-t border-muted-light pt-5 flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted"
                    >
                        <span
                            v-for="trust in trustPoints"
                            :key="trust"
                            class="flex items-center gap-1.5"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-accent shrink-0"
                            >
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                            {{ trust }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
<script setup lang="ts">
import { ref } from "vue";
import Combobox from "~/components/ui/Combobox.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Location from "~/components/icons/location.vue";
import logo from "~/assets/images/Booking_Logo.png";
import BaseButton from "~/components/ui/BaseButton.vue";

useHead({ title: "Bookings" });

const planCode = ref("C");
const searchName = ref("");
const searchLocation = ref("");
const lat = ref("");
const long = ref("");
const locating = ref(false);

const DEFAULT_LOCATION = {
    label: "Davao City",
    lat: 7.1907,
    long: 125.4553,
};

const handleLocation = async (data: any) => {
    searchLocation.value = data.label;
    lat.value = data.lat ?? data.latitude ?? "";
    long.value = data.lng ?? data.longitude ?? "";
};

const searchClick = async () => {
    await navigateTo({
        path: "/booking/search",
        query: {
            provider_name: searchName.value,
            location: searchLocation.value || DEFAULT_LOCATION.label,
            lat: lat.value || DEFAULT_LOCATION.lat,
            long: long.value || DEFAULT_LOCATION.long,
            plan_code: planCode.value,
            per_page: 6,
        },
    });
};

const planCodeList = [
    { label: "All (Homecare & Inhouse Facility)", value: "C" },
    { label: "Homecare Services", value: "A" },
    { label: "In-house Facility", value: "B" },
];

const features = [
    {
        label: "Home care services",
        icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>',
    },
    {
        label: "Verified caregivers",
        icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
    },
    {
        label: "Facility booking",
        icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    },
    {
        label: "Real-time availability",
        icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>',
    },
];

const stats = [
    { value: "500+", label: "Bookings managed" },
    { value: "50+", label: "Care providers" },
    { value: "99.9%", label: "Platform uptime" },
];

const trustPoints = [
    "Verified care providers",
    "Easy online booking",
    "Safe and secure service",
];
</script>
