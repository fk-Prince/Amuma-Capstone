<template>
    <div class="min-h-screen-header bg-slate-50 px-4 py-8 lg:px-8 dark:bg-secondary">
        <BranchDashboard :stats-data="statsData" />

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                >
                    <circle cx="11" cy="11" r="7" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>

                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name, address, email or contact..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-4 text-sm placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                />
            </div>

            <button
                type="button"
                class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 transition hover:bg-slate-50 hover:text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-4 w-4"
                >
                    <line x1="4" y1="6" x2="20" y2="6" />
                    <line x1="8" y1="12" x2="20" y2="12" />
                    <line x1="12" y1="18" x2="20" y2="18" />
                </svg>
            </button>

            <Combobox
                v-model="statusFilter"
                class="w-full shrink-0 sm:w-52"
                placeholder="Filter branches"
                :items="statusOptions"
            />

            <button
                type="button"
                :disabled="branchStore.loading"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                @click="openAddBranch"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    class="h-4 w-4"
                >
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>

                Add New Branch
            </button>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary">
            <div
                class="mb-6 flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between"
            >
                <div class="flex min-w-0 items-start gap-4">
                    <!-- <div
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50"
                    > -->
                    <img
                        v-if="agency?.image"
                        :src="agency.image"
                        :alt="agency.name"
                        class="h-14 w-14 object-cover"
                    />
                    <!-- </div> -->

                    <div
                        v-else
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary"
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
                            <path d="M8.2 7.3 10.5 16.5M15.8 7.3 13.5 16.5" />
                        </svg>
                    </div>

                    <div v-if="agency" class="min-w-0">
                        <h1
                            class="truncate text-xl font-semibold text-slate-800 dark:text-white"
                        >
                            {{ agency.name }}
                        </h1>

                        <!-- <p
                            v-if="agency.description"
                            class="mt-1 max-w-2xl text-sm leading-6 text-slate-500"
                        >
                            {{ agency.description }}
                        </p> -->

                        <div
                            class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2"
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

                <div
                    class="inline-flex w-fit shrink-0 items-center rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-white/10 dark:bg-white/5"
                >
                    <button
                        type="button"
                        aria-label="List view"
                        class="flex h-8 w-8 items-center justify-center rounded-lg transition"
                        :class="
                            viewMode === 'list'
                                ? 'bg-white text-slate-700 shadow-sm dark:bg-white/10 dark:text-white'
                                : 'text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300'
                        "
                        @click="viewMode = 'list'"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-4 w-4"
                        >
                            <line x1="4" y1="6" x2="20" y2="6" />
                            <line x1="4" y1="12" x2="20" y2="12" />
                            <line x1="4" y1="18" x2="20" y2="18" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        aria-label="Grid view"
                        class="flex h-8 w-8 items-center justify-center rounded-lg transition"
                        :class="
                            viewMode === 'grid'
                                ? 'bg-primary text-white shadow-sm'
                                : 'text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300'
                        "
                        @click="viewMode = 'grid'"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-4 w-4"
                        >
                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mb-6 border-t border-slate-100 pt-5 dark:border-white/10">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Branch Directory
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
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
                            <div class="h-4 w-2/3 rounded bg-slate-200 dark:bg-white/10"></div>
                            <div class="h-3 w-1/3 rounded bg-slate-200 dark:bg-white/10"></div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="h-3 w-full rounded bg-slate-200 dark:bg-white/10"></div>
                        <div class="h-3 w-4/5 rounded bg-slate-200 dark:bg-white/10"></div>
                        <div class="h-3 w-3/5 rounded bg-slate-200 dark:bg-white/10"></div>
                    </div>

                    <div class="h-9 rounded-lg bg-slate-200 dark:bg-white/10"></div>
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

                <p class="text-sm font-medium text-slate-600 dark:text-gray-300">
                    No branches found
                </p>

                <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
                    Try adjusting your search or filters.
                </p>
            </div>

            <div
                v-else
                class="grid gap-5"
                :class="
                    viewMode === 'grid'
                        ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4'
                        : 'grid-cols-1'
                "
            >
                <BranchCard
                    v-for="branch in branches"
                    :key="branch.branch_id"
                    :branch="branch"
                    @edit="onEditBranch"
                    @menu="onBranchMenu"
                />
            </div>

            <div
                v-if="
                    !loading &&
                    branches.length &&
                    currentPage < lastPage
                "
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

        <AddBranchModal
            v-if="showAddBranch && agencyId"
            :agency-id="agencyId"
            :agency-name="agency?.name ?? 'Your agency'"
            @close="showAddBranch = false"
            @created="onBranchCreated"
        />
    </div>
</template>

<script setup lang="ts">
import BranchDashboard from "~/components/sections/app/branches/BranchDashboard.vue";
import BranchCard from "~/components/sections/app/branches/BranchCard.vue";
import AddBranchModal from "~/components/sections/app/Branch/AddBranchModal.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { CircleCheck, Clock, LayoutGrid } from "lucide-vue-next";
import { computed, ref, h, onMounted, onBeforeUnmount, watch } from "vue";
import { agencyService } from "~/api/agency/AgencyService";
import { useBranchStore } from "~/stores/branch";
import { useRoute } from "vue-router";
import logo from "~/assets/logo/logo.png";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});
useHead({ title: "Branches" });

function onEditBranch(branch: Branch) {}

function onBranchMenu(branch: Branch) {}
const route = useRoute();

type Branch = {
    branch_id: number;
    uuid: string;
    name: string;
    address: string;
    phone: string;
    email: string;
    is_verified: boolean;
    rooms: number;
    staffs: number;
    patients: number;
    image: string;
};

const branchStore = useBranchStore();

const search = ref("");
const statusFilter = ref<"all" | "verified" | "pending">("all");

const statusOptions = [
    { label: "All branches", value: "all", iconComponent: LayoutGrid },
    { label: "Verified", value: "verified", iconComponent: CircleCheck },
    { label: "Pending review", value: "pending", iconComponent: Clock },
];
const viewMode = ref<"list" | "grid">("grid");

const showAddBranch = ref(false);

// Every branch belongs to an agency, so fall back to the first loaded branch:
// `activeBranch` resolves by route uuid and is briefly null while the branch
// store hydrates, which would otherwise leave "Add New Branch" stuck disabled.
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

    // Card payments create the branch inline and hand it back, so the list and
    // the counters are patched in place — no refetch, no loading flash.
    // GCash completes through a webhook after the redirect, so there is nothing
    // to sync yet; that branch shows up on the next load.
    if (!created) return;

    branches.value = [mapBranch(created), ...branches.value];

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
    is_verified: Boolean(b.is_verified),
    rooms: b.rooms_count ?? 0,
    staffs: b.staff_count ?? 0,
    patients: b.patients_count ?? 0,
    image: b.image ?? logo,
});

const fetchStats = async () => {
    const res = await agencyService.list({
        per_page: 10,
        agency_id: branchStore.activeBranch?.agency.agency_id,
        type: "stats",
        branch_uuid: route.params.uuid,
    });

    statsData.value = res.data;
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

// Guards against a slow early page overwriting the results of a later, more
// specific query when the user keeps typing.
let requestId = 0;

const fetchBranches = async (page = 1) => {
    const thisRequest = ++requestId;

    if (page === 1) loading.value = true;
    else loadingMore.value = true;

    try {
        const res = await agencyService.list({
            per_page: 10,
            page,
            agency_id: branchStore.activeBranch?.agency.agency_id,
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

onMounted(async () => {
    await Promise.all([fetchStats(), fetchBranches()]);
});
</script>
