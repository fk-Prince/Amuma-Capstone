<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div
            class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-primary-200 dark:border-white/10 dark:bg-secondary dark:hover:border-primary-500/40"
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
                    class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Total Branches
                </p>

                <p class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white">
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
            class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-emerald-200 dark:border-white/10 dark:bg-secondary dark:hover:border-emerald-500/40"
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
                            class="h-5 w-5 text-emerald-600"
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
                        class="flex items-center gap-2 text-xs font-medium text-emerald-600"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                        />
                        Live
                    </span>
                </div>

                <p
                    class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Active Branches
                </p>

                <p class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white">
                    {{ statsData.active_branches }}
                </p>

                <div
                    class="mt-3 flex items-center gap-2 text-xs text-emerald-600"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                    {{ statsData.active_branches_percent }}% of total branches
                </div>
            </div>
        </div>

        <div
            class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-fuchsia-200 dark:border-white/10 dark:bg-secondary dark:hover:border-fuchsia-500/40"
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
                    class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Expiring Soon
                </p>

                <p class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white">
                    {{ statsData.expiring_soon }}
                </p>
            </div>
        </div>

        <div
            class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-rose-200 dark:border-white/10 dark:bg-secondary dark:hover:border-rose-500/40"
        >
            <div
                class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-rose-100/50 blur-2xl dark:bg-rose-500/10"
            />

            <div class="relative">
                <div class="flex items-center justify-between">
                    <div
                        class="h-10 w-10 rounded-xl bg-rose-50 flex items-center justify-center dark:bg-rose-500/10"
                    >
                        <svg
                            class="h-5 w-5 text-rose-500"
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
                    </div>

                    <span
                        class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-500 text-xs font-semibold dark:bg-rose-500/10"
                    >
                        Attention
                    </span>
                </div>

                <p
                    class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                >
                    Maintenance Alerts
                </p>

                <p class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white">
                    {{ statsData.maintenance_alerts }}
                </p>

                <p class="mt-3 text-xs text-rose-500">Subscription expired</p>
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
    expiring_soon: number;
    expiring_soon_percent: number;
    maintenance_alerts: number;
}

defineProps<{
    statsData: BranchStatsData;
}>();
</script>
