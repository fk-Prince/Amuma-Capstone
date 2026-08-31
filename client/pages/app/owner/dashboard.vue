<template>
    <div class="min-h-screen-header">
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
                                    Total Subscriptions
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{ stats.total }}
                                </p>
                            </div>

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary-700 text-white shadow-md shadow-primary-500/30"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                                    />
                                    <circle cx="9" cy="7" r="4" />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"
                                    />
                                </svg>
                            </div>
                        </div>

                        <div class="relative mt-2 flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-accent-500/10 px-2 py-0.5 text-[10px] font-semibold text-accent-700 dark:text-accent-300"
                            >
                                {{ stats.active }} active
                            </span>
                            <span
                                class="text-[10px] font-medium text-muted dark:text-gray-400"
                            >
                                {{ activePct }}% of total
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
                                    Total Revenue
                                </p>

                                <p
                                    v-if="stats.paidPayments"
                                    class="mt-0.5 truncate text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{ formatCurrency(stats.revenue) }}
                                </p>

                                <p
                                    v-else
                                    class="mt-0.5 text-2xl font-bold tracking-tight text-slate-300 dark:text-gray-600"
                                >
                                    —
                                </p>
                            </div>
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent to-accent-700 text-white shadow-md shadow-accent-500/30"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="h-4 w-4"
                                >
                                    <path d="M7 3v18" />
                                    <path d="M7 5h6a4 4 0 0 1 0 8H7" />
                                    <path d="M7 9h8" />
                                    <path d="M7 13h7" />
                                </svg>
                            </div>
                        </div>

                        <div class="relative mt-2">
                            <span
                                v-if="stats.paidPayments"
                                class="rounded-full bg-accent-500/10 px-2 py-0.5 text-[10px] font-semibold text-accent-700 dark:text-accent-300"
                            >
                                {{ stats.paidPayments }} payments
                            </span>
                            <span
                                v-else
                                class="text-[10px] text-slate-400 dark:text-gray-500"
                            >
                                No payments yet
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
                                    Pending Requests
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{ stats.pending }}
                                </p>
                            </div>

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md shadow-amber-500/30"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    class="h-3.5 w-3.5"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 7v5l3 2"
                                    />
                                </svg>
                            </div>
                        </div>

                        <NuxtLink
                            v-if="stats.pending"
                            to="/app/owner/subscription"
                            class="group/link relative mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-200"
                        >
                            Review requests
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
                                    Active Now
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                >
                                    {{ stats.active }}
                                </p>
                            </div>

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent to-accent-700 text-white shadow-md shadow-accent-500/30"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 12l4 4L19 6"
                                    />
                                </svg>
                            </div>
                        </div>

                        <div class="relative mt-2">
                            <span
                                class="rounded-full bg-muted-light dark:bg-white/10 px-2 py-0.5 text-[10px] font-semibold text-muted-dark dark:text-gray-300"
                            >
                                {{ stats.inactive }} inactive
                            </span>
                        </div>
                    </div>
                </section>

                <section
                    class="grid grid-cols-1 gap-3 lg:grid-cols-3 lg:items-stretch"
                >
                    <div
                        class="rounded-xl bg-white dark:bg-white/5 p-3 shadow-sm border border-slate-200/70 dark:border-white/10 ring-1 ring-slate-100/60 dark:ring-white/10 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary-100 to-primary-50 dark:from-primary-500/20 dark:to-primary-500/10 text-primary-600 dark:text-primary-300"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                        class="h-4 w-4"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3 3v18h18M8 17V9m4 8V5m4 12v-6"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                    >
                                        Plan Breakdown
                                    </h2>
                                    <p
                                        class="text-[11px] text-muted dark:text-gray-400"
                                    >
                                        Subscriptions by plan
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="coloredPlanData.length"
                            class="relative mt-2 h-[220px]"
                        >
                            <canvas
                                id="planChart"
                                class="!h-full !w-full"
                                role="img"
                                aria-label="Plan breakdown chart"
                            />
                        </div>

                        <div
                            v-else
                            class="mt-2 flex h-[220px] items-center justify-center rounded-lg bg-primary-50/40 dark:bg-primary-500/10"
                        >
                            <p
                                class="text-xs font-semibold text-secondary dark:text-white"
                            >
                                No plans configured yet
                            </p>
                        </div>

                        <div
                            v-if="coloredPlanData.length"
                            class="mt-2 flex flex-wrap gap-x-3 gap-y-1 border-t border-slate-100 dark:border-white/10 pt-2"
                        >
                            <div
                                v-for="plan in coloredPlanData"
                                :key="plan.plan_code"
                                class="flex min-w-0 items-center gap-1.5"
                            >
                                <span
                                    class="h-1.5 w-1.5 shrink-0 rounded-full"
                                    :style="{
                                        background: plan.color,
                                        boxShadow: `0 0 0 3px ${plan.color}22`,
                                    }"
                                />
                                <span
                                    class="truncate text-[12px] font-medium"
                                    :class="
                                        plan.total
                                            ? 'text-secondary dark:text-white'
                                            : 'text-muted dark:text-gray-400'
                                    "
                                >
                                    {{ plan.name || plan.plan_code }}
                                </span>
                                <span
                                    class="shrink-0 text-[12px] font-bold tabular-nums"
                                    :class="
                                        plan.total
                                            ? 'text-muted dark:text-gray-400'
                                            : 'text-slate-300 dark:text-gray-600'
                                    "
                                >
                                    {{ plan.total }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl bg-white dark:bg-white/5 p-3 shadow-sm border border-slate-200/70 dark:border-white/10 ring-1 ring-slate-100/60 dark:ring-white/10 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent-100 to-accent-50 dark:from-accent-500/20 dark:to-accent-500/10 text-accent-600 dark:text-accent-300"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                        class="h-4 w-4"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M21.21 15.89A10 10 0 118 2.83M22 12A10 10 0 0012 2v10z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                    >
                                        Status
                                    </h2>
                                    <p
                                        class="text-[11px] text-muted dark:text-gray-400"
                                    >
                                        Current breakdown
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-accent-50 dark:bg-accent-500/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.1em] text-accent-700 dark:text-accent-300"
                            >
                                <span
                                    class="h-1 w-1 animate-pulse rounded-full bg-accent-500"
                                />
                                Live
                            </span>
                        </div>

                        <div v-if="stats.total" class="relative mt-2 h-[140px]">
                            <canvas
                                id="statusChart"
                                class="!h-full !w-full"
                                role="img"
                                aria-label="Subscription status chart"
                            />

                            <div
                                class="pointer-events-none absolute inset-0 flex items-center justify-center"
                            >
                                <div class="text-center">
                                    <p
                                        class="text-xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                                    >
                                        {{ stats.total }}
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
                            class="mt-2 flex h-[140px] items-center justify-center rounded-lg bg-accent-50/40 dark:bg-accent-500/10"
                        >
                            <p
                                class="text-xs font-semibold text-secondary dark:text-white"
                            >
                                No data yet
                            </p>
                        </div>

                        <div class="mt-2 space-y-0.5">
                            <div
                                v-for="segment in statusSegments"
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

                    <div class="flex h-full w-full flex-col gap-3">
                        <div
                            class="relative flex w-full flex-1 flex-col items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-primary-50 via-white to-white dark:from-primary-500/10 dark:via-secondary dark:to-secondary p-4 text-center shadow-sm dark:border dark:border-white/10 ring-1 ring-primary-100/60 dark:ring-primary-500/20 transition-all hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div
                                class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-primary-200/30 dark:bg-primary-500/10 blur-2xl"
                            />

                            <div
                                class="relative w-full max-w-lg flex justify-center items-center"
                            >
                                <div
                                    class="flex justify-center items-center gap-4 rounded-lg px-2 py-2"
                                >
                                    <div
                                        class="relative flex h-[64px] w-[64px] shrink-0 items-center justify-center"
                                    >
                                        <svg
                                            viewBox="0 0 80 80"
                                            class="h-[64px] w-[64px] -rotate-90"
                                        >
                                            <circle
                                                cx="40"
                                                cy="40"
                                                r="32"
                                                fill="none"
                                                stroke="#e0e9fb"
                                                stroke-width="7"
                                                class="dark:opacity-10"
                                            />
                                            <circle
                                                cx="40"
                                                cy="40"
                                                r="32"
                                                fill="none"
                                                stroke="#3182ED"
                                                stroke-width="7"
                                                stroke-linecap="round"
                                                :stroke-dasharray="
                                                    RING_CIRCUMFERENCE
                                                "
                                                :stroke-dashoffset="
                                                    agencyDashOffset
                                                "
                                                class="transition-[stroke-dashoffset] duration-700 ease-out"
                                            />
                                        </svg>

                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold tabular-nums text-primary-700 dark:text-primary-300"
                                            >
                                                {{ agencyVerifiedPct }}%
                                            </span>
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1 text-left">
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <div
                                                class="flex min-w-0 items-center gap-1.5"
                                            >
                                                <div
                                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-primary to-primary-700 text-white"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        class="h-3 w-3"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M9 12.75l1.5 1.5L15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                </div>

                                                <h2
                                                    class="truncate text-sm font-bold tracking-tight text-secondary dark:text-white"
                                                >
                                                    Agency Verification
                                                </h2>
                                            </div>

                                            <span
                                                v-if="
                                                    stats.agenciesVerifiedThisMonth
                                                "
                                                class="shrink-0 rounded-full bg-primary-500/10 px-1.5 py-0.5 text-[10px] font-bold text-primary-700 dark:text-primary-300"
                                            >
                                                +{{
                                                    stats.agenciesVerifiedThisMonth
                                                }}
                                                mo
                                            </span>
                                        </div>

                                        <div
                                            class="mt-1.5 flex items-center gap-3"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-secondary dark:text-white"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-primary-500"
                                                />
                                                {{ stats.agenciesVerified }}
                                                verified
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1.5 text-[11px] font-medium text-muted dark:text-gray-400"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-primary-100 ring-1 ring-primary-200 dark:bg-white/10 dark:ring-white/20"
                                                />
                                                {{ agencyUnverified }} pending
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="agencyPending.length"
                                    class="mt-2 border-t border-primary-100/70 dark:border-white/10 pt-2.5 text-left"
                                >
                                    <div
                                        v-for="item in agencyPending"
                                        :key="item.uuid"
                                        class="flex items-center justify-between gap-3 py-0.5"
                                    >
                                        <p
                                            class="min-w-0 truncate text-[11px] font-medium text-secondary/80 dark:text-white/80"
                                        >
                                            {{ item.name }}
                                        </p>

                                        <span
                                            class="shrink-0 text-[10px] font-medium text-amber-600 dark:text-amber-300"
                                        >
                                            {{ timeAgo(item.created_at) }}
                                        </span>
                                    </div>

                                    <NuxtLink
                                        to="/app/owner/subscription"
                                        class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-primary-600 hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-200"
                                    >
                                        Review {{ agencyUnverified }} pending
                                        <span>→</span>
                                    </NuxtLink>
                                </div>
                            </div>

                            <div
                                class="relative my-4 h-px w-full max-w-lg bg-gradient-to-r from-transparent via-slate-200 dark:via-white/10 to-transparent"
                            />

                            <div
                                class="relative w-full max-w-lg flex justify-center items-center"
                            >
                                <div
                                    class="flex items-center gap-4 rounded-lg px-2 py-2"
                                >
                                    <div
                                        class="relative flex h-[64px] w-[64px] shrink-0 items-center justify-center"
                                    >
                                        <svg
                                            viewBox="0 0 80 80"
                                            class="h-[64px] w-[64px] -rotate-90"
                                        >
                                            <circle
                                                cx="40"
                                                cy="40"
                                                r="32"
                                                fill="none"
                                                stroke="#dcefee"
                                                stroke-width="7"
                                                class="dark:opacity-10"
                                            />
                                            <circle
                                                cx="40"
                                                cy="40"
                                                r="32"
                                                fill="none"
                                                stroke="#0E7C7B"
                                                stroke-width="7"
                                                stroke-linecap="round"
                                                :stroke-dasharray="
                                                    RING_CIRCUMFERENCE
                                                "
                                                :stroke-dashoffset="
                                                    branchDashOffset
                                                "
                                                class="transition-[stroke-dashoffset] duration-700 ease-out"
                                            />
                                        </svg>

                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold tabular-nums text-accent-700 dark:text-accent-300"
                                            >
                                                {{ branchVerifiedPct }}%
                                            </span>
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1 text-left">
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <div
                                                class="flex min-w-0 items-center gap-1.5"
                                            >
                                                <div
                                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-accent to-accent-700 text-white"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.75"
                                                        class="h-3 w-3"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M3 21h18M5 21V7l8-4v18M13 21V11l6 3v7M9 9v.01M9 12v.01M9 15v.01"
                                                        />
                                                    </svg>
                                                </div>

                                                <h2
                                                    class="truncate text-sm font-bold tracking-tight text-secondary dark:text-white"
                                                >
                                                    Branch Verification
                                                </h2>
                                            </div>

                                            <span
                                                v-if="
                                                    stats.branchesVerifiedThisMonth
                                                "
                                                class="shrink-0 rounded-full bg-accent-500/10 px-1.5 py-0.5 text-[10px] font-bold text-accent-700 dark:text-accent-300"
                                            >
                                                +{{
                                                    stats.branchesVerifiedThisMonth
                                                }}
                                                mo
                                            </span>
                                        </div>

                                        <div
                                            class="mt-1.5 flex items-center gap-3"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-secondary dark:text-white"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-accent-500"
                                                />
                                                {{ stats.branchesVerified }}
                                                verified
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1.5 text-[11px] font-medium text-muted dark:text-gray-400"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-accent-100 ring-1 ring-accent-200 dark:bg-white/10 dark:ring-white/20"
                                                />
                                                {{ branchUnverified }} pending
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="branchPending.length"
                                    class="mt-2 border-t border-accent-100/70 dark:border-white/10 pt-2.5 text-left"
                                >
                                    <div
                                        v-for="item in branchPending"
                                        :key="item.uuid"
                                        class="flex items-center justify-between gap-3 py-0.5"
                                    >
                                        <p
                                            class="min-w-0 truncate text-[11px] font-medium text-secondary/80 dark:text-white/80"
                                        >
                                            {{ item.name }}

                                            <span
                                                v-if="item.agency_name"
                                                class="text-muted dark:text-gray-400"
                                            >
                                                · {{ item.agency_name }}
                                            </span>
                                        </p>

                                        <span
                                            class="shrink-0 text-[10px] font-medium text-amber-600 dark:text-amber-300"
                                        >
                                            {{ timeAgo(item.created_at) }}
                                        </span>
                                    </div>

                                    <NuxtLink
                                        to="/app/owner/subscription"
                                        class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-accent-700 hover:text-accent-800 dark:text-accent-300 dark:hover:text-accent-200"
                                    >
                                        Review {{ branchUnverified }} pending
                                        <span>→</span>
                                    </NuxtLink>
                                </div>
                            </div>

                            <div
                                class="pointer-events-none absolute -bottom-8 -left-8 h-28 w-28 rounded-full bg-accent-200/20 dark:bg-accent-500/10 blur-3xl"
                            />
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-xl bg-white dark:bg-white/5 p-3 shadow-sm border border-slate-200/70 dark:border-white/10 ring-1 ring-slate-100/60 dark:ring-white/10 transition-all hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent-100 to-accent-50 dark:from-accent-500/20 dark:to-accent-500/10 text-accent-600 dark:text-accent-300"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 3v18h18M8 17V9m4 8V5m4 12v-6"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                >
                                    Revenue by Month
                                </h2>
                                <p class="text-[11px] text-muted dark:text-gray-400">
                                    {{
                                        revenueYear
                                            ? `Jan–Dec ${revenueYear}, paid subscriptions`
                                            : `Last ${revenueRangeMonths} months, paid subscriptions`
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <div
                                class="inline-flex shrink-0 rounded-full bg-slate-100 dark:bg-white/5 p-0.5"
                                role="tablist"
                                aria-label="Revenue range"
                            >
                                <button
                                    type="button"
                                    role="tab"
                                    :aria-selected="!revenueYear && revenueRangeMonths === 6"
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold transition-colors"
                                    :class="
                                        !revenueYear && revenueRangeMonths === 6
                                            ? 'bg-white dark:bg-white/10 text-accent-600 dark:text-accent-300 shadow-sm'
                                            : 'text-slate-500 dark:text-gray-400 hover:text-slate-700 dark:hover:text-white'
                                    "
                                    @click="setRevenueRange(6)"
                                >
                                    6M
                                </button>

                                <button
                                    type="button"
                                    role="tab"
                                    :aria-selected="!revenueYear && revenueRangeMonths === 12"
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold transition-colors"
                                    :class="
                                        !revenueYear && revenueRangeMonths === 12
                                            ? 'bg-white dark:bg-white/10 text-accent-600 dark:text-accent-300 shadow-sm'
                                            : 'text-slate-500 dark:text-gray-400 hover:text-slate-700 dark:hover:text-white'
                                    "
                                    @click="setRevenueRange(12)"
                                >
                                    12M
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
                        v-if="revenueLoading"
                        class="mt-2 h-[220px] animate-pulse rounded-lg bg-accent-50/40 dark:bg-white/5"
                    />

                    <div
                        v-else-if="hasRevenueHistory"
                        class="relative mt-2 h-[220px]"
                    >
                        <canvas
                            id="revenueChart"
                            class="!h-full !w-full"
                            role="img"
                            aria-label="Revenue by month chart"
                        />
                    </div>

                    <div
                        v-else
                        class="mt-2 flex h-[220px] items-center justify-center rounded-lg bg-accent-50/40 dark:bg-accent-500/10"
                    >
                        <p class="text-xs font-semibold text-secondary dark:text-white">
                            {{
                                revenueYear
                                    ? `No revenue recorded in ${revenueYear}`
                                    : `No revenue recorded in the last ${revenueRangeMonths} months`
                            }}
                        </p>
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
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                >
                                    Recent Subscriptions
                                </h2>
                                <p
                                    class="text-[11px] text-muted dark:text-gray-400"
                                >
                                    Latest subscription activity
                                </p>
                            </div>
                        </div>

                        <NuxtLink
                            to="/app/owner/subscription"
                            class="group inline-flex w-fit items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-primary-600 transition-colors hover:bg-primary-50 hover:text-primary-700 dark:text-primary-300 dark:hover:bg-white/10 dark:hover:text-primary-200"
                        >
                            View all
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 01.02-1.06L10.94 10 7.23 6.29a.75.75 0 111.06-1.06l4.24 4.24a.75.75 0 010 1.06l-4.24 4.24a.75.75 0 010 1.06l-4.24 4.24a.75.75 0 01-1.06-.02z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </NuxtLink>
                    </div>

                    <div
                        v-if="recent.length === 0"
                        class="flex flex-col items-center justify-center px-6 py-8 text-center"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10 text-primary-400 dark:text-primary-300"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                                class="h-4 w-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"
                                />
                            </svg>
                        </div>

                        <p
                            class="mt-2 text-xs font-semibold text-secondary dark:text-white"
                        >
                            No recent activity
                        </p>
                        <p class="mt-0.5 text-[11px] text-muted dark:text-gray-400">
                            New subscription requests will appear here.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[700px]">
                            <thead>
                                <tr class="bg-primary-50/40 dark:bg-primary-500/10">
                                    <th
                                        class="px-3 py-2 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                                    >
                                        Agency / Branch
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                                    >
                                        Plan
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                                    >
                                        Period
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                                <tr
                                    v-for="sub in recent"
                                    :key="sub.uuid"
                                    class="transition-colors hover:bg-primary-50/40 dark:hover:bg-white/5"
                                >
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary-100 to-primary-50 dark:from-primary-500/20 dark:to-primary-500/10 text-[10px] font-bold text-primary-700 dark:text-primary-300 ring-1 ring-primary-100 dark:ring-primary-500/20"
                                            >
                                                {{
                                                    initials(
                                                        sub.branch?.agency
                                                            ?.name ||
                                                            sub.branch?.name ||
                                                            "?",
                                                    )
                                                }}
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-xs font-semibold text-secondary dark:text-white"
                                                >
                                                    {{
                                                        sub.branch?.agency
                                                            ?.name || "—"
                                                    }}
                                                </p>
                                                <p
                                                    class="truncate text-[10px] text-muted dark:text-gray-400"
                                                >
                                                    {{
                                                        sub.branch?.name || "—"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2">
                                        <span
                                            class="inline-flex rounded-md bg-primary-50 dark:bg-primary-500/10 px-2 py-0.5 text-[10px] font-semibold text-primary-700 dark:text-primary-300 ring-1 ring-primary-100 dark:ring-primary-500/20"
                                        >
                                            {{ sub.plan?.name || "—" }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-2">
                                        <p
                                            class="text-[11px] font-medium text-secondary/80 dark:text-white/80"
                                        >
                                            {{ formatDate(sub.start_date) }}
                                        </p>
                                        <p class="text-[10px] text-muted dark:text-gray-400">
                                            to {{ formatDate(sub.end_date) }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-2">
                                        <StatusBadge :status="sub.status" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { formatCurrency as formatCurrencyUtil } from "~/utils/currency";
import StatusBadge from "~/components/ui/StatusBadge.vue";
import Combobox from "~/components/ui/Combobox.vue";
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from "chart.js";

Chart.register(
    BarController,
    CategoryScale,
    LinearScale,
    BarElement,
    DoughnutController,
    ArcElement,
    LineController,
    LineElement,
    PointElement,
    Filler,
    Title,
    Tooltip,
    Legend,
);

interface Subscription {
    uuid: string;
    status: string;
    start_date: string;
    end_date: string;
    created_at: string;
    plan?: {
        name: string;
        plan_code: string;
    };
    branch?: {
        uuid: string;
        name: string;
        is_verified: boolean;
        agency?: {
            uuid: string;
            name: string;
            is_verified: boolean;
        };
    };
}

interface PlanBreakdown {
    name: string;
    plan_code: string;
    total: number;
}

interface RevenueByMonth {
    month: string;
    total: number;
}

interface PendingVerificationItem {
    uuid: string;
    name: string;
    agency_name?: string | null;
    created_at: string;
}

interface RecentlyVerifiedItem {
    uuid: string;
    name: string;
    agency_name?: string | null;
    verified_at: string;
}

interface VerificationBucket {
    total: number;
    verified: number;
    unverified: number;
    verified_this_month: number;
    pending: PendingVerificationItem[];
    recently_verified: RecentlyVerifiedItem[];
}

interface DashboardOverview {
    total: number;
    by_status: Record<string, number>;
    branches: VerificationBucket;
    agencies: VerificationBucket;
    revenue_total: number;
    paid_payments_count: number;
    revenue_by_month: RevenueByMonth[];
}

definePageMeta({
    layout: "owner",
    middleware: "auth-client",
});

useHead({
    title: "AMUMA Dashboard",
});

const emptyBucket = (): VerificationBucket => ({
    total: 0,
    verified: 0,
    unverified: 0,
    verified_this_month: 0,
    pending: [],
    recently_verified: [],
});

const subscriptions = ref<Subscription[]>([]);
const planData = ref<PlanBreakdown[]>([]);
const revenueRangeMonths = ref<6 | 12>(6);
const revenueYear = ref<number | null>(null);
const availableRevenueYears = ref<number[]>([]);

const overview = ref<DashboardOverview>({
    total: 0,
    by_status: {},
    branches: emptyBucket(),
    agencies: emptyBucket(),
    revenue_total: 0,
    paid_payments_count: 0,
    revenue_by_month: [],
});

const loading = ref(true);

let planChart: Chart | null = null;
let statusChart: Chart | null = null;
let revenueChart: Chart | null = null;

const RING_CIRCUMFERENCE = 2 * Math.PI * 32;

const PLAN_COLORS = [
    "#3182ED",
    "#F59E0B",
    "#0E7C7B",
    "#A855F7",
    "#F43F5E",
    "#14B8A6",
    "#EAB308",
    "#6366F1",
];

const CHART_TICK_COLOR = "#94a3b8";
const CHART_GRID_COLOR = "rgba(148, 163, 184, 0.15)";

const normalizeBucket = (bucket: any): VerificationBucket => ({
    total: Number(bucket?.total) || 0,
    verified: Number(bucket?.verified) || 0,
    unverified: Number(bucket?.unverified) || 0,
    verified_this_month: Number(bucket?.verified_this_month) || 0,
    pending: Array.isArray(bucket?.pending) ? bucket.pending : [],
    recently_verified: Array.isArray(bucket?.recently_verified)
        ? bucket.recently_verified
        : [],
});

const fetchAll = async () => {
    loading.value = true;

    try {
        const res = await subscriptionService.action({
            action: "overview",
            revenue_months: revenueRangeMonths.value,
            revenue_year: revenueYear.value ?? undefined,
        });

        const data = res.data?.data ?? res.data ?? res;

        subscriptions.value = data.recent || [];
        planData.value = data.plan_breakdown || [];

        overview.value = {
            total: Number(data.total) || 0,
            by_status: data.by_status || {},
            branches: normalizeBucket(data.branches),
            agencies: normalizeBucket(data.agencies),
            revenue_total: Number(data.revenue_total) || 0,
            paid_payments_count: Number(data.paid_payments_count) || 0,
            revenue_by_month: Array.isArray(data.revenue_by_month)
                ? data.revenue_by_month
                : [],
        };

        availableRevenueYears.value = Array.isArray(data.available_revenue_years)
            ? data.available_revenue_years
            : [];
    } catch (err) {
        console.error("Failed to fetch subscriptions:", err);

        subscriptions.value = [];
        planData.value = [];

        overview.value = {
            total: 0,
            by_status: {},
            branches: emptyBucket(),
            agencies: emptyBucket(),
            revenue_total: 0,
            paid_payments_count: 0,
            revenue_by_month: [],
        };
    } finally {
        loading.value = false;

        await nextTick();

        destroyCharts();
        initCharts();
    }
};

const stats = computed(() => ({
    total: overview.value.total,
    pending: Number(overview.value.by_status.pending) || 0,
    active: Number(overview.value.by_status.active) || 0,
    inactive: Number(overview.value.by_status.inactive) || 0,
    expired: Number(overview.value.by_status.expired) || 0,

    agenciesTotal: overview.value.agencies.total,
    agenciesVerified: overview.value.agencies.verified,
    agenciesVerifiedThisMonth: overview.value.agencies.verified_this_month,

    branchesTotal: overview.value.branches.total,
    branchesVerified: overview.value.branches.verified,
    branchesVerifiedThisMonth: overview.value.branches.verified_this_month,

    revenue: overview.value.revenue_total,
    paidPayments: overview.value.paid_payments_count,
}));

const activePct = computed(() => {
    if (!stats.value.total) return 0;

    return Math.round((stats.value.active / stats.value.total) * 100);
});

const agencyVerifiedPct = computed(() => {
    if (!stats.value.agenciesTotal) return 0;

    return Math.round(
        (stats.value.agenciesVerified / stats.value.agenciesTotal) * 100,
    );
});

const branchVerifiedPct = computed(() => {
    if (!stats.value.branchesTotal) return 0;

    return Math.round(
        (stats.value.branchesVerified / stats.value.branchesTotal) * 100,
    );
});

const agencyUnverified = computed(() =>
    Math.max(stats.value.agenciesTotal - stats.value.agenciesVerified, 0),
);

const branchUnverified = computed(() =>
    Math.max(stats.value.branchesTotal - stats.value.branchesVerified, 0),
);

const agencyDashOffset = computed(
    () =>
        RING_CIRCUMFERENCE -
        (agencyVerifiedPct.value / 100) * RING_CIRCUMFERENCE,
);

const branchDashOffset = computed(
    () =>
        RING_CIRCUMFERENCE -
        (branchVerifiedPct.value / 100) * RING_CIRCUMFERENCE,
);

const agencyPending = computed(() =>
    overview.value.agencies.pending.slice(0, 3),
);

const branchPending = computed(() =>
    overview.value.branches.pending.slice(0, 3),
);

const coloredPlanData = computed(() => {
    return [...planData.value]
        .sort((a, b) => a.plan_code.localeCompare(b.plan_code))
        .map((plan, index) => ({
            ...plan,
            color: PLAN_COLORS[index % PLAN_COLORS.length],
        }));
});

const statusSegments = computed(() => {
    const total = stats.value.total || 1;

    return [
        {
            key: "pending",
            label: "Pending",
            count: stats.value.pending,
            pct: Math.round((stats.value.pending / total) * 100),
            color: "#d97706",
        },
        {
            key: "active",
            label: "Active",
            count: stats.value.active,
            pct: Math.round((stats.value.active / total) * 100),
            color: "#0E7C7B",
        },
        {
            key: "inactive",
            label: "Inactive",
            count: stats.value.inactive,
            pct: Math.round((stats.value.inactive / total) * 100),
            color: "#94a3b8",
        },
        {
            key: "expired",
            label: "Expired",
            count: stats.value.expired,
            pct: Math.round((stats.value.expired / total) * 100),
            color: "#f87171",
        },
    ];
});

const recent = computed(() => {
    return [...subscriptions.value]
        .sort(
            (a, b) =>
                new Date(b.created_at || b.start_date).getTime() -
                new Date(a.created_at || a.start_date).getTime(),
        )
        .slice(0, 6);
});

const hasRevenueHistory = computed(() =>
    overview.value.revenue_by_month.some((entry) => entry.total > 0),
);

const revenueYearOptions = computed(() =>
    availableRevenueYears.value.map((year) => ({
        label: String(year),
        value: year,
    })),
);

const revenueLoading = ref(false);

const fetchRevenueByMonth = async () => {
    revenueLoading.value = true;

    try {
        const res = await subscriptionService.action({
            action: "overview",
            revenue_months: revenueRangeMonths.value,
            revenue_year: revenueYear.value ?? undefined,
        });

        const data = res.data?.data ?? res.data ?? res;

        overview.value.revenue_by_month = Array.isArray(data.revenue_by_month)
            ? data.revenue_by_month
            : [];

        if (Array.isArray(data.available_revenue_years)) {
            availableRevenueYears.value = data.available_revenue_years;
        }
    } catch (err) {
        console.error("Failed to fetch revenue by month:", err);
    } finally {
        revenueLoading.value = false;

        await nextTick();

        if (revenueChart) {
            revenueChart.destroy();
            revenueChart = null;
        }

        initRevenueChart();
    }
};

function setRevenueRange(months: 6 | 12) {
    if (revenueYear.value === null && revenueRangeMonths.value === months) {
        return;
    }

    revenueYear.value = null;
    revenueRangeMonths.value = months;
    fetchRevenueByMonth();
}

function setRevenueYear(year: number | null) {
    if (revenueYear.value === year) return;

    revenueYear.value = year;
    fetchRevenueByMonth();
}

const destroyCharts = () => {
    if (planChart) {
        planChart.destroy();
        planChart = null;
    }

    if (statusChart) {
        statusChart.destroy();
        statusChart = null;
    }

    if (revenueChart) {
        revenueChart.destroy();
        revenueChart = null;
    }
};

const initCharts = () => {
    initPlanChart();
    initStatusChart();
    initRevenueChart();
};

const initPlanChart = () => {
    const canvas = document.getElementById(
        "planChart",
    ) as HTMLCanvasElement | null;

    if (!canvas || !coloredPlanData.value.length) return;

    const sorted = coloredPlanData.value;

    planChart = new Chart(canvas, {
        type: "bar",
        data: {
            labels: sorted.map((plan) => plan.name || plan.plan_code),
            datasets: [
                {
                    label: "Subscriptions",
                    data: sorted.map((plan) => plan.total),
                    backgroundColor: sorted.map((plan) => plan.color),
                    hoverBackgroundColor: sorted.map((plan) => plan.color),
                    borderRadius: 6,
                    borderSkipped: false,
                    barThickness: 28,
                    maxBarThickness: 36,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    titleFont: {
                        size: 11,
                        weight: "600",
                    },
                    bodyFont: {
                        size: 11,
                    },
                    displayColors: false,
                    callbacks: {
                        label: (context: any) =>
                            ` ${context.parsed.y} subscriptions`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: {
                            size: 9,
                        },
                        color: CHART_TICK_COLOR,
                    },
                    grid: {
                        color: CHART_GRID_COLOR,
                    },
                    border: {
                        display: false,
                    },
                },
                x: {
                    ticks: {
                        font: {
                            size: 9,
                        },
                        color: CHART_TICK_COLOR,
                    },
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                },
            },
        } as any,
    });
};

const initStatusChart = () => {
    const canvas = document.getElementById(
        "statusChart",
    ) as HTMLCanvasElement | null;

    if (!canvas || !stats.value.total) return;

    const segments = statusSegments.value;

    statusChart = new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: segments.map((segment) => segment.label),
            datasets: [
                {
                    data: segments.map((segment) => segment.count),
                    backgroundColor: segments.map((segment) => segment.color),
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
            animation: {
                duration: 550,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    displayColors: true,
                    callbacks: {
                        label: (context: any) => {
                            const value = Number(context.raw) || 0;

                            const pct = stats.value.total
                                ? Math.round((value / stats.value.total) * 100)
                                : 0;

                            return ` ${value} (${pct}%)`;
                        },
                    },
                },
            },
        } as any,
    });
};

const initRevenueChart = () => {
    const canvas = document.getElementById(
        "revenueChart",
    ) as HTMLCanvasElement | null;

    const entries = overview.value.revenue_by_month;

    if (!canvas || !entries.length) return;

    const gradient = canvas
        .getContext("2d")
        ?.createLinearGradient(0, 0, 0, 220);

    gradient?.addColorStop(0, "rgba(14, 124, 123, 0.35)");
    gradient?.addColorStop(1, "rgba(14, 124, 123, 0)");

    revenueChart = new Chart(canvas, {
        type: "line",
        data: {
            labels: entries.map((entry) => entry.month),
            datasets: [
                {
                    label: "Revenue",
                    data: entries.map((entry) => entry.total),
                    borderColor: "#0E7C7B",
                    backgroundColor: gradient ?? "rgba(14, 124, 123, 0.15)",
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: "#0E7C7B",
                    pointBorderColor: "#ffffff",
                    pointBorderWidth: 1.5,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    titleFont: {
                        size: 11,
                        weight: "600",
                    },
                    bodyFont: {
                        size: 11,
                    },
                    displayColors: false,
                    callbacks: {
                        label: (context: any) =>
                            ` ${formatCurrency(Number(context.parsed.y) || 0)}`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: {
                            size: 9,
                        },
                        color: CHART_TICK_COLOR,
                        callback: (value: any) => formatCurrency(Number(value)),
                    },
                    grid: {
                        color: CHART_GRID_COLOR,
                    },
                    border: {
                        display: false,
                    },
                },
                x: {
                    ticks: {
                        font: {
                            size: 9,
                        },
                        color: CHART_TICK_COLOR,
                    },
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                },
            },
        } as any,
    });
};

const formatCurrency = (value: number | string) => {
    const num = typeof value === "string" ? parseFloat(value) : value;

    return formatCurrencyUtil(num || 0, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
};

const formatDate = (date: string) => {
    if (!date) return "—";

    try {
        return new Date(date).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "2-digit",
        });
    } catch {
        return date;
    }
};

const timeAgo = (date: string) => {
    if (!date) return "—";

    try {
        const diffMs = Date.now() - new Date(date).getTime();
        const diffMin = Math.floor(diffMs / 60000);

        if (diffMin < 1) return "just now";
        if (diffMin < 60) return `${diffMin}m`;

        const diffHr = Math.floor(diffMin / 60);
        if (diffHr < 24) return `${diffHr}h`;

        const diffDay = Math.floor(diffHr / 24);
        if (diffDay < 30) return `${diffDay}d`;

        const diffMonth = Math.floor(diffDay / 30);
        return `${diffMonth}mo`;
    } catch {
        return "—";
    }
};

const initials = (name: string): string => {
    return name
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join("");
};

onMounted(() => {
    fetchAll();
});

onBeforeUnmount(() => {
    destroyCharts();
});
</script>
