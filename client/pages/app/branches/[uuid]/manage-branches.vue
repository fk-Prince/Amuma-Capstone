<template>
    <div class="min-h-screen bg-slate-50 px-4 lg:px-8 py-8">
        <BranchDashboard :stats-data="statsData" />

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
            <div class="relative flex-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
                >
                    <circle cx="11" cy="11" r="7" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search branches, location, manager..."
                    class="w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 py-2.5 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                />
            </div>

            <button
                type="button"
                class="shrink-0 h-[42px] w-[42px] rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-50"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-4 h-4"
                >
                    <line x1="4" y1="6" x2="20" y2="6" />
                    <line x1="8" y1="12" x2="20" y2="12" />
                    <line x1="12" y1="18" x2="20" y2="18" />
                </svg>
            </button>

            <select
                v-model="statusFilter"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-primary/30"
            >
                <option value="all">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <button
                type="button"
                class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:opacity-90 transition"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    class="w-4 h-4"
                >
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Add New Branch
            </button>
        </div>

        <div class="rounded-2xl bg-white border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div
                        v-if="agency?.image"
                        class="h-9 w-9 rounded-xl overflow-hidden shrink-0"
                    >
                        <img
                            :src="agency.image"
                            :alt="agency.name"
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <div
                        v-else
                        class="h-9 w-9 rounded-xl bg-primary-50 flex items-center justify-center text-primary shrink-0"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="w-4.5 h-4.5"
                        >
                            <circle cx="6" cy="6" r="2.5" />
                            <circle cx="18" cy="6" r="2.5" />
                            <circle cx="12" cy="18" r="2.5" />
                            <path d="M8.2 7.3 10.5 16.5M15.8 7.3 13.5 16.5" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Branch Directory
                        </h2>
                        <p
                            v-if="agency"
                            class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5"
                        >
                            <span
                                class="text-sm font-semibold text-slate-700"
                                >{{ agency.name }}</span
                            >
                            <span
                                v-if="agency.email"
                                class="flex items-center gap-1 text-xs text-slate-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="w-3 h-3"
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
                                {{ agency.email }}
                            </span>
                            <span
                                v-if="agency.location"
                                class="flex items-center gap-1 text-xs text-slate-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="w-3 h-3"
                                >
                                    <path
                                        d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                                    />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                                {{ agency.location }}
                            </span>
                        </p>
                    </div>
                </div>

                <div
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1"
                >
                    <button
                        type="button"
                        class="h-8 w-8 rounded-lg flex items-center justify-center transition"
                        :class="
                            viewMode === 'list'
                                ? 'bg-white shadow-sm text-slate-700'
                                : 'text-slate-400 hover:text-slate-600'
                        "
                        @click="viewMode = 'list'"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="w-4 h-4"
                        >
                            <line x1="4" y1="6" x2="20" y2="6" />
                            <line x1="4" y1="12" x2="20" y2="12" />
                            <line x1="4" y1="18" x2="20" y2="18" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="h-8 w-8 rounded-lg flex items-center justify-center transition"
                        :class="
                            viewMode === 'grid'
                                ? 'bg-primary text-white shadow-sm'
                                : 'text-slate-400 hover:text-slate-600'
                        "
                        @click="viewMode = 'grid'"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="w-4 h-4"
                        >
                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                v-if="loading"
                class="grid gap-5 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4"
            >
                <div
                    v-for="n in 4"
                    :key="n"
                    class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 animate-pulse space-y-4"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="h-14 w-14 rounded-xl bg-slate-200 shrink-0"
                        ></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-2/3 rounded bg-slate-200"></div>
                            <div class="h-3 w-1/3 rounded bg-slate-200"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="h-3 w-full rounded bg-slate-200"></div>
                        <div class="h-3 w-4/5 rounded bg-slate-200"></div>
                        <div class="h-3 w-3/5 rounded bg-slate-200"></div>
                    </div>
                    <div class="h-9 rounded-lg bg-slate-200"></div>
                </div>
            </div>

            <div
                v-else-if="!filteredBranches.length"
                class="py-16 flex flex-col items-center justify-center text-center"
            >
                <div
                    class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="w-6 h-6"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-600">
                    No branches found
                </p>
                <p class="text-xs text-slate-400 mt-1">
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
                    v-for="branch in filteredBranches"
                    :key="branch.branch_id"
                    :branch="branch"
                    @toggle-status="toggleStatus"
                    @edit="onEditBranch"
                    @menu="onBranchMenu"
                />
            </div>

            <div
                v-if="
                    !loading &&
                    filteredBranches.length &&
                    currentPage < lastPage
                "
                class="flex justify-center pt-6"
            >
                <button
                    type="button"
                    :disabled="loadingMore"
                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium hover:bg-slate-50 transition disabled:opacity-50"
                    @click="fetchBranches(currentPage + 1)"
                >
                    {{ loadingMore ? "Loading..." : "Load More" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import BranchDashboard from "~/components/sections/app/branches/BranchDashboard.vue";
import BranchCard from "~/components/sections/app/branches/BranchCard.vue";
import { computed, ref, h, onMounted } from "vue";
import { agencyService } from "~/api/agency/AgencyService";
import { useBranchStore } from "~/stores/branch";
import { useRoute } from "vue-router";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

function onEditBranch(branch: Branch) {}

function onBranchMenu(branch: Branch) {}
const route = useRoute();

type Branch = {
    branch_id: number;
    uuid: string;
    name: string;
    region: string;
    address: string;
    phone: string;
    email: string;
    tags: string;
    status: "active" | "inactive";
    rooms: number;
    staffs: number;
    patients: number;
    image: string;
};

const branchStore = useBranchStore();

const search = ref("");
const statusFilter = ref<"all" | "active" | "inactive">("all");
const viewMode = ref<"list" | "grid">("grid");

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

const filteredBranches = computed(() => {
    return branches.value.filter((b) => {
        const matchesSearch =
            !search.value ||
            b.name.toLowerCase().includes(search.value.toLowerCase()) ||
            b.region.toLowerCase().includes(search.value.toLowerCase());

        const matchesStatus =
            statusFilter.value === "all" || b.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

function toggleStatus(branch: Branch) {
    branch.status = branch.status === "active" ? "inactive" : "active";
}

const mapBranch = (b: any): Branch => ({
    branch_id: b.branch_id,
    uuid: b.uuid,
    name: b.name,
    region: b.location?.province ?? "—",
    address:
        [b.location?.street, b.location?.city].filter(Boolean).join(", ") ||
        "—",
    phone: b.contact_number ?? "—",
    email: b.email ?? "—",
    tags: Array.isArray(b.services) ? b.services.join(" + ") : "",
    status: b.status,
    rooms: b.rooms_count ?? 0,
    staffs: b.staff_count ?? 0,
    patients: b.patients_count ?? 0,
    image: b.image ?? "/assets/placeholder-branch.png",
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
        image:
            a.image instanceof File
                ? URL.createObjectURL(a.image)
                : (a.image ?? null),
        location: [a.location?.city, a.location?.province]
            .filter(Boolean)
            .join(", "),
    };
});

const fetchBranches = async (page = 1) => {
    if (page === 1) loading.value = true;
    else loadingMore.value = true;

    try {
        const res = await agencyService.list({
            per_page: 10,
            page,
            agency_id: branchStore.activeBranch?.agency.agency_id,
            type: "agency_branches",
            branch_uuid: route.params.uuid,
        });

        const mapped = res.data.map(mapBranch);
        branches.value = page === 1 ? mapped : [...branches.value, ...mapped];
        currentPage.value = res.current_page;
        lastPage.value = res.last_page;
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

onMounted(async () => {
    await Promise.all([fetchStats(), fetchBranches()]);
});
</script>
