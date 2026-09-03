import { ref, watch, onUnmounted, type Ref } from "vue";
import { bookingService } from "~/api/booking/BookingService";
import { usePagination } from "~/composables/usePagination";
import type { BookingRetrieve } from "~/types/booking";


export function useBookingList(branchUuid: Ref<string>) {
    const bookingData = ref<BookingRetrieve[] | null>(null);
    const isLoading = ref(true);
    const isFetching = ref(false);

    const searchQuery = ref("");
    const statusFilter = ref<string>("all");
    const typeFilter = ref<string>("all");
    const bookingTypeFilter = ref<string>("all");

    function toDateInputValue(date: Date): string {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    const defaultDateRange = getDefaultDateRange();
    function getDefaultDateRange() {
        const from = new Date();
        from.setDate(from.getDate() - 1);

        const to = new Date(from);
        to.setDate(to.getDate() + 7);

        return {
            from: toDateInputValue(from),
            to: toDateInputValue(to),
        };
    }
    const today = toDateInputValue(new Date());
    const dateFrom = ref<string>(defaultDateRange.from);
    const dateTo = ref<string>(defaultDateRange.to);

    const pagination = usePagination({ pageSize: 10 });

    let requestId = 0;
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;


    async function fetchBookings() {
        const thisRequest = ++requestId;
        isFetching.value = true;

        try {
            const res: any = await bookingService.list({
                category: typeFilter.value !== "all" ? typeFilter.value : "all",
                service_type: bookingTypeFilter.value !== "all"
                    ? bookingTypeFilter.value
                    : undefined,
                branch_uuid: branchUuid.value,
                page: pagination.currentPage.value,
                per_page: pagination.pageSize.value,
                search: searchQuery.value.trim() || undefined,
                status:
                    statusFilter.value !== "all" ? statusFilter.value : undefined,
                date_from: dateFrom.value || undefined,
                date_to: dateTo.value || undefined,
                booking_type: "online"
            });

            if (thisRequest !== requestId) return;

            bookingData.value = res.data ?? res.data.data;

            const total = res.meta?.total ?? res.total ?? res.data.length;
            pagination.setTotal(total);
        } catch (err) {
            console.error("Failed to fetch bookings", err);
            if (thisRequest === requestId) bookingData.value = [];
        } finally {
            if (thisRequest === requestId) {
                isFetching.value = false;
                isLoading.value = false;
            }
        }
    }
    async function goToPage(page: number) {
        if (page < 1 || page > pagination.totalPages.value) return;
        pagination.currentPage.value = page;
        await fetchBookings();

    }

    watch([searchQuery, statusFilter, typeFilter, bookingTypeFilter, dateFrom, dateTo], () => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            pagination.reset();
            fetchBookings();
        }, 350);
    });

    function jumpToToday() {
        dateFrom.value = toDateInputValue(new Date());
        dateTo.value = toDateInputValue(new Date());
    }

    function clearDateRange() {
        dateFrom.value = "";
        dateTo.value = "";
    }

    onUnmounted(() => {
        if (debounceTimer) clearTimeout(debounceTimer);
        requestId++;
    });

    return {
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
        jumpToToday,
        clearDateRange,
    };
}