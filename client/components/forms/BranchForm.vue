<template>
    <div class="max-w-5xl mx-auto space-y-4">
        <div class="flex gap-4 items-start">
            <div class="flex flex-col flex-1 gap-2">
                <LabelInput
                    v-model="checkout.branch.name"
                    label="Branch Name"
                    @update:modelValue="clearError('branch_name')"
                    :error="checkout.errors?.branch_name"
                />

                <LabelInput
                    v-model="checkout.branch.description"
                    label="Description"
                    :text-max="500"
                    @update:modelValue="clearError('branch_description')"
                    :error="checkout.errors?.branch_description"
                />

                <LabelInput
                    v-model="checkout.branch.contact_number"
                    label="Contact Number"
                    @update:modelValue="clearError('branch_contact_number')"
                    :error="checkout.errors?.branch_contact_number"
                />
            </div>

            <div class="flex flex-col gap-1">
                <div class="flex justify-between">
                    <label class="block text-sm font-semibold text-slate-700">
                        Branch Image
                    </label>

                    <button
                        v-if="checkout.branch.image"
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

                <p
                    v-if="checkout.errors?.branch_image"
                    class="text-xs text-red-500"
                >
                    {{ checkout.errors.branch_image }}
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
                <LocationSelector
                    :initial-lat="
                        checkout.branch.location?.latitude || undefined
                    "
                    :initial-lng="
                        checkout.branch.location?.longitude || undefined
                    "
                    :initial-street="
                        checkout.branch.location?.street || undefined
                    "
                    :initial-city="checkout.branch.location?.city || undefined"
                    :initial-province="
                        checkout.branch.location?.province || undefined
                    "
                    :initial-country="
                        checkout.branch.location?.country || undefined
                    "
                    @location-selected="handleLocation"
                />
            </template>

            <div v-else class="grid grid-cols-1 gap-2">
                <LabelInput
                    v-model="checkout.branch.location.street"
                    label="Street"
                    @update:modelValue="clearError('location.street')"
                    :error="checkout.errors?.['location.street']"
                />

                <LabelInput
                    v-model="checkout.branch.location.city"
                    label="City"
                    @update:modelValue="clearError('location.city')"
                    :error="checkout.errors?.['location.city']"
                />

                <LabelInput
                    v-model="checkout.branch.location.province"
                    label="Province"
                    @update:modelValue="clearError('location.province')"
                    :error="checkout.errors?.['location.province']"
                />

                <LabelInput
                    v-model="checkout.branch.location.country"
                    label="Country"
                    @update:modelValue="clearError('location.country')"
                    :error="checkout.errors?.['location.country']"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import LocationSelector from "../ui/LocationSelector.vue";
import LabelInput from "../ui/BaseInput.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";

const checkout = useSubscriptionCheckout();

const branchImagePreview = ref<string | null>(null);
const branchImageInput = ref<HTMLInputElement | null>(null);
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
    checkout.branch.location = {
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

        if (!street?.trim()) errors["location.street"] = "Street is required";

        if (!city?.trim()) errors["location.city"] = "City is required";

        if (!province?.trim())
            errors["location.province"] = "Province is required";

        if (!country?.trim())
            errors["location.country"] = "Country is required";

        checkout.setErrors(errors);
        useGeolocation.value = false;
        return;
    }

    [
        "branch_name",
        "branch_description",
        "location.street",
        "location.city",
        "location.province",
        "location.country",
    ].forEach(clearError);
};

const handleBranchImage = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    checkout.branch.image = file;
    branchImagePreview.value = URL.createObjectURL(file);
    clearError("branch_image");
};

const removeBranchImage = () => {
    checkout.branch.image = null;
    branchImagePreview.value = null;

    if (branchImageInput.value) {
        branchImageInput.value.value = "";
    }
};

function clearError(field: string) {
    if (checkout.errors) delete checkout.errors[field];
}
</script>
