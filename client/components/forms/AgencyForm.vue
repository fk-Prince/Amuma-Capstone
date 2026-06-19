<template>
    <div class="max-w-5xl mx-auto space-y-4">
        <LabelInput
            v-model="checkout.agency.agency_name"
            label="Agency Name"
            :error="checkout.errors?.agency_name"
            @clear-error="clearError('agency_name')"
        />

        <LabelInput
            v-model="checkout.agency.agency_description"
            label="Description"
            :error="checkout.errors?.agency_description"
            @clear-error="clearError('agency_description')"
        />

        <div class="flex gap-2 flex-col">
            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold text-slate-700">
                    Primary Address
                    <p
                        v-if="locationError && useGeolocation"
                        class="text-xs font-normal text-red-500"
                    >
                        {{ locationError }}
                    </p>
                </label>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">Use map</span>
                    <button
                        type="button"
                        @click="useGeolocation = !useGeolocation"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                        :class="useGeolocation ? 'bg-primary' : 'bg-slate-200'"
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
                    :initial-city="checkout.agency.location.city || undefined"
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
                    :error="checkout.errors?.['location.street']"
                    @clear-error="clearError('location.street')"
                />
                <LabelInput
                    v-model="checkout.agency.location.city"
                    label="City"
                    :error="checkout.errors?.['location.city']"
                    @clear-error="clearError('location.city')"
                />
                <LabelInput
                    v-model="checkout.agency.location.province"
                    label="Province"
                    :error="checkout.errors?.['location.province']"
                    @clear-error="clearError('location.province')"
                />
                <LabelInput
                    v-model="checkout.agency.location.country"
                    label="Country"
                    :error="checkout.errors?.['location.country']"
                    @clear-error="clearError('location.country')"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import LabelInput from "../ui/BaseInput.vue";
import LocationSelector from "../ui/LocationSelector.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";

const checkout = useSubscriptionCheckout();
const useGeolocation = ref(false);

const locationError = computed(() => {
    const keys = [
        "location.street",
        "location.city",
        "location.province",
        "location.country",
    ];

    return keys.some((k) => checkout.errors?.[k])
        ? "Location is required. Please complete address information."
        : "";
});

const clearError = (key: string) => {
    checkout.errors = Object.fromEntries(
        Object.entries(checkout.errors || {}).filter(([k]) => k !== key),
    );
};

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

    if (
        !street?.trim() ||
        !city?.trim() ||
        !province?.trim() ||
        !country?.trim()
    ) {
        const errors: Record<string, string> = {};

        if (!street?.trim())
            errors["location.street"] =
                "We couldn't detect your street. Please enter it manually.";

        if (!city?.trim())
            errors["location.city"] =
                "We couldn't detect your city. Please enter it manually.";

        if (!province?.trim())
            errors["location.province"] =
                "We couldn't detect your province. Please enter it manually.";

        if (!country?.trim())
            errors["location.country"] =
                "We couldn't detect your country. Please enter it manually.";

        checkout.setErrors(errors);
        useGeolocation.value = false;
        return;
    }

    [
        "agency_name",
        "agency_description",
        "location.street",
        "location.city",
        "location.province",
        "location.country",
    ].forEach(clearError);
};
</script>
