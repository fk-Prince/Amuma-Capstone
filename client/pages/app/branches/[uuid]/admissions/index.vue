<template>
    <div class="min-h-screen-header bg-slate-50 dark:bg-surface rounded-lg">
        <div class="w-full mx-auto p-4">
            <PlanLockNotice
                v-if="facilityLocked"
                class="mb-6"
                title="Admissions are read-only"
                message="This branch has no In-house Facility plan. You can view admissions, but starting or processing one is locked."
            />

            <div
                class="relative mb-6 grid w-full grid-cols-3 rounded-xl border border-slate-200 bg-white p-1 shadow-sm sm:inline-grid sm:w-auto dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="absolute inset-y-1 left-1 rounded-lg bg-primary transition-transform duration-300 ease-out"
                    :style="{
                        width: 'calc((100% - 0.5rem) / 3)',
                        transform: `translateX(${sliderOffset})`,
                    }"
                />

                <button
                    type="button"
                    :disabled="facilityLocked"
                    :title="
                        facilityLocked
                            ? 'Locked — this branch has no In-house Facility plan.'
                            : undefined
                    "
                    class="relative z-10 rounded-lg px-2 sm:px-4 py-2 text-center text-xs sm:text-sm font-medium leading-tight sm:whitespace-nowrap transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                    :class="
                        viewMode === 'form'
                            ? 'text-white'
                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
                    "
                    @click="viewMode = 'form'"
                >
                    New Admission
                </button>

                <button
                    type="button"
                    class="relative z-10 rounded-lg px-2 sm:px-4 py-2 text-center text-xs sm:text-sm font-medium leading-tight sm:whitespace-nowrap transition-colors"
                    :class="
                        viewMode === 'table'
                            ? 'text-white'
                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
                    "
                    @click="viewMode = 'table'"
                >
                    All Admissions
                </button>

                <button
                    type="button"
                    class="relative z-10 rounded-lg px-2 sm:px-4 py-2 text-center text-xs sm:text-sm font-medium leading-tight sm:whitespace-nowrap transition-colors"
                    :class="
                        viewMode === 'bookings'
                            ? 'text-white'
                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
                    "
                    @click="viewMode = 'bookings'"
                >
                    Admission Bookings
                </button>
            </div>

            <div
                v-if="viewMode === 'table'"
                class="min-h-[24rem] lg:h-[calc(100dvh-var(--header-h)-8rem)]"
            >
                <DataTable
                    :columns="admissionColumns"
                    :rows="admissionRows"
                    :pagination="admissionPagination"
                    :loading="loadingAdmissions"
                    searchable
                    search-placeholder="Search by patient code or name…"
                    empty-title="No admissions found"
                    empty-description="Try adjusting your search."
                    @search="onAdmissionSearch"
                    @page-change="onAdmissionPageChange"
                >
                    <!-- :on-row-click="selectAdmissionRow" -->
                    <template #actions>
                        <button
                            v-if="!facilityLocked"
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
                        <span
                            class="font-medium text-slate-800 dark:text-white"
                        >
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

            <div
                v-if="viewMode === 'bookings'"
                class="min-h-[24rem] lg:h-[calc(100dvh-var(--header-h)-8rem)]"
            >
                <DataTable
                    :columns="bookingColumns"
                    :rows="bookingRows"
                    :pagination="bookingPagination"
                    :loading="loadingBookings"
                    searchable
                    search-placeholder="Search by name or booking reference…"
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
                        <div class="flex flex-wrap justify-end gap-x-4 gap-y-1">
                            <button
                                v-if="canProcessBooking(row)"
                                type="button"
                                class="text-primary font-medium hover:underline"
                                @click="openBooking(row)"
                            >
                                Process
                            </button>
                            <button
                                v-if="
                                    row.p_uuid &&
                                    row.status?.toLowerCase() !== 'pending' &&
                                    row.status?.toLowerCase() !== 'rejected' &&
                                    row.status?.toLowerCase() !== 'missed'
                                "
                                type="button"
                                class="text-primary font-medium hover:underline"
                                @click="viewAdmission(row)"
                            >
                                View Admission
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>

            <div
                v-if="viewMode === 'form'"
                class="grid lg:grid-cols-[1fr_320px] gap-8"
            >
                <main class="bg-white rounded-2xl dark:bg-secondary">
                    <div class="px-[3rem] md:px-[3.5rem] py-[1.5rem]">
                        <p
                            class="text-sm font-semibold text-primary uppercase tracking-wider"
                        >
                            Facility Admission
                        </p>

                        <h1
                            class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"
                        >
                            Patient Admission Request
                        </h1>

                        <p
                            class="mt-3 text-slate-500 leading-relaxed max-w-3xl dark:text-gray-400"
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

                    <div
                        v-if="referenceNotice"
                        class="mx-[3rem] md:mx-[3.5rem] mt-3 flex items-start gap-2 rounded-xl border px-4 py-3 text-[13px] leading-relaxed"
                        :class="{
                            'border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300':
                                referenceNotice.tone === 'pending',
                            'border-emerald-100 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300':
                                referenceNotice.tone === 'approved',
                            'border-sky-100 bg-sky-50 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300':
                                referenceNotice.tone === 'processed',
                            'border-rose-100 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300':
                                referenceNotice.tone === 'blocked',
                        }"
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ referenceNotice.message }}</span>
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

                    <section class="px-6" id="step3" ref="step3">
                        <GuardianForm
                            :isAdmission="true"
                            :model="guardianData"
                            :errors="guardianErrors"
                            @update:model="Object.assign(guardianData, $event)"
                            @update:errors="guardianErrors = $event"
                        />
                    </section>

                    <section class="px-6" id="step4" ref="step4">
                        <DiagnosisForm
                            :model="diagnosisData"
                            :errors="assessmentErrors"
                            @update:model="
                                diagnosisData.splice(
                                    0,
                                    diagnosisData.length,
                                    ...$event,
                                )
                            "
                            @update:errors="assessmentErrors = $event"
                        />
                    </section>

                    <section class="px-6" id="step5" ref="step5">
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
                        <BookingProgressHeader
                            title="Admission Progress"
                            :progress="progress"
                        />

                        <div class="flex-1 overflow-y-auto px-3 py-2">
                            <BookingSteps
                                compact
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
import {
    computed,
    ref,
    onMounted,
    onBeforeUnmount,
    toRaw,
    watch,
    nextTick,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import { useBookingFlowValidation } from "~/composables/useBookingFlowValidation";

import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import BookingProgressHeader from "~/components/sections/booking/provider/BookingProgressHeader.vue";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import GuardianForm from "~/components/forms/GuardianForm.vue";
import PatientForm from "~/components/forms/PatientForm.vue";
import { Info } from "lucide-vue-next";
import BaseButton from "~/components/ui/BaseButton.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";
import DiagnosisForm from "~/components/forms/DiagnosisForm.vue";

import {
    patientData,
    createPatientSchema,
    assessmentData,
    diagnosisData,
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
import { useBranchPlan } from "~/composables/useBranchPlan";
import PlanLockNotice from "~/components/ui/PlanLockNotice.vue";

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
    diagnosisData,
    reserved,
});

const { hasFacilityPlan } = useBranchPlan();
const facilityLocked = computed(() => !hasFacilityPlan.value);
const viewMode = ref<"form" | "table" | "bookings">(
    facilityLocked.value ? "table" : "form",
);
const isPaid = ref(false);
const referenceInput = ref((route.query.reference_id as string) ?? "");
const roomContract = ref<RoomContract[]>([]);
const loadingContract = ref(true);
const loadingReference = ref(false);
const referenceError = ref("");
const bookingProcessed = ref(false);
const referenceNotice = ref<{
    tone: "pending" | "approved" | "processed" | "blocked";
    message: string;
} | null>(null);

async function loadByReference() {
    if (!referenceInput.value) return;

    loadingReference.value = true;
    referenceError.value = "";
    referenceNotice.value = null;
    bookingProcessed.value = false;

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

        if (booking.is_processed) {
            bookingProcessed.value = true;
            referenceNotice.value = {
                tone: "processed",
                message: `Booking ${booking.reference_id} has already been processed.`,
            };
            return;
        }

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
            avatar: booking.patient?.avatar ?? null,
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

        if (booking.diagnoses?.length) {
            diagnosisData.splice(
                0,
                diagnosisData.length,
                ...booking.diagnoses.map((entry: any) => ({
                    ...entry,
                    diagnosis_file_name:
                        entry.diagnosis_file_name ??
                        fileNameFromUrl(entry.diagnosis_file),
                })),
            );
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

        referenceNotice.value =
            booking.status === "approved"
                ? {
                      tone: "approved",
                      message: `Booking ${booking.reference_id} has already been approved. You can continue with the admission.`,
                  }
                : {
                      tone: "pending",
                      message: `Booking ${booking.reference_id} has not been approved in Bookings yet. You can still continue with the admission; completing it will approve the booking.`,
                  };

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
        referenceNotice.value = {
            tone: "blocked",
            message: referenceError.value,
        };
    } finally {
        loadingReference.value = false;
    }
}
function fileNameFromUrl(value?: unknown): string | undefined {
    if (typeof value !== "string" || !value) return undefined;

    const last = value.split("?")[0]?.split("/").pop();

    return last ? decodeURIComponent(last) : undefined;
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
    { key: "patient_code", label: "Patient ID" },
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
    { key: "type_label", label: "Type" },
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
                patient_code: booking.patient.patient_code,
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
                admission_type: booking.facility?.type ?? "",
                type_label: admissionTypeLabel(booking.facility?.type),
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

function admissionTypeLabel(type?: string | null) {
    switch ((type ?? "").toLowerCase()) {
        case "pre-admission":
            return "Pre-admission";
        case "complete":
            return "Complete admission";
        default:
            return "—";
    }
}

const canProcessBooking = (row: any) =>
    !facilityLocked.value &&
    row.admission_type?.toLowerCase() === "pre-admission" &&
    ["pending", "approved"].includes(row.status?.toLowerCase());

function openBooking(row: any) {
    if (facilityLocked.value) return;
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
            sections: "admissions",
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
                    patient_code: data.patient_code,
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
    admissionPagination.reset();
    fetchAdmissions();
}

function onAdmissionPageChange(_page: number) {
    fetchAdmissions();
}

function statusBadgeClass(status: string) {
    const value = (status ?? "").toLowerCase();

    if (value === "approved" || value === "admitted") {
        return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300";
    }

    if (value === "pending") {
        return "bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300";
    }

    if (value === "cancelled" || value === "rejected") {
        return "bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300";
    }

    if (value === "waiting") {
        return "bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300";
    }

    return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
}

function startNewAdmission() {
    if (facilityLocked.value) return;
    referenceInput.value = "";
    referenceError.value = "";
    referenceNotice.value = null;
    bookingProcessed.value = false;
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
        if (facilityLocked.value) {
            await fetchAdmissions();
        } else if (referenceInput.value) {
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
    if (bookingProcessed.value) {
        error(
            `Booking ${referenceInput.value} has already been processed and can't be submitted again.`,
        );
        return;
    }

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
    bookingStore.diagnoses = deepToRaw(diagnosisData);
    bookingStore.branchFacility = branch.value?.facility ?? [];

    router.push({
        path: `/app/branches/${uuid.value}/admissions/review`,
        query: {
            reference_id: referenceInput.value,
        },
    });
}

function deepToRaw<T>(val: T): T {
    if (val instanceof Blob) {
        return val;
    }
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
const step5 = ref<HTMLElement | null>(null);

const activeStep = ref("step1");

const stepRefs = { step1, step2, step3, step4, step5 };
type StepKey = keyof typeof stepRefs;
const stepOrder: StepKey[] = ["step1", "step2", "step3", "step4", "step5"];

const scrollTo = (step: string) => {
    if (step === "step6") {
        submit();
        return;
    }

    activeStep.value = step;

    nextTick(() => {
        stepRefs[step as StepKey]?.value?.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
    });
};

const findScroller = (el: HTMLElement) => {
    let node = el.parentElement;

    while (node) {
        const { overflowY } = getComputedStyle(node);
        if (overflowY === "auto" || overflowY === "scroll") return node;
        node = node.parentElement;
    }

    return null;
};

const updateActiveStepFromScroll = () => {
    const first = stepRefs.step1.value;
    if (!first) return;

    const scroller = findScroller(first);
    const containerTop = scroller ? scroller.getBoundingClientRect().top : 0;

    let current: StepKey = "step1";

    for (const key of stepOrder) {
        const el = stepRefs[key].value;
        if (!el) continue;

        if (el.getBoundingClientRect().top - containerTop - 24 <= 0) {
            current = key;
        }
    }

    activeStep.value = current;
};

onMounted(() => {
    window.addEventListener("scroll", updateActiveStepFromScroll, {
        capture: true,
        passive: true,
    });

    updateActiveStepFromScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener("scroll", updateActiveStepFromScroll, {
        capture: true,
    });
});
const viewModes = ["form", "table", "bookings"] as const;
const sliderOffset = computed(() => {
    const index = viewModes.indexOf(
        viewMode.value as (typeof viewModes)[number],
    );

    return `${index * 100}%`;
});
</script>
