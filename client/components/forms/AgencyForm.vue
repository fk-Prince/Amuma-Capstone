<template>
    <div class="max-w-5xl mx-auto space-y-4">
        <div class="flex flex-col gap-2">
            <LabelInput
                v-model="agency.agency_name"
                label="Agency Name"
                :error="errors?.agency_name"
                @clear-error="clearError('agency_name')"
            />
            <LabelInput
                v-model="agency.agency_description"
                label="Description"
                mode="textarea"
                :allowResize="true"
                :textMax="1000"
                :error="errors?.agency_description"
                @clear-error="clearError('agency_description')"
            />
        </div>

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
                <ClientOnly>
                    <LocationSelector
                        :initial-lat="agency.location?.latitude || undefined"
                        :initial-lng="agency.location?.longitude || undefined"
                        :initial-street="agency.location?.street || undefined"
                        :initial-city="agency.location?.city || undefined"
                        :initial-province="
                            agency.location?.province || undefined
                        "
                        :initial-country="agency.location?.country || undefined"
                        @location-selected="handleLocation"
                    />

                    <template #fallback>
                        <div
                            class="h-64 w-full rounded-lg border bg-slate-50 flex items-center justify-center text-sm text-gray-400"
                        >
                            Loading map...
                        </div>
                    </template>
                </ClientOnly>
            </template>

            <div v-else class="grid grid-cols-1 gap-2">
                <LabelInput
                    v-model="agency.location.street"
                    label="Street"
                    :error="errors?.['location.street']"
                    @clear-error="clearError('location.street')"
                />
                <LabelInput
                    v-model="agency.location.city"
                    label="City"
                    :error="errors?.['location.city']"
                    @clear-error="clearError('location.city')"
                />
                <LabelInput
                    v-model="agency.location.province"
                    label="Province"
                    :error="errors?.['location.province']"
                    @clear-error="clearError('location.province')"
                />
                <LabelInput
                    v-model="agency.location.country"
                    label="Country"
                    :error="errors?.['location.country']"
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
import type { Agency } from "~/types/agency";

const props = defineProps<{
    agency: Agency | any;
    errors?: Record<string, string> | null;
    mode?: "new" | "edit";
}>();

const emit = defineEmits<{
    (e: "update:agency", value: Agency | any): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const agency = computed({
    get: () => props.agency,
    set: (value) => emit("update:agency", value),
});

const errors = computed(() => props.errors);

const useGeolocation = ref(true);

const locationError = computed(() => {
    const keys = [
        "location",
        "location.street",
        "location.city",
        "location.province",
        "location.country",
    ];

    return keys.some((k) => props.errors?.[k])
        ? "Location is required. Please complete address information."
        : "";
});

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
    const updatedAgency = {
        ...agency.value,
        location: {
            street: street ?? "",
            city: city ?? "",
            province: province ?? "",
            country: country ?? "",
            latitude: lat ?? 0,
            longitude: lng ?? 0,
        },
    };

    emit("update:agency", updatedAgency);

    const updatedErrors = {
        ...(props.errors || {}),
    };

    Object.keys(updatedErrors).forEach((key) => {
        if (key === "location" || key.startsWith("location.")) {
            delete updatedErrors[key];
        }
    });

    if (
        !street?.trim() ||
        !city?.trim() ||
        !province?.trim() ||
        !country?.trim()
    ) {
        if (!street?.trim()) {
            updatedErrors["location.street"] = "Street is required";
        }

        if (!city?.trim()) {
            updatedErrors["location.city"] = "City is required";
        }

        if (!province?.trim()) {
            updatedErrors["location.province"] = "Province is required";
        }

        if (!country?.trim()) {
            updatedErrors["location.country"] = "Country is required";
        }

        useGeolocation.value = false;
    }

    emit("update:errors", updatedErrors);
};

function clearError(field: string) {
    if (!props.errors) return;

    const updatedErrors = {
        ...props.errors,
    };

    delete updatedErrors[field];

    emit("update:errors", updatedErrors);
}
</script>
