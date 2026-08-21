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
                class="group relative w-full overflow-hidden rounded-2xl border bg-white p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-blue-200"
                :class="
                    isRequestsActive
                        ? '-translate-y-1 border-blue-300 shadow-xl ring-2 ring-blue-100'
                        : 'border-slate-200'
                "
                @click="select({ view: 'requests' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-blue-100/50 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50"
                        >
                            <svg
                                class="h-5 w-5 text-blue-600"
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
                            class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-600"
                        >
                            Pending
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    >
                        Requests
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800"
                        >
                            {{ overview.pending }}
                        </p>
                        <p class="mt-3 text-xs text-blue-600">
                            Awaiting your review
                        </p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100"
                        />
                    </template>
                </div>
            </button>

            <!-- Active -->
            <button
                type="button"
                :aria-pressed="isApprovedActive('active')"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-emerald-200"
                :class="
                    isApprovedActive('active')
                        ? '-translate-y-1 border-emerald-300 shadow-xl ring-2 ring-emerald-100'
                        : 'border-slate-200'
                "
                @click="select({ view: 'approved', status: 'active' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-emerald-100/50 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50"
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
                            Active
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    >
                        Active Subscriptions
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800"
                        >
                            {{ overview.active }}
                        </p>
                        <p class="mt-3 text-xs text-emerald-600">
                            Billing normally
                        </p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100"
                        />
                    </template>
                </div>
            </button>

            <!-- Inactive -->
            <button
                type="button"
                :aria-pressed="isApprovedActive('inactive')"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-amber-200"
                :class="
                    isApprovedActive('inactive')
                        ? '-translate-y-1 border-amber-300 shadow-xl ring-2 ring-amber-100'
                        : 'border-slate-200'
                "
                @click="select({ view: 'approved', status: 'inactive' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-amber-100/50 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50"
                        >
                            <svg
                                class="h-5 w-5 text-amber-600"
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
                            class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-600"
                        >
                            Paused
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    >
                        Inactive
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800"
                        >
                            {{ overview.inactive }}
                        </p>
                        <p class="mt-3 text-xs text-amber-600">
                            Paused by owner or admin
                        </p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100"
                        />
                    </template>
                </div>
            </button>

            <!-- Expired -->
            <button
                type="button"
                :aria-pressed="isApprovedActive('expired')"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white p-5 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-rose-200"
                :class="
                    isApprovedActive('expired')
                        ? '-translate-y-1 border-rose-300 shadow-xl ring-2 ring-rose-100'
                        : 'border-slate-200'
                "
                @click="select({ view: 'approved', status: 'expired' })"
            >
                <div
                    class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-rose-100/50 blur-2xl"
                />

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50"
                        >
                            <svg
                                class="h-5 w-5 text-rose-500"
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
                            class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-500"
                        >
                            Attention
                        </span>
                    </div>

                    <p
                        class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    >
                        Expired
                    </p>

                    <template v-if="!loading">
                        <p
                            class="mt-1 text-3xl font-bold tabular-nums text-slate-800"
                        >
                            {{ overview.expired }}
                        </p>
                        <p class="mt-3 text-xs text-rose-500">Needs renewal</p>
                    </template>
                    <template v-else>
                        <div
                            class="mt-2 h-8 w-14 animate-pulse rounded bg-slate-200"
                        />
                        <div
                            class="mt-3 h-3 w-28 animate-pulse rounded bg-slate-100"
                        />
                    </template>
                </div>
            </button>
        </div>
    </div>
</template>
