<template>
    <div class="bg-white dark:bg-secondary">
    <section class="relative z-20 bg-[#EEF3FB] pt-[50px] font-sans dark:bg-secondary-950">
        <img
            :src="logo"
            class="pointer-events-none select-none absolute inset-0 h-full w-full object-cover object-[75%_center]"
            alt=""
        />

        <div
            class="absolute inset-0 bg-gradient-to-r from-[#EEF3FB] via-[#EEF3FB]/85 to-transparent dark:from-secondary-950 dark:via-secondary-950/85 dark:to-transparent"
        ></div>
        <div
            class="absolute inset-0 bg-gradient-to-t from-[#EEF3FB]/50 via-transparent to-transparent dark:from-secondary-950/50"
        ></div>

        <div
            class="absolute inset-x-0 bottom-0 h-40 sm:h-56 bg-gradient-to-t from-white via-white/60 to-transparent dark:from-secondary dark:via-secondary/60"
        ></div>

        <div
            class="relative z-10 w-[88%] max-w-[1600px] mx-auto px-4 sm:px-10 pt-[120px] pb-24 lg:pt-[130px] lg:pb-28"
        >
            <div class="max-w-xl">
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-light px-4 py-1.5 text-xs font-medium tracking-wide text-primary dark:bg-primary-500/15 dark:text-primary-300"
                >
                    <ShieldCheck class="h-3.5 w-3.5" />
                    Trusted Home Care Services
                </span>

                <h1
                    class="mt-6 text-4xl md:text-5xl lg:text-6xl font-bold text-secondary leading-[1.08] tracking-tight dark:text-white"
                >
                    Find quality
                    <br />
                    <span class="text-primary">care near you</span>
                </h1>

                <p
                    class="mt-6 text-base md:text-lg text-muted leading-relaxed max-w-md dark:text-gray-400"
                >
                    Book professional caregivers, home-care services, and
                    trusted care facilities. Compare options, check
                    availability, and schedule care with confidence, all in
                    one place.
                </p>
            </div>

           <div
                class="relative mt-9 w-full max-w-5xl rounded-2xl md:rounded-full bg-white shadow-2xl ring-1 ring-black/5 p-3 md:p-3 flex flex-col md:flex-row md:items-stretch gap-1 md:gap-0 dark:bg-white/[0.06] dark:backdrop-blur-2xl dark:ring-1 dark:ring-white/15 dark:shadow-[0_25px_70px_-20px_rgba(0,0,0,0.65)]"
            >
                <div class="flex-[1.3] min-w-0 flex flex-col justify-center px-5 py-3">
                    <label
                        class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted dark:text-gray-300"
                    >
                        <Search class="h-3.5 w-3.5" />
                        Provider name
                    </label>
                    <input
                        v-model="searchName"
                        type="text"
                        placeholder="Enter provider name"
                        class="mt-1 w-full min-w-0 border-none bg-transparent p-0 text-sm md:text-[15px] text-secondary outline-none placeholder:text-muted dark:text-white dark:placeholder:text-gray-400"
                    />
                </div>

                <div class="hidden md:block w-px my-2.5 bg-muted-light dark:bg-white/25"></div>

                <div class="flex-1 min-w-0 flex flex-col justify-center px-5 py-3">
                    <label
                        class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted dark:text-gray-300"
                    >
                        <MapPin class="h-3.5 w-3.5" />
                        Location
                    </label>
                    <div class="mt-1 flex items-center gap-1.5">
                        <input
                            v-model="searchLocation"
                            type="text"
                            :readonly="locating"
                            :placeholder="locating ? 'Locating...' : 'Enter city'"
                            class="w-full min-w-0 border-none bg-transparent p-0 text-sm md:text-[15px] text-secondary outline-none placeholder:text-muted dark:text-white dark:placeholder:text-gray-400"
                        />
                        <Location
                            clickable
                            @get-location="handleLocation"
                            @loading="locating = $event"
                        />
                    </div>
                </div>

                <div class="hidden md:block w-px my-2.5 bg-muted-light dark:bg-white/25"></div>

                <div ref="careTypeRef" class="relative flex-1 min-w-0">
                    <button
                        type="button"
                        class="w-full flex flex-col justify-center px-5 py-3 text-left"
                        @click="careTypeOpen = !careTypeOpen"
                    >
                        <span
                            class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted dark:text-gray-300"
                        >
                            <ClipboardList class="h-3.5 w-3.5" />
                            Care type
                        </span>
                        <span class="mt-1 flex items-center justify-between gap-2">
                            <span class="truncate text-sm md:text-[15px] text-secondary dark:text-white">
                                {{ selectedPlan.short }}
                            </span>
                            <ChevronDown
                                class="h-3.5 w-3.5 shrink-0 text-muted dark:text-gray-300 transition-transform duration-200"
                                :class="careTypeOpen && 'rotate-180'"
                            />
                        </span>
                    </button>

                    <Transition name="fade">
                        <div
                            v-if="careTypeOpen"
                            class="absolute left-0 top-[calc(100%+10px)] z-30 w-72 rounded-2xl border border-muted-light bg-white p-1.5 shadow-xl dark:border-white/10 dark:bg-secondary"
                        >
                            <button
                                v-for="opt in planCodeList"
                                :key="opt.value"
                                type="button"
                                class="flex w-full items-start justify-between gap-3 rounded-xl px-3.5 py-2.5 text-left transition-colors hover:bg-light dark:hover:bg-white/10"
                                :class="planCode === opt.value ? 'bg-light dark:bg-white/10' : ''"
                                @click="selectPlan(opt.value)"
                            >
                                <span>
                                    <span
                                        class="block text-sm font-medium"
                                        :class="planCode === opt.value ? 'text-primary' : 'text-secondary dark:text-white'"
                                    >
                                        {{ opt.short }}
                                    </span>
                                    <span class="mt-0.5 block text-xs text-muted dark:text-gray-400">
                                        {{ opt.description }}
                                    </span>
                                </span>
                                <Check
                                    v-if="planCode === opt.value"
                                    class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                                />
                            </button>
                        </div>
                    </Transition>
                </div>

                <BaseButton
                    @click="searchClick"
                    class="w-full md:w-auto md:self-center py-4 px-8 rounded-xl md:rounded-full bg-primary hover:bg-primary-600 text-white font-medium shadow-sm transition-colors flex items-center justify-center gap-2 shrink-0 md:mr-0.5"
                >
                    <Search class="h-4 w-4" />
                    Search
                </BaseButton>
            </div>

            <div class="mt-8 flex flex-wrap gap-x-7 gap-y-4">
                <div
                    v-for="item in trustItems"
                    :key="item.label"
                    class="flex items-center gap-2.5"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-light text-primary shrink-0 dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                    </span>
                    <span class="text-sm text-secondary dark:text-gray-300">{{ item.label }}</span>
                </div>
            </div>
        </div>
    </section>

    <div class="relative z-10 rounded-t-[28px] sm:rounded-t-[40px] bg-white dark:bg-secondary border-b border-muted-light dark:border-white/10 font-sans">
        <div
            class="w-[88%] max-w-[1600px] mx-auto px-4 sm:px-10 py-6 flex flex-wrap items-center justify-between gap-8"
        >
            <div class="flex items-center gap-4">
                <div class="flex -space-x-3">
                    <span
                        v-for="(initial, i) in avatarInitials"
                        :key="i"
                        class="flex h-9 w-9 items-center justify-center rounded-full ring-2 ring-white dark:ring-secondary text-xs font-semibold text-white"
                        :class="avatarColors[i]"
                    >
                        {{ initial }}
                    </span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-secondary dark:text-white">
                        Trusted by thousands
                    </p>
                    <p class="text-xs text-muted dark:text-gray-400">
                        Families count on us for better care.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-x-10 gap-y-4">
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="flex items-center gap-3"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-light text-primary shrink-0 dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        <component :is="stat.icon" class="h-4.5 w-4.5" />
                    </span>
                    <div>
                        <p class="text-lg sm:text-xl font-bold text-primary dark:text-primary-300 tabular-nums leading-tight">
                            {{ stat.value }}
                        </p>
                        <p class="text-xs text-muted dark:text-gray-400">
                            {{ stat.label }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { onClickOutside } from "@vueuse/core";
import {
    ShieldCheck,
    Search,
    MapPin,
    ClipboardList,
    ChevronDown,
    Check,
    Home,
    CalendarCheck,
    Lock,
    Clock3,
    Heart,
    Users,
} from "lucide-vue-next";
import Location from "~/components/icons/location.vue";
import logo from "~/assets/images/bookingbg.png";
import BaseButton from "~/components/ui/BaseButton.vue";

useHead({ title: "Bookings" });

const planCode = ref("C");
const searchName = ref("");
const searchLocation = ref("");
const lat = ref("");
const long = ref("");
const locating = ref(false);
const careTypeOpen = ref(false);
const careTypeRef = ref(null);

onClickOutside(careTypeRef, () => {
    careTypeOpen.value = false;
});

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
    {
        label: "All (Homecare & Inhouse Facility)",
        short: "All Services",
        description: "Browse every kind of care available",
        value: "C",
    },
    {
        label: "Homecare Services",
        short: "Homecare",
        description: "A caregiver visits you at home",
        value: "A",
    },
    {
        label: "In-house Facility",
        short: "In-house Facility",
        description: "Full-time stay at a care facility",
        value: "B",
    },
];

const selectedPlan = computed(
    // 🆕 FIXED — TS treats `planCodeList[0]` as possibly-undefined by
    // default (that's what the "possibly 'undefined'" warning was
    // about), even though it's a fixed 3-item literal array that always
    // has an index 0. The `!` here just tells TS what's already true at
    // runtime: this fallback is never actually undefined.
    () => planCodeList.find((p) => p.value === planCode.value) ?? planCodeList[0]!,
);

const selectPlan = (value: string) => {
    planCode.value = value;
    careTypeOpen.value = false;
};

const trustItems = [
    { icon: ShieldCheck, label: "Trusted & Verified Caregivers" },
    { icon: Home, label: "Home care services" },
    { icon: CalendarCheck, label: "Easy Online Booking" },
    { icon: Lock, label: "Safe & Secure Payments" },
    { icon: Clock3, label: "Real-time Availability" },
];

const stats = [
    { icon: Heart, value: "500+", label: "Families Served" },
    { icon: Users, value: "50+", label: "Caregivers" },
    { icon: ShieldCheck, value: "99.9%", label: "Satisfaction Rate" },
];

const avatarInitials = ["M", "J", "A"];
const avatarColors = ["bg-primary", "bg-accent", "bg-primary-400"];
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>