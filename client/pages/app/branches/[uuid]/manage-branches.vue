<template>
    <div class="min-h-screen bg-slate-50 px-4 lg:px-8 py-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="relative rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden"
            >
                <div
                    class="absolute top-0 left-0 right-0 h-1"
                    :class="stat.barClass"
                />

                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div
                            class="h-10 w-10 rounded-xl flex items-center justify-center"
                            :class="stat.iconBgClass"
                        >
                            <component
                                :is="stat.icon"
                                class="h-5 w-5"
                                :class="stat.iconClass"
                            />
                        </div>
                    </div>

                    <p
                        class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400"
                    >
                        {{ stat.label }}
                    </p>

                    <p class="mt-1 text-3xl font-semibold text-slate-900">
                        {{ stat.value }}
                    </p>

                    <p
                        class="mt-2 text-xs flex items-center gap-1"
                        :class="stat.trendClass"
                    >
                        <svg
                            v-if="stat.trend"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            class="w-3 h-3"
                        >
                            <polyline points="18 15 12 9 6 15" />
                        </svg>
                        <span v-if="stat.trend">{{ stat.trend }}</span>
                        <span v-else class="text-rose-500 font-medium"
                            >Requires Attention</span
                        >
                    </p>
                </div>
            </div>
        </div>

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
                <option value="deactivated">Deactivated</option>
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
                        class="h-9 w-9 rounded-xl bg-primary-50 flex items-center justify-center text-primary"
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
                    <h2 class="text-base font-semibold text-slate-900">
                        Branch Directory
                    </h2>
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
                class="grid gap-5"
                :class="
                    viewMode === 'grid'
                        ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4'
                        : 'grid-cols-1'
                "
            >
                <div
                    v-for="branch in filteredBranches"
                    :key="branch.id"
                    class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 hover:border-primary-200 hover:shadow-sm transition"
                >
                    <div class="flex items-start gap-3">
                        <img
                            :src="branch.image"
                            :alt="branch.name"
                            class="h-14 w-14 rounded-xl object-cover shrink-0"
                        />

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-slate-900 truncate"
                                    >
                                        {{ branch.name }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ branch.region }}
                                    </p>
                                </div>

                                <span
                                    class="shrink-0 text-[11px] font-medium rounded-full px-2.5 py-1"
                                    :class="
                                        branch.status === 'Active'
                                            ? 'bg-emerald-50 text-emerald-600'
                                            : 'bg-slate-200 text-slate-500'
                                    "
                                >
                                    {{ branch.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-1.5 text-xs text-slate-500">
                        <p class="flex items-center gap-1.5">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-3.5 h-3.5 shrink-0"
                            >
                                <path
                                    d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                                />
                                <circle cx="12" cy="10" r="2.5" />
                            </svg>
                            {{ branch.address }}
                        </p>
                        <p class="flex items-center gap-1.5">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-3.5 h-3.5 shrink-0"
                            >
                                <path
                                    d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2.1Z"
                                />
                            </svg>
                            {{ branch.phone }}
                        </p>
                        <p class="flex items-center gap-1.5">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-3.5 h-3.5 shrink-0"
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
                            {{ branch.email }}
                        </p>
                    </div>

                    <span
                        class="inline-block mt-3 text-[11px] font-medium rounded-full px-2.5 py-1 bg-primary-50 text-primary"
                    >
                        {{ branch.tags }}
                    </span>

                    <div
                        class="mt-4 grid grid-cols-3 divide-x divide-slate-200 border-t border-slate-200 pt-3"
                    >
                        <div class="text-center">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ branch.rooms }}
                            </p>
                            <p class="text-[11px] text-slate-400">Rooms</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ branch.staffs }}
                            </p>
                            <p class="text-[11px] text-slate-400">Staffs</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ branch.patients }}
                            </p>
                            <p class="text-[11px] text-slate-400">Patients</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <button
                            type="button"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white py-2 text-xs font-medium text-slate-600 hover:border-primary hover:text-primary transition"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-3.5 h-3.5"
                            >
                                <path d="M12 20h9" />
                                <path
                                    d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"
                                />
                            </svg>
                            Edit
                        </button>

                        <div
                            class="flex-1 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2"
                        >
                            <span class="text-xs text-slate-500"
                                >Deactivate</span
                            >
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="branch.status === 'Active'"
                                class="relative h-5 w-9 rounded-full transition-colors"
                                :class="
                                    branch.status === 'Active'
                                        ? 'bg-primary'
                                        : 'bg-slate-200'
                                "
                                @click="toggleStatus(branch)"
                            >
                                <span
                                    class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                                    :class="
                                        branch.status === 'Active'
                                            ? 'translate-x-4'
                                            : 'translate-x-0'
                                    "
                                />
                            </button>
                        </div>

                        <button
                            type="button"
                            class="h-8 w-8 shrink-0 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-400 hover:text-slate-600"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-4 h-4"
                            >
                                <circle cx="12" cy="5" r="1.2" />
                                <circle cx="12" cy="12" r="1.2" />
                                <circle cx="12" cy="19" r="1.2" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, h } from "vue";
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});
type Branch = {
    id: number;
    name: string;
    region: string;
    address: string;
    phone: string;
    email: string;
    tags: string;
    status: "Active" | "Deactivated";
    rooms: number;
    staffs: number;
    patients: number;
    image: string;
};

const search = ref("");
const statusFilter = ref<"all" | "active" | "deactivated">("all");
const viewMode = ref<"list" | "grid">("grid");

const branches = ref<Branch[]>([
    {
        id: 1,
        name: "CareHaven Home Services",
        region: "Mindanao",
        address: "1234 Street, Davao City",
        phone: "(000) 1234-4567",
        email: "mindanao@amuma.com",
        tags: "Homecare + In-House facility",
        status: "Active",
        rooms: 7,
        staffs: 10,
        patients: 50,
        image: "https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200&h=200&fit=crop",
    },
    {
        id: 2,
        name: "CareHaven Home Services",
        region: "Visayas",
        address: "1234 Street, Visayas",
        phone: "(000) 1234-4567",
        email: "mindanao@amuma.com",
        tags: "Homecare + In-House facility",
        status: "Deactivated",
        rooms: 7,
        staffs: 10,
        patients: 50,
        image: "https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200&h=200&fit=crop",
    },
    {
        id: 3,
        name: "FamilyFirst In-Home Care",
        region: "Mindanao",
        address: "1234 Street, Davao City",
        phone: "(000) 1234-4567",
        email: "mindanao@amuma.com",
        tags: "Homecare + In-House facility",
        status: "Active",
        rooms: 7,
        staffs: 10,
        patients: 50,
        image: "https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200&h=200&fit=crop",
    },
    {
        id: 4,
        name: "FamilyFirst In-Home Care",
        region: "Luzon",
        address: "1234 Street, Davao City",
        phone: "(000) 1234-4567",
        email: "mindanao@amuma.com",
        tags: "Homecare + In-House facility",
        status: "Active",
        rooms: 7,
        staffs: 10,
        patients: 50,
        image: "https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200&h=200&fit=crop",
    },
]);

const filteredBranches = computed(() => {
    return branches.value.filter((b) => {
        const matchesSearch =
            !search.value ||
            b.name.toLowerCase().includes(search.value.toLowerCase()) ||
            b.region.toLowerCase().includes(search.value.toLowerCase());

        const matchesStatus =
            statusFilter.value === "all" ||
            b.status.toLowerCase() === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

function toggleStatus(branch: Branch) {
    branch.status = branch.status === "Active" ? "Deactivated" : "Active";
}

const IconBranches = () =>
    h(
        "svg",
        {
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2",
        },
        [
            h("circle", { cx: "6", cy: "6", r: "2.5" }),
            h("circle", { cx: "18", cy: "6", r: "2.5" }),
            h("circle", { cx: "12", cy: "18", r: "2.5" }),
            h("path", { d: "M8.2 7.3 10.5 16.5M15.8 7.3 13.5 16.5" }),
        ],
    );

const IconPin = () =>
    h(
        "svg",
        {
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2",
        },
        [
            h("path", {
                d: "M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z",
            }),
            h("circle", { cx: "12", cy: "10", r: "2.5" }),
        ],
    );

const IconScissors = () =>
    h(
        "svg",
        {
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2",
        },
        [
            h("circle", { cx: "6", cy: "6", r: "3" }),
            h("circle", { cx: "6", cy: "18", r: "3" }),
            h("line", { x1: "8.1", y1: "8.1", x2: "20", y2: "20" }),
            h("line", { x1: "8.1", y1: "15.9", x2: "20", y2: "4" }),
        ],
    );

const IconAlert = () =>
    h(
        "svg",
        {
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2",
        },
        [
            h("path", {
                d: "M10.3 3.9 1.8 18a1.8 1.8 0 0 0 1.5 2.7h17.4a1.8 1.8 0 0 0 1.5-2.7L13.7 3.9a1.8 1.8 0 0 0-3.4 0Z",
            }),
            h("line", { x1: "12", y1: "9", x2: "12", y2: "13" }),
            h("line", { x1: "12", y1: "17", x2: "12.01", y2: "17" }),
        ],
    );

const stats = [
    {
        label: "Total Branches",
        value: 10,
        trend: "3 new this month",
        barClass: "bg-primary",
        iconBgClass: "bg-primary-50",
        iconClass: "text-primary",
        trendClass: "text-emerald-600",
        icon: IconBranches,
    },
    {
        label: "Active Branches",
        value: 6,
        trend: "75% of total branches",
        barClass: "bg-emerald-500",
        iconBgClass: "bg-emerald-50",
        iconClass: "text-emerald-600",
        trendClass: "text-emerald-600",
        icon: IconPin,
    },
    {
        label: "Branches in Setup",
        value: 1,
        trend: "12% of total branches",
        barClass: "bg-fuchsia-500",
        iconBgClass: "bg-fuchsia-50",
        iconClass: "text-fuchsia-600",
        trendClass: "text-emerald-600",
        icon: IconScissors,
    },
    {
        label: "Maintenance Alerts",
        value: 1,
        trend: "",
        barClass: "bg-rose-500",
        iconBgClass: "bg-rose-50",
        iconClass: "text-rose-500",
        trendClass: "text-rose-500",
        icon: IconAlert,
    },
];
</script>
