<template>
    <div class="min-h-screen-header">
        <div class="space-y-3 p-3 sm:p-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div v-if="canSeeExecutiveOverview" class="min-w-0">
                    <h2
                        class="text-base font-bold text-secondary sm:text-lg dark:text-white"
                    >
                        All branches at a glance
                    </h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Comparing {{ overviewBranches.length }} branches of
                        {{ agency?.name }}
                    </p>
                </div>

                <div
                    v-if="canSeeExecutiveOverview"
                    class="flex items-center justify-between gap-3 text-xs text-gray-400 sm:justify-end dark:text-gray-500"
                >
                    <span v-if="updatedAt">
                        {{ periodLabel }} · Updated {{ updatedAt }}
                    </span>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-100 bg-white px-3 py-2 font-semibold text-gray-600 shadow-sm transition hover:text-primary disabled:opacity-60 dark:border-white/10 dark:bg-secondary dark:text-gray-300"
                        :disabled="loading"
                        @click="load"
                    >
                        <RefreshCw
                            class="h-3.5 w-3.5"
                            :class="{ 'animate-spin': loading }"
                        />
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Not an owner of a multi-branch agency -->
            <div
                v-if="branchStore.loaded && !canSeeExecutiveOverview"
                class="flex min-h-[60vh] flex-col items-center justify-center gap-3 rounded-2xl bg-white/60 p-8 text-center dark:bg-white/5"
            >
                <LayoutGrid class="h-10 w-10 text-primary-300" />
                <p class="text-lg font-semibold text-secondary dark:text-white">
                    The Executive Overview is for agency owners
                </p>
                <p class="max-w-md text-sm text-muted dark:text-gray-400">
                    It brings all of an agency's branches together. It appears
                    once your agency has more than one branch.
                </p>
                <NuxtLink
                    :to="`/app/branches/${uuid}/dashboard`"
                    class="mt-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white"
                >
                    Back to dashboard
                </NuxtLink>
            </div>

            <template v-else-if="canSeeExecutiveOverview">
                <!-- Totals across every branch -->
                <section
                    v-if="loading && !rows.length"
                    class="grid grid-cols-2 gap-2 xl:grid-cols-4"
                >
                    <div
                        v-for="n in 8"
                        :key="n"
                        class="h-24 animate-pulse rounded-xl bg-white/60 dark:bg-white/5"
                    />
                </section>

                <section
                    v-else
                    class="grid grid-cols-2 gap-2 xl:grid-cols-4"
                >
                    <OverviewStatCard
                        label="Branches"
                        :value="`${totals.activeBranches} / ${rows.length}`"
                        :icon="Building2"
                        tone="primary"
                        :chip="
                            totals.pendingBranches
                                ? `${totals.pendingBranches} awaiting approval`
                                : 'All branches active'
                        "
                        note="active / total"
                    />
                    <OverviewStatCard
                        label="Admitted Patients"
                        :value="totals.admitted"
                        :icon="Users"
                        tone="accent"
                        :chip="`+${totals.newThisMonth} this month`"
                        :note="
                            totals.waiting
                                ? `${totals.waiting} waiting for a bed`
                                : undefined
                        "
                    />
                    <OverviewStatCard
                        label="Bed Occupancy"
                        :value="percent(totals.occupied, totals.beds)"
                        :icon="BedDouble"
                        :tone="occupancyTone(totals.occupied, totals.beds)"
                        :chip="`${totals.occupied} of ${totals.beds} beds`"
                        :note="`${totals.rooms} rooms`"
                    />
                    <OverviewStatCard
                        label="Pending Bookings"
                        :value="totals.pendingBookings"
                        :icon="ClipboardList"
                        :tone="totals.pendingBookings ? 'amber' : 'primary'"
                        :chip="`${totals.todayBookings} today`"
                        :note="
                            totals.expiringSoon
                                ? `${totals.expiringSoon} expiring soon`
                                : undefined
                        "
                    />
                    <OverviewStatCard
                        label="Staff"
                        :value="totals.staff"
                        :icon="UserCog"
                        tone="primary"
                        :chip="`${totals.caregivers} caregivers`"
                        note="across all branches"
                    />
                    <OverviewStatCard
                        label="Homecare Visits"
                        :value="totals.visits"
                        :icon="House"
                        tone="accent"
                        chip="this week"
                        :note="`${totals.patientsOnRecord} patients on record`"
                    />
                    <OverviewStatCard
                        label="Billed This Month"
                        :value="billingValue(totals.billed)"
                        :icon="Wallet"
                        tone="primary"
                        :chip="
                            totals.billingBranches
                                ? `Collected ${money(totals.collected)}`
                                : undefined
                        "
                        :note="billingNote"
                    />
                    <OverviewStatCard
                        label="Outstanding Balance"
                        :value="billingValue(totals.outstanding)"
                        :icon="HandCoins"
                        :tone="totals.outstanding > 0 ? 'rose' : 'primary'"
                        :chip="
                            totals.billingBranches
                                ? totals.outstanding > 0
                                    ? 'To collect'
                                    : 'Nothing due'
                                : undefined
                        "
                        :note="billingNote"
                    />
                </section>

                <!-- Revenue by month, same look as the platform dashboard -->
                <section
                    class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5 dark:border-white/10 dark:bg-secondary"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-2.5">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent-100 to-accent-50 text-accent-600 dark:from-accent-500/20 dark:to-accent-500/10 dark:text-accent-300"
                            >
                                <ChartColumnBig class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <h2
                                    class="text-sm font-bold tracking-tight text-secondary sm:text-base dark:text-white"
                                >
                                    Revenue by Month
                                </h2>
                                <p
                                    class="text-[11px] text-muted sm:text-xs dark:text-gray-400"
                                >
                                    {{ revenueSubtitle }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <div
                                class="inline-flex shrink-0 rounded-full bg-slate-100 p-0.5 dark:bg-white/5"
                                role="tablist"
                                aria-label="Revenue range"
                            >
                                <button
                                    v-for="months in [6, 12]"
                                    :key="months"
                                    type="button"
                                    role="tab"
                                    :aria-selected="!revenueYear && revenueRange === months"
                                    class="rounded-full px-3 py-1 text-[11px] font-semibold transition-colors"
                                    :class="
                                        !revenueYear && revenueRange === months
                                            ? 'bg-white text-accent-600 shadow-sm dark:bg-white/10 dark:text-accent-300'
                                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white'
                                    "
                                    @click="setRevenueRange(months as 6 | 12)"
                                >
                                    {{ months }}M
                                </button>
                            </div>

                            <Combobox
                                :model-value="revenueYear"
                                :items="revenueYearOptions"
                                placeholder="Year"
                                input-class="px-2.5 py-1.5 text-[11px] font-semibold rounded-full"
                                class="w-[92px] shrink-0"
                                @update:model-value="setRevenueYear($event as number | null)"
                            />
                        </div>
                    </div>

                    <div
                        v-if="(loading && !rows.length) || revenueLoading"
                        class="mt-3 h-[240px] animate-pulse rounded-lg bg-accent-50/40 sm:h-[260px] dark:bg-white/5"
                    />

                    <div
                        v-else-if="!billingRows.length"
                        class="mt-3 flex h-[240px] items-center justify-center rounded-lg bg-accent-50/40 px-4 text-center sm:h-[260px] dark:bg-accent-500/10"
                    >
                        <p class="text-xs font-semibold text-secondary dark:text-white">
                            Your account has no billing access in these branches.
                        </p>
                    </div>

                    <div
                        v-else-if="!revenue.hasRevenue"
                        class="mt-3 flex h-[240px] items-center justify-center rounded-lg bg-accent-50/40 px-4 text-center sm:h-[260px] dark:bg-accent-500/10"
                    >
                        <p class="text-xs font-semibold text-secondary dark:text-white">
                            {{
                                revenueYear
                                    ? `No revenue recorded in ${revenueYear}`
                                    : `No revenue recorded in the last ${revenueRange} months`
                            }}
                        </p>
                    </div>

                    <div v-else class="mt-3">
                        <RevenueByMonthChart
                            :labels="revenue.labels"
                            :values="revenue.values"
                            :details="revenue.details"
                        />
                    </div>

                    <p
                        v-if="billingRows.length && revenue.hasRevenue"
                        class="mt-3 border-t border-gray-100 pt-3 text-xs text-gray-400 dark:border-white/10 dark:text-gray-500"
                    >
                        Total for this period
                        <b class="ml-1 text-sm font-bold text-secondary dark:text-white">{{
                            money(revenue.total)
                        }}</b>
                        <span v-if="billingRows.length < rows.length" class="ml-2">
                            · {{ billingRows.length }} of {{ rows.length }} branches (billing access)
                        </span>
                    </p>
                </section>

                <!-- Branch comparison -->
                <section
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div
                        class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-white/10"
                    >
                        <div>
                            <h2
                                class="text-sm font-bold text-secondary dark:text-white"
                            >
                                Branch comparison
                            </h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Open a branch to see its own dashboard.
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <label class="relative block">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    v-model="search"
                                    type="search"
                                    placeholder="Search branch"
                                    class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary sm:w-56 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                />
                            </label>

                            <Combobox
                                v-model="sortBy"
                                :items="SORT_OPTIONS"
                                placeholder="Sort"
                                input-class="px-3 py-2 text-sm"
                                class="w-full sm:w-60"
                            />
                        </div>
                    </div>

                    <!-- Table (tablet and up) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full min-w-[860px] text-sm">
                            <thead>
                                <tr
                                    class="bg-gray-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:bg-white/5 dark:text-gray-500"
                                >
                                    <th class="py-3 pl-5 pr-3">Branch</th>
                                    <th class="px-3 py-3 text-right">Admitted</th>
                                    <th class="px-3 py-3">Beds</th>
                                    <th class="px-3 py-3 text-right">Pending</th>
                                    <th class="px-3 py-3 text-right">Today</th>
                                    <th class="px-3 py-3 text-right">Staff</th>
                                    <th class="px-3 py-3 text-right">Billed</th>
                                    <th class="px-3 py-3 text-right">Outstanding</th>
                                    <th class="py-3 pl-3 pr-5"></th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-white/10"
                            >
                                <tr
                                    v-for="row in visibleRows"
                                    :key="row.uuid"
                                    class="cursor-pointer transition hover:bg-primary-50/40 dark:hover:bg-white/5"
                                    @click="openBranch(row)"
                                >
                                    <td class="py-3 pl-5 pr-3">
                                        <div class="flex items-center gap-3">
                                            <BranchAvatar :row="row" />
                                            <div class="min-w-0">
                                                <p
                                                    class="max-w-[16rem] truncate font-semibold text-gray-800 dark:text-white"
                                                >
                                                    {{ row.shortName }}
                                                </p>
                                                <BranchStatus :row="row" />
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200"
                                    >
                                        {{ metric(row, row.admitted) }}
                                        <span
                                            v-if="row.ok && row.waiting"
                                            class="block text-[11px] text-amber-600 dark:text-amber-300"
                                        >
                                            {{ row.waiting }} waiting
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div v-if="row.ok" class="w-32">
                                            <div
                                                class="mb-1 flex justify-between text-[11px] tabular-nums text-gray-500 dark:text-gray-400"
                                            >
                                                <span
                                                    >{{ row.occupied }}/{{
                                                        row.beds
                                                    }}</span
                                                >
                                                <span>{{
                                                    percent(
                                                        row.occupied,
                                                        row.beds,
                                                    )
                                                }}</span>
                                            </div>
                                            <div
                                                class="h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                                            >
                                                <div
                                                    class="h-full rounded-full"
                                                    :class="
                                                        barTone(
                                                            row.occupied,
                                                            row.beds,
                                                        )
                                                    "
                                                    :style="{
                                                        width: barWidth(
                                                            row.occupied,
                                                            row.beds,
                                                        ),
                                                    }"
                                                />
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right tabular-nums"
                                        :class="
                                            row.ok && row.bookings.pending
                                                ? 'font-semibold text-amber-600 dark:text-amber-300'
                                                : 'text-gray-700 dark:text-gray-200'
                                        "
                                    >
                                        {{ metric(row, row.bookings.pending) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200"
                                    >
                                        {{ metric(row, row.todayBookings) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200"
                                    >
                                        {{ metric(row, row.staff) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200"
                                    >
                                        {{
                                            row.billing
                                                ? money(row.billing.billed)
                                                : "—"
                                        }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right tabular-nums"
                                        :class="
                                            row.billing?.outstanding
                                                ? 'font-semibold text-rose-600 dark:text-rose-300'
                                                : 'text-gray-700 dark:text-gray-200'
                                        "
                                    >
                                        {{
                                            row.billing
                                                ? money(row.billing.outstanding)
                                                : "—"
                                        }}
                                    </td>
                                    <td class="py-3 pl-3 pr-5 text-right">
                                        <NuxtLink
                                            :to="branchLink(row)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-200"
                                            @click.stop
                                        >
                                            Open
                                            <ArrowRight class="h-3.5 w-3.5" />
                                        </NuxtLink>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Cards (phones) -->
                    <ul
                        class="divide-y divide-gray-100 md:hidden dark:divide-white/10"
                    >
                        <li v-for="row in visibleRows" :key="row.uuid">
                            <NuxtLink
                                :to="branchLink(row)"
                                class="block p-4 transition active:bg-gray-50 dark:active:bg-white/5"
                            >
                                <div class="flex items-start gap-3">
                                    <BranchAvatar :row="row" />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="font-semibold leading-snug text-gray-800 dark:text-white"
                                        >
                                            {{ row.shortName }}
                                        </p>
                                        <BranchStatus :row="row" />
                                    </div>
                                    <ArrowRight
                                        class="mt-1 h-4 w-4 shrink-0 text-gray-300"
                                    />
                                </div>

                                <dl
                                    v-if="row.ok"
                                    class="mt-3 grid grid-cols-3 gap-px overflow-hidden rounded-xl border border-gray-100 bg-gray-100 text-center dark:border-white/10 dark:bg-white/10"
                                >
                                    <div
                                        v-for="cell in mobileCells(row)"
                                        :key="cell.label"
                                        class="bg-gray-50 px-2 py-2.5 dark:bg-secondary"
                                    >
                                        <dt
                                            class="text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                        >
                                            {{ cell.label }}
                                        </dt>
                                        <dd
                                            class="mt-0.5 truncate text-sm font-semibold tabular-nums"
                                            :class="
                                                cell.tone ??
                                                'text-gray-800 dark:text-white'
                                            "
                                        >
                                            {{ cell.value }}
                                        </dd>
                                    </div>
                                </dl>
                            </NuxtLink>
                        </li>
                    </ul>

                    <p
                        v-if="!loading && !visibleRows.length"
                        class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500"
                    >
                        No branch matches "{{ search }}".
                    </p>

                    <p
                        v-if="rows.length && totals.billingBranches < totals.activeBranches"
                        class="border-t border-gray-100 px-4 py-3 text-[11px] text-gray-400 sm:px-5 dark:border-white/10 dark:text-gray-500"
                    >
                        Billing figures show "—" for branches where your account
                        has no billing access.
                    </p>
                </section>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    ArrowRight,
    BedDouble,
    ChartColumnBig,
    Building2,
    ClipboardList,
    HandCoins,
    House,
    LayoutGrid,
    RefreshCw,
    Search,
    UserCog,
    Users,
    Wallet,
} from "lucide-vue-next";
import OverviewStatCard from "~/components/sections/app/Dashboard/overview/OverviewStatCard.vue";
import RevenueByMonthChart from "~/components/sections/app/Dashboard/overview/RevenueByMonthChart.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { branchService } from "~/api/branch/BranchService";
import { invoiceService } from "~/api/invoice/InvoiceService";
import { useBranchStore } from "~/stores/branch";
import { useAgencyBranches } from "~/composables/useAgencyBranches";
import { formatCurrency } from "~/utils/currency";
import { getBranchImage, type Branch } from "~/types/branch";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Executive Overview" });

const route = useRoute();
const router = useRouter();
const branchStore = useBranchStore();
const { agency, overviewBranches, canSeeExecutiveOverview } =
    useAgencyBranches();

const uuid = computed(() => route.params.uuid as string);

type BookingKey = "pending" | "approved" | "expired" | "rejected";

interface Row {
    uuid: string;
    name: string;
    shortName: string;
    image: Branch["image"];
    verified: boolean;
    ok: boolean;
    admitted: number;
    waiting: number;
    newThisMonth: number;
    staff: number;
    caregivers: number;
    rooms: number;
    beds: number;
    occupied: number;
    available: number;
    maintenance: number;
    bookings: Record<BookingKey, number>;
    bookingsTotal: number;
    todayBookings: number;
    expiringSoon: number;
    visits: number;
    patientsOnRecord: number;
    billing: {
        billed: number;
        collected: number;
        outstanding: number;
        /** Billed per month this year (Jan–Dec). */
        monthly: number[];
    } | null;
}

const SORT_OPTIONS = [
    { value: "name", label: "Sort: Name" },
    { value: "admitted", label: "Sort: Most patients" },
    { value: "occupancy", label: "Sort: Highest occupancy" },
    { value: "pending", label: "Sort: Most pending bookings" },
    { value: "billed", label: "Sort: Highest billed" },
];

const rows = ref<Row[]>([]);
const loading = ref(false);
const updatedAt = ref("");
const search = ref("");
const sortBy = ref("name");

const now = new Date();
const periodLabel = now.toLocaleDateString("en-PH", {
    month: "long",
    year: "numeric",
});

// Branch names usually repeat the agency name ("AMUMA Incorporation - Davao Branch").
function shortName(name: string) {
    const prefix = agency.value?.name;
    if (!prefix) return name;

    const trimmed = name.replace(
        new RegExp(`^${prefix.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")}\\s*[-–—:]\\s*`, "i"),
        "",
    );

    return trimmed || name;
}

const num = (value: unknown) => Number(value) || 0;

function emptyRow(branch: Branch): Row {
    return {
        uuid: branch.uuid as string,
        name: branch.name,
        shortName: shortName(branch.name),
        image: branch.image,
        verified:
            branch.status === "verified" &&
            branch.agency?.status === "verified",
        ok: false,
        admitted: 0,
        waiting: 0,
        newThisMonth: 0,
        staff: 0,
        caregivers: 0,
        rooms: 0,
        beds: 0,
        occupied: 0,
        available: 0,
        maintenance: 0,
        bookings: { pending: 0, approved: 0, expired: 0, rejected: 0 },
        bookingsTotal: 0,
        todayBookings: 0,
        expiringSoon: 0,
        visits: 0,
        patientsOnRecord: 0,
        billing: null,
    };
}

async function loadBranch(branch: Branch): Promise<Row> {
    const row = emptyRow(branch);
    if (!row.verified) return row;

    const [dashRes, billRes] = await Promise.allSettled([
        branchService.dashboard({ branch_uuid: row.uuid }),
        invoiceService.overview({
            branch_uuid: row.uuid,
            period: "month",
            month: now.getMonth() + 1,
            year: now.getFullYear(),
        }),
    ]);

    if (dashRes.status === "fulfilled") {
        const data: any = (dashRes.value as any)?.data ?? dashRes.value;
        const occupied = num(data?.occupancy?.occupied?.value);
        const available = num(data?.occupancy?.available?.value);
        const maintenance = num(data?.occupancy?.maintenance?.value);
        const b = data?.bookings ?? {};

        Object.assign(row, {
            ok: true,
            admitted: num(data?.patients?.admitted),
            waiting: num(data?.patients?.waiting),
            newThisMonth: num(data?.patients?.new_this_month),
            staff: num(data?.staff?.total),
            caregivers: num(data?.contracts?.caregivers),
            rooms: num(data?.occupancy?.total_rooms?.value),
            beds: occupied + available + maintenance,
            occupied,
            available,
            maintenance,
            bookings: {
                pending: num(b.pending_confirmation),
                approved: num(b.approved) + num(b.accepted),
                expired: num(b.expired),
                rejected: num(b.rejected),
            },
            todayBookings: num(b.today),
            expiringSoon: num(b.expiring_soon),
            visits: num(data?.contracts?.scheduled_visits),
            patientsOnRecord: num(data?.contracts?.total_patients),
        });

        row.bookingsTotal = Object.values(row.bookings).reduce(
            (sum, value) => sum + value,
            0,
        );
    }

    if (billRes.status === "fulfilled") {
        const data: any = (billRes.value as any)?.data ?? billRes.value;

        row.billing = {
            billed: num(data?.total_revenue?.value),
            collected: num(data?.payments_received?.value),
            outstanding: num(data?.outstanding_balance?.value),
            monthly: Array.isArray(data?.chart?.revenue)
                ? data.chart.revenue.map(num)
                : [],
        };
    }

    return row;
}

// A few branches at a time, so a large agency doesn't flood the server.
async function load() {
    if (!canSeeExecutiveOverview.value) return;

    loading.value = true;

    const branches = overviewBranches.value;
    const results: Row[] = [];

    for (let i = 0; i < branches.length; i += 4) {
        results.push(
            ...(await Promise.all(branches.slice(i, i + 4).map(loadBranch))),
        );
    }

    rows.value = results;
    updatedAt.value = new Date().toLocaleTimeString("en-PH", {
        hour: "numeric",
        minute: "2-digit",
    });
    loading.value = false;
}

const totals = computed(() => {
    const live = rows.value.filter((row) => row.ok);
    const billed = rows.value.filter((row) => row.billing);
    const sum = (list: Row[], pick: (row: Row) => number) =>
        list.reduce((total, row) => total + pick(row), 0);

    return {
        activeBranches: rows.value.filter((row) => row.verified).length,
        pendingBranches: rows.value.filter((row) => !row.verified).length,
        admitted: sum(live, (r) => r.admitted),
        waiting: sum(live, (r) => r.waiting),
        newThisMonth: sum(live, (r) => r.newThisMonth),
        staff: sum(live, (r) => r.staff),
        caregivers: sum(live, (r) => r.caregivers),
        rooms: sum(live, (r) => r.rooms),
        beds: sum(live, (r) => r.beds),
        occupied: sum(live, (r) => r.occupied),
        pendingBookings: sum(live, (r) => r.bookings.pending),
        todayBookings: sum(live, (r) => r.todayBookings),
        expiringSoon: sum(live, (r) => r.expiringSoon),
        visits: sum(live, (r) => r.visits),
        patientsOnRecord: sum(live, (r) => r.patientsOnRecord),
        billingBranches: billed.length,
        billed: sum(billed, (r) => r.billing!.billed),
        collected: sum(billed, (r) => r.billing!.collected),
        outstanding: sum(billed, (r) => r.billing!.outstanding),
    };
});

const billingNote = computed(() => {
    const { billingBranches, activeBranches } = totals.value;

    if (!billingBranches) return "No billing access";
    if (billingBranches < activeBranches) {
        return `${billingBranches} of ${activeBranches} branches`;
    }

    return periodLabel;
});


// ---- Revenue by month: agency total, last 6/12 months or a whole year ----
const revenueRange = ref<6 | 12>(6);
const revenueYear = ref<number | null>(null);
const revenueLoading = ref(false);
/** year -> branch uuid -> billed per month (Jan–Dec). */
const revenueCache = ref<Record<number, Record<string, number[]>>>({});

const billingRows = computed(() => rows.value.filter((row) => row.billing));

const revenueYearOptions = computed(() =>
    [0, 1, 2].map((back) => {
        const year = now.getFullYear() - back;
        return { label: String(year), value: year };
    }),
);

// The months the chart shows, oldest first.
const revenueMonths = computed(() => {
    if (revenueYear.value) {
        return Array.from({ length: 12 }, (_, m) => ({ year: revenueYear.value!, month: m }));
    }

    return Array.from({ length: revenueRange.value }, (_, i) => {
        const d = new Date(now.getFullYear(), now.getMonth() - (revenueRange.value - 1 - i), 1);
        return { year: d.getFullYear(), month: d.getMonth() };
    });
});

async function ensureRevenueYears() {
    const years = [...new Set(revenueMonths.value.map((m) => m.year))].filter(
        (year) => !revenueCache.value[year],
    );
    if (!years.length || !billingRows.value.length) return;

    revenueLoading.value = true;

    try {
        for (const year of years) {
            const byBranch: Record<string, number[]> = {};
            const list = billingRows.value;

            for (let i = 0; i < list.length; i += 4) {
                await Promise.all(
                    list.slice(i, i + 4).map(async (row) => {
                        try {
                            const res: any = await invoiceService.overview({
                                branch_uuid: row.uuid,
                                period: "month",
                                month: 1,
                                year,
                            });
                            const data = res?.data ?? res;
                            byBranch[row.uuid] = Array.isArray(data?.chart?.revenue)
                                ? data.chart.revenue.map(num)
                                : [];
                        } catch {
                            byBranch[row.uuid] = [];
                        }
                    }),
                );
            }

            revenueCache.value = { ...revenueCache.value, [year]: byBranch };
        }
    } finally {
        revenueLoading.value = false;
    }
}

// This year's figures already came with the branch data.
watch(rows, (list) => {
    const year = now.getFullYear();
    const byBranch: Record<string, number[]> = {};
    list.forEach((row) => {
        if (row.billing) byBranch[row.uuid] = row.billing.monthly;
    });
    revenueCache.value = { [year]: byBranch };
    ensureRevenueYears();
});

watch(revenueMonths, ensureRevenueYears);

function setRevenueRange(months: 6 | 12) {
    revenueYear.value = null;
    revenueRange.value = months;
}

function setRevenueYear(year: number | null) {
    revenueYear.value = year;
}

const revenue = computed(() => {
    const months = revenueMonths.value;
    const branchValue = (uuid: string, m: { year: number; month: number }) =>
        revenueCache.value[m.year]?.[uuid]?.[m.month] ?? 0;

    const values = months.map((m) =>
        billingRows.value.reduce((sum, row) => sum + branchValue(row.uuid, m), 0),
    );

    return {
        labels: months.map((m) =>
            new Date(m.year, m.month, 1).toLocaleDateString("en-US", {
                month: "short",
                year: "numeric",
            }),
        ),
        values,
        total: values.reduce((sum, v) => sum + v, 0),
        hasRevenue: values.some((v) => v > 0),
        // Each branch's share in the tooltip, biggest first.
        details: (i: number) => {
            const m = months[i];
            if (!m || billingRows.value.length < 2) return [];

            return billingRows.value
                .map((row) => ({ name: row.shortName, value: branchValue(row.uuid, m) }))
                .filter((b) => b.value > 0)
                .sort((a, b) => b.value - a.value)
                .map((b) => `${b.name}: ${money(b.value)}`);
        },
    };
});

const revenueSubtitle = computed(() =>
    revenueYear.value
        ? `Jan–Dec ${revenueYear.value}, billed across all branches`
        : `Last ${revenueRange.value} months, billed across all branches`,
);

const visibleRows = computed(() => {
    const term = search.value.trim().toLowerCase();
    const list = term
        ? rows.value.filter((row) => row.name.toLowerCase().includes(term))
        : [...rows.value];

    const ratio = (row: Row) => (row.beds ? row.occupied / row.beds : -1);

    const sorters: Record<string, (a: Row, b: Row) => number> = {
        name: (a, b) => a.shortName.localeCompare(b.shortName),
        admitted: (a, b) => b.admitted - a.admitted,
        occupancy: (a, b) => ratio(b) - ratio(a),
        pending: (a, b) => b.bookings.pending - a.bookings.pending,
        billed: (a, b) =>
            (b.billing?.billed ?? -1) - (a.billing?.billed ?? -1),
    };

    return list.sort(sorters[sortBy.value] ?? sorters.name);
});

function percent(part: number, whole: number) {
    if (!whole) return "—";
    return `${Math.round((part / whole) * 100)}%`;
}

function barWidth(part: number, whole: number) {
    if (!whole) return "0%";
    return `${Math.min(100, (part / whole) * 100)}%`;
}

function occupancyTone(part: number, whole: number) {
    const ratio = whole ? part / whole : 0;
    if (ratio >= 0.9) return "rose" as const;
    if (ratio >= 0.7) return "amber" as const;
    return "accent" as const;
}

function barTone(part: number, whole: number) {
    const tone = occupancyTone(part, whole);
    if (tone === "rose") return "bg-rose-500";
    if (tone === "amber") return "bg-amber-500";
    return "bg-gradient-to-r from-primary-400 to-primary";
}

function money(value: number) {
    return formatCurrency(value, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
}

function billingValue(value: number) {
    return totals.value.billingBranches ? money(value) : "—";
}

function metric(row: Row, value: number) {
    return row.ok ? value : "—";
}

function mobileCells(row: Row) {
    return [
        { label: "Admitted", value: row.admitted },
        { label: "Beds", value: `${row.occupied}/${row.beds}` },
        {
            label: "Pending",
            value: row.bookings.pending,
            tone: row.bookings.pending
                ? "text-amber-600 dark:text-amber-300"
                : undefined,
        },
        { label: "Today", value: row.todayBookings },
        { label: "Staff", value: row.staff },
        {
            label: "Billed",
            value: row.billing ? money(row.billing.billed) : "—",
        },
    ];
}

const branchLink = (row: Row) => `/app/branches/${row.uuid}/dashboard`;

function openBranch(row: Row) {
    router.push(branchLink(row));
}

// Small presentational pieces used in both the table and the cards.
const BranchAvatar = defineComponent({
    props: { row: { type: Object as () => Row, required: true } },
    setup(props) {
        const broken = ref(false);

        return () =>
            h(
                "span",
                {
                    class: "flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary-50 text-primary ring-1 ring-primary-100 dark:bg-white/10 dark:text-primary-300 dark:ring-white/10",
                },
                props.row.image && !broken.value
                    ? h("img", {
                          src: getBranchImage(props.row.image),
                          alt: props.row.name,
                          class: "h-full w-full object-cover",
                          onError: () => (broken.value = true),
                      })
                    : h(Building2, { class: "h-4 w-4" }),
            );
    },
});

const BranchStatus = defineComponent({
    props: { row: { type: Object as () => Row, required: true } },
    setup(props) {
        return () =>
            h(
                "span",
                {
                    class: [
                        "mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium",
                        props.row.verified
                            ? "text-emerald-600 dark:text-emerald-300"
                            : "text-amber-600 dark:text-amber-300",
                    ],
                },
                [
                    h("span", {
                        class: [
                            "h-1.5 w-1.5 rounded-full",
                            props.row.verified
                                ? "bg-emerald-500"
                                : "bg-amber-500",
                        ],
                    }),
                    props.row.verified ? "Active" : "Awaiting approval",
                ],
            );
    },
});

// Loads once the owner's branches are known, and again for another agency.
watch(
    () => [canSeeExecutiveOverview.value, agency.value?.agency_id],
    ([canSee]) => {
        if (canSee) load();
    },
    { immediate: true },
);
</script>
