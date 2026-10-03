<template>
    <div class="w-full mx-auto space-y-8" :class="cardClass">
        <div class="space-y-6">
            <FormSectionHeader
                :title="isNew ? 'Agency Profile' : 'Agency Information'"
                description="Configure your agency profile, branding, and agency details."
                :icon="isNew ? Building2 : undefined"
            />

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_220px] gap-8">
                <div class="space-y-5">
                    <LabelInput
                        v-model="agency.name"
                        label="Agency Name"
                        placeholder="Enter agency name"
                        :disabled="lockVerification"
                        :error="errors?.agency_name"
                        @update:modelValue="clearError('agency_name')"
                        data-field="agency_name"
                    />

                    <LabelInput
                        v-model="agency.email"
                        label="Email Address"
                        type="email"
                        placeholder="Enter agency email address"
                        @update:modelValue="clearError('agency_email')"
                        :error="errors?.agency_email"
                        data-field="agency_email"
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
                        @update:modelValue="clearError('agency_description')"
                        data-field="agency_description"
                    />
                </div>

                <div class="space-y-2" data-field="agency_image">
                    <div class="flex items-center justify-between">
                        <label
                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
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
                        class="relative h-52 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group dark:bg-secondary dark:border-white/10"
                        @click="agencyImageInput?.click()"
                    >
                        <img
                            v-if="agencyImagePreview"
                            :src="agencyImagePreview"
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
        <div class="space-y-5" :class="dividerClass">
            <FormSectionHeader
                title="Verification Documents"
                :description="
                    lockVerification
                        ? 'The documents this agency was verified with, these cannot be changed here.'
                        : 'Upload a valid ID and a supporting document for verification.'
                "
                :icon="isNew ? ShieldCheck : undefined"
            />

            <div v-if="lockVerification" class="space-y-2">
                <p
                    class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                >
                    Documents
                </p>

                <div v-if="hasVerificationFiles" class="flex flex-wrap gap-1.5">
                    <DocumentLink
                        v-if="fileUrls.id_front"
                        :url="fileUrls.id_front"
                        label="ID Front"
                    />

                    <DocumentLink
                        v-if="fileUrls.id_back"
                        :url="fileUrls.id_back"
                        label="ID Back"
                    />

                    <DocumentLink
                        v-if="fileUrls.document"
                        :url="fileUrls.document"
                        label="Agency Document"
                    />
                </div>

                <p v-else class="text-xs text-muted dark:text-gray-500">
                    No documents on file
                </p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div
                    class="space-y-2 p-4"
                    data-field="agency_id_front agency_id_back"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
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
                                class="flex items-center gap-0.5 rounded-full border border-slate-200 bg-slate-100 p-0.5 text-xs font-semibold dark:border-white/10 dark:bg-white/5"
                            >
                                <button
                                    type="button"
                                    @click="idSide = 'id_front'"
                                    class="min-w-[52px] rounded-full px-3 py-1.5 text-center transition"
                                    :class="
                                        idSide === 'id_front'
                                            ? 'bg-primary text-white shadow-sm'
                                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    "
                                >
                                    Front
                                </button>

                                <button
                                    type="button"
                                    @click="idSide = 'id_back'"
                                    class="min-w-[52px] rounded-full px-3 py-1.5 text-center transition"
                                    :class="
                                        idSide === 'id_back'
                                            ? 'bg-primary text-white shadow-sm'
                                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    "
                                >
                                    Back
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="relative h-40 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group dark:bg-secondary dark:border-white/10"
                        @click="idInput?.click()"
                    >
                        <img
                            v-if="currentIdPreview"
                            :src="currentIdPreview"
                            class="h-full w-full object-cover"
                        />

                        <div
                            v-else
                            class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 dark:text-gray-500"
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
                        v-for="message in idErrors"
                        :key="message"
                        class="text-xs text-red-500"
                    >
                        {{ message }}
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
                            class="mt-2 space-y-1 rounded-lg bg-primary/5 border border-primary/10 p-3 text-[11px] text-slate-600 list-disc list-inside dark:text-gray-300"
                        >
                            <li v-for="item in applicableIds" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-2 p-4" data-field="agency_document">
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
                            v-if="agency.document"
                            type="button"
                            @click="removeFile('document')"
                            class="text-xs font-medium text-red-500 hover:text-red-600"
                        >
                            Remove
                        </button>
                    </div>

                    <div
                        class="relative h-40 w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden cursor-pointer hover:border-primary/40 hover:bg-slate-100 transition group dark:bg-secondary dark:border-white/10"
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
                                class="max-w-full truncate text-sm font-medium text-slate-700 dark:text-gray-300"
                            >
                                {{ fileNames.document }}
                            </p>

                            <span
                                class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                            >
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
                            class="mt-2 space-y-1 rounded-lg bg-primary/5 border border-primary/10 p-3 text-[11px] text-slate-600 list-disc list-inside dark:text-gray-300"
                        >
                            <li v-for="item in applicableDocuments" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="space-y-5"
            :class="dividerClass"
            data-field="location.street location.city location.province location.country location"
        >
            <FormSectionHeader
                title="Primary Address"
                required
                description="Pick the agency location on the map."
                :icon="isNew ? MapPin : undefined"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="resetLocation"
                        class="text-xs font-medium text-red-500 hover:text-red-600 whitespace-nowrap"
                    >
                        Reset
                    </button>
                </template>
            </FormSectionHeader>

            <ClientOnly>
                <LocationSelector
                    :initial-lat="agency.location?.latitude || undefined"
                    :initial-lng="agency.location?.longitude || undefined"
                    :initial-street="agency.location?.street || undefined"
                    :initial-city="agency.location?.city || undefined"
                    :initial-province="agency.location?.province || undefined"
                    :initial-country="agency.location?.country || undefined"
                    manual-fallback
                    @location-selected="handleLocation"
                    @location-cleared="clearLocation"
                    ref="locationSelectorRef"
                />

                <template #fallback>
                    <div
                        class="w-full h-[400px] rounded-xl border border-gray-200 bg-slate-50 animate-pulse dark:border-white/10 dark:bg-secondary"
                    />
                </template>
            </ClientOnly>

            <p v-if="locationError" class="text-xs text-red-500">
                {{ locationError }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Building2, Check, MapPin, ShieldCheck } from "lucide-vue-next";
import FormSectionHeader from "../ui/FormSectionHeader.vue";
import { ref, computed } from "vue";
import LocationSelector from "../ui/LocationSelector.vue";
import LabelInput from "../ui/BaseInput.vue";
import DocumentLink from "../ui/DocumentLink.vue";
import type { Agency } from "~/types/agency";

const props = defineProps<{
    agency: Agency | any;
    errors?: Record<string, string> | null;
    mode?: "new" | "edit";
    lockVerification?: boolean;
}>();

const isNew = computed(() => props.mode === "new");

const cardClass = computed(() =>
    isNew.value
        ? "rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8 dark:border-white/10 dark:bg-white/[0.03]"
        : "",
);

const dividerClass = computed(() =>
    isNew.value ? "border-t border-slate-200 pt-8 dark:border-white/10" : "",
);

const fileUrls = computed(() => ({
    id_front:
        typeof props.agency.id_front === "string" ? props.agency.id_front : "",
    id_back:
        typeof props.agency.id_back === "string" ? props.agency.id_back : "",
    document:
        typeof props.agency.document === "string" ? props.agency.document : "",
}));

const hasVerificationFiles = computed(() =>
    Object.values(fileUrls.value).some(Boolean),
);

const emit = defineEmits<{
    (e: "update:agency", value: Agency | any): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const agency = computed({
    get: () => props.agency,
    set: (value) => emit("update:agency", value),
});

const errors = computed(() => props.errors);

function initialPreview(value: unknown): string | null {
    if (typeof value === "string") return value;
    if (value instanceof File && value.type !== "application/pdf") {
        return URL.createObjectURL(value);
    }
    return null;
}

const agencyImagePreview = ref<string | null>(
    initialPreview(props.agency.image),
);
const agencyImageInput = ref<HTMLInputElement | null>(null);

type FileField = "id_front" | "id_back" | "document";

const idSide = ref<"id_front" | "id_back">("id_front");

const idErrors = computed(() => {
    const front = props.errors?.agency_id_front;
    const back = props.errors?.agency_id_back;
    if (front?.includes("required") && back?.includes("required")) {
        return ["ID Front & Back is required"];
    }
    return [...new Set([front, back].filter(Boolean))];
});
const idInput = ref<HTMLInputElement | null>(null);
const documentInput = ref<HTMLInputElement | null>(null);

const showIdList = ref(false);
const showDocumentList = ref(false);

const applicableIds = [
    "Philippine Passport",
    "Driver's License",
    "UMID (Unified Multi-Purpose ID)",
    "SSS ID (Social Security System)",
    "PRC ID (Professional Regulation Commission)",
    "PhilSys National ID (ePhilID)",
];

const applicableDocuments = [
    "DTI Business Name Registration",
    "SEC Certificate of Registration",
    "BIR Certificate of Registration (Form 2303)",
    "DOH / Home Health Agency Accreditation",
];

const idFrontPreview = ref<string | null>(
    initialPreview(props.agency.id_front),
);
const idBackPreview = ref<string | null>(initialPreview(props.agency.id_back));
const documentPreview = ref<string | null>(
    initialPreview(props.agency.document),
);

const previewRefs: Record<FileField, ReturnType<typeof ref<string | null>>> = {
    id_front: idFrontPreview,
    id_back: idBackPreview,
    document: documentPreview,
};

// PDFs have no image preview, so their file name is what gets shown instead.
// A previously saved PDF arrives as a URL string; one picked but not yet
// uploaded is still a raw File, so derive the name from whichever it is.
const pdfNameFrom = (value: unknown): string | null => {
    if (value instanceof File && value.type === "application/pdf") {
        return value.name;
    }

    return typeof value === "string" && value.toLowerCase().endsWith(".pdf")
        ? decodeURIComponent(value.split("/").pop() ?? "Document.pdf")
        : null;
};

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
        ? "Location is required. Pick a spot on the map or enter the full address with the city and province."
        : "";
});

const handleLocation = ({
    lat,
    lng,
    label,
    street,
    city,
    province,
    country,
}: {
    lat: number | null;
    lng: number | null;
    label: string;
    street: string;
    city: string;
    province: string;
    country: string;
}) => {
    const resolvedStreet = street || label.split(",")[0]?.trim() || "";

    emit("update:agency", {
        ...agency.value,
        location: {
            street: resolvedStreet,
            full_address: label,
            city: city ?? "",
            province: province ?? "",
            country: country ?? "",
            latitude: lat ?? undefined,
            longitude: lng ?? undefined,
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
        !resolvedStreet?.trim() ||
        !city?.trim() ||
        !province?.trim() ||
        !country?.trim()
    ) {
        if (!resolvedStreet?.trim()) {
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
    }

    emit("update:errors", updatedErrors);
};

const clearLocation = () => {
    emit("update:agency", {
        ...agency.value,
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
    if (locationSelectorRef.value) {
        locationSelectorRef.value.clearSelection();
    } else {
        clearLocation();
    }
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
