<template>
    <div class="max-w-5xl mx-auto space-y-4">
        <div class="flex gap-4 items-start">
            <div class="flex flex-col flex-1 gap-2">
                <LabelInput
                    v-model="branch.name"
                    label="Branch Name"
                    @update:modelValue="clearError('branch_name')"
                    :error="errors?.branch_name"
                />

                <LabelInput
                    v-model="branch.description"
                    label="Description"
                    mode="textarea"
                    :textMax="1000"
                    :allowResize="true"
                    @update:modelValue="clearError('branch_description')"
                    :error="errors?.branch_description"
                />

                <LabelInput
                    v-model="branch.contact_number"
                    label="Contact Number"
                    @update:modelValue="clearError('branch_contact_number')"
                    :error="errors?.branch_contact_number"
                />
            </div>

            <div class="flex flex-col gap-1">
                <div class="flex justify-between">
                    <label class="block text-sm font-semibold text-slate-700">
                        Branch Image
                    </label>

                    <button
                        v-if="branch.image"
                        @click="removeBranchImage"
                        class="text-[12px] text-danger hover:underline"
                    >
                        Remove
                    </button>
                </div>

                <div
                    class="h-48 w-48 border-2 border-dashed rounded-lg cursor-pointer flex items-center justify-center overflow-hidden hover:border-primary"
                    @click="branchImageInput?.click()"
                >
                    <img
                        v-if="branchImagePreview"
                        :src="branchImagePreview"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="text-center text-gray-400 text-sm">
                        Upload Image
                    </div>
                </div>

                <input
                    ref="branchImageInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleBranchImage"
                />

                <p v-if="errors?.branch_image" class="text-xs text-red-500">
                    {{ errors.branch_image }}
                </p>
            </div>
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
                        :initial-lat="branch.location?.latitude || undefined"
                        :initial-lng="branch.location?.longitude || undefined"
                        :initial-street="branch.location?.street || undefined"
                        :initial-city="branch.location?.city || undefined"
                        :initial-province="
                            branch.location?.province || undefined
                        "
                        :initial-country="branch.location?.country || undefined"
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
                    v-model="branch.location.street"
                    label="Street"
                    @update:modelValue="clearError('location.street')"
                    :error="errors?.['location.street']"
                />

                <LabelInput
                    v-model="branch.location.city"
                    label="City"
                    @update:modelValue="clearError('location.city')"
                    :error="errors?.['location.city']"
                />

                <LabelInput
                    v-model="branch.location.province"
                    label="Province"
                    @update:modelValue="clearError('location.province')"
                    :error="errors?.['location.province']"
                />

                <LabelInput
                    v-model="branch.location.country"
                    label="Country"
                    @update:modelValue="clearError('location.country')"
                    :error="errors?.['location.country']"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import LocationSelector from "../ui/LocationSelector.vue";
import LabelInput from "../ui/BaseInput.vue";
import type { Branch } from "~/types/branch";

const props = defineProps<{
    branch: Branch;
    errors?: Record<string, string> | null;
}>();

const emit = defineEmits<{
    (e: "update:branch", value: Branch): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const branch = computed({
    get: () => props.branch,
    set: (value) => emit("update:branch", value),
});

const errors = computed(() => props.errors);

const branchImagePreview = ref<string | null>(
    typeof props.branch.image === "string" ? props.branch.image : null,
);
const branchImageInput = ref<HTMLInputElement | null>(null);
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
    emit("update:branch", {
        ...props.branch,
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
const handleBranchImage = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    emit("update:branch", { ...props.branch, image: file });
    branchImagePreview.value = URL.createObjectURL(file);
    clearError("branch_image");
};

const removeBranchImage = () => {
    emit("update:branch", { ...props.branch, image: null });
    branchImagePreview.value = null;

    if (branchImageInput.value) {
        branchImageInput.value.value = "";
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
