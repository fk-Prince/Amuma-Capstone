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
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Verification Documents
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Upload a valid ID and a supporting document for
                    verification.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div class="space-y-2 p-4">
                    <div class="flex items-center justify-between">
                        <label
                            class="flex items-center gap-1.5 text-sm font-semibold text-slate-700"
                        >
                            <svg
                                class="w-4 h-4 text-slate-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="2"
                                    y="5"
                                    width="20"
                                    height="14"
                                    rx="2"
                                />
                                <circle cx="8" cy="12" r="2" />
                                <path d="M14 10h4" />
                                <path d="M14 14h4" />
                            </svg>

                            Valid ID
                            <span class="text-red-500">*</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="currentIdFile"
                                type="button"
                                @click="removeFile(idSide)"
                                class="text-xs font-medium text-red-500 hover:text-red-600"
                            >
                                Remove
                            </button>

                            <div
                                class="flex items-center rounded-full bg-slate-100 p-0.5 text-xs font-medium"
                            >
                                <button
                                    type="button"
                                    @click="idSide = 'id_front'"
                                    class="px-2.5 py-1 rounded-full transition"
                                    :class="
                                        idSide === 'id_front'
                                            ? 'bg-primary shadow-sm text-white'
                                            : 'text-slate-500'
                                    "
                                >
                                    Front
                                </button>

                                <button
                                    type="button"
                                    @click="idSide = 'id_back'"
                                    class="px-2.5 py-1 rounded-full transition"
                                    :class="
                                        idSide === 'id_back'
                                            ? 'bg-primary shadow-sm text-white'
                                            : 'text-slate-500'
                                    "
                                >
                                    Back
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="relative h-40 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group"
                        @click="idInput?.click()"
                    >
                        <img
                            v-if="currentIdPreview"
                            :src="currentIdPreview"
                            class="h-full w-full object-cover"
                        />

                        <div
                            v-else
                            class="absolute inset-0 flex flex-col items-center justify-center text-slate-400"
                        >
                            <div
                                class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-xl mb-2"
                            >
                                +
                            </div>

                            <p class="text-sm font-medium">
                                Upload ID
                                {{ idSide === "id_front" ? "Front" : "Back" }}
                            </p>

                            <span class="text-xs"> PNG, JPG up to 5MB </span>
                        </div>

                        <div
                            v-if="currentIdPreview"
                            class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"
                        />

                        <span
                            v-if="idFrontPreview && idBackPreview"
                            class="absolute bottom-2 right-2 flex gap-1"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="
                                    idSide === 'id_front'
                                        ? 'bg-primary'
                                        : 'bg-white/70'
                                "
                            />

                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="
                                    idSide === 'id_back'
                                        ? 'bg-primary'
                                        : 'bg-white/70'
                                "
                            />
                        </span>
                    </div>

                    <input
                        ref="idInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="(e) => handleFile(e, idSide)"
                    />

                    <p
                        v-if="errors?.agency_id_front || errors?.agency_id_back"
                        class="text-xs text-red-500"
                    >
                        {{ errors.agency_id_front || errors.agency_id_back }}
                    </p>

                    <div>
                        <button
                            type="button"
                            @click="showIdList = !showIdList"
                            class="flex items-center gap-1 text-xs font-medium text-primary hover:text-primary-600"
                        >
                            {{ showIdList ? "Hide" : "Show" }} applicable IDs

                            <svg
                                class="w-3 h-3 transition-transform"
                                :class="{ 'rotate-180': showIdList }"
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
                            v-if="showIdList"
                            class="mt-2 space-y-1 rounded-lg bg-primary/5 border border-primary/10 p-3 text-[11px] text-slate-600 list-disc list-inside"
                        >
                            <li v-for="item in applicableIds" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-2 p-4">
                    <div class="flex items-center justify-between">
                        <label
                            class="flex items-center gap-1.5 text-sm font-semibold text-slate-700"
                        >
                            <svg
                                class="w-4 h-4 text-slate-400"
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
                            v-if="agency.document"
                            type="button"
                            @click="removeFile('document')"
                            class="text-xs font-medium text-red-500 hover:text-red-600"
                        >
                            Remove
                        </button>
                    </div>

                    <div
                        class="relative h-40 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group"
                        @click="documentInput?.click()"
                    >
                        <img
                            v-if="documentPreview"
                            :src="documentPreview"
                            class="h-full w-full object-cover"
                        />

                        <!-- PDFs can't be previewed as an image, so show the
                             file name instead of an empty dropzone. -->
                        <div
                            v-else-if="fileNames.document"
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
                                class="max-w-full truncate text-sm font-medium text-slate-700"
                            >
                                {{ fileNames.document }}
                            </p>

                            <span class="mt-0.5 text-xs text-slate-400">
                                PDF selected — click to replace
                            </span>
                        </div>

                        <div
                            v-else
                            class="absolute inset-0 flex flex-col items-center justify-center text-slate-400"
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
                            v-if="documentPreview"
                            class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"
                        />
                    </div>

                    <input
                        ref="documentInput"
                        type="file"
                        accept="image/*,application/pdf"
                        class="hidden"
                        @change="(e) => handleFile(e, 'document')"
                    />

                    <p
                        v-if="errors?.agency_document"
                        class="text-xs text-red-500"
                    >
                        {{ errors.agency_document }}
                    </p>

                    <div>
                        <button
                            type="button"
                            @click="showDocumentList = !showDocumentList"
                            class="flex items-center gap-1 text-xs font-medium text-primary hover:text-primary-600"
                        >
                            {{ showDocumentList ? "Hide" : "Show" }} applicable
                            documents

                            <svg
                                class="w-3 h-3 transition-transform"
                                :class="{ 'rotate-180': showDocumentList }"
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
                            v-if="showDocumentList"
                            class="mt-2 space-y-1 rounded-lg bg-primary/5 border border-primary/10 p-3 text-[11px] text-slate-600 list-disc list-inside"
                        >
                            <li v-for="item in applicableDocuments" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
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

type FileField = "id_front" | "id_back" | "document";

const idSide = ref<"id_front" | "id_back">("id_front");
const idInput = ref<HTMLInputElement | null>(null);
const documentInput = ref<HTMLInputElement | null>(null);

const showIdList = ref(false);
const showDocumentList = ref(false);

const applicableIds = [
    "Philippine Passport",
    "Driver's License",
    "UMID (Unified Multi-Purpose ID)",
    "SSS ID",
    "PhilHealth ID",
    "PRC ID",
    "Voter's ID",
    "Postal ID",
    "PhilSys National ID (ePhilID)",
];

const applicableDocuments = [
    "DTI Business Name Registration",
    "SEC Certificate of Registration",
    // "Mayor's / Business Permit",
    "BIR Certificate of Registration (Form 2303)",
    "DOH / Home Health Agency Accreditation",
];

const idFrontPreview = ref<string | null>(
    typeof props.agency.id_front === "string" ? props.agency.id_front : null,
);
const idBackPreview = ref<string | null>(
    typeof props.agency.id_back === "string" ? props.agency.id_back : null,
);
const documentPreview = ref<string | null>(
    typeof props.agency.document === "string" ? props.agency.document : null,
);

const previewRefs: Record<FileField, ReturnType<typeof ref<string | null>>> = {
    id_front: idFrontPreview,
    id_back: idBackPreview,
    document: documentPreview,
};

// PDFs have no image preview, so their file name is what gets shown instead.
// A previously saved PDF arrives as a URL string, so derive the name from it.
const pdfNameFrom = (value: unknown): string | null =>
    typeof value === "string" && value.toLowerCase().endsWith(".pdf")
        ? decodeURIComponent(value.split("/").pop() ?? "Document.pdf")
        : null;

const fileNames = ref<Record<FileField, string | null>>({
    id_front: pdfNameFrom(props.agency.id_front),
    id_back: pdfNameFrom(props.agency.id_back),
    document: pdfNameFrom(props.agency.document),
});

const errorKeys: Record<FileField, string> = {
    id_front: "agency_id_front",
    id_back: "agency_id_back",
    document: "agency_document",
};

const currentIdFile = computed(() =>
    idSide.value === "id_front" ? agency.value.id_front : agency.value.id_back,
);

const currentIdPreview = computed(() =>
    idSide.value === "id_front" ? idFrontPreview.value : idBackPreview.value,
);

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

const handleFile = (event: Event, field: FileField) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    emit("update:agency", { ...agency.value, [field]: file });

    const previewRef = previewRefs[field];

    if (file.type === "application/pdf") {
        previewRef.value = null;
        fileNames.value = { ...fileNames.value, [field]: file.name };
    } else {
        previewRef.value = URL.createObjectURL(file);
        fileNames.value = { ...fileNames.value, [field]: null };
    }

    clearError(errorKeys[field]);
};

const removeFile = (field: FileField) => {
    emit("update:agency", { ...agency.value, [field]: null });

    previewRefs[field].value = null;
    fileNames.value = { ...fileNames.value, [field]: null };

    if (field === "document" && documentInput.value) {
        documentInput.value.value = "";
    }

    if ((field === "id_front" || field === "id_back") && idInput.value) {
        idInput.value.value = "";
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
