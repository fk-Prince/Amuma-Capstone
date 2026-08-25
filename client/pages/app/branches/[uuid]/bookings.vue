<template>
    <div class="min-h-screen-header bg-slate-100 p-2 overflow-visible">
        <div
            class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_400px] gap-4 items-stretch max-w-8xl h-full min-h-0"
        >
            <div class="w-full min-w-0 min-h-0 flex flex-col order-1">
                <template v-if="!selectedReferenceId">
                    <div
                        class="rounded-lg shadow-sm border border-[#E4EFED] overflow-visible flex flex-col h-full min-h-0"
                    >
                        <div
                            class="flex flex-col gap-3 px-6 py-4 border-b border-[#E4EFED] shrink-0"
                        >
                            <div class="flex gap-3 items-center">
                                <BookingFilter
                                    :search="searchQuery"
                                    :type="typeFilter"
                                    :status="statusFilter"
                                    :date-from="dateFrom"
                                    :date-to="dateTo"
                                    @update:search="searchQuery = $event"
                                    @update:type="typeFilter = $event"
                                    @update:status="statusFilter = $event"
                                    @update:dateFrom="dateFrom = $event"
                                    @update:dateTo="dateTo = $event"
                                />
                            </div>
                        </div>

                        <div class="flex-1 min-h-0 overflow-visible">
                            <DataTable
                                class="rounded-none border-none h-full"
                                :columns="columns"
                                :rows="bookingData ?? []"
                                :pagination="pagination"
                                :loading="isFetching || isLoading"
                                :searchable="false"
                                :row-key="(row) => row.booking_id"
                                empty-title="No bookings yet"
                                :empty-description="emptyStateSubtitle"
                                @page-change="handlePageChange"
                            >
                                <template #cell-reference_id="{ row }">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="inline-flex items-center gap-2 text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] w-fit"
                                        >
                                            <span
                                                class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                                                :class="
                                                    statusDotClasses(row.status)
                                                "
                                            />
                                            #{{ row.reference_id }}
                                        </span>

                                        <span class="text-[11px] text-gray-400">
                                            {{
                                                stringToDateTime(row.created_at)
                                            }}
                                        </span>
                                    </div>
                                </template>

                                <template #cell-patient="{ row }">
                                    <div
                                        class="flex items-center gap-3 min-w-0"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="font-semibold text-[#16302E] truncate text-sm"
                                            >
                                                {{
                                                    fullName(
                                                        row.patient?.first_name,
                                                        row.patient
                                                            ?.middle_name,
                                                        row.patient?.last_name,
                                                    )
                                                }}
                                            </p>

                                            <p
                                                class="text-xs text-muted truncate"
                                            >
                                                {{
                                                    row.homecare?.address ||
                                                    row.patient?.address ||
                                                    ""
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-category="{ row }">
                                    <span class="capitalize">
                                        {{ row.category ?? "—" }}
                                    </span>
                                </template>

                                <template #cell-type="{ row }">
                                    {{ bookingType(row) }}
                                </template>

                                <template #cell-service_date="{ row }">
                                    {{ serviceDate(row) }}
                                </template>

                                <template #cell-valid_until="{ row }">
                                    <span
                                        v-if="row.valid_until"
                                        class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                                    >
                                        {{ stringToDateTime(row.valid_until) }}
                                    </span>

                                    <span v-else>—</span>
                                </template>

                                <template #cell-status="{ row }">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                                        :class="statusClasses(row.status)"
                                    >
                                        {{ formatStatus(row.status) }}
                                    </span>
                                </template>

                                <template #cell-actions="{ row }">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            v-if="
                                                row.status?.toLowerCase() ===
                                                    'pending' &&
                                                row.facility?.type !==
                                                    'Pre-Admission'
                                            "
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-medium rounded-md border border-red-300 text-red-600 hover:bg-red-50 transition"
                                            @click.stop="rejectBooking(row)"
                                        >
                                            Reject
                                        </button>

                                        <button
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] hover:bg-[#F0F5F4] transition flex items-center gap-1 shrink-0"
                                            @click.stop="
                                                selectBooking(row.reference_id)
                                            "
                                        >
                                            View

                                            <svg
                                                class="w-3.5 h-3.5 text-[#6B8A87]"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                            >
                                                <path
                                                    d="M7.5 5L12.5 10L7.5 15"
                                                    stroke="currentColor"
                                                    stroke-width="1.75"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </div>
                </template>

                <div v-else-if="isLoadingSelected" class="space-y-5">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-secondary transition"
                        @click="unSelectRefId"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                            <path
                                d="M12.5 15L7.5 10L12.5 5"
                                stroke="currentColor"
                                stroke-width="1.75"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Back to bookings
                    </button>

                    <div
                        class="bg-white rounded-2xl min-h-[calc(100dvh-var(--header-h))] shadow-sm border border-[#E4EFED] py-16 text-center"
                    >
                        <div
                            class="mx-auto h-6 w-6 rounded-full border-2 border-primary border-t-transparent animate-spin"
                        />

                        <p class="text-sm text-muted mt-3">Loading booking…</p>
                    </div>
                </div>

                <div v-else-if="selectedBooking" class="space-y-5">
                    <div
                        class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-secondary transition"
                            @click="unSelectRefId"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <path
                                    d="M12.5 15L7.5 10L12.5 5"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            Back to bookings
                        </button>
                    </div>

                    <BookingDetails
                        :booking="selectedBooking"
                        :loading="isSubmitting"
                    />
                </div>

                <div
                    v-else-if="selectedReferenceId && !isLoadingSelected"
                    class="space-y-5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-secondary transition"
                        @click="unSelectRefId"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                            <path
                                d="M12.5 15L7.5 10L12.5 5"
                                stroke="currentColor"
                                stroke-width="1.75"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Back to bookings
                    </button>

                    <div
                        class="bg-white rounded-2xl min-h-[calc(100dvh-var(--header-h))] shadow-sm border border-[#E4EFED] py-16 text-center"
                    >
                        <p class="text-sm font-medium text-gray-500">
                            Booking not found
                        </p>

                        <p class="text-xs text-gray-400 mt-1">
                            We couldn't find a booking with reference "{{
                                selectedReferenceId
                            }}".
                        </p>
                    </div>
                </div>
            </div>

            <aside
                class="w-full lg:self-start order-2"
                :class="{
                    'lg:sticky lg:top-[6%]': selectedBooking,
                    'h-full': !selectedBooking,
                }"
            >
                <template v-if="!selectedReferenceId">
                    <BookingSidebar
                        class="w-full"
                        :overview="overview"
                        @newBooking="handleNewBooking"
                    />
                </template>

                <template v-else-if="selectedBooking">
                    <div
                        class="w-full bg-white rounded-2xl shadow-sm ring-1 ring-black/5 overflow-hidden"
                    >
                        <div
                            class="px-5 py-4 border-b border-[#EDF4F3] bg-gradient-to-r from-[#0E7C7B]/[0.05] to-transparent"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M12 6v12" />
                                            <path d="M6 12h12" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="text-[10px] uppercase tracking-[0.16em] text-[#6B8A87] font-mono"
                                        >
                                            Booking Actions
                                        </p>

                                        <span
                                            class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] inline-block"
                                        >
                                            #{{ selectedBooking.reference_id }}
                                        </span>
                                    </div>
                                </div>

                                <span
                                    class="px-3 py-1 rounded-sm text-xs font-medium capitalize"
                                    :class="
                                        statusClasses(selectedBooking.status)
                                    "
                                >
                                    {{ formatStatus(selectedBooking.status) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5">
                            <div
                                v-if="
                                    showAccommodationButton &&
                                    !isPreAdmissionFacility
                                "
                                class="mb-5 rounded-xl border border-[#E4EFED] bg-[#F8FBFA] p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-[#0E7C7B] shadow-sm ring-1 ring-[#E4EFED]"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M3 10h18" />
                                            <path
                                                d="M5 10V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3"
                                            />
                                            <path d="M5 10v9" />
                                            <path d="M19 10v9" />
                                            <path d="M3 19h18" />
                                            <path d="M8 14h8" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-semibold text-[#16302E]"
                                        >
                                            Accommodation
                                        </p>

                                        <p
                                            class="text-xs text-[#6B8A87] mt-0.5 leading-relaxed"
                                        >
                                            {{
                                                selectedBooking.reserved
                                                    ? "Accommodation has already been selected."
                                                    : "Select an accommodation type for this admission."
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <ActionButton
                                    type="button"
                                    variant="primary"
                                    :loading="isSubmitting"
                                    :disabled="isSubmitting"
                                    extra-class="w-full mt-3"
                                    @click="
                                        openAccommodationModal(selectedBooking)
                                    "
                                >
                                    <span
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M3 10h18" />
                                            <path
                                                d="M5 10V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3"
                                            />
                                            <path d="M5 10v9" />
                                            <path d="M19 10v9" />
                                            <path d="M3 19h18" />
                                        </svg>

                                        {{
                                            selectedBooking.reserved
                                                ? "Change Accommodation"
                                                : "Choose Accommodation Type"
                                        }}
                                    </span>
                                </ActionButton>
                            </div>

                            <div
                                v-if="
                                    selectedBooking.status?.toLowerCase() ===
                                        'pending' && !isPreAdmissionFacility
                                "
                                class="space-y-3"
                            >
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="h-px flex-1 bg-[#EDF4F3]" />

                                    <span
                                        class="text-[10px] uppercase tracking-[0.14em] text-[#8AA09D] font-mono whitespace-nowrap"
                                    >
                                        Review Booking
                                    </span>

                                    <div class="h-px flex-1 bg-[#EDF4F3]" />
                                </div>

                                <div class="flex flex-col gap-3">
                                    <ActionButton
                                        type="button"
                                        variant="primary"
                                        :loading="isApproving"
                                        :disabled="isApproving"
                                        extra-class="w-full"
                                        :tooltip="
                                            !isApproving && !canApproveBooking
                                                ? 'Please select an accommodation before approving this admission.'
                                                : ''
                                        "
                                        @click="confirmBooking(selectedBooking)"
                                    >
                                        <span
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="m5 12 4 4L19 6" />
                                            </svg>

                                            {{
                                                isFacility
                                                    ? "Approve Admission Booking"
                                                    : "Approve Homecare Booking"
                                            }}
                                        </span>
                                    </ActionButton>

                                    <ActionButton
                                        type="button"
                                        variant="danger"
                                        :loading="isRejecting"
                                        :disabled="isRejecting"
                                        extra-class="w-full"
                                        :tooltip="
                                            selectedBooking.payment
                                                ?.payment_status === 'paid'
                                                ? 'The payment will be refunded when this booking is rejected.'
                                                : ''
                                        "
                                        @click="rejectBooking(selectedBooking)"
                                    >
                                        <span
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M6 6l12 12" />
                                                <path d="M18 6 6 18" />
                                            </svg>

                                            {{
                                                selectedBooking.payment
                                                    ?.payment_status === "paid"
                                                    ? "Reject & Refund"
                                                    : "Reject Booking"
                                            }}
                                        </span>
                                    </ActionButton>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-[#E4EFED] bg-[#F8FBFA] px-4 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#EAF4F2] text-[#0E7C7B]"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="m5 12 4 4L19 6" />
                                        </svg>
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-[#16302E]"
                                        >
                                            No actions available
                                        </p>

                                        <p
                                            class="text-xs text-[#6B8A87] mt-0.5"
                                        >
                                            This booking is no longer pending.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </aside>
        </div>

        <AdmissionDetail
            v-if="showAccommodationModal && selectedBooking"
            variant="modal"
            :roomContract="roomContract"
            :loading="loadingContract"
            @update:model="reserved = $event"
            @confirm="onAccommodationConfirm"
            @close="showAccommodationModal = false"
            :accommodation="selectedBooking.facility?.plan"
            :initial-billing-cycle="
                selectedBooking.facility?.billing_cycle?.toLowerCase() ===
                'yearly'
                    ? 'yearly'
                    : 'monthly'
            "
            :require-admission-date="false"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { bookingService } from "~/api/booking/BookingService";
import BookingSidebar from "~/components/sections/app/Booking/BookingSidebar.vue";
import BookingFilter from "~/components/sections/app/Booking/BookingFilter.vue";
import BookingDetails from "~/components/sections/app/Booking/BookingDetails.vue";
import AdmissionDetail from "~/components/sections/app/Admission/AdmissionDetail.vue";
import DataTable from "~/components/ui/DataTable.vue";
import { useToast } from "~/composables/useToast";
import { useBookingList } from "~/composables/useBookingList";
import type { RoomContract, Reserved } from "~/types/contract";
import type { BookingRetrieve } from "~/types/booking";
import { formatStatus, statusClasses } from "~/types/booking";
import { fullName } from "~/utils/user";
import { stringToDateTime } from "~/utils/time";
import { branchContractService } from "~/api/branch-contract/BranchContractService";
import ActionButton from "~/components/ui/ActionButton.vue";

const { success, error } = useToast();

const route = useRoute();
const router = useRouter();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Bookings",
});

const branch_uuid = computed(() => route.params.uuid as string);

const {
    bookingData,
    isLoading,
    searchQuery,
    statusFilter,
    typeFilter,
    dateFrom,
    dateTo,
    pagination,
    fetchBookings,
    goToPage,
    isFetching,
} = useBookingList(branch_uuid);

const isSubmitting = ref(false);
const isApproving = ref(false);
const isRejecting = ref(false);
const isLoadingSelected = ref(true);

const selectedBooking = ref<any | null>(null);
const showAccommodationModal = ref(false);
const loadingContract = ref(false);
const roomContract = ref<RoomContract[]>([]);
const reserved = ref<Reserved | null>(null);
const overview = ref<any>(null);

const columns = [
    {
        key: "reference_id",
        label: "Reference ID",
    },
    {
        key: "patient",
        label: "Patient",
    },
    {
        key: "category",
        label: "Category",
    },
    {
        key: "type",
        label: "Type",
    },
    {
        key: "service_date",
        label: "Service Date",
    },
    {
        key: "valid_until",
        label: "Valid Until",
    },
    {
        key: "status",
        label: "Status",
    },
    {
        key: "actions",
        label: "Actions",
        align: "right" as const,
    },
];

const isFacility = computed(() => {
    return selectedBooking.value?.category?.toLowerCase() === "facility";
});

const isPreAdmissionFacility = computed(() => {
    return (
        selectedBooking.value?.category?.toLowerCase() === "facility" &&
        selectedBooking.value?.facility?.type === "Pre-Admission"
    );
});

const showAccommodationButton = computed(() => {
    const currentStatus = selectedBooking.value?.status?.toLowerCase() ?? "";

    return currentStatus === "pending" && isFacility.value;
});

function bookingType(booking: BookingRetrieve) {
    return booking.homecare?.type === "Medical"
        ? "Medical Services"
        : booking.facility?.type === "Complete"
          ? "Complete Admission"
          : booking.facility?.type === "Pre-Admission"
            ? booking.facility.type
            : booking.homecare?.type === "ADL"
              ? "Activity of Daily Living (ADL)"
              : "—";
}

function serviceDate(booking: BookingRetrieve) {
    if (booking.category === "facility") {
        return booking.facility?.admission_date
            ? formatDate(booking.facility.admission_date)
            : "—";
    }

    return booking.homecare?.date ? formatDate(booking.homecare.date) : "—";
}

function formatDate(value?: string) {
    if (!value) return "—";

    const d = new Date(value);

    if (isNaN(d.getTime())) {
        return value;
    }

    return d.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function statusDotClasses(status?: string) {
    const s = (status ?? "").toLowerCase().replace("-", "_");

    switch (s) {
        case "approved":
            return "bg-[#1F7A4D]";

        case "rejected":
        case "cancelled":
            return "bg-[#B3402F]";

        case "expired":
            return "bg-gray-400";

        case "pending":
        default:
            return "bg-[#966B1F]";
    }
}

async function handlePageChange(page: number) {
    goToPage(page);
}

const handleNewBooking = (booking: any) => {
    if (!booking?.booking_id) return;

    const current = bookingData.value ?? [];

    const exists = current.some(
        (item: any) => item.booking_id === booking.booking_id,
    );

    if (exists) return;

    const updated = [booking, ...current];

    if (updated.length > pagination.pageSize.value) {
        updated.pop();
    }

    bookingData.value = updated;
    pagination.totalItems.value++;
};

const rejectBooking = async (booking: any) => {
    if (!booking?.booking_id || isRejecting.value) return;

    isRejecting.value = true;

    try {
        const res = await bookingService.actionBooking({
            ...booking,
            action: "reject",
            branch_uuid: branch_uuid.value,
        });

        success(res.message ?? "Booking rejected successfully.");

        selectedBooking.value = {
            ...selectedBooking.value,
            ...(res.data ?? {}),
            status: res.data?.status ?? "rejected",
        };
    } catch (err: any) {
        error(err?.message ?? "Failed to reject booking.");
        console.error("Failed to reject booking:", err);
    } finally {
        isRejecting.value = false;
    }
};

const confirmBooking = async (booking: any) => {
    if (!booking?.reference_id || isApproving.value) return;

    isApproving.value = true;

    try {
        const res = await bookingService.actionBooking({
            ...booking,
            reference_id: booking.reference_id,
            action: "approve",
            branch_uuid: branch_uuid.value,
        });

        success(res.message ?? "Booking approved successfully.");

        selectedBooking.value = {
            ...selectedBooking.value,
            ...(res.data ?? {}),
            status: res.data?.status ?? "approved",
        };
    } catch (err: any) {
        error(err?.message ?? "Failed to approve booking.");
        console.error("Failed to approve booking:", err);
    } finally {
        isApproving.value = false;
    }
};
const refresh = async () => {
    if (!selectedReferenceId.value) {
        await fetchBookings();
        return;
    }

    try {
        const [, res] = await Promise.all([
            fetchBookings(),
            bookingService.show(selectedReferenceId.value, {
                reference_id: selectedReferenceId.value,
                branch_uuid: branch_uuid.value,
            }),
        ]);

        selectedBooking.value = res ?? null;
    } catch (err) {
        console.error(err);
    }
};

async function openAccommodationModal(booking: any) {
    selectedBooking.value = booking;
    showAccommodationModal.value = true;
    loadingContract.value = true;
    reserved.value = null;

    try {
        const response = await branchContractService.list({
            type: "room_contract",
            branch_uuid: branch_uuid.value,
        });

        roomContract.value = response;
    } catch (err) {
        console.error("Failed loading room contracts", err);
        roomContract.value = [];
    } finally {
        loadingContract.value = false;
    }
}

async function onAccommodationConfirm(payload: Reserved) {
    reserved.value = payload;

    if (!selectedBooking.value) return;

    selectedBooking.value = {
        ...selectedBooking.value,
        reserved: {
            ...payload,
            admitted_at: selectedBooking.value.facility?.admission_date ?? null,
        },
    };

    showAccommodationModal.value = false;
}

const unSelectRefId = async () => {
    const query = { ...route.query };

    delete query.reference_id;

    selectedBooking.value = null;
    isLoadingSelected.value = false;

    await router.push({
        path: route.path,
        query,
    });
};

const selectedReferenceId = computed<string | null>(() => {
    const value = route.query.reference_id;

    if (Array.isArray(value)) {
        return value[0] ?? null;
    }

    return typeof value === "string" ? value : null;
});

function selectBooking(referenceId: string | null) {
    if (!referenceId) return;

    if (String(selectedReferenceId.value) === String(referenceId)) {
        return resolveSelectedBooking(referenceId);
    }

    router.push({
        path: route.path,
        query: {
            ...route.query,
            reference_id: referenceId,
        },
    });
}

async function resolveSelectedBooking(referenceId: string | null) {
    if (!referenceId) {
        selectedBooking.value = null;
        isLoadingSelected.value = false;
        return;
    }

    if (
        selectedBooking.value &&
        String(selectedBooking.value.reference_id) === String(referenceId)
    ) {
        isLoadingSelected.value = false;
        return;
    }

    const found = bookingData.value?.find(
        (booking: any) => String(booking.reference_id) === String(referenceId),
    );

    if (found) {
        selectedBooking.value = found;
        isLoadingSelected.value = false;
        return;
    }

    isLoadingSelected.value = true;

    try {
        const res = await bookingService.show(referenceId, {
            reference_id: referenceId,
            branch_uuid: branch_uuid.value,
        });

        selectedBooking.value = res ?? null;
    } catch (err) {
        console.error("Failed to load booking by reference_id", err);
        selectedBooking.value = null;
    } finally {
        isLoadingSelected.value = false;
    }
}

onMounted(async () => {
    try {
        const [, overviewData] = await Promise.all([
            fetchBookings(),
            bookingService.overview({
                branch_uuid: branch_uuid.value,
            }),
        ]);

        overview.value = overviewData;

        if (selectedReferenceId.value) {
            await resolveSelectedBooking(selectedReferenceId.value);
        }
    } catch (err) {
        console.error("Failed loading booking data", err);
    } finally {
        isLoadingSelected.value = false;
    }
});

watch(selectedReferenceId, (referenceId) => {
    if (!referenceId) {
        selectedBooking.value = null;
        isLoadingSelected.value = false;
        return;
    }

    resolveSelectedBooking(referenceId);
});

watch(bookingData, () => {
    if (selectedReferenceId.value) {
        resolveSelectedBooking(selectedReferenceId.value);
    }
});

const emptyStateSubtitle = computed(() =>
    searchQuery.value ||
    statusFilter.value !== "all" ||
    dateFrom.value ||
    dateTo.value
        ? "Try a different search term or filter."
        : "New bookings for this branch will show up here.",
);

const canApproveBooking = computed(() => {
    if (!selectedBooking.value) return false;

    if (!isFacility.value) return true;

    if (isPreAdmissionFacility.value) return true;

    return !!selectedBooking.value.reserved;
});
</script>
