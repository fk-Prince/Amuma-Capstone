<script lang="ts" setup>
import type { Overview } from "~/types/room";

const props = defineProps<{
    overview?: Overview | null;
}>();

const overview = computed<Overview>(() => {
    return (
        props.overview ?? {
            total_rooms: { value: 0, secondary: "", trend: "" },
            available: { value: 0, secondary: "", trend: "" },
            occupied: { value: 0, secondary: "", trend: "" },
            maintenance: { value: 0, secondary: "", trend: "" },
        }
    );
});
</script>

<template>
    <div class="w-full">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-blue-200 dark:border-white/10 dark:bg-secondary dark:hover:border-blue-500/20"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-blue-100/50 blur-2xl dark:bg-blue-500/15"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="h-10 w-10 rounded-xl bg-blue-50 flex items-center justify-center dark:bg-blue-500/10"
                        >
                            <svg
                                class="h-5 w-5 text-blue-600 dark:text-blue-300"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"
                                />
                                <path d="M10 6h4M10 10h4M10 14h4M10 18h4" />
                            </svg>
                        </div>

                        <span
                            class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-semibold dark:bg-blue-500/10 dark:text-blue-300"
                        >
                            Rooms
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Total Rooms
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                    >
                        {{ overview.total_rooms.value }}
                    </p>

                    <p class="mt-3 text-xs text-slate-400 dark:text-gray-500">
                        {{ overview.total_rooms.secondary }}
                    </p>
                </div>
            </div>

            <div
                class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-emerald-200 dark:border-white/10 dark:bg-secondary dark:hover:border-emerald-500/20"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-emerald-100/50 blur-2xl dark:bg-emerald-500/15"
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
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                        </div>

                        <span
                            class="flex items-center gap-2 text-xs font-medium text-emerald-600 dark:text-emerald-300"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                            />
                            Available
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Available Beds
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                    >
                        {{ overview.available.value }}
                    </p>

                    <p
                        class="mt-3 text-xs text-emerald-600 dark:text-emerald-300"
                    >
                        Ready for occupancy
                    </p>
                </div>
            </div>

            <!-- Occupied -->
            <div
                class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-violet-200 dark:border-white/10 dark:bg-secondary dark:hover:border-violet-500/20"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-violet-100/50 blur-2xl dark:bg-violet-500/15"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center dark:bg-violet-500/10"
                        >
                            <svg
                                class="h-5 w-5 text-violet-600 dark:text-violet-300"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <circle cx="9" cy="7" r="4" />
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />
                            </svg>
                        </div>

                        <span
                            class="px-2.5 py-1 rounded-full bg-violet-50 text-violet-600 text-xs font-semibold dark:bg-violet-500/10 dark:text-violet-300"
                        >
                            Active
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Occupied
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                    >
                        {{ overview.occupied.value }}
                    </p>

                    <p
                        class="mt-3 text-xs text-violet-600 dark:text-violet-300"
                    >
                        Currently in use
                    </p>
                </div>
            </div>

            <!-- Maintenance -->
            <div
                class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-rose-200 dark:border-white/10 dark:bg-secondary dark:hover:border-rose-500/20"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-rose-100/50 blur-2xl dark:bg-rose-500/15"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="h-10 w-10 rounded-xl bg-rose-50 flex items-center justify-center dark:bg-rose-500/10"
                        >
                            <svg
                                class="h-5 w-5 text-rose-500 dark:text-rose-300"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                                <path
                                    d="M10.3 3.8 2.1 18a2 2 0 0 0 1.7 3h16.4a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0z"
                                />
                            </svg>
                        </div>

                        <span
                            class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-500 text-xs font-semibold dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            Attention
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Maintenance
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                    >
                        {{ overview.maintenance.value }}
                    </p>

                    <p class="mt-3 text-xs text-rose-500 dark:text-rose-300">
                        Needs inspection
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
