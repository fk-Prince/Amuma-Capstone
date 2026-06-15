<template>
    <div class="max-w-4xl mx-auto p-6 space-y-4">
        <div class="flex gap-4 items-start">
            <div class="flex flex-col flex-1 gap-2">
                <LabelInput
                    v-model="checkout.branch.name"
                    label="Branch Name"
                    required
                    @update:modelValue="checkout.clearError('branch_name')"
                    :error="checkout.errors?.branch_name"
                />

                <LabelInput
                    v-model="checkout.branch.description"
                    label="Description"
                    required
                    @update:modelValue="
                        checkout.clearError('branch_description')
                    "
                    :error="checkout.errors?.branch_description"
                />

                <LabelInput
                    v-model="checkout.branch.contact_number"
                    label="Contact Number"
                    @update:modelValue="
                        checkout.clearError('branch_contact_number')
                    "
                    :error="checkout.errors?.branch_contact_number"
                />
            </div>

            <div class="flex flex-col gap-1">
                <div class="flex justify-between">
                    <label class="block text-sm font-semibold text-slate-700"
                        >Branch Display Image</label
                    >
                    <button
                        v-if="checkout.branch.image"
                        @click="removeBranchImage"
                        class="text-[12px] text-danger hover:underline flex gap-2"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
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
                        alt="Preview"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="text-center text-gray-400 text-sm">
                        <div class="text-2xl">📷</div>
                        <div>Upload</div>
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
                    class="mt-1 text-xs text-red-500"
                >
                    {{ checkout.errors.branch_image }}
                </p>
            </div>
        </div>

        <div class="flex gap-2 flex-col">
            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold text-slate-700">
                    Location
                    <span class="text-red-500 ml-0.5">*</span>
                    <p
                        ref="locationErrorRef"
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
                    placeholder="e.g. 123 Roxas Avenue"
                    @update:modelValue="checkout.clearError('branch_street')"
                    :error="checkout.errors?.branch_street"
                />
                <LabelInput
                    v-model="checkout.branch.location.city"
                    label="City"
                    placeholder="e.g. Davao City"
                    @update:modelValue="checkout.clearError('branch_city')"
                    :error="checkout.errors?.branch_city"
                />
                <LabelInput
                    v-model="checkout.branch.location.province"
                    label="Province"
                    placeholder="e.g. Davao del sur"
                    @update:modelValue="checkout.clearError('branch_province')"
                    :error="checkout.errors?.branch_province"
                />
                <LabelInput
                    v-model="checkout.branch.location.country"
                    label="Country"
                    placeholder="e.g. Philippines"
                    @update:modelValue="checkout.clearError('branch_country')"
                    :error="checkout.errors?.branch_country"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import LocationSelector from "../ui/LocationSelector.vue";
import LabelInput from "../ui/BaseInput.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";

const props = defineProps<{
    errors?: Record<string, string>;
    choices?: Boolean;
}>();

const checkout = useSubscriptionCheckout();
const branchImagePreview = ref<string | null>(null);
const branchImageInput = ref<HTMLInputElement | null>(null);
const useGeolocation = ref(true);
const locationErrorRef = ref<HTMLElement | null>(null);

const locationError = computed(() => {
    const keys = [
        "branch_street",
        "branch_city",
        "branch_province",
        "branch_country",
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

    if (checkout.errors) {
        delete checkout.errors.branch_street;
        delete checkout.errors.branch_city;
        delete checkout.errors.branch_province;
        delete checkout.errors.branch_country;
    }
};

function clearError(field: string) {
    delete checkout.errors[field];
}
</script>
