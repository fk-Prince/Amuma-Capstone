<template>
    <div class="min-h-screen-header rounded-lg p-3 sm:p-1">
        <BranchDashboard :stats-data="statsData" :loading="statsLoading" />

        <div
            class="mt-2 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div
                class="border-b border-slate-100 px-3 py-5 dark:border-white/10"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="relative w-full min-w-[220px] sm:max-w-sm sm:flex-1">
                        <svg
                            viewBox="0 0 24 24"
                            class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <path
                                d="M21 21l-4.35-4.35"
                                stroke-linecap="round"
                            />
                        </svg>

                        <BaseInput
                            v-model="search"
                            placeholder="Search by branch name, address or email..."
                            input-class="pl-11"
                        />
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto">
                        <Combobox
                            v-model="statusFilter"
                            class="w-full sm:w-56"
                            placeholder="Filter branches"
                            :items="statusOptions"
                        />

                        <ActionButton
                            variant="primary"
                            extra-class="!rounded-xl !px-5 !py-2.5"
                            :disabled="
                                addDisabled || !canAddBranch || testingAtMax
                            "
                            :tooltip="addBranchTooltip"
                            @click="openAddBranch"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>

                            <span>Add New Branch</span>
                        </ActionButton>

                        <span
                            v-if="statsData.branch_capacity?.capacity"
                            class="shrink-0 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-medium text-slate-500 dark:border-white/10 dark:text-gray-400"
                        >
                            {{ statsData.branch_capacity.used }} of
                            {{ statsData.branch_capacity.capacity }} branches
                            used
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div
                    class="mb-6 flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between"
                >
                    <div class="flex min-w-0 items-start gap-4">
                        <!-- <div
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50 dark:border-white/10 dark:bg-white/5"
                    > -->
                        <img
                            v-if="agency?.image"
                            :src="agency.image"
                            :alt="agency.name"
                            class="h-16 w-16 shrink-0 rounded-2xl border border-slate-200 object-cover shadow-sm dark:border-white/10"
                        />
                        <!-- </div> -->

                        <div
                            v-else
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-primary/10 bg-primary-50 text-primary shadow-sm dark:bg-primary-500/10"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-6 w-6"
                            >
                                <circle cx="6" cy="6" r="2.5" />
                                <circle cx="18" cy="6" r="2.5" />
                                <circle cx="12" cy="18" r="2.5" />
                                <path
                                    d="M8.2 7.3 10.5 16.5M15.8 7.3 13.5 16.5"
                                />
                            </svg>
                        </div>

                        <div v-if="agency" class="min-w-0">
                            <h1
                                class="truncate text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                            >
                                {{ agency.name }}
                            </h1>

                            <!-- <p
                            v-if="agency.description"
                            class="mt-1 max-w-2xl text-sm leading-6 text-slate-500 dark:text-gray-400"
                        >
                            {{ agency.description }}
                        </p> -->

                            <p
                                v-if="subscriptionPlan?.name"
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                {{ planTypeLabel(subscriptionPlan.type) }} ·
                                {{ subscriptionPlan.name }}
                            </p>
                            <div
                                class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-100 pt-3 dark:border-white/10"
                            >
                                <span
                                    v-if="agency.email"
                                    class="flex items-center gap-2 text-sm text-slate-500 dark:text-gray-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500"
                                    >
                                        <rect
                                            x="2"
                                            y="4"
                                            width="20"
                                            height="16"
                                            rx="2"
                                        />
                                        <path d="m22 6-10 7L2 6" />
                                    </svg>

                                    <span>{{ agency.email }}</span>
                                </span>

                                <span
                                    v-if="agency.location"
                                    class="flex items-center gap-2 text-sm text-slate-500 dark:text-gray-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500"
                                    >
                                        <path
                                            d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                                        />
                                        <circle cx="12" cy="10" r="2.5" />
                                    </svg>

                                    <span>{{ agency.location }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <ActionButton
                        variant="primary"
                        extra-class="!rounded-xl !px-5 !py-2.5 shrink-0"
                        :disabled="!renewalAvailable"
                        :tooltip="renewalBlockedReason"
                        @click="showRenewal = true"
                    >
                        <RefreshCw class="h-4 w-4" />
                        <span>Renewal</span>
                    </ActionButton>
                </div>

                <div
                    class="mb-6 border-t border-slate-100 pt-5 dark:border-white/10"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold text-slate-900 dark:text-white"
                        >
                            Branch Directory
                        </h2>

                        <p
                            class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                        >
                            Manage and view branches under this agency.
                        </p>
                    </div>
                </div>

                <div
                    v-if="loading"
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <div
                        v-for="n in 4"
                        :key="n"
                        class="space-y-4 rounded-2xl border border-slate-100 bg-slate-50/60 p-4 animate-pulse dark:border-white/10 dark:bg-white/5"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="h-14 w-14 shrink-0 rounded-xl bg-slate-200 dark:bg-white/10"
                            ></div>

                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-4 w-2/3 rounded bg-slate-200 dark:bg-white/10"
                                ></div>
                                <div
                                    class="h-3 w-1/3 rounded bg-slate-200 dark:bg-white/10"
                                ></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                class="h-3 w-full rounded bg-slate-200 dark:bg-white/10"
                            ></div>
                            <div
                                class="h-3 w-4/5 rounded bg-slate-200 dark:bg-white/10"
                            ></div>
                            <div
                                class="h-3 w-3/5 rounded bg-slate-200 dark:bg-white/10"
                            ></div>
                        </div>

                        <div
                            class="h-9 rounded-lg bg-slate-200 dark:bg-white/10"
                        ></div>
                    </div>
                </div>

                <div
                    v-else-if="!branches.length"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div
                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-6 w-6"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </div>

                    <p
                        class="text-sm font-medium text-slate-600 dark:text-gray-300"
                    >
                        No branches found
                    </p>

                    <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
                        Try adjusting your search or filters.
                    </p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <BranchCard
                        v-for="branch in branches"
                        :key="branch.branch_id"
                        :branch="branch"
                        :active="branch.uuid === route.params.uuid"
                        :can-resubmit="canResubmit"
                        @resubmit="onResubmitBranch"
                    />
                </div>

                <div
                    v-if="!loading && branches.length && currentPage < lastPage"
                    class="flex justify-center pt-6"
                >
                    <button
                        type="button"
                        :disabled="loadingMore"
                        class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                        @click="fetchBranches(currentPage + 1)"
                    >
                        {{ loadingMore ? "Loading..." : "Load More" }}
                    </button>
                </div>
            </div>
        </div>

        <AddBranchModal
            v-if="showAddBranch && agencyId"
            :agency-id="agencyId"
            :agency-name="agency?.name ?? 'Your agency'"
            :branch-uuid="route.params.uuid as string"
            :capacity="statsData.branch_capacity"
            @close="showAddBranch = false"
            @created="onBranchCreated"
        />

        <RenewalModal
            :open="showRenewal"
            :uuid="route.params.uuid as string"
            @close="closeRenewal"
        />

        <ResubmitBranchModal
            v-if="resubmitTarget"
            :branch="resubmitTarget.branch"
            :acting-branch-uuid="route.params.uuid as string"
            :rejection-reason="resubmitTarget.reason"
            :requires-purchase="!!resubmitTarget.purchaseReason"
            :purchase-reason="resubmitTarget.purchaseReason"
            @close="resubmitTarget = null"
            @resubmitted="onBranchResubmitted"
        />

        <ConfirmDialog
            :open="!!purchasePrompt"
            title="Purchase a new subscription?"
            :message="`${purchasePrompt?.purchaseReason ?? ''} To resubmit ${purchasePrompt?.branch.name ?? 'this branch'}, it needs a new subscription.`"
            description="You'll update the branch details first, then choose a plan and pay. The branch moves onto the new subscription and goes back for review."
            confirm-label="Buy subscription"
            @confirm="confirmPurchasePrompt"
            @cancel="purchasePrompt = null"
        />
    </div>
</template>

<script setup lang="ts">
import BranchDashboard from "~/components/sections/app/branches/BranchDashboard.vue";
import BranchCard from "~/components/sections/app/branches/BranchCard.vue";
import AddBranchModal from "~/components/sections/app/Branch/AddBranchModal.vue";
import ResubmitBranchModal from "~/components/sections/app/branches/ResubmitBranchModal.vue";
import RenewalModal from "~/components/sections/app/branches/RenewalModal.vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import ActionButton from "~/components/ui/ActionButton.vue";
import type { Branch as FullBranch } from "~/types/branch";
import Combobox from "~/components/ui/Combobox.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import {
    CircleCheck,
    CircleX,
    Clock,
    LayoutGrid,
    RefreshCw,
} from "lucide-vue-next";
import { computed, ref, h, onMounted, onBeforeUnmount, watch } from "vue";
import { agencyService } from "~/api/agency/AgencyService";
import { useBranchStore } from "~/stores/branch";
import { useToast } from "~/composables/useToast";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";
import { useRoute } from "vue-router";
import logo from "~/assets/logo/logo.png";
import {
    planTypeBranchLimit,
    planTypeLabel,
    type PlanType,
} from "~/utils/planType";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});
useHead({ title: "Manage Subscription" });

const route = useRoute();
const { error } = useToast();
const { canCreate, canUpdate } = usePermissions();

const canResubmit = computed(() => canUpdate(Modules.ManageSubscription));
const canAddBranch = computed(() => canCreate(Modules.ManageSubscription));

const resubmitTarget = ref<{
    branch: FullBranch;
    reason: string | null;
    purchaseReason: string | null;
} | null>(null);

const purchaseReasonFor = (branch: Branch): string | null => {
    if (branch.subscription_status === "rejected") {
        return "This branch's subscription was refunded when it was rejected.";
    }

    if (branch.subscription_status === "expired") {
        return "This branch's subscription has expired.";
    }

    if (!branch.slot_available) {
        return "This branch's subscription has no free slot left.";
    }

    return null;
};

function onResubmitBranch(branch: Branch) {
    if (!canResubmit.value) return;

    const full = branchStore.branches.find((b) => b.uuid === branch.uuid);

    if (!full) {
        error(
            "Couldn't load this branch's details. Refresh the page and try again.",
        );
        return;
    }

    const target = {
        branch: full,
        reason: branch.rejection_reason ?? null,
        purchaseReason: purchaseReasonFor(branch),
    };

    if (target.purchaseReason) {
        purchasePrompt.value = target;
        return;
    }

    resubmitTarget.value = target;
}

const purchasePrompt = ref<typeof resubmitTarget.value>(null);

const confirmPurchasePrompt = () => {
    resubmitTarget.value = purchasePrompt.value;
    purchasePrompt.value = null;
};

const onBranchResubmitted = (result: any) => {
    resubmitTarget.value = null;

    const updated = result?.branch;
    if (!updated) return;

    const purchased = result.purchased && result.subscription;

    branches.value =
        statusFilter.value === "rejected"
            ? branches.value.filter((b) => b.uuid !== updated.uuid)
            : branches.value.map((b) =>
                  b.uuid === updated.uuid
                      ? mapBranch({
                            ...updated,
                            staff_count: b.staffs,
                            patients_count: b.patients,
                            plan: purchased
                                ? {
                                      plan_code: result.subscription.plan_code,
                                      name: result.subscription.plan_name,
                                  }
                                : b.plan,
                            subscription_status: purchased
                                ? result.subscription.status
                                : b.subscription_status,
                            slot_available: true,
                        })
                      : b,
              );

    const storeIndex = branchStore.branches.findIndex(
        (b) => b.uuid === updated.uuid,
    );

    if (storeIndex !== -1) {
        const current = branchStore.branches[storeIndex];

        branchStore.branches[storeIndex] = {
            ...current,
            name: updated.name,
            email: updated.email,
            contact_number: updated.contact_number,
            description: updated.description,
            image: updated.image,
            document: updated.document,
            settings: updated.settings,
            status: updated.status,
            subscription_status: "pending",
            rejection_reason: null,
            location: {
                ...updated.location,
                address: updated.location?.full_address,
            },
        };
    }

    const capacity = statsData.value.branch_capacity;
    const branchLimit = planTypeBranchLimit(result.subscription?.plan_type);
    const nextUsed = capacity.used + 1;
    const nextCapacity = capacity.capacity + (purchased ? branchLimit : 0);

    const existing = (capacity.available_subscriptions ?? [])
        .map((option) =>
            option.uuid === result.subscription_uuid
                ? {
                      ...option,
                      branches_used: option.branches_used + 1,
                      slots_left: option.slots_left - 1,
                  }
                : option,
        )
        .filter((option) => option.slots_left > 0);

    statsData.value = {
        ...statsData.value,
        branch_capacity: {
            ...capacity,
            used: nextUsed,
            capacity: nextCapacity,
            remaining: Math.max(0, nextCapacity - nextUsed),
            has_room: nextUsed < nextCapacity,
            available_subscriptions: purchased
                ? [
                      ...existing,
                      {
                          ...result.subscription,
                          branches_used: 1,
                          branch_limit: branchLimit,
                          slots_left: branchLimit - 1,
                      },
                  ]
                : existing,
        },
    };
};

type Branch = {
    branch_id: number;
    uuid: string;
    name: string;
    address: string;
    phone: string;
    email: string;
    tin?: string | null;
    status: "pending" | "verified" | "rejected";
    review_status?: "pending" | "verified" | "rejected";
    rejection_reason?: string | null;
    subscription_status?: "active" | "expired" | "pending" | "rejected" | null;
    slot_available?: boolean;
    staffs: number;
    patients: number;
    plan: { plan_code: string; name: string } | null;
    image: string;
};

type AvailableSubscription = {
    uuid: string;
    plan_name: string | null;
    plan_code: string | null;
    plan_type: PlanType | null;
    status?: string | null;
    end_date: string | null;
    branches_used: number;
    branch_limit: number;
    slots_left: number;
};

const branchStore = useBranchStore();

const search = ref("");
const statusFilter = ref<"all" | "verified" | "pending" | "rejected">("all");

const statusOptions = [
    { label: "All branches", value: "all", iconComponent: LayoutGrid },
    { label: "Verified", value: "verified", iconComponent: CircleCheck },
    { label: "Pending review", value: "pending", iconComponent: Clock },
    { label: "Rejected", value: "rejected", iconComponent: CircleX },
];

const showAddBranch = ref(false);

const agencyId = computed(
    () =>
        branchStore.activeBranch?.agency?.agency_id ??
        branchStore.branches[0]?.agency?.agency_id ??
        null,
);

const openAddBranch = async () => {
    // Direct loads can reach this page before the branch store has hydrated,
    // so pull the list in on demand rather than leaving the button inert.
    if (!branchStore.branches.length) {
        await branchStore.fetchBranches();
    }

    showAddBranch.value = true;
};

const onBranchCreated = (result: any) => {
    showAddBranch.value = false;

    const created = result?.branch;

    if (!created) return;

    branches.value = [mapBranch(created), ...branches.value];

    const addedCapacity = result?.used_existing_capacity
        ? 0
        : planTypeBranchLimit(result?.plan_type);
    const nextCapacity =
        statsData.value.branch_capacity.capacity + addedCapacity;
    const nextUsed = statsData.value.branch_capacity.used + 1;

    statsData.value = {
        ...statsData.value,
        total_branches: statsData.value.total_branches + 1,
        total_branches_new_this_month:
            statsData.value.total_branches_new_this_month + 1,
        active_branches_percent: percentOfTotal(
            statsData.value.active_branches,
            statsData.value.total_branches + 1,
        ),
        expiring_soon_percent: percentOfTotal(
            statsData.value.expiring_soon,
            statsData.value.total_branches + 1,
        ),
        branch_capacity: {
            ...statsData.value.branch_capacity,
            used: nextUsed,
            capacity: nextCapacity,
            remaining: Math.max(0, nextCapacity - nextUsed),
            has_room: nextUsed < nextCapacity,
        },
    };
};

const percentOfTotal = (value: number, total: number) =>
    total ? Math.round((value / total) * 100) : 0;

const loading = ref(true);
const loadingMore = ref(false);

const branches = ref<Branch[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);

const statsData = ref({
    total_branches: 0,
    total_branches_new_this_month: 0,
    active_branches: 0,
    active_branches_percent: 0,
    expiring_soon: 0,
    expiring_soon_percent: 0,
    maintenance_alerts: 0,
    branch_capacity: {
        used: 0,
        capacity: 0,
        remaining: 0,
        has_room: false,
        available_subscriptions: [] as AvailableSubscription[],
        is_testing: false,
    },
});

const mapBranch = (b: any): Branch => ({
    branch_id: b.branch_id,
    uuid: b.uuid,
    name: b.name,
    address:
        b.location?.full_address ||
        [
            b.location?.street,
            b.location?.city,
            b.location?.province,
            b.location?.country,
        ]
            .filter(Boolean)
            .join(", ") ||
        "—",
    phone: b.contact_number ?? "—",
    email: b.email ?? "—",
    tin: b.tin ?? null,
    status: b.status,
    review_status: b.review_status,
    rejection_reason: b.rejection_reason ?? null,
    subscription_status: b.subscription_status ?? null,
    slot_available: b.slot_available ?? false,
    staffs: b.staff_count ?? 0,
    patients: b.patients_count ?? 0,
    plan: b.plan ?? null,
    image: b.image ?? logo,
});

const statsLoading = ref(true);

const isTesting = computed(() =>
    Boolean(statsData.value.branch_capacity?.is_testing),
);

const testingAtMax = computed(
    () =>
        isTesting.value &&
        !statsData.value.branch_capacity?.available_subscriptions?.length,
);

const addBranchTooltip = computed(() => {
    if (!canAddBranch.value) {
        return "You need permission to create in Manage Subscription to add a branch.";
    }

    return testingAtMax.value
        ? "You've reached your plan's branch limit. More branches can be added once your free testing ends."
        : "";
});

const addDisabled = computed(
    () => branchStore.loading || loading.value || statsLoading.value,
);

const fetchStats = async () => {
    try {
        const res = await agencyService.list({
            per_page: 10,
            agency_id: branchStore.activeBranch?.agency?.agency_id,
            type: "stats",
            branch_uuid: route.params.uuid,
        });

        statsData.value = res.data;
    } catch (err: any) {
        error(err?.message ?? "Failed to load branch stats.");
    } finally {
        statsLoading.value = false;
    }
};

const agency = computed(() => {
    const a = branchStore.activeBranch?.agency;
    if (!a) return null;

    return {
        name: a.name ?? "",
        email: a.email ?? "",
        description: a.description ?? "",
        image:
            a.image instanceof File
                ? URL.createObjectURL(a.image)
                : (a.image ?? logo),
        location: [a.location?.city, a.location?.province]
            .filter(Boolean)
            .join(", "),
    };
});

let requestId = 0;

const fetchBranches = async (page = 1) => {
    const thisRequest = ++requestId;

    if (page === 1) loading.value = true;
    else loadingMore.value = true;

    try {
        const res = await agencyService.list({
            per_page: 10,
            page,
            agency_id: branchStore.activeBranch?.agency?.agency_id,
            type: "agency_branches",
            branch_uuid: route.params.uuid,
            search: search.value.trim() || undefined,
            status: statusFilter.value,
        });

        if (thisRequest !== requestId) return;

        const mapped = res.data.map(mapBranch);
        branches.value = page === 1 ? mapped : [...branches.value, ...mapped];
        currentPage.value = res.current_page;
        lastPage.value = res.last_page;
    } catch (err: any) {
        if (thisRequest === requestId) {
            error(err?.message ?? "Failed to load branches.");
        }
    } finally {
        if (thisRequest === requestId) {
            loading.value = false;
            loadingMore.value = false;
        }
    }
};

let searchDebounce: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchBranches(1), 400);
});

// The select commits in one action, so it refetches straight away.
watch(statusFilter, () => fetchBranches(1));

onBeforeUnmount(() => clearTimeout(searchDebounce));

const showRenewal = ref(false);
const renewalSummary = ref<any>(null);
const subscriptionPlan = ref<any>(null);

const renewalAvailable = computed(
    () =>
        canUpdate(Modules.ManageSubscription) &&
        Boolean(
            renewalSummary.value?.can_renew ||
            renewalSummary.value?.can_upgrade ||
            renewalSummary.value?.can_resubscribe,
        ),
);

const renewalBlockedReason = computed(() => {
    if (renewalAvailable.value) return "";

    if (!canUpdate(Modules.ManageSubscription)) {
        return "You need permission to update Manage Subscription to renew.";
    }

    if (!renewalSummary.value) return "The subscription details couldn't be loaded.";

    return "There's nothing to renew yet. Renewal opens 7 days before the plan ends.";
});

const fetchRenewal = async () => {
    try {
        const res = await subscriptionService.list({
            branch_uuid: route.params.uuid,
            per_page: 1,
        });

        renewalSummary.value = res?.data?.[0]?.renewal ?? null;
        subscriptionPlan.value = res?.data?.[0]?.plan ?? null;
    } catch {
        renewalSummary.value = null;
        subscriptionPlan.value = null;
    }
};

const closeRenewal = () => {
    showRenewal.value = false;
    fetchRenewal();
};

onMounted(async () => {
    await Promise.all([fetchStats(), fetchBranches(), fetchRenewal()]);
});
</script>
