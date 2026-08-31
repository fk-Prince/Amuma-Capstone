<template>
    <div class="w-full">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                        </div>

                        <span
                            class="px-2.5 py-1 rounded-full bg-primary-50 text-primary text-xs font-semibold dark:bg-primary-500/10"
                        >
                            Staff
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Total Employees
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                    >
                        {{ props.totalEmployee ?? 0 }}
                    </p>

                    <div
                        class="mt-3 flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                        Assigned to this branch
                    </div>
                </div>
            </div>

            <!-- On Duty -->
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
                                <path d="M20 6L9 17l-5-5" />
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
                        On Duty
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                    >
                        {{ props.onDuty ?? 0 }}
                    </p>

                    <div
                        class="mt-3 flex items-center gap-2 text-xs text-emerald-600"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        Active staff today
                    </div>
                </div>
            </div>

            <!-- On Leave -->
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
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4l3 3" />
                            </svg>
                        </div>

                        <span
                            class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-500 text-xs font-semibold dark:bg-rose-500/10"
                        >
                            Away
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        On Leave
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-slate-800 tabular-nums dark:text-white"
                    >
                        {{ props.onLeave ?? 0 }}
                    </p>

                    <p class="mt-3 text-xs text-rose-500">Current on leave</p>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
useHead({ title: "Staff" });

const props = defineProps<{
    totalEmployee: number;
    onDuty: number;
    onLeave: number;
}>();

const emit = defineEmits<{
    addEmployee: [];
}>();
</script>
