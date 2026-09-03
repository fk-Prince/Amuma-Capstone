<template>
    <div class="min-h-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <SubscriptionOverview
            :overview="overview"
            :loading="overviewLoading"
            :active-view="view"
            :active-status="approvedStatus"
            class="mb-6"
            @select="applyStatSelection"
        />

        <div
            class="mb-6 flex flex-col gap-4 border-b border-slate-100 dark:border-white/10 pb-6 lg:flex-row lg:items-center lg:justify-between"
        >
            <SubscriptionFilterBar
                v-model:search="search"
                v-model:view="view"
                v-model:approvedStatus="approvedStatus"
                class="lg:flex-1"
            />

            <div
                class="flex items-center justify-between gap-2 lg:shrink-0 lg:justify-end"
            >
                <button
                    type="button"
                    :disabled="loading"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-white/10 bg-white dark:bg-secondary px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-gray-300 transition hover:bg-slate-50 dark:hover:bg-white/10 disabled:opacity-50"
                    @click="fetchSubscriptions()"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="h-3.5 w-3.5"
                        :class="{ 'animate-spin': loading }"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    Refresh
                </button>
                <!-- 
                <div
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-600 ring-1 ring-inset ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20"
                >
                    <span class="relative flex h-1.5 w-1.5">
                        <span
                            v-if="subscriptions.length > 0"
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"
                        />
                        <span
                            class="relative inline-flex h-1.5 w-1.5 rounded-full bg-amber-500"
                        />
                    </span>

                    {{ total }} {{ totalLabel }}
                </div> -->
            </div>
        </div>

        <div
            v-if="loading"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="n in 6"
                :key="n"
                class="overflow-hidden rounded-2xl border border-slate-100 dark:border-white/10 bg-white dark:bg-secondary shadow-sm"
            >
                <div class="animate-pulse space-y-0 divide-y divide-slate-100 dark:divide-white/10">
                    <div class="space-y-2 bg-slate-50/70 dark:bg-white/5 p-4">
                        <div class="h-4 w-2/3 rounded bg-slate-200 dark:bg-white/10" />
                        <div class="h-3 w-1/3 rounded bg-slate-100 dark:bg-white/5" />
                    </div>

                    <div class="space-y-3 p-4">
                        <div class="h-3 w-1/4 rounded bg-slate-100 dark:bg-white/5" />
                        <div class="flex gap-3">
                            <div
                                class="h-9 w-9 shrink-0 rounded-full bg-slate-200 dark:bg-white/10"
                            />
                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-3/4 rounded bg-slate-200 dark:bg-white/10" />
                                <div class="h-3 w-1/2 rounded bg-slate-100 dark:bg-white/5" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 p-4">
                        <div class="h-3 w-1/4 rounded bg-slate-100 dark:bg-white/5" />
                        <div class="flex gap-3">
                            <div
                                class="h-9 w-9 shrink-0 rounded-full bg-slate-200 dark:bg-white/10"
                            />
                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-3/4 rounded bg-slate-200 dark:bg-white/10" />
                                <div class="h-3 w-1/2 rounded bg-slate-100 dark:bg-white/5" />
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 bg-slate-50/70 dark:bg-white/5 p-3">
                        <div class="h-8 flex-1 rounded-xl bg-slate-200 dark:bg-white/10" />
                        <div class="h-8 flex-1 rounded-xl bg-slate-100 dark:bg-white/5" />
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else-if="subscriptions.length === 0"
            class="flex min-h-80 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-white/5 px-6 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-accent-50 dark:bg-accent-500/10 text-accent-500 dark:text-accent-300 ring-1 ring-inset ring-accent-100 dark:ring-accent-500/20"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-7 w-7"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m5 12 4 4L19 6"
                    />
                </svg>
            </div>

            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                {{ emptyStateTitle }}
            </h2>

            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-gray-400">
                {{ emptyStateDescription }}
            </p>

            <button
                type="button"
                class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-white/10 bg-white dark:bg-secondary px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-gray-300 transition hover:bg-slate-50 dark:hover:bg-white/10"
                @click="isSearching ? clearSearch() : fetchSubscriptions()"
            >
                <svg
                    v-if="!isSearching"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-3.5 w-3.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-3.5 w-3.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18 18 6M6 6l12 12"
                    />
                </svg>
                {{ isSearching ? "Clear search" : "Check again" }}
            </button>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <SubscriptionCard
                    v-for="subscription in subscriptions"
                    :key="subscription.uuid"
                    :subscription="subscription"
                    :show-actions="view === 'requests'"
                    :action-loading="
                        processingAction[subscription.uuid] ?? null
                    "
                    @approve="approveSubscription"
                    @reject="rejectSubscription"
                />
            </div>

            <div class="mt-8 flex flex-col items-center gap-3">
                <button
                    v-if="hasMore"
                    type="button"
                    :disabled="loadingMore"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 dark:border-white/10 bg-white dark:bg-secondary px-5 py-2.5 text-xs font-semibold text-slate-700 dark:text-gray-300 shadow-sm transition hover:bg-slate-50 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="loadMore"
                >
                    <svg
                        v-if="loadingMore"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="h-3.5 w-3.5 animate-spin"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    {{ loadingMore ? "Loading..." : "Load more" }}
                </button>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import SubscriptionCard from "~/components/sections/owner/SubscriptionCard.vue";
import SubscriptionFilterBar from "~/components/sections/owner/SubscriptionFilter.vue";
import SubscriptionOverview from "~/components/sections/owner/SubscriptionOverview.vue";

definePageMeta({
    layout: "owner",
    middleware: "auth-client",
});

useHead({
    title: "AMUMA Subscription",
});

const PER_PAGE = 9;
const SEARCH_DEBOUNCE_MS = 350;

type SubscriptionView = "requests" | "approved";
type ApprovedStatus = "active" | "inactive" | "expired";

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
const view = ref<SubscriptionView>("requests");
const approvedStatus = ref<ApprovedStatus>("active");

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const statusParam = computed(() =>
    view.value === "requests" ? "pending" : approvedStatus.value,
);

const isSearching = computed(() => search.value.trim().length > 0);

const totalLabel = computed(() => {
    if (view.value === "requests") {
        return total.value === 1 ? "Request" : "Requests";
    }

    const statusText =
        approvedStatus.value.charAt(0).toUpperCase() +
        approvedStatus.value.slice(1);

    return `${statusText} ${total.value === 1 ? "Subscription" : "Subscriptions"}`;
});

const emptyStateTitle = computed(() => {
    if (isSearching.value) return "No matches found";

    return view.value === "requests"
        ? "No pending requests"
        : "No subscriptions found";
});

const emptyStateDescription = computed(() => {
    const query = search.value.trim();

    if (isSearching.value) {
        const scope =
            view.value === "requests"
                ? "pending requests"
                : `${approvedStatus.value} subscriptions`;

        return `No ${scope} match "${query}". Try a different agency or branch name.`;
    }

    if (view.value === "requests") {
        return "There are currently no subscription requests waiting for approval. New requests will show up here automatically.";
    }

    return `There are no ${approvedStatus.value} subscriptions right now.`;
});

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

const applyStatSelection = (
    payload:
        | { view: "requests" }
        | { view: "approved"; status: ApprovedStatus },
) => {
    view.value = payload.view;

    if (payload.view === "approved") {
        approvedStatus.value = payload.status;
    }
};

const approveSubscription = async (subscription: any) => {
    const uuid = subscription.uuid;

    if (processingAction.value[uuid]) return;

    processingAction.value = { ...processingAction.value, [uuid]: "approve" };

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

const rejectSubscription = async (subscription: any) => {
    const uuid = subscription.uuid;
    if (processingAction.value[uuid]) return;
    processingAction.value = { ...processingAction.value, [uuid]: "reject" };
    try {
        await subscriptionService.action({
            action: "reject",
            branch_subscription_uuid: uuid,
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
</script>
