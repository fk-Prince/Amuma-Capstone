import { computed, onMounted, ref, watch } from "vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";

const PER_PAGE = 9;
const SEARCH_DEBOUNCE_MS = 350;

export type SubscriptionView = "requests" | "approved" | "rejected";
export type ApprovedStatus = "active" | "inactive" | "expired";

export function useSubscriptionBrowser(initialView: SubscriptionView) {
    const subscriptions = ref<any[]>([]);
    const loading = ref(true);
    const loadingMore = ref(false);
    const page = ref(1);
    const total = ref(0);
    const hasMore = ref(false);

    const overview = ref<any | null>(null);
    const overviewLoading = ref(true);

    const processingAction = ref<Record<string, "approve" | "reject">>({});

    const search = ref("");
    const view = ref<SubscriptionView>(initialView);
    const approvedStatus = ref<ApprovedStatus>("active");

    let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

    const statusParam = computed(() => {
        if (view.value === "requests") return "pending";
        if (view.value === "rejected") return "rejected";
        return approvedStatus.value;
    });

    const isSearching = computed(() => search.value.trim().length > 0);

    const fetchSubscriptions = async (targetPage = 1) => {
        if (targetPage === 1) {
            loading.value = true;
        } else {
            loadingMore.value = true;
        }

        try {
            const res = await subscriptionService.list({
                status: statusParam.value,
                search: search.value.trim() || undefined,
                page: targetPage,
                per_page: PER_PAGE,
            });

            const list = res.data?.data ?? res.data ?? res;
            const meta = res.data?.meta ?? res.meta ?? null;

            subscriptions.value =
                targetPage === 1 ? list : [...subscriptions.value, ...list];
            page.value = targetPage;

            if (meta) {
                total.value = meta.total ?? subscriptions.value.length;
                hasMore.value =
                    (meta.current_page ?? targetPage) <
                    (meta.last_page ?? targetPage);
            } else {
                total.value = subscriptions.value.length;
                hasMore.value = list.length === PER_PAGE;
            }
        } catch (err) {
            console.error("Failed to fetch subscriptions:", err);

            if (targetPage === 1) {
                subscriptions.value = [];
                total.value = 0;
                hasMore.value = false;
            }
        } finally {
            loading.value = false;
            loadingMore.value = false;
        }
    };

    const loadMore = () => {
        if (loadingMore.value || !hasMore.value) return;

        fetchSubscriptions(page.value + 1);
    };

    const fetchOverview = async () => {
        overviewLoading.value = true;

        try {
            const res = await subscriptionService.action({
                action: "overview_subscription",
            });
            overview.value = res.data ?? res;
        } catch (err) {
            console.error("Failed to fetch subscription overview:", err);
        } finally {
            overviewLoading.value = false;
        }
    };

    const clearSearch = () => {
        search.value = "";
    };

    const approveSubscription = async (subscription: any) => {
        const uuid = subscription.uuid;

        if (processingAction.value[uuid]) return;

        processingAction.value = {
            ...processingAction.value,
            [uuid]: "approve",
        };

        try {
            await subscriptionService.action({
                action: "approve",
                branch_subscription_uuid: uuid,
            });

            subscriptions.value = subscriptions.value.filter(
                (s) => s.uuid !== uuid,
            );
            total.value = Math.max(0, total.value - 1);

            fetchOverview();
        } catch (err) {
            console.error("Failed to approve subscription:", err);
        } finally {
            const next = { ...processingAction.value };
            delete next[uuid];
            processingAction.value = next;
        }
    };

    const rejectSubscription = async (subscription: any, reason = "") => {
        const uuid = subscription.uuid;

        if (processingAction.value[uuid]) return;

        processingAction.value = {
            ...processingAction.value,
            [uuid]: "reject",
        };

        try {
            await subscriptionService.action({
                action: "reject",
                branch_subscription_uuid: uuid,
                rejection_reason: reason,
            });

            subscriptions.value = subscriptions.value.filter(
                (s) => s.uuid !== uuid,
            );
            total.value = Math.max(0, total.value - 1);

            fetchOverview();
        } catch (err) {
            console.error("Failed to reject subscription:", err);
        } finally {
            const next = { ...processingAction.value };
            delete next[uuid];
            processingAction.value = next;
        }
    };

    watch(search, () => {
        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);

        searchDebounceTimer = setTimeout(
            () => fetchSubscriptions(1),
            SEARCH_DEBOUNCE_MS,
        );
    });

    watch([view, approvedStatus], () => fetchSubscriptions(1));

    onMounted(() => {
        fetchSubscriptions(1);
        fetchOverview();
    });

    return {
        subscriptions,
        loading,
        loadingMore,
        total,
        hasMore,
        overview,
        overviewLoading,
        processingAction,
        search,
        view,
        approvedStatus,
        isSearching,
        fetchSubscriptions,
        loadMore,
        clearSearch,
        approveSubscription,
        rejectSubscription,
    };
}
