<template>
    <div class="min-h-screen-header bg-slate-50">
        <div class="w-full mx-auto px-4 lg:px-8 py-8">
            <div
                class="relative mb-6 grid w-full grid-cols-3 rounded-xl border border-slate-200 bg-white p-1 shadow-sm sm:inline-grid sm:w-auto"
            >
                <div
                    class="absolute inset-y-1 left-1 rounded-lg bg-primary transition-transform duration-300 ease-out"
                    :style="{
                        width: 'calc((100% - 0.45rem) / 3)',
                        transform: `translateX(${sliderOffset})`,
                    }"
                />

                <button
                    type="button"
                    class="relative z-10 rounded-lg px-2 sm:px-4 py-2 text-xs sm:text-sm font-medium transition-colors"
                    :class="
                        viewMode === 'form'
                            ? 'text-white'
                            : 'text-slate-500 hover:text-slate-700'
                    "
                    @click="viewMode = 'form'"
                >
                    New Admission
                </button>

                <button
                    type="button"
                    class="relative z-10 rounded-lg px-2 sm:px-4 py-2 text-xs sm:text-sm font-medium transition-colors"
                    :class="
                        viewMode === 'table'
                            ? 'text-white'
                            : 'text-slate-500 hover:text-slate-700'
                    "
                    @click="viewMode = 'table'"
                >
                    All Admissions
                </button>

                <button
                    type="button"
                    class="relative z-10 rounded-lg px-2 sm:px-4 py-2 text-xs sm:text-sm font-medium transition-colors"
                    :class="
                        viewMode === 'bookings'
                            ? 'text-white'
                            : 'text-slate-500 hover:text-slate-700'
                    "
                    @click="viewMode = 'bookings'"
                >
                    Admission Bookings
                </button>
            </div>

            <div v-if="viewMode === 'table'" class="min-h-[24rem] lg:h-[calc(100dvh-var(--header-h)-8rem)]">
                <DataTable
                    :columns="admissionColumns"
                    :rows="admissionRows"
                    :pagination="admissionPagination"
                    :loading="loadingAdmissions"
                    searchable
                    search-placeholder="Search by reference ID or patient name…"
                    empty-title="No admissions found"
                    empty-description="Try adjusting your search."
                    @search="onAdmissionSearch"
                    @page-change="onAdmissionPageChange"
                >
                    <!-- :on-row-click="selectAdmissionRow" -->
                    <template #actions>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                            @click="startNewAdmission"
                        >
                            + New Admission
                        </button>
                    </template>

                    <template #cell-status="{ value }">
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                            :class="statusBadgeClass(value)"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-reference_id="{ row }">
                        <span class="font-medium text-slate-800">
                            {{ row.reference_id }}
                        </span>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                class="font-medium text-primary hover:underline"
                                @click="viewAdmission(row)"
                            >
                                View
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>

            <div v-if="viewMode === 'bookings'" class="min-h-[24rem] lg:h-[calc(100dvh-var(--header-h)-8rem)]">
                <DataTable
                    :columns="bookingColumns"
                    :rows="bookingRows"
                    :pagination="bookingPagination"
                    :loading="loadingBookings"
                    searchable
                    search-placeholder="Search booking reference or patient name…"
                    empty-title="No bookings found"
                    empty-description="No pending admission bookings available."
                    @search="onBookingSearch"
                    @page-change="onBookingPageChange"
                >
                    <template #cell-status="{ value }">
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                            :class="statusBadgeClass(value)"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex justify-end">
                            <button
                                v-if="
                                    row.status?.toLowerCase() === 'approved' &&
                                    row.admission_type?.toLowerCase() ===
                                        'pre-admission'
                                "
                                type="button"
                                class="text-primary font-medium hover:underline"
                                @click="openBooking(row)"
                            >
                                Process
                            </button>
                            <!-- v-if="row.status?.toLowerCase() === 'pending'" -->
                            <!-- <button
                                type="button"
                                class="text-primary font-medium hover:underline"
                                @click="
                                    router.push({
                                        path: `/app/branches/${uuid}/bookings`,
                                        query: {
                                            reference_id: row.reference_id,
                                        },
                                    })
                                "
                            >
                                View Booking
                            </button> -->
                            <button
                                v-if="
                                    row.status?.toLowerCase() !== 'pending' &&
                                    row.status?.toLowerCase() !== 'rejected' &&
                                    row.status?.toLowerCase() !== 'missed'
                                "
                                type="button"
                                class="text-primary font-medium hover:underline"
                                @click="
                                    router.push({
                                        path: `/app/branches/${uuid}/patients/${row.p_uuid}`,
                                    })
                                "
                            >
                                View Patient
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>

            <div
                v-if="viewMode === 'form'"
                class="grid lg:grid-cols-[1fr_320px] gap-8"
            >
                <main class="bg-white rounded-2xl">
                    <div class="px-[3rem] md:px-[3.5rem] py-[1.5rem]">
                        <p
                            class="text-sm font-semibold text-primary uppercase tracking-wider"
                        >
                            Facility Admission
                        </p>

                        <h1 class="mt-2 text-3xl font-bold text-slate-900">
                            Patient Admission Request
                        </h1>

                        <p
                            class="mt-3 text-slate-500 leading-relaxed max-w-3xl"
                        >
                            Complete the information below to register a patient
                            for admission. Required fields are marked with
                            <span class="text-red-500">*</span>.
                        </p>
                    </div>

                    <div
                        class="px-[3rem] md:px-[3.5rem] flex items-center gap-4 w-full"
                    >
                        <BaseInput
                            class="flex-1"
                            label="Reference Number"
                            placeholder="BKN-000001"
                            :model-value="referenceInput"
                            @update:model-value="referenceInput = $event"
                        />

                        <button
                            type="button"
                            :disabled="loadingReference || !referenceInput"
                            @click="loadByReference"
                            class="flex items-center cursor-pointer mt-6 gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                        >
                            {{
                                loadingReference
                                    ? "Loading..."
                                    : "Load Admission"
                            }}
                        </button>
                    </div>

                    <section class="px-6" id="step1" ref="step1">
                        <AdmissionDetail
                            variant="page"
                            :loading="loadingContract"
                            :roomContract="roomContract"
                            :model="reserved"
                            :errors="reservedErrors"
                            @update:model="reserved = $event"
                            :requireAdmissionDate="true"
                        />
                    </section>

                    <section class="px-6" id="step2" ref="step2">
                        <PatientForm
                            category="facility"
                            :model="patientData"
                            :errors="patientErrors"
                            @update:model="Object.assign(patientData, $event)"
                            @update:errors="patientErrors = $event"
                        />
                    </section>

                    <section class="px-6" id="step1" ref="step2">
                        <GuardianForm
                            :isAdmission="true"
                            :model="guardianData"
                            :errors="guardianErrors"
                            @update:model="Object.assign(guardianData, $event)"
                            @update:errors="guardianErrors = $event"
                        />
                    </section>

                    <section class="px-6" id="step1" ref="step4">
                        <AssessmentForm
                            :model="assessmentData"
                            :errors="assessmentErrors"
                            @update:model="
                                Object.assign(assessmentData, $event)
                            "
                            @update:errors="assessmentErrors = $event"
                        />
                    </section>
                </main>

                <aside class="hidden lg:block">
                    <div class="sticky top-8 space-y-5">
                        <div class="px-6 py-6 border-b">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                            >
                                Completion
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <div
                                    class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary transition-all duration-300"
                                        :style="{ width: `${progress}%` }"
                                    ></div>
                                </div>

                                <span
                                    class="text-xs font-medium text-gray-400 shrink-0"
                                >
                                    {{ Math.round(progress) }}%
                                </span>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto px-3 py-4">
                            <BookingSteps
                                :active="activeStep"
                                :completed="completedSteps"
                                @go="scrollTo"
                            />
                        </div>

                        <BaseButton class="w-full py-3" @click="submit">
                            {{ actionLabel }}
                        </BaseButton>
                    </div>
                </aside>
            </div>

            <div
                v-if="viewMode === 'form'"
                class="lg:hidden sticky bottom-0 left-0 right-0 border-t p-4"
            >
                <BaseButton class="w-full py-3" @click="submit">
                    {{ actionLabel }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed, ref, onMounted, toRaw, watch, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useBookingFlowValidation } from "~/composables/useBookingFlowValidation";

import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import GuardianForm from "~/components/forms/GuardianForm.vue";
import PatientForm from "~/components/forms/PatientForm.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";

import {
    patientData,
    createPatientSchema,
    assessmentData,
    assessmentSchema,
    guardianData,
    guardianSchema,
} from "~/schema/patient-schema";

import {
    homecareData,
    createHomecareBookingSchema,
    facilityData,
    facilityBookingSchema,
} from "~/schema/booking-schema";

import { useBookingStore } from "~/stores/booking";
import { useBranch } from "~/composables/useBranchProvider";
import { branchContractService } from "~/api/branch-contract/BranchContractService";
import { usePagination } from "~/composables/usePagination";
import type { RoomContract } from "~/types/contract";
import { reserved } from "~/types/contract";
import type { BookingRetrieve } from "~/types/booking";
import { stringToDateTime, formatDate } from "~/utils/time";
import type { PatientRetrieve } from "~/types/patient";
import { patientService } from "~/api/patient/PatientService";
import { admissionService } from "~/api/admission/AdmissionService";
import AdmissionDetail from "~/components/sections/app/Admission/AdmissionDetail.vue";
import { useToast } from "~/composables/useToast";

useHead({ title: "Admission" });

definePageMeta({
    layout: "dashboard",
    middleware: ["auth-client"],
});

const { success, error } = useToast();
const bookingStore = useBookingStore();
const route = useRoute();
const router = useRouter();
const uuid = computed(() => route.params.uuid as string);
const { branch } = useBranch();
const loading = ref(true);
const category = computed<"facility">(() => "facility");
const validationMode = ref<"facility" | "reserved">("reserved");
const patientSchema = computed(() => createPatientSchema(category.value));
const homecareSchema = computed(() =>
    createHomecareBookingSchema(branch.value?.homecare.adl_min_hour ?? 0),
);

function viewAdmission(row: any) {
    router.push({
        path: `/app/branches/${uuid.value}/admissions/${row.p_uuid}`,
    });
}

const {
    facilityErrors,
    reservedErrors,
    patientErrors,
    guardianErrors,
    assessmentErrors,
    progress,
    validateAll,
    completedSteps,
} = useBookingFlowValidation({
    category,
    validationMode,
    homecareSchema,
    facilityBookingSchema,
    patientSchema,
    guardianSchema,
    assessmentSchema,
    homecareData,
    facilityData,
    patientData,
    guardianData,
    assessmentData,
    reserved,
});

const viewMode = ref<"form" | "table" | "bookings">("form");
const isPaid = ref(false);
const referenceInput = ref((route.query.reference_id as string) ?? "");
const roomContract = ref<RoomContract[]>([]);
const loadingContract = ref(true);
const loadingReference = ref(false);
const referenceError = ref("");

async function loadByReference() {
    if (!referenceInput.value) return;

    loadingReference.value = true;
    referenceError.value = "";

    try {
        const [bookingResponse] = await Promise.all([
            admissionService.show(referenceInput.value, {
                branch_uuid: uuid.value,
                reference_id: referenceInput.value,
            }),
            loadRoomContracts(),
        ]);

        const booking: BookingRetrieve =
            bookingResponse.data ?? bookingResponse;

        Object.assign(patientData, {
            first_name: booking.patient?.first_name ?? "",
            middle_name: booking.patient?.middle_name ?? "",
            last_name: booking.patient?.last_name ?? "",
            gender: booking.patient?.gender ?? "",
            citizenship: booking.patient?.citizenship ?? "",
            occupation: booking.patient?.occupation ?? "",
            date_of_birth: booking.patient?.date_of_birth ?? "",
            phone_number: booking.patient?.phone_number ?? "",
            marital_status: booking.patient?.marital_status ?? "",
            height: booking.patient?.height ?? "",
            weight: booking.patient?.weight ?? "",
            blood_type: booking.patient?.blood_type ?? "",
            address: booking.patient?.address ?? "",
            allergies: booking.patient?.allergies ?? "",
        });

        Object.assign(guardianData, {
            first_name: booking.guardian?.first_name ?? "",
            middle_name: booking.guardian?.middle_name ?? "",
            last_name: booking.guardian?.last_name ?? "",
            phone_number: booking.guardian?.phone_number ?? "",
            email: booking.guardian?.email ?? "",
            relationship: booking.guardian?.relationship ?? "",
            occupation: booking.guardian?.occupation ?? "",
            address: booking.guardian?.address ?? "",
        });

        if (booking.assessment) {
            Object.assign(assessmentData, booking.assessment);
        }

        if (booking.facility) {
            Object.assign(facilityData, booking.facility);
        }

        isPaid.value = Boolean(booking.payment?.paid);
        if (booking.reserved) {
            reserved.value = {
                room: booking.reserved.room,
                bed: booking.reserved.bed,
                contract_id: booking.reserved.contract_id,
                billing_cycle: booking.reserved.billing_cycle,
                price: booking.reserved.price,
                accommodation_type: normalizeAccommodationType(
                    booking.reserved.accommodation_type,
                ),
                admitted_at: booking.reserved.admitted_at,
            };
        }

        bookingStore.lastSubmittedId = booking.reference_id;

        router.replace({
            query: {
                ...route.query,
                reference_id: booking.reference_id,
            },
        });
    } catch (err: any) {
        error(err.message ?? "Internal Server Error.");
        referenceError.value =
            err.message ?? "Couldn't find an admission with that reference ID.";
    } finally {
        loadingReference.value = false;
    }
}
function normalizeAccommodationType(type: string): "Common" | "VIP" {
    return type?.toUpperCase() === "VIP" ? "VIP" : "Common";
}
const actionLabel = computed(() => {
    if (!referenceInput.value) {
        return "Submit Admission";
    }

    return isPaid.value ? "Admit Patient" : "Submit Admission";
});
async function loadRoomContracts() {
    loadingContract.value = true;

    try {
        const response = await branchContractService.list({
            type: "room_contract",
            branch_uuid: uuid.value,
        });
        roomContract.value = response;
    } catch (err) {
        console.error("Failed loading room contracts", err);
        roomContract.value = [];
    } finally {
        loadingContract.value = false;
    }
}

const admissionColumns: DataTableColumn[] = [
    { key: "p_uuid", label: "Patient ID" },
    { key: "patient_name", label: "Patient", sortable: true },
    { key: "accommodation", label: "Accommodation" },
    { key: "room_bed", label: "Room & Bed" },
    { key: "status", label: "Status" },
    { key: "admission_date", label: "Admission Date", sortable: true },
    { key: "actions", label: "Action", align: "right" },
];

const bookingColumns: DataTableColumn[] = [
    { key: "reference_id", label: "Reference ID" },
    { key: "patient_name", label: "Patient" },
    { key: "accommodation", label: "Accommodation" },
    { key: "room_bed", label: "Room / Bed" },
    { key: "status", label: "Status" },
    { key: "created_at", label: "Created Date", sortable: true },
    { key: "actions", label: "Action", align: "right" },
];
const bookingRows = ref<any[]>([]);
const loadingBookings = ref(false);
const bookingSearchQuery = ref("");

const bookingPagination = usePagination({
    pageSize: 10,
});

async function fetchBookings() {
    loadingBookings.value = true;

    try {
        const response = await admissionService.list({
            branch_uuid: uuid.value,
            search: bookingSearchQuery.value,
            page: bookingPagination.currentPage.value,
            per_page: bookingPagination.pageSize.value,
            category: "facility",
            type: "booking-admission",
        });
        bookingRows.value = (response.data ?? []).map(
            (booking: BookingRetrieve) => ({
                p_uuid: booking.patient.uuid,
                reference_id: booking.reference_id,
                patient_name: [
                    booking.patient.first_name,
                    booking.patient.last_name,
                ]
                    .filter(Boolean)
                    .join(" "),
                accommodation: booking.reserved?.accommodation_type ?? "N / A",

                room_bed:
                    booking.reserved?.room?.room_no ||
                    booking.reserved?.bed?.bed_no
                        ? [
                              booking.reserved?.room?.room_no,
                              booking.reserved?.bed?.bed_no,
                          ]
                              .filter(Boolean)
                              .join(" / ")
                        : "N / A",
                status: booking.status,
                created_at: stringToDateTime(booking.created_at) ?? "—",
            }),
        );
        bookingPagination.totalItems.value = response.meta?.total ?? 0;
    } catch (err) {
        console.error("Failed loading bookings", err);
        bookingRows.value = [];
    } finally {
        loadingBookings.value = false;
    }
}

function onBookingSearch(query: string) {
    bookingSearchQuery.value = query;
    fetchBookings();
}

function onBookingPageChange() {
    fetchBookings();
}

function openBooking(row: any) {
    referenceInput.value = row.reference_id;
    viewMode.value = "form";
    loadByReference();
}

const admissionRows = ref<any[]>([]);
const loadingAdmissions = ref(false);
const admissionSearchQuery = ref("");

const admissionPagination = usePagination({
    pageSize: 10,
});

async function fetchAdmissions() {
    loadingAdmissions.value = true;

    try {
        const response = await patientService.list({
            branch_uuid: uuid.value,
            type: "admission",
            search: admissionSearchQuery.value,
            page: admissionPagination.currentPage.value,
            per_page: admissionPagination.pageSize.value,
        });
        admissionRows.value = (response.data ?? []).map(
            (data: PatientRetrieve) => {
                const admission = data.latest_admission;
                const isActiveAdmission = ["waiting", "admitted"].includes(
                    admission?.status ?? "",
                );

                return {
                    p_uuid: data.uuid,
                    patient_admission_id: admission?.patient_admission_id,

                    patient_name: data.full_name,

                    accommodation: isActiveAdmission
                        ? (admission?.current_contract?.accommodation_type ??
                          "—")
                        : "—",

                    room_bed: isActiveAdmission
                        ? [admission?.room?.room_no, admission?.bed?.bed_no]
                              .filter(Boolean)
                              .join(" / ") || "N/A"
                        : "—",

                    status: admission?.status ?? "—",

                    admission_date: admission?.admitted_at
                        ? formatDate(admission.admitted_at)
                        : "—",
                };
            },
        );
        admissionPagination.totalItems.value = response.meta?.total ?? 0;
    } catch (err) {
        console.error("Failed loading admissions", err);
        admissionRows.value = [];
        admissionPagination.totalItems.value = 0;
    } finally {
        loadingAdmissions.value = false;
    }
}

function onAdmissionSearch(query: string) {
    admissionSearchQuery.value = query;
    fetchAdmissions();
}

function onAdmissionPageChange(_page: number) {
    fetchAdmissions();
}

function statusBadgeClass(status: string) {
    const value = (status ?? "").toLowerCase();

    if (value === "approved" || value === "admitted") {
        return "bg-emerald-100 text-emerald-700";
    }

    if (value === "pending") {
        return "bg-amber-100 text-amber-700";
    }

    if (value === "cancelled" || value === "rejected") {
        return "bg-rose-100 text-rose-700";
    }

    if (value === "waiting") {
        return "bg-blue-100 text-blue-700";
    }

    return "bg-slate-100 text-slate-600";
}

function startNewAdmission() {
    referenceInput.value = "";
    referenceError.value = "";
    viewMode.value = "form";
}

watch(viewMode, (mode) => {
    if (mode === "table" && !admissionRows.value.length) {
        fetchAdmissions();
    }

    if (mode === "bookings" && !bookingRows.value.length) {
        fetchBookings();
    }
});

onMounted(async () => {
    loading.value = true;

    try {
        if (referenceInput.value) {
            await loadByReference();
        } else {
            await loadRoomContracts();
        }
        await nextTick();

        if (route.query.step) {
            scrollTo(route.query.step as string);
        }
    } finally {
        loading.value = false;
    }
});

async function submit() {
    const firstInvalid = validateAll();
    if (firstInvalid) {
        scrollTo(firstInvalid);
        return;
    }

    bookingStore.contract = roomContract.value;
    bookingStore.reserved = deepToRaw(reserved.value);
    bookingStore.category = "facility";
    bookingStore.booking_type = "walk-in";
    bookingStore.payment.total_amount = reserved.value.price;
    facilityData.plan = reserved.value.accommodation_type;
    facilityData.admission_date = new Date().toISOString();
    bookingStore.facility = deepToRaw(facilityData);
    bookingStore.patient = deepToRaw(patientData);
    bookingStore.guardian = deepToRaw(guardianData);
    bookingStore.assessment = deepToRaw(assessmentData);
    bookingStore.branchFacility = branch.value?.facility ?? [];

    router.push({
        path: `/app/branches/${uuid.value}/admissions/review`,
        query: {
            reference_id: referenceInput.value,
        },
    });
}

function deepToRaw<T>(val: T): T {
    if (Array.isArray(val)) {
        return val.map(deepToRaw) as any;
    }
    if (val && typeof val === "object") {
        const raw = toRaw(val);
        return Object.fromEntries(
            Object.entries(raw).map(([k, v]) => [k, deepToRaw(v)]),
        ) as any;
    }
    return val;
}

const step1 = ref<HTMLElement | null>(null);
const step2 = ref<HTMLElement | null>(null);
const step3 = ref<HTMLElement | null>(null);
const step4 = ref<HTMLElement | null>(null);

const activeStep = ref("step1");

const scrollTo = (step: string) => {
    if (step === "step5") {
        submit();
        return;
    }

    activeStep.value = step;

    const map: Record<string, any> = {
        step1,
        step2,
        step3,
        step4,
    };

    nextTick(() => {
        map[step]?.value?.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
    });
};
const viewModes = ["form", "table", "bookings"] as const;
const sliderOffset = computed(() => {
    const index = viewModes.indexOf(
        viewMode.value as (typeof viewModes)[number],
    );

    return `${index * 100}%`;
});
</script>
