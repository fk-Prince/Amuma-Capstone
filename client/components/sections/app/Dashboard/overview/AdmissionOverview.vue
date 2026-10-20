<template>
    <div class="space-y-3">
        <div
            v-if="accessDenied"
            class="flex min-h-[60vh] flex-col items-center justify-center gap-2 rounded-xl bg-white/50 p-8 text-center dark:bg-white/5"
        >
            <p class="text-lg font-semibold text-secondary dark:text-white">
                You can't access this branch
            </p>
            <p class="max-w-md text-sm text-muted dark:text-gray-400">
                {{ accessDeniedMessage }}
            </p>
        </div>

        <div v-else-if="loading" class="space-y-3">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-24 animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                />
            </div>
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="h-[300px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                />
            </div>
        </div>

        <template v-else>
            <section
                class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4"
            >
                <OverviewStatCard
                    label="Pending Bookings"
                    :value="overview.bookings.pending_confirmation"
                    :icon="ClipboardList"
                    tone="amber"
                    :chip="`${overview.bookings.today} today`"
                    :to="bookingsLink"
                    :action-label="
                        overview.bookings.pending_confirmation
                            ? 'Review bookings'
                            : undefined
                    "
                    :note="
                        overview.bookings.pending_confirmation
                            ? undefined
                            : 'All caught up'
                    "
                />
                <OverviewStatCard
                    v-if="canSeeInquiries"
                    label="New Inquiries"
                    :value="inquiryCounts.new"
                    :icon="MessageCircleQuestion"
                    :tone="inquiryCounts.new ? 'rose' : 'primary'"
                    :chip="`${inquiryCounts.read} read · ${inquiryCounts.resolved} resolved`"
                    :to="inquiriesLink"
                    :action-label="inquiryCounts.new ? 'Reply to inquiries' : undefined"
                    :note="inquiryCounts.new ? undefined : 'All caught up'"
                />
                <OverviewStatCard
                    v-else
                    label="Expiring Soon"
                    :value="overview.bookings.expiring_soon"
                    :icon="Hourglass"
                    :tone="overview.bookings.expiring_soon ? 'rose' : 'primary'"
                    note="Requests close to expiry"
                />
                <OverviewStatCard
                    label="Patients Admitted"
                    :value="overview.patients.admitted"
                    :icon="Users"
                    tone="primary"
                    :chip="`+${overview.patients.new_this_month} this month`"
                    :note="`${overview.patients.waiting} waiting`"
                />
                <OverviewStatCard
                    label="Bed Occupancy"
                    :value="`${occupancyPct}%`"
                    :icon="BedDouble"
                    tone="accent"
                    :chip="`${overview.occupancy.available} available`"
                    :note="`${overview.occupancy.occupied} of ${totalBeds} beds`"
                />
            </section>

            <section class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                <OverviewDonutCard
                    title="Occupancy Breakdown"
                    subtitle="Beds by current status"
                    :icon="BedDouble"
                    tone="accent"
                    :segments="occupancySegments"
                    :center-value="`${occupancyPct}%`"
                    center-label="Occupied"
                    empty-text="No beds configured yet"
                />

                <OverviewDonutCard
                    title="Booking Status"
                    subtitle="All booking requests"
                    :icon="ClipboardList"
                    tone="amber"
                    :segments="bookingSegments"
                    :center-value="totalBookings"
                    center-label="Requests"
                    empty-text="No bookings yet"
                    live
                />

                <div
                    class="relative flex w-full flex-col overflow-hidden rounded-xl bg-gradient-to-br from-primary-50 via-white to-white p-4 shadow-sm ring-1 ring-primary-100/60 transition-all hover:-translate-y-0.5 hover:shadow-md dark:border dark:border-white/10 dark:from-primary-500/10 dark:via-secondary dark:to-secondary dark:ring-primary-500/20"
                >
                    <div
                        class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-primary-200/30 blur-2xl dark:bg-primary-500/10"
                    />

                    <div class="relative flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary-700 text-white shadow-md shadow-primary-500/30"
                        >
                            <CircleCheck class="h-4 w-4" />
                        </div>
                        <div>
                            <h2
                                class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                            >
                                Needs your action
                            </h2>
                            <p
                                class="text-[11px] text-muted dark:text-gray-400"
                            >
                                What's waiting on admissions
                            </p>
                        </div>
                    </div>

                    <ul v-if="actions.length" class="relative mt-3 space-y-1">
                        <li v-for="item in actions" :key="item.key">
                            <NuxtLink
                                :to="item.to"
                                class="flex items-center justify-between gap-2 rounded-lg px-2 py-2 text-xs transition-colors hover:bg-white/70 dark:hover:bg-white/5"
                            >
                                <span
                                    class="font-semibold text-secondary dark:text-white"
                                    >{{ item.label }}</span
                                >
                                <ArrowRight
                                    class="h-3.5 w-3.5 shrink-0 text-muted"
                                />
                            </NuxtLink>
                        </li>
                    </ul>

                    <div
                        v-else
                        class="relative flex flex-1 flex-col items-center justify-center gap-1 py-8 text-center"
                    >
                        <p
                            class="text-xs font-semibold text-secondary dark:text-white"
                        >
                            Nothing waiting on you
                        </p>
                        <p class="text-[11px] text-muted dark:text-gray-400">
                            New requests will show up here.
                        </p>
                    </div>
                </div>
            </section>

            <DutyRosterCard v-if="showRoster" :branch-uuid="branchUuid" />

            <div
                class="grid grid-cols-1 gap-3"
                :class="canSeeInquiries ? 'lg:grid-cols-2' : ''"
            >
            <section
                class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm ring-1 ring-slate-100/60 dark:border-white/10 dark:bg-white/5 dark:ring-white/10"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5 dark:border-white/10"
                >
                    <div>
                        <h2
                            class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                        >
                            Recent booking activity
                        </h2>
                        <p class="text-[11px] text-muted dark:text-gray-400">
                            Latest requests for this branch
                        </p>
                    </div>
                    <NuxtLink
                        v-if="bookingsLink"
                        :to="bookingsLink"
                        class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300"
                    >
                        View all →
                    </NuxtLink>
                </div>

                <p
                    v-if="!overview.recent_activity.length"
                    class="px-3 py-8 text-center text-[11px] text-muted dark:text-gray-400"
                >
                    New booking requests will appear here.
                </p>

                <div
                    v-else
                    class="divide-y divide-slate-100 dark:divide-white/10"
                >
                    <div
                        v-for="(item, index) in overview.recent_activity"
                        :key="index"
                        class="flex items-center gap-3 px-3 py-2.5"
                    >
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

            <section
                v-if="canSeeInquiries"
                class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm ring-1 ring-slate-100/60 dark:border-white/10 dark:bg-white/5 dark:ring-white/10"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5 dark:border-white/10"
                >
                    <div>
                        <h2
                            class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                        >
                            Recent inquiries
                        </h2>
                        <p class="text-[11px] text-muted dark:text-gray-400">
                            Messages from the provider page
                        </p>
                    </div>
                    <NuxtLink
                        v-if="inquiriesLink"
                        :to="inquiriesLink"
                        class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300"
                    >
                        View all →
                    </NuxtLink>
                </div>

                <p
                    v-if="!inquiries.length"
                    class="px-3 py-8 text-center text-[11px] text-muted dark:text-gray-400"
                >
                    New inquiries will appear here.
                </p>

                <div
                    v-else
                    class="divide-y divide-slate-100 dark:divide-white/10"
                >
                    <NuxtLink
                        v-for="inquiry in inquiries"
                        :key="inquiry.uuid"
                        :to="inquiriesLink ?? '#'"
                        class="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-light dark:hover:bg-white/5"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-xs font-semibold text-secondary dark:text-white"
                            >
                                {{ inquiry.full_name }}
                            </p>
                            <p
                                class="truncate text-[11px] text-muted dark:text-gray-400"
                            >
                                {{ inquiry.message }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                :class="INQUIRY_STATUS_CLASSES[inquiry.status]"
                            >
                                {{ INQUIRY_STATUS_LABELS[inquiry.status] }}
                            </span>
                            <p
                                class="mt-1 text-[10px] text-muted dark:text-gray-400"
                            >
                                {{ formatDate(inquiry.created_at) }}
                            </p>
                        </div>
                    </NuxtLink>
                </div>
            </section>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, toRef, watch } from "vue";
import {
    ArrowRight,
    BedDouble,
    CircleCheck,
    ClipboardList,
    Hourglass,
    MessageCircleQuestion,
    Users,
} from "lucide-vue-next";

import DutyRosterCard from "~/components/sections/app/Dashboard/overview/DutyRosterCard.vue";
import OverviewDonutCard from "~/components/sections/app/Dashboard/overview/OverviewDonutCard.vue";
import OverviewStatCard from "~/components/sections/app/Dashboard/overview/OverviewStatCard.vue";
import StatusBadge from "~/components/ui/StatusBadge.vue";
import { inquiryService } from "~/api/inquiry/InquiryService";
import { useBranchOverview } from "~/composables/useBranchOverview";
import { useBranchPlan } from "~/composables/useBranchPlan";
import { usePermissions } from "~/composables/usePermission";
import {
    INQUIRY_STATUS_CLASSES,
    INQUIRY_STATUS_LABELS,
    type Inquiry,
} from "~/types/inquiry";
import { Modules } from "~/types/module";

const props = defineProps<{ branchUuid: string }>();

const { overview, loading, accessDenied, accessDeniedMessage } =
    useBranchOverview(toRef(props, "branchUuid"));

const { hasModule, canReview } = usePermissions();

const base = computed(() => `/app/branches/${props.branchUuid}`);

const { hasFacilityPlan } = useBranchPlan();

// Staff-on-duty timeline: facility plan + Schedules read access only.
const showRoster = computed(
    () => hasFacilityPlan.value && hasModule(Modules.Schedules),
);

const bookingsLink = computed(() =>
    hasModule(Modules.Bookings) ? `${base.value}/bookings` : undefined,
);
const admissionsLink = computed(() =>
    hasModule(Modules.Admissions) ? `${base.value}/admissions` : undefined,
);

const inquiriesLink = computed(() =>
    hasModule(Modules.Inquiries) ? `${base.value}/inquiries` : undefined,
);

const canSeeInquiries = computed(() => hasModule(Modules.Inquiries));

const inquiries = ref<Inquiry[]>([]);
const inquiryCounts = ref({ new: 0, read: 0, resolved: 0 });

const loadInquiries = async () => {
    if (!canSeeInquiries.value) return;

    try {
        const res: any = await inquiryService.list({
            branch_uuid: props.branchUuid,
            per_page: 5,
        });

        inquiries.value = res.data ?? [];
        inquiryCounts.value = {
            new: Number(res.status_counts?.new) || 0,
            read: Number(res.status_counts?.read) || 0,
            resolved: Number(res.status_counts?.resolved) || 0,
        };
    } catch (err) {
        console.error("Failed to fetch inquiries:", err);
    }
};

watch(() => props.branchUuid, loadInquiries, { immediate: true });

const totalBeds = computed(
    () => overview.value.occupancy.occupied + overview.value.occupancy.available,
);

const occupancyPct = computed(() => {
    const { occupied, available, maintenance } = overview.value.occupancy;
    const total = occupied + available + maintenance;

    return total ? Math.round((occupied / total) * 100) : 0;
});

const occupancySegments = computed(() => [
    { key: "occupied", label: "Occupied", count: overview.value.occupancy.occupied, color: "#0E7C7B" },
    { key: "available", label: "Available", count: overview.value.occupancy.available, color: "#3182ED" },
    { key: "maintenance", label: "Maintenance", count: overview.value.occupancy.maintenance, color: "#f87171" },
]);

const totalBookings = computed(() => {
    const b = overview.value.bookings;

    return b.pending_confirmation + b.approved + b.expired + b.rejected;
});

const bookingSegments = computed(() => [
    { key: "pending", label: "Pending", count: overview.value.bookings.pending_confirmation, color: "#d97706" },
    { key: "approved", label: "Approved / Accepted", count: overview.value.bookings.approved, color: "#3182ED" },
    { key: "expired", label: "Expired", count: overview.value.bookings.expired, color: "#0E7C7B" },
    { key: "rejected", label: "Rejected", count: overview.value.bookings.rejected, color: "#f87171" },
]);

const plural = (count: number, word: string) =>
    `${count} ${word}${count === 1 ? "" : "s"}`;

const actions = computed(() => {
    const list: { key: string; label: string; to: string }[] = [];
    const b = overview.value.bookings;

    if (b.pending_confirmation && bookingsLink.value) {
        list.push({
            key: "pending",
            label: canReview(Modules.Bookings)
                ? `Accept or reject ${plural(b.pending_confirmation, "booking request")}`
                : `${plural(b.pending_confirmation, "booking request")} pending`,
            to: bookingsLink.value,
        });
    }

    if (b.expiring_soon && bookingsLink.value) {
        list.push({
            key: "expiring",
            label: `${plural(b.expiring_soon, "request")} expiring soon`,
            to: bookingsLink.value,
        });
    }

    if (overview.value.patients.waiting && admissionsLink.value) {
        list.push({
            key: "waiting",
            label: `${plural(overview.value.patients.waiting, "patient")} waiting to be admitted`,
            to: admissionsLink.value,
        });
    }

    if (inquiryCounts.value.new && inquiriesLink.value) {
        list.push({
            key: "inquiries",
            label: `Reply to ${plural(inquiryCounts.value.new, "new inquiry").replace("inquirys", "inquiries")}`,
            to: inquiriesLink.value,
        });
    }

    return list;
});

const formatDate = (date: string) => {
    if (!date) return "—";

    const parsed = new Date(date);

    return Number.isNaN(parsed.getTime())
        ? date
        : parsed.toLocaleDateString("en-US", { month: "short", day: "numeric" });
};
</script>