<template>
    <div class="max-w-4xl mx-auto p-6 space-y-4">
        <div
            v-if="mode === 'choice'"
            class="grid grid-cols-1 md:grid-cols-2 gap-4"
        >
            <button
                class="flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-lg hover:border-blue-500 hover:bg-blue-50 transition"
                @click="mode = 'new'"
            >
                <span class="text-3xl font-bold text-blue-600">+</span>
                <span class="mt-2 font-medium">Create New Agency</span>
                <span class="text-sm text-slate-500"
                    >Register a new agency</span
                >
            </button>

            <button
                class="flex flex-col items-center justify-center p-6 border rounded-lg hover:border-green-500 hover:bg-green-50 transition"
                @click="mode = 'existing'"
            >
                <span class="text-3xl text-green-600">🏢</span>
                <span class="mt-2 font-medium">Select Existing Agency</span>
                <span class="text-sm text-slate-500">Use existing agency</span>
            </button>
        </div>

        <div v-else-if="mode === 'new'" class="space-y-4">
            <button
                class="text-sm text-primary w-fit"
                @click="
                    () => {
                        mode = 'choice';
                        resetAgencies();
                        clearAgency();
                    }
                "
            >
                ← Back
            </button>

            <LabelInput
                v-model="checkout.agency.name"
                label="Agency Name"
                required
                :error="agencyNameError"
            />
            <LabelInput
                v-model="checkout.agency.description"
                label="Description"
            />

            <div class="flex gap-2 flex-col">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-semibold text-slate-700"
                        >Location
                        <p
                            ref="locationErrorRef"
                            v-if="locationError && useGeolocation"
                            class="text-xs font-normal text-red-500"
                        >
                            {{ locationError }}
                        </p></label
                    >
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">Use map</span>
                        <button
                            type="button"
                            @click="useGeolocation = !useGeolocation"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                            :class="
                                useGeolocation ? 'bg-primary' : 'bg-slate-200'
                            "
                        >
                            <span
                                class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                                :class="
                                    useGeolocation
                                        ? 'translate-x-4'
                                        : 'translate-x-1'
                                "
                            />
                        </button>
                    </div>
                </div>

                <template v-if="useGeolocation">
                    <LocationSelector
                        :initial-lat="
                            checkout.agency.location.latitude || undefined
                        "
                        :initial-lng="
                            checkout.agency.location.longitude || undefined
                        "
                        :initial-street="
                            checkout.agency.location.street || undefined
                        "
                        :initial-city="
                            checkout.agency.location.city || undefined
                        "
                        :initial-province="
                            checkout.agency.location.province || undefined
                        "
                        :initial-country="
                            checkout.agency.location.country || undefined
                        "
                        @location-selected="handleLocation"
                    />
                </template>

                <div v-else class="grid grid-cols-1 gap-2">
                    <LabelInput
                        v-model="checkout.agency.location.street"
                        label="Street"
                        placeholder="e.g. 123 Roxas Avenue"
                        :error="streetError"
                    />
                    <LabelInput
                        v-model="checkout.agency.location.city"
                        label="City"
                        placeholder="e.g. Davao City"
                        :error="cityError"
                    />
                    <LabelInput
                        v-model="checkout.agency.location.province"
                        label="Province"
                        placeholder="e.g. Davao del sur"
                        :error="provinceError"
                    />
                    <LabelInput
                        v-model="checkout.agency.location.country"
                        label="Country"
                        placeholder="e.g. Philippines"
                        :error="countryError"
                    />
                </div>
            </div>
        </div>

        <div v-else class="space-y-4">
            <div class="flex gap-5 flex-col">
                <button
                    class="text-sm text-primary w-fit"
                    @click="
                        () => {
                            mode = 'choice';
                            resetAgencies();
                            clearAgency();
                        }
                    "
                >
                    ← Back
                </button>

                <div class="flex justify-between">
                    <label class="text-sm font-semibold text-slate-700"
                        >Select Agency</label
                    >
                    <button
                        class="text-sm text-red-500 hover:text-red-600 w-fit"
                        @click="clearAgency"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div v-if="agencies && agencies.length > 0" class="flex flex-col">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label
                        v-for="agency in agencies"
                        :key="agency.agency_id"
                        class="flex items-start justify-between rounded-xl border p-4 cursor-pointer transition"
                        :class="
                            selectedId === agency.agency_id
                                ? 'border-primary bg-blue-50'
                                : 'border-gray-200 hover:border-gray-300'
                        "
                        @click.prevent="selectAgency(agency)"
                    >
                        <div class="flex items-start gap-3">
                            <input
                                type="radio"
                                class="mt-1 accent-primary"
                                :value="agency.agency_id"
                                :checked="selectedId === agency.agency_id"
                                readonly
                            />
                            <div class="space-y-1">
                                <p class="font-semibold text-gray-900">
                                    {{ agency.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ agency.description }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ formatAddress(agency) }}
                                </p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="w-full flex justify-center mt-2">
                    <button
                        v-if="!isFullyLoaded"
                        @click="loadMoreAgencies"
                        class="py-2 px-4 border rounded-lg text-primary hover:bg-primary/20 w-full"
                    >
                        {{ loading ? "Loading..." : "Show more" }}
                    </button>

                    <button
                        v-else
                        @click="resetAgencies"
                        class="py-2 px-4 border rounded-lg text-danger hover:bg-danger/20 w-full"
                    >
                        Show less
                    </button>
                </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500">
                No agencies registered to you.
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import LabelInput from "../ui/BaseInput.vue";
import LocationSelector from "../ui/LocationSelector.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { agencyService } from "~/api/agency/AgencyService.js";

const checkout = useSubscriptionCheckout();
const mode = ref<"choice" | "new" | "existing">("choice");
const selectedId = ref<number | null>(null);
const useGeolocation = ref(true);
const locationErrorRef = ref<HTMLElement | null>(null);

const hasName = computed(() => !!checkout.agency.name);

const hasLocation = computed(
    () =>
        checkout.agency.location?.street &&
        checkout.agency.location?.city &&
        checkout.agency.location?.province &&
        checkout.agency.location?.country,
);

const agencyNameError = computed(() => {
    if (hasLocation.value && !checkout.agency.name) {
        return "Agency name is required when a location is provided.";
    }
    return "";
});

const streetError = computed(() =>
    hasName.value && !checkout.agency.location?.street
        ? "Street is required."
        : "",
);
const cityError = computed(() =>
    hasName.value && !checkout.agency.location?.city ? "City is required." : "",
);
const provinceError = computed(() =>
    hasName.value && !checkout.agency.location?.province
        ? "Province is required."
        : "",
);
const countryError = computed(() =>
    hasName.value && !checkout.agency.location?.country
        ? "Country is required."
        : "",
);

const locationError = computed(() => {
    const keys = [
        "agency_street",
        "agency_city",
        "agency_province",
        "agency_country",
    ];
    const found = keys.find((k) => checkout.errors?.[k]);
    return found
        ? "Location is required. Please pin your location on the map."
        : "";
});

watch(locationError, async (val) => {
    if (!val || !useGeolocation.value) return;
    await nextTick();
    locationErrorRef.value?.scrollIntoView({
        behavior: "smooth",
        block: "center",
    });
});

const formatAddress = (agency: any) => {
    return `${agency.locations.street}, ${agency.locations.city}, ${agency.locations.province}, ${agency.locations.country}`;
};

const page = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const agencies = ref<any[]>([]);

const isFullyLoaded = computed(() => page.value >= lastPage.value);

const selectAgency = (agency: any) => {
    selectedId.value = agency.agency_id;
    checkout.agency.id = agency.agency_id;
    checkout.agency.name = agency.name;
    checkout.agency.description = agency.description;
    checkout.agency.location = {
        address: agency.locations?.address ?? "",
        street: agency.locations?.street ?? "",
        city: agency.locations?.city ?? "",
        province: agency.locations?.province ?? "",
        country: agency.locations?.country ?? "",
        latitude: agency.locations?.lat ?? 0,
        longitude: agency.locations?.lng ?? 0,
    };
};

const clearAgency = () => {
    selectedId.value = null;
    checkout.clearAgency();
};

const fetchAgencies = async (reset = false) => {
    if (loading.value) return;
    loading.value = true;

    try {
        const res = await agencyService.list({
            page: page.value,
            per_page: 3,
            owned: true,
        });

        if (reset) {
            agencies.value = res.data;
        } else {
            agencies.value.push(...res.data);
        }

        lastPage.value = res.meta.last_page;
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const loadMoreAgencies = async () => {
    if (page.value >= lastPage.value) return;
    page.value++;
    await fetchAgencies();
};

const resetAgencies = async () => {
    page.value = 1;
    agencies.value = [];
    await fetchAgencies(true);
};

fetchAgencies();

const handleLocation = ({
    lat,
    lng,
    street,
    city,
    province,
    country,
}: {
    lat: number;
    lng: number;
    street: string;
    city: string;
    province: string;
    country: string;
}) => {
    checkout.agency.location = {
        street: street ?? "",
        city: city ?? "",
        province: province ?? "",
        country: country ?? "",
        latitude: lat ?? 0,
        longitude: lng ?? 0,
    };

    if (checkout.errors) {
        delete checkout.errors.agency_street;
        delete checkout.errors.agency_city;
        delete checkout.errors.agency_province;
        delete checkout.errors.agency_country;
    }
};
</script>
