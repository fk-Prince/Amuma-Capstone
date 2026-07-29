<template>
    <div class="min-h-[calc(100vh-90px)] bg-slate-100 p-6">
        <div
            class="grid grid-cols-1 lg:grid-cols-[1fr_250px] gap-4 items-start max-w-8xl h-[calc(100vh-90px-3rem)]"
        >
            <div class="w-full h-full flex flex-col gap-6 min-h-0">
                <PageHeader
                    title="Bookings"
                    subtitle="Care Coordination"
                    description="Manage and monitor all care coordination bookings in one place."
                />

                <template v-if="!selectedReferenceId">
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-[#E4EFED] overflow-hidden flex-1 min-h-0 flex flex-col"
                    >
                        <div
                            class="shrink-0 flex flex-col sm:flex-row sm:items-center gap-3 px-6 py-4 border-b border-[#E4EFED]"
                        >
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

                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button
                                    v-for="filter in statusFilters"
                                    :key="filter.value"
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-medium rounded-full border transition whitespace-nowrap"
                                    :class="
                                        statusFilter === filter.value
                                            ? 'bg-primary text-white '
                                            : 'border-[#E4EFED] text-[#16302E] hover:bg-[#F7FAF9]'
                                    "
                                    @click="statusFilter = filter.value"
                                >
                                    {{ filter.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 min-h-0 overflow-auto relative">
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
                                            Reference ID
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
                                            Date
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
                                                    {{
                                                        searchQuery ||
                                                        statusFilter !== "all"
                                                            ? "No matching bookings"
                                                            : "No bookings yet"
                                                    }}
                                                </p>

                                                <p
                                                    class="text-xs text-gray-400 mt-1"
                                                >
                                                    {{
                                                        searchQuery ||
                                                        statusFilter !== "all"
                                                            ? "Try a different search term or filter."
                                                            : "New bookings for this branch will show up here."
                                                    }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>

                                    <BookingCard
                                        v-else
                                        v-for="booking in bookingData"
                                        :key="booking.booking_id"
                                        :booking="booking"
                                        @confirm="confirmBooking"
                                        @reject="rejectBooking"
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

                    <BookingDetails
                        :booking="selectedBooking"
                        @reject="rejectBooking"
                        @confirm="confirmBooking"
                    />
                </div>
            </div>

            <div
                class="w-full h-full hidden lg:flex gap-4 flex-col overflow-auto"
            >
                <BookingSidebar />
            </div>
        </div>

        <!-- <AssignEmployeeModal
            v-if="isAssignedOpen && selectedBooking"
            :open="isAssignedOpen"
            :reference-id="selectedBooking.reference_id"
            :services="selectedBooking.booking_data?.service?.services ?? []"
            v-model="assignments"
            @close="isAssignedOpen = false"
            @confirm="handleAssignConfirm"
            :type="selectedBooking.category ?? ''"
        /> -->
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import { bookingService } from "~/api/booking/BookingService";
import BaseInput from "~/components/ui/BaseInput.vue";
import BookingCard from "~/components/sections/app/Booking/BookingCard.vue";
import BookingSidebar from "~/components/sections/app/Booking/BookingSidebar.vue";
import { useRoute, useRouter } from "vue-router";
import PageHeader from "~/components/ui/PageHeader.vue";
// import AssignEmployeeModal from "~/components/sections/app/Booking/AssignEmployeeModal.vue";
import BookingDetails from "~/components/sections/app/Booking/BookingDetails.vue";
import { usePagination } from "~/composables/usePagination";
import { useToast } from "~/composables/useToast";

const { success, error } = useToast();
const route = useRoute();
const router = useRouter();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Bookings" });

const bookingData = ref<any[] | null>(null);
const isLoading = ref(true);
const isFetching = ref(false);
const assignments = ref<Record<string, string>>({});
const selectedBooking = ref<any | null>(null);
const isAssignedOpen = ref(false);

const searchQuery = ref("");
const statusFilter = ref<string>("all");

const statusFilters = [
    { label: "All", value: "all" },
    { label: "Pending", value: "pending" },
    { label: "Confirmed", value: "confirmed" },
    { label: "Completed", value: "completed" },
    { label: "Rejected", value: "rejected" },
];

const pagination = usePagination({ pageSize: 10 });
const branch_uuid = computed(() => route.params.uuid as string);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let requestId = 0;

async function fetchBookings() {
    const thisRequest = ++requestId;
    isFetching.value = true;

    try {
        const res: any = await bookingService.list({
            category: "all",
            branch_uuid: branch_uuid.value,
            page: pagination.currentPage.value,
            per_page: pagination.pageSize.value,
            search: searchQuery.value.trim() || undefined,
            status:
                statusFilter.value !== "all" ? statusFilter.value : undefined,
        });

        if (thisRequest !== requestId) return;

        bookingData.value = res.data;

        const total = res.meta?.total ?? res.total ?? res.data.length;
        pagination.setTotal(total);

        if (selectedReferenceId.value) {
            resolveSelectedBooking(selectedReferenceId.value);
        }
    } catch (err: any) {
        console.error(err);
    } finally {
        if (thisRequest === requestId) {
            isFetching.value = false;
            isLoading.value = false;
        }
    }
}

function goToPage(page: number) {
    if (page < 1 || page > pagination.totalPages.value) return;
    pagination.currentPage.value = page;
    fetchBookings();
}

watch([searchQuery, statusFilter], () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        pagination.reset();
        fetchBookings();
    }, 350);
});

const rejectBooking = async (e: any) => {};

const confirmBooking = async (e: any) => {
    const payload = {
        booking_id: e.booking_id,
        action: "approve",
        branch_uuid: branch_uuid.value,
    };
    try {
        const res = await bookingService.facilityAdmission(payload);
        success(res.message);
        // const res = await bookingService.actionBooking(payload);
        // console.log(res);
        // fetchBookings();
    } catch (err: any) {
        error(err.message);
        console.log(err);
    }
};

const unSelectRefId = async () => {
    const query = { ...route.query };

    delete query.reference_id;

    await router.push({
        path: route.path,
        query,
    });

    isAssignedOpen.value = false;
};

const selectedReferenceId = computed<string | null>(() => {
    const value = route.query.reference_id;

    if (Array.isArray(value)) {
        return value[0] ?? null;
    }

    return typeof value === "string" ? value : null;
});

function resolveSelectedBooking(referenceId: string | null) {
    if (!referenceId || !bookingData.value) {
        selectedBooking.value = null;
        return;
    }

    selectedBooking.value =
        bookingData.value.find(
            (booking: any) =>
                String(booking.reference_id) === String(referenceId),
        ) ?? null;
}

watch(selectedReferenceId, (referenceId) => {
    resolveSelectedBooking(referenceId);

    if (referenceId && selectedBooking.value) {
        assignments.value = {};
    }
});

const handleAssignConfirm = async (e: any) => {
    console.log(e);
};

onMounted(() => {
    fetchBookings();
});
</script>
