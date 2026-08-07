<template>
    <div class="min-h-[calc(100vh-90px)] bg-slate-100 p-6">
        <div
            class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-4 items-stretch max-w-8xl min-h-[calc(100vh-90px-3rem)] lg:h-[calc(100vh-90px-3rem)]"
        >
            <div class="w-full min-h-0 flex flex-col order-1">
                <template v-if="!selectedReferenceId">
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-[#E4EFED] overflow-hidden flex-1 min-h-0 flex flex-col"
                    >
                        <div
                            class="flex flex-col gap-3 px-6 py-4 border-b border-[#E4EFED]"
                        >
                            <div class="flex gap-3 items-center">
                                <div class="relative flex-1">
                                    <svg
                                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="9" cy="9" r="6" />
                                        <path d="m17 17-4-4" />
                                    </svg>

                                    <BaseInput
                                        mode="text"
                                        v-model="searchQuery"
                                        placeholder="Search by reference ID or patient name"
                                        inputClass="pl-[2.3rem]"
                                    />

                                    <button
                                        v-if="searchQuery"
                                        type="button"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9AB3AF] hover:text-[#16302E] transition"
                                        @click="searchQuery = ''"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 20 20"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.75"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M5 5l10 10M15 5 5 15" />
                                        </svg>
                                    </button>
                                </div>

                                <Combobox
                                    v-model="typeFilter"
                                    :items="typeFilters"
                                    placeholder="Categories"
                                    :searchBar="false"
                                    inputClass="px-4 py-2 font-medium rounded-full min-w-[140px]"
                                />

                                <Combobox
                                    v-model="statusFilter"
                                    :items="statusFilters"
                                    placeholder="Status"
                                    :searchBar="false"
                                    inputClass="px-4 py-2 font-medium rounded-full min-w-[140px]"
                                />
                            </div>

                            <div class="flex flex-wrap gap-2 items-center">
                                <div class="flex gap-3 items-center">
                                    <p class="text-sm">From</p>
                                    <BaseInput
                                        v-model="dateFrom"
                                        mode="date"
                                        class-name="w-full sm:max-w-[150px]"
                                    />
                                </div>

                                <div
                                    class="hidden h-10 w-6 items-center justify-center text-slate-400 sm:flex"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </div>

                                <div class="flex gap-3 items-center">
                                    <p class="text-sm">To</p>
                                    <BaseInput
                                        v-model="dateTo"
                                        mode="date"
                                        class-name="w-full sm:max-w-[150px]"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 min-h-0 overflow-y-auto relative">
                            <div
                                v-if="isFetching && !isLoading"
                                class="absolute inset-0 bg-white/50 z-20 pointer-events-none"
                            />

                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 z-10">
                                    <tr
                                        class="border-b border-[#E4EFED] bg-[#F7FAF9]"
                                    >
                                        <th
                                            class="py-3 pl-6 pr-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                        >
                                            <div class="flex flex-col">
                                                <span>Reference ID</span>
                                                <span
                                                    class="text-[11px] font-normal text-gray-400 normal-case"
                                                >
                                                    Created at
                                                </span>
                                            </div>
                                        </th>
                                        <th
                                            class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                        >
                                            Patient
                                        </th>
                                        <th
                                            class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                        >
                                            Category
                                        </th>
                                        <th
                                            class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                        >
                                            Type
                                        </th>
                                        <th
                                            class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                        >
                                            Service Date
                                        </th>

                                        <th
                                            class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                        >
                                            Status
                                        </th>
                                        <th
                                            class="py-3 pl-3 pr-6 text-xs font-semibold text-muted uppercase tracking-wide text-right"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-[#E4EFED]">
                                    <template v-if="isLoading">
                                        <tr
                                            v-for="n in pagination.pageSize
                                                .value"
                                            :key="n"
                                        >
                                            <td colspan="7" class="py-4 px-6">
                                                <div
                                                    class="h-6 rounded-md bg-slate-100 animate-pulse"
                                                />
                                            </td>
                                        </tr>
                                    </template>

                                    <tr
                                        v-else-if="
                                            !bookingData ||
                                            bookingData.length === 0
                                        "
                                    >
                                        <td
                                            colspan="7"
                                            class="py-16 text-center"
                                        >
                                            <div
                                                class="flex flex-col items-center justify-center"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    class="w-10 h-10 text-gray-300 mb-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <circle
                                                        cx="11"
                                                        cy="11"
                                                        r="7"
                                                    />
                                                    <path d="m20 20-3.5-3.5" />
                                                </svg>

                                                <p
                                                    class="text-sm font-medium text-gray-500"
                                                >
                                                    {{ emptyStateTitle }}
                                                </p>

                                                <p
                                                    class="text-xs text-gray-400 mt-1"
                                                >
                                                    {{ emptyStateSubtitle }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>

                                    <BookingCard
                                        v-for="booking in bookingData"
                                        :key="booking.booking_id"
                                        :booking="booking"
                                        @confirm="confirmBooking"
                                        @reject="rejectBooking"
                                        @view-details="
                                            (b) => selectBooking(b.reference_id)
                                        "
                                    />
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="
                                !isLoading &&
                                bookingData &&
                                bookingData.length > 0
                            "
                            class="shrink-0 flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-[#E4EFED] bg-white"
                        >
                            <p class="text-xs text-muted">
                                Showing {{ pagination.rangeStart }}–{{
                                    pagination.rangeEnd
                                }}
                                of
                                {{ pagination.totalItems }}
                            </p>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#F7FAF9] transition"
                                    :disabled="!pagination.canGoPrev"
                                    @click="
                                        goToPage(
                                            pagination.currentPage.value - 1,
                                        )
                                    "
                                >
                                    Prev
                                </button>

                                <button
                                    v-for="p in pagination.pageNumbers.value"
                                    :key="p"
                                    type="button"
                                    class="w-8 h-8 text-xs font-medium rounded-md border transition"
                                    :class="
                                        p === pagination.currentPage.value
                                            ? 'bg-primary text-white border-primary/80'
                                            : 'border-[#E4EFED] text-[#16302E] hover:bg-[#F7FAF9]'
                                    "
                                    @click="goToPage(p)"
                                >
                                    {{ p }}
                                </button>

                                <button
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#F7FAF9] transition"
                                    :disabled="!pagination.canGoNext"
                                    @click="
                                        goToPage(
                                            pagination.currentPage.value + 1,
                                        )
                                    "
                                >
                                    Next
                                </button>
                            </div>
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
                        class="bg-white rounded-2xl min-h-screen shadow-sm border border-[#E4EFED] py-16 text-center"
                    >
                        <div
                            class="mx-auto h-6 w-6 rounded-full border-2 border-primary border-t-transparent animate-spin"
                        />
                        <p class="text-sm text-muted mt-3">Loading booking…</p>
                    </div>
                </div>

                <div v-else-if="selectedBooking" class="space-y-5">
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

                    <!-- @admit="admitPatient"
                        @accommodation="openAccommodationModal"
                        @assign="showAssign = true" -->
                    <BookingDetails
                        :booking="selectedBooking"
                        @reject="rejectBooking"
                        @confirm="confirmBooking"
                        @accommodation="openAccommodationModal"
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
                        class="bg-white rounded-2xl shadow-sm border border-[#E4EFED] py-16 text-center"
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
            <div class="w-full min-h-0 order-2">
                <BookingSidebar
                    class="w-full lg:h-full"
                    :overview="overview"
                    @newBooking="handleNewBooking"
                />
            </div>
        </div>

        <!-- <BookingServiceAssign
            :open="showAssign"
            :booking="selectedBooking"
            :branchUuid="branch_uuid"
            :isSaving="isAssigning"
            @close="showAssign = false"
            @confirm="handleAssignConfirm"
        /> -->

        <AdmissionDetail
            v-if="showAccommodationModal && selectedBooking"
            variant="modal"
            :roomContract="roomContract"
            :loading="loadingContract"
            @update:model="reserved = $event"
            @confirm="onAccommodationConfirm"
            @close="showAccommodationModal = false"
            :accommodation="selectedBooking.facility?.plan"
            :require-admission-date="false"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import { ChevronRight } from "lucide-vue-next";
import { bookingService } from "~/api/booking/BookingService";
import BaseInput from "~/components/ui/BaseInput.vue";
import BookingCard from "~/components/sections/app/Booking/BookingCard.vue";
import BookingSidebar from "~/components/sections/app/Booking/BookingSidebar.vue";
import { useRoute, useRouter } from "vue-router";
import BookingDetails from "~/components/sections/app/Booking/BookingDetails.vue";
import { useToast } from "~/composables/useToast";
import { useBookingList } from "~/composables/useBookingList";
import { typeFilters, statusFilters } from "~/types/booking";
import Combobox from "~/components/ui/Combobox.vue";
import type { RoomContract, Reserved } from "~/types/contract";
import { branchContractService } from "~/api/branch-contract/BranchContractService";
import AdmissionDetail from "~/components/sections/app/Admission/AdmissionDetail.vue";

const { success, error } = useToast();
const route = useRoute();
const router = useRouter();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Bookings" });

const branch_uuid = computed(() => route.params.uuid as string);

const {
    bookingData,
    isLoading,
    isFetching,
    searchQuery,
    statusFilter,
    typeFilter,
    dateFrom,
    dateTo,
    pagination,
    fetchBookings,
    goToPage,
} = useBookingList(branch_uuid);

const isAssigning = ref(false);
const isSubmitting = ref(false);
const isLoadingSelected = ref(true);
const selectedBooking = ref<any | null>(null);
const showAssign = ref(false);
const showAccommodationModal = ref(false);
const loadingContract = ref(false);
const roomContract = ref<RoomContract[]>([]);
const reserved = ref<Reserved | null>(null);

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
    if (!booking?.booking_id) return;
    isSubmitting.value = true;

    try {
        const res = await bookingService.actionBooking({
            ...booking,
            action: "reject",
            branch_uuid: branch_uuid.value,
        });
        success(res.message ?? res);
        refresh();
    } catch (err: any) {
        error(err.message);
        console.error(err);
    } finally {
        isSubmitting.value = false;
    }
};

const confirmBooking = async (booking: any) => {
    isSubmitting.value = true;

    const payload = {
        ...selectedBooking.value,
        reference_id: booking.reference_id,
        action: "approve",
        branch_uuid: branch_uuid.value,
    };
    try {
        const res = await bookingService.actionBooking(payload);
        success(res.message ?? res);
        refresh();
    } catch (err: any) {
        error(err.message);
        console.error(err);
    } finally {
        isSubmitting.value = false;
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

const admitPatient = async (e: any) => {
    isSubmitting.value = true;
    try {
        const res = await bookingService.actionBooking({
            action: "admit",
            branch_uuid: branch_uuid.value,
            ...e,
        });
        success(res.message ?? res);
        refresh();
    } catch (err: any) {
        error(err.message);
        console.error(err);
    } finally {
        isSubmitting.value = false;
    }
};

const handleAssignConfirm = (e: {
    booking: any;
    assignments: {
        employee_id: number;
        service_id: number | null;
        employee_name: string;
        avatar?: string;
        role_name?: string;
    }[];
}) => {
    selectedBooking.value = {
        ...e.booking,
        assignments: e.assignments,
    };
    showAssign.value = false;
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
    console.log(selectedBooking.value);
    showAccommodationModal.value = false;
}

const unSelectRefId = async () => {
    const query = { ...route.query };
    delete query.reference_id;

    await router.push({
        path: route.path,
        query,
    });

    showAssign.value = false;
};

const selectedReferenceId = computed<string | null>(() => {
    const value = route.query.reference_id;
    if (Array.isArray(value)) return value[0] ?? null;
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

const overview = ref<any>(null);

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
    resolveSelectedBooking(referenceId);
});

watch(bookingData, () => {
    resolveSelectedBooking(selectedReferenceId.value);
});

const emptyStateTitle = computed(() =>
    searchQuery.value || statusFilter.value !== "all"
        ? "No matching bookings"
        : "No bookings yet",
);
const emptyStateSubtitle = computed(() =>
    searchQuery.value || statusFilter.value !== "all"
        ? "Try a different search term or filter."
        : "New bookings for this branch will show up here.",
);
</script>
