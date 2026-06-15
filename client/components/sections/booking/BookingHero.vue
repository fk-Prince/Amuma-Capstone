<template>
    <section class="relative min-h-screen overflow-hidden pt-[70px]">
        <img
            :src="Logo"
            class="absolute inset-0 w-full h-full object-cover opacity-60 object-center"
            alt=""
        />
        <div class="absolute inset-0 bg-black/50"></div>

        <div
            class="relative z-10 max-w-[85rem] mx-auto px-6 py-16 h-full flex items-center"
        >
            <div
                class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center w-full"
            >
                <div class="max-w-2xl">
                    <span
                        class="inline-flex items-center rounded-full bg-white/10 backdrop-blur-sm border border-white/20 px-4 py-2 text-sm font-medium text-white"
                    >
                        Trusted Home Care Services
                    </span>

                    <h1
                        class="mt-6 text-4xl md:text-6xl font-extrabold text-white leading-tight"
                    >
                        Find Quality
                        <span class="text-primary">Care Near You</span>
                    </h1>

                    <p
                        class="mt-6 text-lg text-white/80 leading-relaxed max-w-xl"
                    >
                        Easily book professional caregivers, home-care services,
                        and trusted care facilities. Compare options, check
                        availability, and schedule care with confidence all in
                        one place.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-4">
                        <div
                            class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur-sm text-white"
                        >
                            Home Care Services
                        </div>

                        <div
                            class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur-sm text-white"
                        >
                            Verified Caregivers
                        </div>

                        <div
                            class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur-sm text-white"
                        >
                            Facility Booking
                        </div>

                        <div
                            class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur-sm text-white"
                        >
                            Real-Time Availability
                        </div>
                    </div>
                    <div class="mt-10 flex gap-10">
                        <div>
                            <p class="text-3xl font-bold text-white">500+</p>
                            <p class="text-sm text-white/70">
                                Bookings Managed
                            </p>
                        </div>

                        <div>
                            <p class="text-3xl font-bold text-white">50+</p>
                            <p class="text-sm text-white/70">Care Providers</p>
                        </div>

                        <div>
                            <p class="text-3xl font-bold text-white">99.9%</p>
                            <p class="text-sm text-white/70">Platform Uptime</p>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 md:p-8 w-full max-w-lg lg:ml-auto"
                >
                    <div class="mb-6">
                        <span
                            class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                        >
                            Trusted Care Services
                        </span>

                        <h2 class="mt-3 text-2xl font-bold text-slate-900">
                            Find Care Near You
                        </h2>

                        <p class="mt-2 text-sm text-slate-500">
                            Search verified caregivers, home-care services, and
                            residential care facilities tailored to your needs.
                        </p>
                    </div>

                    <div class="grid gap-5">
                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                            >
                                Search
                            </label>

                            <BaseInput
                                v-model="searchName"
                                :is-search="true"
                                placeholder="Provider name or service"
                                input-class="px-4 py-3"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                            >
                                Location
                            </label>

                            <BaseInput
                                v-model="searchLocation"
                                placeholder="Choose City / Area"
                                input-class="px-4 py-3"
                            >
                                <template #suffix>
                                    <Location
                                        clickable
                                        @get-location="handleLocation"
                                    />
                                </template>
                            </BaseInput>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                            >
                                Care Type
                            </label>

                            <Combobox
                                v-model="careType"
                                :items="careTypeList"
                                input-class="px-4 py-3"
                                :searchBar="false"
                            />
                        </div>
                    </div>

                    <BaseButton
                        @click="searchClick"
                        class="mt-6 w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-lg transition-all"
                    >
                        Search Available Homecare
                    </BaseButton>

                    <div
                        class="mt-6 border-t border-slate-100 pt-5 flex flex-wrap gap-4 text-xs text-slate-500"
                    >
                        <span>✓ Verified Care Providers</span>
                        <span>✓ Easy Online Booking</span>
                        <span>✓ Safe & Secure Service</span>
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
import Logo from "~/assets/logo/booking_logo.png";
import BaseButton from "~/components/ui/BaseButton.vue";

useHead({ title: "Bookings" });
const careType = ref("all");
const searchName = ref("");
const searchLocation = ref("");

const handleLocation = async (data: any) => {
    searchLocation.value = data.label;
};

const searchClick = async () => {
    await navigateTo({
        path: "/booking/search",
        query: {
            service: searchName.value,
            location: searchLocation.value,
            type: careType.value,
        },
    });
};

const careTypeList = [
    { label: "All (Homecare & Inhouse Facility)", value: "all" },
    { label: "Homecare", value: "homecare" },
    { label: "Inhouse Facility", value: "facility" },
];
</script>
