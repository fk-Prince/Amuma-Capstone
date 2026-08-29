<template>
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    Branch Information
                </h2>

                <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                    Update your branch details and contact information.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_220px] gap-8">
                <div class="space-y-5">
                    <LabelInput
                        v-model="branch.name"
                        label="Branch Name"
                        @update:modelValue="clearError('branch_name')"
                        :error="errors?.branch_name"
                        data-field="branch_name"
                    />

                    <LabelInput
                        v-model="branch.email"
                        label="Email Address"
                        type="email"
                        @update:modelValue="clearError('branch_email')"
                        :error="errors?.branch_email"
                        data-field="branch_email"
                    />

                    <LabelInput
                        v-model="branch.description"
                        label="Description"
                        mode="textarea"
                        :textMax="1000"
                        :allowResize="true"
                        :rows="3"
                        @update:modelValue="clearError('branch_description')"
                        :error="errors?.branch_description"
                        data-field="branch_description"
                    />

                    <PhoneInput
                        v-model="branch.contact_number"
                        label="Contact Number"
                        @update:modelValue="clearError('branch_contact_number')"
                        :error="errors?.branch_contact_number"
                        data-field="branch_contact_number"
                    />
                </div>

                <div class="space-y-2" data-field="branch_image">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-semibold text-slate-700 dark:text-gray-300">
                            Branch Image
                        </label>

                        <button
                            v-if="branch.image"
                            type="button"
                            @click="removeBranchImage"
                            class="text-xs font-medium text-red-500 hover:text-red-600"
                        >
                            Remove
                        </button>
                    </div>

                    <div
                        class="relative h-52 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group dark:bg-secondary dark:border-white/10"
                        @click="branchImageInput?.click()"
                    >
                        <img
                            v-if="branchImagePreview"
                            :src="branchImagePreview"
                            class="h-full w-full object-cover"
                        />

                        <div
                            v-else
                            class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 dark:text-gray-500"
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
                            v-if="branchImagePreview"
                            class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"
                        />
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
        </div>

        <div class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    Verification Document
                </h2>

                <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                    Upload a supporting document for this branch.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-2 p-4" data-field="branch_document">
                    <div class="flex items-center justify-between">
                        <label
                            class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            <svg
                                class="w-4 h-4 text-slate-400 dark:text-gray-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                />
                                <path d="M14 2v6h6" />
                                <path d="M9 13h6" />
                                <path d="M9 17h6" />
                            </svg>

                            Document
                            <span class="text-red-500">*</span>
                        </label>

                        <button
                            v-if="branch.document"
                            type="button"
                            @click="removeBranchDocument"
                            class="text-xs font-medium text-red-500 hover:text-red-600"
                        >
                            Remove
                        </button>
                    </div>

                    <div
                        class="relative h-40 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group dark:bg-secondary dark:border-white/10"
                        @click="branchDocumentInput?.click()"
                    >
                        <img
                            v-if="branchDocumentPreview"
                            :src="branchDocumentPreview"
                            class="h-full w-full object-cover"
                        />

                        <!-- PDFs can't be previewed as an image, so show the
                             file name instead of an empty dropzone. -->
                        <div
                            v-else-if="branchDocumentName"
                            class="absolute inset-0 flex flex-col items-center justify-center px-4 text-center"
                        >
                            <div
                                class="mb-2 flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-500"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                    />
                                    <path d="M14 2v6h6" />
                                </svg>
                            </div>

                            <p
                                class="max-w-full truncate text-sm font-medium text-slate-700 dark:text-gray-300"
                            >
                                {{ branchDocumentName }}
                            </p>

                            <span class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                                PDF selected — click to replace
                            </span>
                        </div>

                        <div
                            v-else
                            class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 dark:text-gray-500"
                        >
                            <div
                                class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-xl mb-2"
                            >
                                +
                            </div>

                            <p class="text-sm font-medium">Upload Document</p>

                            <span class="text-xs">
                                PNG, JPG, PDF up to 5MB
                            </span>
                        </div>

                        <div
                            v-if="branchDocumentPreview"
                            class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"
                        />
                    </div>

                    <input
                        ref="branchDocumentInput"
                        type="file"
                        accept="image/*,application/pdf"
                        class="hidden"
                        @change="handleBranchDocument"
                    />

                    <p
                        v-if="errors?.branch_document"
                        class="text-xs text-red-500"
                    >
                        {{ errors.branch_document }}
                    </p>

                    <div>
                        <button
                            type="button"
                            @click="
                                showBranchDocumentList = !showBranchDocumentList
                            "
                            class="flex items-center gap-1 text-xs font-medium text-primary hover:text-primary-600"
                        >
                            {{ showBranchDocumentList ? "Hide" : "Show" }}
                            applicable documents

                            <svg
                                class="w-3 h-3 transition-transform"
                                :class="{
                                    'rotate-180': showBranchDocumentList,
                                }"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <path
                                    d="M5 7.5L10 12.5L15 7.5"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>

                        <ul
                            v-if="showBranchDocumentList"
                            class="mt-2 space-y-1 rounded-lg bg-primary/5 border border-primary/10 p-3 text-[11px] text-slate-600 list-disc list-inside dark:text-gray-300"
                        >
                            <li
                                v-for="item in applicableBranchDocuments"
                                :key="item"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="space-y-5"
            data-field="location.street location.city location.province location.country location"
        >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Primary Address
                    </h2>

                    <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                        Choose between map location or manual address.
                    </p>

                    <p
                        v-if="locationError && useGeolocation"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ locationError }}
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <button
                        type="button"
                        @click="resetLocation"
                        class="text-xs font-medium text-red-500 hover:text-red-600 whitespace-nowrap"
                    >
                        Reset
                    </button>

                    <span class="text-xs text-slate-500 dark:text-gray-400 whitespace-nowrap"> Use map </span>

                    <button
                        type="button"
                        @click="useGeolocation = !useGeolocation"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                        :class="
                            useGeolocation
                                ? 'bg-primary'
                                : 'bg-slate-200 dark:bg-white/10'
                        "
                    >
                        <span
                            class="h-4 w-4 rounded-full bg-white shadow transition-transform dark:bg-secondary"
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
                        :initial-lat="branch.location?.latitude || undefined"
                        :initial-lng="branch.location?.longitude || undefined"
                        :initial-street="branch.location?.street || undefined"
                        :initial-city="branch.location?.city || undefined"
                        :initial-province="
                            branch.location?.province || undefined
                        "
                        :initial-country="branch.location?.country || undefined"
                        @location-selected="handleLocation"
                        @location-cleared="clearLocation"
                        ref="locationSelectorRef"
                    />

                    <template #fallback>
                        <div
                            class="h-64 rounded-xl bg-slate-50 flex items-center justify-center text-sm text-gray-400 dark:text-gray-500 dark:bg-secondary"
                        >
                            Loading map...
                        </div>
                    </template>
                </ClientOnly>
            </template>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <LabelInput
                    v-model="branch.location.street"
                    label="Street"
                    @update:modelValue="clearError('location.street')"
                    :error="errors?.['location.street']"
                    data-field="location.street"
                />

                <LabelInput
                    v-model="branch.location.city"
                    label="City"
                    @update:modelValue="clearError('location.city')"
                    :error="errors?.['location.city']"
                    data-field="location.city"
                />

                <LabelInput
                    v-model="branch.location.province"
                    label="Province"
                    @update:modelValue="clearError('location.province')"
                    :error="errors?.['location.province']"
                    data-field="location.province"
                />

                <LabelInput
                    v-model="branch.location.country"
                    label="Country"
                    @update:modelValue="clearError('location.country')"
                    :error="errors?.['location.country']"
                    data-field="location.country"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import LocationSelector from "../ui/LocationSelector.vue";
import LabelInput from "../ui/BaseInput.vue";
import PhoneInput from "../ui/PhoneInput.vue";
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

function initialPreview(value: unknown): string | null {
    if (typeof value === "string") return value;
    if (value instanceof File && value.type !== "application/pdf") {
        return URL.createObjectURL(value);
    }
    return null;
}

const branchImagePreview = ref<string | null>(initialPreview(props.branch.image));
const branchImageInput = ref<HTMLInputElement | null>(null);
const useGeolocation = ref(true);

const branchDocumentPreview = ref<string | null>(
    initialPreview((props.branch as any).document),
);
const branchDocumentInput = ref<HTMLInputElement | null>(null);
const showBranchDocumentList = ref(false);

// A previously saved PDF comes back as a URL string; one picked but not yet
// uploaded is still a raw File, so derive its name from whichever it is.
const branchDocumentName = ref<string | null>(
    (props.branch as any).document instanceof File &&
    (props.branch as any).document.type === "application/pdf"
        ? (props.branch as any).document.name
        : typeof (props.branch as any).document === "string" &&
            (props.branch as any).document.toLowerCase().endsWith(".pdf")
          ? decodeURIComponent(
                (props.branch as any).document.split("/").pop() ??
                    "Document.pdf",
            )
          : null,
);

const applicableBranchDocuments = [
    "Mayor's / Business Permit",
    "Barangay Clearance",
];

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

// Manual fields only exist in the DOM with map mode off, so a location error caught on the map would have nowhere to show.
watch(locationError, (hasError) => {
    if (hasError) {
        useGeolocation.value = false;
    }
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

const clearLocation = () => {
    emit("update:branch", {
        ...props.branch,
        location: {
            street: "",
            city: "",
            province: "",
            country: "",
            latitude: undefined,
            longitude: undefined,
        },
    });
};

const locationSelectorRef = ref<InstanceType<typeof LocationSelector> | null>(
    null,
);

const resetLocation = () => {
    if (useGeolocation.value && locationSelectorRef.value) {
        locationSelectorRef.value.clearSelection();
    } else {
        clearLocation();
    }
};

const handleBranchImage = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    emit("update:branch", { ...props.branch, image: file });
    branchImagePreview.value = URL.createObjectURL(file);
    clearError("image");
};

const removeBranchImage = () => {
    emit("update:branch", { ...props.branch, image: null });
    branchImagePreview.value = null;

    if (branchImageInput.value) {
        branchImageInput.value.value = "";
    }
};

const handleBranchDocument = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    emit("update:branch", { ...props.branch, document: file } as any);

    if (file.type === "application/pdf") {
        branchDocumentPreview.value = null;
        branchDocumentName.value = file.name;
    } else {
        branchDocumentPreview.value = URL.createObjectURL(file);
        branchDocumentName.value = null;
    }

    clearError("branch_document");
};

const removeBranchDocument = () => {
    emit("update:branch", { ...props.branch, document: null } as any);
    branchDocumentPreview.value = null;
    branchDocumentName.value = null;

    if (branchDocumentInput.value) {
        branchDocumentInput.value.value = "";
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
