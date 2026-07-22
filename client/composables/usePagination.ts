import { ref, computed } from "vue";

export interface UseServerPaginationOptions {
    pageSize?: number;
    delta?: number;
}


export function usePagination(options: UseServerPaginationOptions = {}) {
    const currentPage = ref(1);
    const pageSize = ref(options.pageSize ?? 10);
    const delta = options.delta ?? 2;

    const totalItems = ref(0);
    const totalPages = computed(() =>
        totalItems.value > 0 ? Math.ceil(totalItems.value / pageSize.value) : 1,
    );

    const rangeStart = computed(() => {
        if (!totalItems.value) return 0;
        return (currentPage.value - 1) * pageSize.value + 1;
    });

    const rangeEnd = computed(() => {
        if (!totalItems.value) return 0;
        return Math.min(currentPage.value * pageSize.value, totalItems.value);
    });

    const pageNumbers = computed(() => {
        const total = totalPages.value;
        const current = currentPage.value;
        const start = Math.max(1, current - delta);
        const end = Math.min(total, current + delta);

        const pages: number[] = [];
        for (let i = start; i <= end; i++) pages.push(i);
        return pages;
    });

    const canGoPrev = computed(() => currentPage.value > 1);
    const canGoNext = computed(() => currentPage.value < totalPages.value);

    function setTotal(total: number) {
        totalItems.value = total;
    }

    function reset() {
        currentPage.value = 1;
    }

    return {
        currentPage,
        pageSize,
        totalItems,
        totalPages,
        rangeStart,
        rangeEnd,
        pageNumbers,
        canGoPrev,
        canGoNext,
        setTotal,
        reset,
    };
}