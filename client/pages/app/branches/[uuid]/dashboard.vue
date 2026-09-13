<template>
    <div
        class="min-h-screen-header bg-light flex items-center justify-center p-4 sm:p-6 lg:p-8 dark:bg-surface"
        v-if="isSubscriptionPending"
    >
        <SubscriptionReview />
    </div>

    <div v-else class="min-h-screen-header">
        <div class="px-4 py-3 sm:px-5 lg:px-6 lg:py-4">
            <div v-if="loading" class="space-y-3">
                <div
                    class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <div
                        v-for="n in 4"
                        :key="n"
                        class="h-24 animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                    />
                </div>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                    <div
                        class="h-[380px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                    />
                    <div
                        class="h-[380px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                    />
                    <div
                        class="h-[380px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                    />
                </div>

                <div
                    class="h-[260px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                />
            </div>

            <div v-else class="space-y-3">
                <section
                    class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <div
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-50 via-white to-white dark:from-primary-500/10 dark:via-secondary dark:to-secondary p-3 shadow-sm dark:border dark:border-white/10 ring-1 ring-primary-100/60 dark:ring-primary-500/20 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div
                            class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-primary-200/30 dark:bg-primary-500/10 blur-2xl"
                        />

                        <div
                            class="relative flex items-start justify-between gap-2"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wider text-primary-600/80 dark:text-primary-300/80"
                                >
                                    Patients Admitted
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{ dashboard.patients.admitted }}
                                </p>
                            </div>

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary-700 text-white shadow-md shadow-primary-500/30"
                            >
                                <Users class="h-3.5 w-3.5" />
                            </div>
                        </div>

                        <div class="relative mt-2 flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-accent-500/10 px-2 py-0.5 text-[10px] font-semibold text-accent-700 dark:text-accent-300"
                            >
                                +{{ dashboard.patients.new_this_month }} this
                                month
                            </span>
                            <span
                                v-if="dashboard.patients.waiting"
                                class="text-[10px] font-medium text-amber-600 dark:text-amber-300"
                            >
                                {{ dashboard.patients.waiting }} waiting
                            </span>
                        </div>
                    </div>

                    <div
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-50 via-white to-white dark:from-primary-500/10 dark:via-secondary dark:to-secondary p-3 shadow-sm dark:border dark:border-white/10 ring-1 ring-primary-100/60 dark:ring-primary-500/20 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div
                            class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-primary-200/30 dark:bg-primary-500/10 blur-2xl"
                        />

                        <div
                            class="relative flex items-start justify-between gap-2"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wider text-primary-600/80 dark:text-primary-300/80"
                                >
                                    Active Homecare
                                </p>

                                <p
                                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{ dashboard.contracts.active_patient }}
                                </p>
                            </div>
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary-700 text-white shadow-md shadow-primary-500/30"
                            >
                                <Home class="h-4 w-4" />
                            </div>
                        </div>

                        <div class="relative mt-2">
                            <span
                                class="rounded-full bg-primary-500/10 px-2 py-0.5 text-[10px] font-semibold text-primary-700 dark:text-primary-300"
                            >
                                Pending or ongoing visits
                            </span>
                        </div>
                    </div>

                    <div
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-accent-50 via-white to-white dark:from-accent-500/10 dark:via-secondary dark:to-secondary p-3 shadow-sm dark:border dark:border-white/10 ring-1 ring-accent-100/60 dark:ring-accent-500/20 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div
                            class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-accent-200/30 dark:bg-accent-500/10 blur-2xl"
                        />

                        <div
                            class="relative flex items-start justify-between gap-2"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wider text-accent-600/80 dark:text-accent-300/80"
                                >
                                    Bed Occupancy
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{ occupancyPct }}%
                                </p>
                            </div>
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent to-accent-700 text-white shadow-md shadow-accent-500/30"
                            >
                                <BedDouble class="h-4 w-4" />
                            </div>
                        </div>

                        <div class="relative mt-2">
                            <span
                                class="rounded-full bg-accent-500/10 px-2 py-0.5 text-[10px] font-semibold text-accent-700 dark:text-accent-300"
                            >
                                {{ dashboard.occupancy.occupied.value }}
                                occupied
                            </span>
                            <span
                                class="ml-1.5 text-[10px] text-slate-400 dark:text-gray-500"
                            >
                                of
                                {{
                                    dashboard.occupancy.occupied.value +
                                    dashboard.occupancy.available.value
                                }}
                                beds
                            </span>
                        </div>
                    </div>

                    <div
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 via-white to-white dark:from-amber-500/10 dark:via-secondary dark:to-secondary p-3 shadow-sm dark:border dark:border-white/10 ring-1 ring-amber-100/60 dark:ring-amber-500/20 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div
                            class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-amber-200/30 dark:bg-amber-500/10 blur-2xl"
                        />

                        <div
                            class="relative flex items-start justify-between gap-2"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wider text-amber-600/80 dark:text-amber-300/80"
                                >
                                    Pending Bookings
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{
                                        dashboard.bookings.pending_confirmation
                                    }}
                                </p>
                            </div>

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md shadow-amber-500/30"
                            >
                                <ClipboardList class="h-3.5 w-3.5" />
                            </div>
                        </div>

                        <NuxtLink
                            v-if="dashboard.bookings.pending_confirmation"
                            :to="`/app/branches/${uuid}/bookings`"
                            class="group/link relative mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-200"
                        >
                            Review bookings
                            <span
                                class="transition-transform group-hover/link:translate-x-0.5"
                                >→</span
                            >
                        </NuxtLink>

                        <p
                            v-else
                            class="relative mt-2 text-[10px] text-slate-400 dark:text-gray-500"
                        >
                            All caught up
                        </p>
                    </div>
                </section>

                <section
                    class="grid grid-cols-1 gap-3 lg:grid-cols-3 lg:items-stretch"
                >
                    <div
                        class="rounded-xl bg-white dark:bg-white/5 p-3 shadow-sm border border-slate-200/70 dark:border-white/10 ring-1 ring-slate-100/60 dark:ring-white/10 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent-100 to-accent-50 dark:from-accent-500/20 dark:to-accent-500/10 text-accent-600 dark:text-accent-300"
                            >
                                <BedDouble class="h-4 w-4" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                >
                                    Occupancy Breakdown
                                </h2>
                                <p
                                    class="text-[11px] text-muted dark:text-gray-400"
                                >
                                    Beds by current status
                                </p>
                            </div>
                        </div>

                        <div v-if="hasBeds" class="relative mt-2 h-[180px]">
                            <canvas
                                id="occupancyChart"
                                class="!h-full !w-full"
                                role="img"
                                aria-label="Occupancy breakdown chart"
                            />

                            <div
                                class="pointer-events-none absolute inset-0 flex items-center justify-center"
                            >
                                <div class="text-center">
                                    <p
                                        class="text-xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                    >
                                        {{ occupancyPct }}%
                                    </p>
                                    <p
                                        class="text-[8px] font-bold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                                    >
                                        Occupied
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-2 flex h-[180px] items-center justify-center rounded-lg bg-accent-50/40 dark:bg-accent-500/10"
                        >
                            <p
                                class="text-xs font-semibold text-secondary dark:text-white"
                            >
                                No beds configured yet
                            </p>
                        </div>

                        <div class="mt-2 space-y-0.5">
                            <div
                                v-for="segment in occupancySegments"
                                :key="segment.key"
                                class="flex items-center justify-between rounded-md px-1.5 py-1 transition-colors hover:bg-light dark:hover:bg-white/5"
                            >
                                <div class="flex min-w-0 items-center gap-1.5">
                                    <span
                                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                                        :style="{
                                            background: segment.color,
                                            boxShadow: `0 0 0 3px ${segment.color}22`,
                                        }"
                                    />
                                    <p
                                        class="text-[11px] font-semibold text-secondary dark:text-white"
                                    >
                                        {{ segment.label }}
                                    </p>
                                </div>

                                <p
                                    class="text-[11px] font-bold tabular-nums text-secondary dark:text-white"
                                >
                                    {{ segment.count }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl bg-white dark:bg-white/5 p-3 shadow-sm border border-slate-200/70 dark:border-white/10 ring-1 ring-slate-100/60 dark:ring-white/10 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-100 to-amber-50 dark:from-amber-500/20 dark:to-amber-500/10 text-amber-600 dark:text-amber-300"
                                >
                                    <ClipboardList class="h-4 w-4" />
                                </div>
                                <div>
                                    <h2
                                        class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                    >
                                        Booking Status
                                    </h2>
                                    <p
                                        class="text-[11px] text-muted dark:text-gray-400"
                                    >
                                        This branch's bookings
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.1em] text-amber-700 dark:text-amber-300"
                            >
                                <span
                                    class="h-1 w-1 animate-pulse rounded-full bg-amber-500"
                                />
                                Live
                            </span>
                        </div>

                        <div
                            v-if="totalBookings"
                            class="relative mt-2 h-[180px]"
                        >
                            <canvas
                                id="bookingChart"
                                class="!h-full !w-full"
                                role="img"
                                aria-label="Booking status chart"
                            />

                            <div
                                class="pointer-events-none absolute inset-0 flex items-center justify-center"
                            >
                                <div class="text-center">
                                    <p
                                        class="text-xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                    >
                                        {{ totalBookings }}
                                    </p>
                                    <p
                                        class="text-[8px] font-bold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                                    >
                                        Total
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-2 flex h-[180px] items-center justify-center rounded-lg bg-amber-50/40 dark:bg-amber-500/10"
                        >
                            <p
                                class="text-xs font-semibold text-secondary dark:text-white"
                            >
                                No bookings yet
                            </p>
                        </div>

                        <div class="mt-2 space-y-0.5">
                            <div
                                v-for="segment in bookingSegments"
                                :key="segment.key"
                                class="flex items-center justify-between rounded-md px-1.5 py-1 transition-colors hover:bg-light dark:hover:bg-white/5"
                            >
                                <div class="flex min-w-0 items-center gap-1.5">
                                    <span
                                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                                        :style="{
                                            background: segment.color,
                                            boxShadow: `0 0 0 3px ${segment.color}22`,
                                        }"
                                    />
                                    <p
                                        class="text-[11px] font-semibold text-secondary dark:text-white"
                                    >
                                        {{ segment.label }}
                                    </p>
                                </div>

                                <p
                                    class="text-[11px] font-bold tabular-nums text-secondary dark:text-white"
                                >
                                    {{ segment.count }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="relative flex w-full flex-col overflow-hidden rounded-xl bg-gradient-to-br from-primary-50 via-white to-white dark:from-primary-500/10 dark:via-secondary dark:to-secondary p-4 shadow-sm dark:border dark:border-white/10 ring-1 ring-primary-100/60 dark:ring-primary-500/20 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div
                            class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-primary-200/30 dark:bg-primary-500/10 blur-2xl"
                        />

                        <div class="relative flex items-center gap-2">
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary-700 text-white"
                            >
                                <FileText class="h-4 w-4" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                >
                                    Contracts
                                </h2>
                                <p
                                    class="text-[11px] text-muted dark:text-gray-400"
                                >
                                    Plans and staffing
                                </p>
                            </div>
                        </div>

                        <div class="relative mt-3 flex items-end gap-1.5">
                            <p
                                class="text-3xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                            >
                                {{ dashboard.contracts.total_active_plans }}
                            </p>
                            <p
                                class="mb-1 text-[11px] font-medium text-muted dark:text-gray-400"
                            >
                                active plans
                            </p>
                        </div>

                        <div
                            class="relative my-3 h-px w-full bg-gradient-to-r from-transparent via-slate-200 dark:via-white/10 to-transparent"
                        />

                        <div class="relative space-y-2">
                            <div
                                v-for="group in groupedPlans"
                                :key="`${group.category}-${group.accommodation_type}`"
                                class="rounded-md px-1.5 py-1 transition-colors hover:bg-light dark:hover:bg-white/5"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-white"
                                        :class="
                                            group.category === 'Homecare'
                                                ? 'bg-gradient-to-br from-amber-400 to-amber-600'
                                                : 'bg-gradient-to-br from-accent to-accent-700'
                                        "
                                    >
                                        <Home
                                            v-if="group.category === 'Homecare'"
                                            class="h-3.5 w-3.5"
                                        />
                                        <UserCog v-else class="h-3.5 w-3.5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-[11px] font-semibold text-secondary dark:text-white"
                                        >
                                            {{
                                                accommodationTypeLabel(
                                                    group.accommodation_type,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="truncate text-[10px] text-muted dark:text-gray-400"
                                        >
                                            {{ group.category }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-1 space-y-0.5 pl-9">
                                    <div
                                        v-for="cycle in group.cycles"
                                        :key="cycle.branch_contract_id"
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <p
                                            class="text-[10px] capitalize text-muted dark:text-gray-400"
                                        >
                                            {{
                                                cycle.billing_cycle.toLowerCase()
                                            }}
                                        </p>

                                        <p
                                            class="shrink-0 text-xs font-bold tabular-nums text-secondary dark:text-white"
                                        >
                                            {{ formatCurrency(cycle.price) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <p
                                v-if="!groupedPlans.length"
                                class="px-1.5 py-2 text-[11px] text-muted dark:text-gray-400"
                            >
                                No active plans yet.
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-xl bg-white dark:bg-white/5 shadow-sm border border-slate-200/70 dark:border-white/10 ring-1 ring-slate-100/60 dark:ring-white/10 transition-all hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div
                        class="flex flex-col gap-2 border-b border-slate-100 dark:border-white/10 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-secondary to-slate-700 text-white"
                            >
                                <Clock class="h-4 w-4" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                >
                                    Recent Activity
                                </h2>
                                <p
                                    class="text-[11px] text-muted dark:text-gray-400"
                                >
                                    Latest booking activity
                                </p>
                            </div>
                        </div>

                        <NuxtLink
                            :to="`/app/branches/${uuid}/bookings`"
                            class="group inline-flex w-fit items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-primary-600 transition-colors hover:bg-primary-50 hover:text-primary-700 dark:text-primary-300 dark:hover:bg-white/10 dark:hover:text-primary-200"
                        >
                            View all
                            <span
                                class="transition-transform group-hover:translate-x-0.5"
                                >→</span
                            >
                        </NuxtLink>
                    </div>

                    <div
                        v-if="dashboard.recent_activity.length === 0"
                        class="flex flex-col items-center justify-center px-9 py-8 text-center"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10 text-primary-400 dark:text-primary-300"
                        >
                            <ClipboardList class="h-4 w-4" />
                        </div>

                        <p
                            class="mt-2 text-xs font-semibold text-secondary dark:text-white"
                        >
                            No recent bookings
                        </p>
                        <p
                            class="mt-0.5 text-[11px] text-muted dark:text-gray-400"
                        >
                            New bookings for this branch will appear here.
                        </p>
                    </div>

                    <div
                        v-else
                        class="divide-y divide-slate-100 dark:divide-white/10"
                    >
                        <div
                            v-for="(item, index) in dashboard.recent_activity"
                            :key="index"
                            class="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-primary-50/40 dark:hover:bg-white/5"
                        >
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                <ClipboardList class="h-3.5 w-3.5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs font-semibold text-secondary dark:text-white"
                                >
                                    {{ item.title }}
                                </p>
                                <p
                                    class="truncate text-[11px] text-muted dark:text-gray-400"
                                >
                                    {{ item.subtitle }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <StatusBadge :status="item.status" />
                                <p
                                    class="mt-1 text-[10px] text-muted dark:text-gray-400"
                                >
                                    {{ formatDate(item.date) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import {
    BedDouble,
    ClipboardList,
    Clock,
    FileText,
    Home,
    UserCog,
    Users,
} from "lucide-vue-next";
import {
    ArcElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from "chart.js";

import SubscriptionReview from "~/components/sections/app/Dashboard/SubscriptionReview.vue";
import StatusBadge from "~/components/ui/StatusBadge.vue";
import { branchService } from "~/api/branch/BranchService";
import { useBranchStore } from "~/stores/branch";
import { formatCurrency } from "~/utils/currency";

Chart.register(
    CategoryScale,
    LinearScale,
    DoughnutController,
    ArcElement,
    Title,
    Tooltip,
    Legend,
);

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Dashboard",
});

const route = useRoute();
const uuid = route.params.uuid as string;

const branchStore = useBranchStore();

const activeBranch = computed(() => branchStore.activeBranch);

const isSubscriptionPending = computed(() => {
    const branch = activeBranch.value;
    return !branch?.agency?.is_verified || !branch?.is_verified;
});

interface StatBucket {
    value: number;
    secondary: string;
    trend: string;
}

interface DashboardOverview {
    patients: {
        admitted: number;
        waiting: number;
        new_this_month: number;
    };
    staff: {
        total: number;
    };
    occupancy: {
        total_rooms: StatBucket;
        available: StatBucket;
        occupied: StatBucket;
        maintenance: StatBucket;
    };
    bookings: {
        pending_confirmation: number;
        approved: number;
        expired: number;
        cancelled: number;
        rejected: number;
        expiring_soon: number;
        today: number;
        recent: any[];
    };
    contracts: {
        total_active_plans: number;
        plans: {
            branch_contract_id: number;
            category: string;
            accommodation_type: string;
            billing_cycle: string;
            price: number;
        }[];
        patient_with_plan: number;
        new_monthy_patients: number;
        patient_retention: string;
        active_patient: number;
        caregivers: number;
        scheduled_visits: number;
        homecare_retention: string;
    };
    recent_activity: {
        type: "booking";
        title: string;
        subtitle: string;
        status: string;
        date: string;
    }[];
}

const emptyStatBucket = (): StatBucket => ({
    value: 0,
    secondary: "",
    trend: "up",
});

const emptyContracts = () => ({
    total_active_plans: 0,
    plans: [] as DashboardOverview["contracts"]["plans"],
    patient_with_plan: 0,
    new_monthy_patients: 0,
    patient_retention: "—",
    active_patient: 0,
    caregivers: 0,
    scheduled_visits: 0,
    homecare_retention: "—",
});

const dashboard = ref<DashboardOverview>({
    patients: { admitted: 0, waiting: 0, new_this_month: 0 },
    staff: { total: 0 },
    occupancy: {
        total_rooms: emptyStatBucket(),
        available: emptyStatBucket(),
        occupied: emptyStatBucket(),
        maintenance: emptyStatBucket(),
    },
    bookings: {
        pending_confirmation: 0,
        approved: 0,
        expired: 0,
        cancelled: 0,
        rejected: 0,
        expiring_soon: 0,
        today: 0,
        recent: [],
    },
    contracts: emptyContracts(),
    recent_activity: [],
});

const loading = ref(true);

let occupancyChart: Chart | null = null;
let bookingChart: Chart | null = null;

const hasBeds = computed(
    () =>
        dashboard.value.occupancy.occupied.value +
            dashboard.value.occupancy.available.value >
        0,
);

function accommodationTypeLabel(type: string) {
    return type === "ADL" ? "Activity of Daily Living (ADL)" : type;
}

const groupedPlans = computed(() => {
    const groups = new Map<
        string,
        {
            accommodation_type: string;
            category: string;
            cycles: { branch_contract_id: number; billing_cycle: string; price: number }[];
        }
    >();

    for (const plan of dashboard.value.contracts.plans) {
        const key = `${plan.category}-${plan.accommodation_type}`;
        const group = groups.get(key) ?? {
            accommodation_type: plan.accommodation_type,
            category: plan.category,
            cycles: [],
        };

        group.cycles.push({
            branch_contract_id: plan.branch_contract_id,
            billing_cycle: plan.billing_cycle,
            price: plan.price,
        });

        groups.set(key, group);
    }

    return Array.from(groups.values());
});

const occupancyPct = computed(() => {
    const occupied = dashboard.value.occupancy.occupied.value;
    const total = occupied + dashboard.value.occupancy.available.value;

    if (!total) return 0;

    return Math.round((occupied / total) * 100);
});

const occupancySegments = computed(() => [
    {
        key: "occupied",
        label: "Occupied",
        count: dashboard.value.occupancy.occupied.value,
        color: "#0E7C7B",
    },
    {
        key: "available",
        label: "Available",
        count: dashboard.value.occupancy.available.value,
        color: "#3182ED",
    },
    {
        key: "maintenance",
        label: "Maintenance",
        count: dashboard.value.occupancy.maintenance.value,
        color: "#f87171",
    },
]);

const totalBookings = computed(() => {
    const b = dashboard.value.bookings;
    return (
        b.pending_confirmation +
        b.approved +
        b.expired +
        b.rejected
    );
});

const bookingSegments = computed(() => [
    {
        key: "pending",
        label: "Pending",
        count: dashboard.value.bookings.pending_confirmation,
        color: "#d97706",
    },
    {
        key: "approved",
        label: "Approved",
        count: dashboard.value.bookings.approved,
        color: "#3182ED",
    },
    {
        key: "expired",
        label: "Expired",
        count: dashboard.value.bookings.expired,
        color: "#0E7C7B",
    },
    {
        key: "rejected",
        label: "Rejected",
        count: dashboard.value.bookings.rejected,
        color: "#f87171",
    },
]);

const fetchDashboard = async () => {
    loading.value = true;

    try {
        const res: any = await branchService.dashboard({ branch_uuid: uuid });
        const data = res.data ?? res;

        dashboard.value = {
            patients: {
                admitted: Number(data.patients?.admitted) || 0,
                waiting: Number(data.patients?.waiting) || 0,
                new_this_month: Number(data.patients?.new_this_month) || 0,
            },
            staff: { total: Number(data.staff?.total) || 0 },
            occupancy: {
                total_rooms: data.occupancy?.total_rooms ?? emptyStatBucket(),
                available: data.occupancy?.available ?? emptyStatBucket(),
                occupied: data.occupancy?.occupied ?? emptyStatBucket(),
                maintenance: data.occupancy?.maintenance ?? emptyStatBucket(),
            },
            bookings: {
                pending_confirmation:
                    Number(data.bookings?.pending_confirmation) || 0,
                approved: Number(data.bookings?.approved) || 0,
                expired: Number(data.bookings?.expired) || 0,
                cancelled: Number(data.bookings?.cancelled) || 0,
                rejected: Number(data.bookings?.rejected) || 0,
                expiring_soon: Number(data.bookings?.expiring_soon) || 0,
                today: Number(data.bookings?.today) || 0,
                recent: Array.isArray(data.bookings?.recent)
                    ? data.bookings.recent
                    : [],
            },
            contracts: {
                total_active_plans:
                    Number(data.contracts?.total_active_plans) || 0,
                plans: Array.isArray(data.contracts?.plans)
                    ? data.contracts.plans
                    : [],
                patient_with_plan:
                    Number(data.contracts?.patient_with_plan) || 0,
                new_monthy_patients:
                    Number(data.contracts?.new_monthy_patients) || 0,
                patient_retention: data.contracts?.patient_retention ?? "—",
                active_patient: Number(data.contracts?.active_patient) || 0,
                caregivers: Number(data.contracts?.caregivers) || 0,
                scheduled_visits: Number(data.contracts?.scheduled_visits) || 0,
                homecare_retention: data.contracts?.homecare_retention ?? "—",
            },
            recent_activity: Array.isArray(data.recent_activity)
                ? data.recent_activity
                : [],
        };
    } catch (err) {
        console.error("Failed to fetch branch dashboard:", err);
    } finally {
        loading.value = false;

        await nextTick();

        destroyCharts();
        initCharts();
    }
};

const destroyCharts = () => {
    if (occupancyChart) {
        occupancyChart.destroy();
        occupancyChart = null;
    }

    if (bookingChart) {
        bookingChart.destroy();
        bookingChart = null;
    }
};

const initCharts = () => {
    initOccupancyChart();
    initBookingChart();
};

const initOccupancyChart = () => {
    const canvas = document.getElementById(
        "occupancyChart",
    ) as HTMLCanvasElement | null;

    if (!canvas || !hasBeds.value) return;

    const segments = occupancySegments.value.filter((s) => s.count > 0);

    occupancyChart = new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: segments.map((s) => s.label),
            datasets: [
                {
                    data: segments.map((s) => s.count),
                    backgroundColor: segments.map((s) => s.color),
                    borderWidth: 0,
                    hoverOffset: 6,
                    hoverBorderWidth: 0,
                },
            ],
        },
        options: {
            cutout: "72%",
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    displayColors: true,
                },
            },
        } as any,
    });
};

const initBookingChart = () => {
    const canvas = document.getElementById(
        "bookingChart",
    ) as HTMLCanvasElement | null;

    if (!canvas || !totalBookings.value) return;

    const segments = bookingSegments.value.filter((s) => s.count > 0);

    bookingChart = new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: segments.map((s) => s.label),
            datasets: [
                {
                    data: segments.map((s) => s.count),
                    backgroundColor: segments.map((s) => s.color),
                    borderWidth: 0,
                    hoverOffset: 6,
                    hoverBorderWidth: 0,
                },
            ],
        },
        options: {
            cutout: "72%",
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    displayColors: true,
                },
            },
        } as any,
    });
};

const formatDate = (date: string) => {
    if (!date) return "—";

    try {
        return new Date(date).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
        });
    } catch {
        return date;
    }
};

onMounted(() => {
    if (!isSubscriptionPending.value) {
        fetchDashboard();
    }
});

onBeforeUnmount(() => {
    destroyCharts();
});
</script>
