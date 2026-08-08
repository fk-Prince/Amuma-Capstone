<template>
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Agency Information
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Configure your agency profile, branding, and agency details.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_220px] gap-8">
                <div class="space-y-5">
                    <LabelInput
                        v-model="agency.name"
                        label="Agency Name"
                        placeholder="Enter agency name"
                        :error="errors?.agency_name"
                        @clear-error="clearError('agency_name')"
                    />

                    <LabelInput
                        v-model="agency.email"
                        label="Email Address"
                        type="email"
                        placeholder="Enter agency email address"
                        @update:modelValue="clearError('agency_email')"
                        :error="errors?.agency_email"
                    />

                    <LabelInput
                        v-model="agency.description"
                        label="Description"
                        mode="textarea"
                        :rows="4"
                        placeholder="Describe your agency"
                        :allowResize="true"
                        :textMax="1000"
                        :error="errors?.agency_description"
                        @clear-error="clearError('agency_description')"
                    />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-semibold text-slate-700">
                            Agency Image
                        </label>

                        <button
                            v-if="agency.image"
                            type="button"
                            @click="removeAgencyImage"
                            class="text-xs font-medium text-red-500 hover:text-red-600"
                        >
                            Remove
                        </button>
                    </div>
                    <div
                        class="relative h-52 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group"
                        @click="agencyImageInput?.click()"
                    >
                        <img
                            v-if="agencyImagePreview"
                            :src="agencyImagePreview"
                            class="h-full w-full object-cover"
                        />

                        <div
                            v-else
                            class="absolute inset-0 flex flex-col items-center justify-center text-slate-400"
                        >
                            <div
                                class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-2xl mb-3"
                            >
                                +
                            </div>

                            <p class="text-sm font-medium">Upload Image</p>

                            <span class="text-xs"> PNG, JPG up to 5MB </span>
                        </div>

                        <div
                            v-if="agencyImagePreview"
                            class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"
                        />
                    </div>

                    <input
                        ref="agencyImageInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleAgencyImage"
                    />

                    <p v-if="errors?.agency_image" class="text-xs text-red-500">
                        {{ errors.agency_image }}
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Primary Address
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Choose between map location or manual address.
                    </p>

                    <p
                        v-if="locationError && useGeolocation"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ locationError }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500"> Use map </span>

                    <button
                        type="button"
                        @click="useGeolocation = !useGeolocation"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                        :class="useGeolocation ? 'bg-primary' : 'bg-slate-200'"
                    >
                        <span
                            class="h-4 w-4 rounded-full bg-white shadow transition-transform"
                            :class="
                                useGeolocation
                                    ? 'translate-x-6'
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
                            class="h-64 rounded-xl bg-slate-50 flex items-center justify-center text-sm text-gray-400"
                        >
                            Loading map...
                        </div>
                    </template>
                </ClientOnly>
            </template>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <LabelInput
                    v-model="agency.location.street"
                    label="Street"
                    @update:modelValue="clearError('location.street')"
                    :error="errors?.['location.street']"
                />

                <LabelInput
                    v-model="agency.location.city"
                    label="City"
                    @update:modelValue="clearError('location.city')"
                    :error="errors?.['location.city']"
                />

                <LabelInput
                    v-model="agency.location.province"
                    label="Province"
                    @update:modelValue="clearError('location.province')"
                    :error="errors?.['location.province']"
                />

                <LabelInput
                    v-model="agency.location.country"
                    label="Country"
                    @update:modelValue="clearError('location.country')"
                    :error="errors?.['location.country']"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Check } from "lucide-vue-next";
import { ref, computed } from "vue";
import LocationSelector from "../ui/LocationSelector.vue";
import LabelInput from "../ui/BaseInput.vue";
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

const agencyImagePreview = ref<string | null>(
    typeof props.agency.image === "string" ? props.agency.image : null,
);
const agencyImageInput = ref<HTMLInputElement | null>(null);
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
    emit("update:agency", {
        ...agency.value,
        location: {
            street: street ?? "",
            city: city ?? "",
            province: province ?? "",
            country: country ?? "",
            latitude: lat ?? 0,
            longitude: lng ?? 0,
        },
    });

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

const handleAgencyImage = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    emit("update:agency", { ...agency.value, image: file });
    agencyImagePreview.value = URL.createObjectURL(file);
    clearError("agency_image");
};

const removeAgencyImage = () => {
    emit("update:agency", { ...agency.value, image: null });
    agencyImagePreview.value = null;

    if (agencyImageInput.value) {
        agencyImageInput.value.value = "";
    }
};

function clearError(field: string) {
    if (!props.errors) return;

    const updated = {
        ...props.errors,
    };

    delete updated[field];

    emit("update:errors", updated);
}
</script>
