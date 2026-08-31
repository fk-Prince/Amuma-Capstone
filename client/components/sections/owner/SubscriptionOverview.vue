<script lang="ts" setup>
type ApprovedStatus = "active" | "inactive" | "expired";
type StatSelectPayload =
    | { view: "requests" }
    | { view: "approved"; status: ApprovedStatus };

const props = defineProps<{
    overview?: any | null;
    loading?: boolean;
    activeView?: "requests" | "approved";
    activeStatus?: ApprovedStatus;
}>();

const emit = defineEmits<{
    (e: "select", payload: StatSelectPayload): void;
}>();

const overview = computed<any>(() => {
    return (
        props.overview ?? {
            pending: 0,
            active: 0,
            inactive: 0,
            expired: 0,
        }
    );
});

const isRequestsActive = computed(() => props.activeView === "requests");

function isApprovedActive(status: ApprovedStatus) {
    return props.activeView === "approved" && props.activeStatus === status;
}

function select(payload: StatSelectPayload) {
    emit("select", payload);
}
</script>

<template>
    <div class="w-full">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Requests (pending) -->
            <button
                type="button"
                :aria-pressed="isRequestsActive"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white dark:bg-secondary p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-amber-200"
                :class="
                    isRequestsActive
                        ? '-translate-y-1 border-amber-300 shadow-xl ring-2 ring-amber-100 dark:ring-amber-500/20'
                        : 'border-slate-200 dark:border-white/10'
                "
                @click="select({ view: 'requests' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-amber-100/50 dark:bg-amber-500/10 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-500/10"
                        >
                            <svg
                                class="h-5 w-5 text-amber-600 dark:text-amber-300"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 7v5l3 3" />
                            </svg>
                        </div>

                        <span
                            class="rounded-full bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-600 dark:text-amber-300"
                        >
                            Pending
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Requests
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800 dark:text-white"
                        >
                            {{ overview.pending }}
                        </p>
                        <p class="mt-3 text-xs text-amber-600 dark:text-amber-300">
                            Awaiting your review
                        </p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200 dark:bg-white/10"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100 dark:bg-white/5"
                        />
                    </template>
                </div>
            </button>

            <!-- Active -->
            <button
                type="button"
                :aria-pressed="isApprovedActive('active')"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white dark:bg-secondary p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-accent-200"
                :class="
                    isApprovedActive('active')
                        ? '-translate-y-1 border-accent-300 shadow-xl ring-2 ring-accent-100 dark:ring-accent-500/20'
                        : 'border-slate-200 dark:border-white/10'
                "
                @click="select({ view: 'approved', status: 'active' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-accent-100/50 dark:bg-accent-500/10 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent-50 dark:bg-accent-500/10"
                        >
                            <svg
                                class="h-5 w-5 text-accent-600 dark:text-accent-300"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                        </div>

                        <span
                            class="flex items-center gap-2 text-xs font-medium text-accent-600 dark:text-accent-300"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-accent-500 animate-pulse"
                            />
                            Active
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Active Subscriptions
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800 dark:text-white"
                        >
                            {{ overview.active }}
                        </p>
                        <p class="mt-3 text-xs text-accent-600 dark:text-accent-300">
                            Billing normally
                        </p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200 dark:bg-white/10"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100 dark:bg-white/5"
                        />
                    </template>
                </div>
            </button>

            <!-- Inactive -->
            <button
                type="button"
                :aria-pressed="isApprovedActive('inactive')"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white dark:bg-secondary p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-slate-300"
                :class="
                    isApprovedActive('inactive')
                        ? '-translate-y-1 border-slate-300 shadow-xl ring-2 ring-slate-200 dark:ring-white/10'
                        : 'border-slate-200 dark:border-white/10'
                "
                @click="select({ view: 'approved', status: 'inactive' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-slate-200/50 dark:bg-white/5 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10"
                        >
                            <svg
                                class="h-5 w-5 text-slate-500 dark:text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <rect
                                    x="6"
                                    y="4"
                                    width="4"
                                    height="16"
                                    rx="1"
                                />
                                <rect
                                    x="14"
                                    y="4"
                                    width="4"
                                    height="16"
                                    rx="1"
                                />
                            </svg>
                        </div>

                        <span
                            class="rounded-full bg-slate-100 dark:bg-white/10 px-2.5 py-1 text-xs font-semibold text-slate-500 dark:text-gray-300"
                        >
                            Paused
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Inactive
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800 dark:text-white"
                        >
                            {{ overview.inactive }}
                        </p>
                        <p class="mt-3 text-xs text-slate-500 dark:text-gray-400">
                            Paused by owner or admin
                        </p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200 dark:bg-white/10"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100 dark:bg-white/5"
                        />
                    </template>
                </div>
            </button>

            <!-- Expired -->
            <button
                type="button"
                :aria-pressed="isApprovedActive('expired')"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white dark:bg-secondary p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-rose-200"
                :class="
                    isApprovedActive('expired')
                        ? '-translate-y-1 border-rose-300 shadow-xl ring-2 ring-rose-100 dark:ring-rose-500/20'
                        : 'border-slate-200 dark:border-white/10'
                "
                @click="select({ view: 'approved', status: 'expired' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-rose-100/50 dark:bg-rose-500/10 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-500/10"
                        >
                            <svg
                                class="h-5 w-5 text-rose-500 dark:text-rose-300"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                            </svg>
                        </div>

                        <span
                            class="rounded-full bg-rose-50 dark:bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-500 dark:text-rose-300"
                        >
                            Attention
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Expired
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800 dark:text-white"
                        >
                            {{ overview.expired }}
                        </p>
                        <p class="mt-3 text-xs text-rose-500 dark:text-rose-300">Needs renewal</p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200 dark:bg-white/10"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100 dark:bg-white/5"
                        />
                    </template>
                </div>
            </button>
        </div>
    </div>
</template>
