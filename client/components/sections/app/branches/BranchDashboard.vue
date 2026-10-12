<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div
            class="relative overflow-hidden group rounded-lg border border-slate-200 bg-white p-3.5 sm:p-5 shadow-sm transition-all duration-300 sm:hover:-translate-y-1 hover:shadow-xl hover:border-primary-200 dark:border-white/10 dark:bg-secondary dark:hover:border-primary-500/40"
        >
            <div
                class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-primary-100/40 blur-2xl dark:bg-primary-500/10"
            />

            <div class="relative">
                <div class="flex items-center justify-between">
                    <div
                        class="h-10 w-10 rounded-xl bg-primary-50 flex items-center justify-center dark:bg-primary-500/10"
                    >
                        <svg
                            class="h-5 w-5 text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="6" cy="6" r="2.5" />
                            <circle cx="18" cy="6" r="2.5" />
                            <circle cx="12" cy="18" r="2.5" />
                            <path d="M8.2 7.3 10.5 16.5M15.8 7.3 13.5 16.5" />
                        </svg>
                    </div>
                </div>

                <p
                    class="mt-3 sm:mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Total Branches
                </p>

                <p
                    class="mt-1 text-2xl sm:text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                >
                    {{ statsData.total_branches }}
                </p>

                <div class="mt-3 flex items-center gap-2 text-xs text-primary">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        class="w-3 h-3"
                    >
                        <polyline points="18 15 12 9 6 15" />
                    </svg>
                    {{ statsData.total_branches_new_this_month }} new this month
                </div>
            </div>
        </div>

        <div
            class="relative overflow-hidden group rounded-lg border border-slate-200 bg-white p-3.5 sm:p-5 shadow-sm transition-all duration-300 sm:hover:-translate-y-1 hover:shadow-xl hover:border-emerald-200 dark:border-white/10 dark:bg-secondary dark:hover:border-emerald-500/40"
        >
            <div
                class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-emerald-100/50 blur-2xl dark:bg-emerald-500/10"
            />

            <div class="relative">
                <div class="flex items-center justify-between">
                    <div
                        class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center dark:bg-emerald-500/10"
                    >
                        <svg
                            class="h-5 w-5 text-emerald-600 dark:text-emerald-300"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                            />
                            <circle cx="12" cy="10" r="2.5" />
                        </svg>
                    </div>

                    <span
                        class="flex items-center gap-2 text-xs font-medium text-emerald-600 dark:text-emerald-300"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                        />
                        Live
                    </span>
                </div>

                <p
                    class="mt-3 sm:mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Active Branches
                </p>

                <p
                    class="mt-1 text-2xl sm:text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                >
                    {{ statsData.active_branches }}
                </p>

                <div
                    class="mt-3 flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                    {{ statsData.active_branches_percent }}% of total branches
                </div>
            </div>
        </div>

        <div
            class="relative overflow-hidden group rounded-lg border border-slate-200 bg-white p-3.5 sm:p-5 shadow-sm transition-all duration-300 sm:hover:-translate-y-1 hover:shadow-xl hover:border-fuchsia-200 dark:border-white/10 dark:bg-secondary dark:hover:border-fuchsia-500/40"
        >
            <div
                class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-fuchsia-100/50 blur-2xl dark:bg-fuchsia-500/10"
            />

            <div class="relative">
                <div class="flex items-center justify-between">
                    <div
                        class="h-10 w-10 rounded-xl bg-fuchsia-50 flex items-center justify-center dark:bg-fuchsia-500/10"
                    >
                        <svg
                            class="h-5 w-5 text-fuchsia-600"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 3" />
                        </svg>
                    </div>
                </div>

                <p
                    class="mt-3 sm:mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Subscription Ends
                </p>

                <p
                    class="mt-1 text-xl sm:text-3xl font-bold break-words text-slate-800 tabular-nums dark:text-white"
                >
                    {{ expiresInLabel }}
                </p>

                <div
                    v-if="isTesting && !loading"
                    class="mt-3 flex items-center gap-2 text-xs font-semibold text-amber-600 dark:text-amber-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                    Still on free testing
                    <template v-if="testingEndsLabel">
                        · ends on {{ testingEndsLabel }}
                    </template>
                </div>

                <div
                    v-if="daysLeftLabel"
                    class="mt-3 flex items-center gap-2 text-xs text-fuchsia-600 dark:text-fuchsia-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-fuchsia-500" />
                    {{ daysLeftLabel }}
                </div>
            </div>
        </div>

        <div
            class="relative overflow-hidden group rounded-lg border border-slate-200 bg-white p-3.5 sm:p-5 shadow-sm transition-all duration-300 sm:hover:-translate-y-1 hover:shadow-xl dark:border-white/10 dark:bg-secondary"
            :class="alerts.length ? alertTone.cardHover : 'hover:border-emerald-200 dark:hover:border-emerald-500/40'"
        >
            <div
                class="absolute -top-10 -right-10 h-28 w-28 rounded-full blur-2xl"
                :class="alertTone.glow"
            />

            <div class="relative">
                <div class="flex items-center justify-between">
                    <div
                        class="h-10 w-10 rounded-xl flex items-center justify-center"
                        :class="alertTone.tile"
                    >
                        <svg
                            v-if="alerts.length"
                            class="h-5 w-5"
                            :class="alertTone.icon"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M10.3 3.9 1.8 18a1.8 1.8 0 0 0 1.5 2.7h17.4a1.8 1.8 0 0 0 1.5-2.7L13.7 3.9a1.8 1.8 0 0 0-3.4 0Z"
                            />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>

                        <svg
                            v-else
                            class="h-5 w-5"
                            :class="alertTone.icon"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                    </div>

                    <span
                        class="px-2.5 py-1 rounded-full text-xs font-semibold"
                        :class="alertTone.badge"
                    >
                        {{ alerts.length ? "Attention" : "All good" }}
                    </span>
                </div>

                <p
                    class="mt-3 sm:mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Alerts
                </p>

                <p
                    class="mt-1 text-2xl sm:text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                >
                    {{ loading ? "…" : alertTotal }}
                </p>

                <ul v-if="alerts.length && !loading" class="mt-3 space-y-1.5">
                    <li
                        v-for="alert in alerts"
                        :key="alert.key"
                        class="flex items-center gap-2 text-xs"
                        :class="alertTone.text"
                    >
                        <span
                            class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="alertTone.dot"
                        />
                        {{ alert.label }}
                    </li>
                </ul>

                <p
                    v-else-if="!loading"
                    class="mt-3 text-xs"
                    :class="alertTone.text"
                >
                    Every branch is in good standing
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface BranchStatsData {
    total_branches: number;
    total_branches_new_this_month: number;
    active_branches: number;
    active_branches_percent: number;
    expires_in_days?: number | null;
    subscription_end_date?: string | null;
    expiring_soon: number;
    expiring_soon_percent: number;
    maintenance_alerts: number;
    cancelled_subscriptions?: number;
    rejected_branches?: number;
    branch_capacity?: { is_testing?: boolean } | null;
    pending_plan?: {
        name?: string | null;
        type?: string | null;
        starts_at?: string | null;
        ends_at?: string | null;
    } | null;
}

import { computed } from "vue";
import { formatDate } from "~/utils/time";

const props = defineProps<{
    statsData: BranchStatsData;
    loading?: boolean;
}>();

const MONTH_DAYS = 30;

const plural = (count: number, singular: string, pluralForm = singular + "s") =>
    `${count} ${count === 1 ? singular : pluralForm}`;

const alerts = computed(() => {
    const list: { key: string; label: string }[] = [];

    const rejected = props.statsData.rejected_branches ?? 0;
    const expired = props.statsData.maintenance_alerts ?? 0;
    const cancelled = props.statsData.cancelled_subscriptions ?? 0;

    if (rejected > 0) {
        list.push({
            key: "rejected",
            label: `${plural(rejected, "branch", "branches")} rejected`,
        });
    }

    if (expired > 0) {
        list.push({
            key: "expired",
            label: `${plural(expired, "subscription")} expired`,
        });
    }

    if (cancelled > 0) {
        list.push({
            key: "cancelled",
            label: `${plural(cancelled, "subscription")} cancelled`,
        });
    }

    return list;
});

const alertTotal = computed(
    () =>
        (props.statsData.rejected_branches ?? 0) +
        (props.statsData.maintenance_alerts ?? 0) +
        (props.statsData.cancelled_subscriptions ?? 0),
);

const alertTone = computed(() =>
    alerts.value.length
        ? {
              cardHover:
                  "hover:border-rose-200 dark:hover:border-rose-500/40",
              glow: "bg-rose-100/50 dark:bg-rose-500/10",
              tile: "bg-rose-50 dark:bg-rose-500/10",
              icon: "text-rose-500 dark:text-rose-300",
              badge: "bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-300",
              text: "text-rose-500 dark:text-rose-300",
              dot: "bg-rose-500",
          }
        : {
              cardHover: "",
              glow: "bg-emerald-100/50 dark:bg-emerald-500/10",
              tile: "bg-emerald-50 dark:bg-emerald-500/10",
              icon: "text-emerald-600 dark:text-emerald-300",
              badge: "bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300",
              text: "text-emerald-600 dark:text-emerald-300",
              dot: "bg-emerald-500",
          },
);

const pendingPlan = computed(() => props.statsData.pending_plan ?? null);

const isTesting = computed(() =>
    Boolean(props.statsData.branch_capacity?.is_testing),
);

const hasPendingPlan = computed(
    () => isTesting.value && Boolean(pendingPlan.value?.ends_at),
);

// While on free testing, the paid plan waiting to start takes over the
// headline date and the countdown.
const effectiveEndDate = computed(() =>
    hasPendingPlan.value
        ? pendingPlan.value?.ends_at
        : props.statsData.subscription_end_date,
);

const testingEndsLabel = computed(() =>
    hasPendingPlan.value && props.statsData.subscription_end_date
        ? formatDate(props.statsData.subscription_end_date)
        : null,
);

const expiresInLabel = computed(() => {
    if (props.loading) return "…";

    return effectiveEndDate.value ? formatDate(effectiveEndDate.value) : "—";
});

const daysLeftLabel = computed(() => {
    if (props.loading) return null;

    if (hasPendingPlan.value && effectiveEndDate.value) {
        const end = new Date(effectiveEndDate.value);
        const today = new Date();

        end.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        const left = Math.max(
            0,
            Math.round((end.getTime() - today.getTime()) / 86_400_000),
        );

        return `${left} ${left === 1 ? "day" : "days"} left`;
    }

    const days = props.statsData.expires_in_days;

    if (props.loading || days === null || days === undefined) return null;
    if (days >= MONTH_DAYS) return null;
    if (days === 0) return "Ends today";

    return `${days} ${days === 1 ? "day" : "days"} left`;
});
</script>
