<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { History, LoaderCircle, X } from "lucide-vue-next";
import { employeeService } from "~/api/employee/EmployeeService";
import { formatLeaveType, type EmployeeLeave } from "~/types/employee";
import { formatDate } from "~/utils/time";

const props = defineProps<{
    open: boolean;
    employeeUuid?: string | null;
    branchUuid: string;
    name: string;
}>();

const emit = defineEmits<{ close: [] }>();

const leaves = ref<EmployeeLeave[]>([]);
const loading = ref(false);
const loadError = ref<string | null>(null);

watch(
    () => props.open,
    async (open) => {
        if (!open || !props.employeeUuid) return;

        loading.value = true;
        loadError.value = null;

        try {
            const res: any = await employeeService.leaves(props.employeeUuid, {
                branch_uuid: props.branchUuid,
            });
            leaves.value = (res?.data ?? []) as EmployeeLeave[];
        } catch (err: any) {
            loadError.value =
                err?.data?.message || "Couldn't load the leave history.";
        } finally {
            loading.value = false;
        }
    },
);

const DAY = 86_400_000;

const dayCount = (leave: EmployeeLeave) => {
    if (!leave.returned_at) return null;

    const start = new Date(`${leave.starts_on}T00:00:00`).getTime();
    const end = new Date(leave.returned_at).setHours(0, 0, 0, 0);

    return Math.max(1, Math.round((end - start) / DAY));
};

const daysLabel = (leave: EmployeeLeave) => {
    const days = dayCount(leave);

    return days === null ? "ongoing" : `${days} day${days === 1 ? "" : "s"}`;
};

const formatDay = (day: string) => formatDate(`${day}T00:00:00`);

const period = (leave: EmployeeLeave) => {
    if (leave.returned_at) {
        return `${formatDay(leave.starts_on)} – ${formatDate(leave.returned_at)}`;
    }

    return leave.ends_on
        ? `${formatDay(leave.starts_on)} – ${formatDay(leave.ends_on)}`
        : `Since ${formatDay(leave.starts_on)}`;
};

const outcome = (leave: EmployeeLeave) => {
    if (!leave.returned_at) {
        return {
            label: "On leave",
            class: "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300",
        };
    }

    if (!leave.returned_by) {
        return {
            label: "Ended automatically",
            class: "border-slate-200 text-slate-500 dark:border-white/10 dark:text-gray-400",
        };
    }

    const returned = new Date(leave.returned_at).setHours(0, 0, 0, 0);
    const planned = leave.ends_on
        ? new Date(`${leave.ends_on}T00:00:00`).getTime()
        : null;

    if (planned !== null && returned < planned) {
        return {
            label: "Returned early",
            class: "border-transparent bg-primary/10 text-primary dark:text-primary-300",
        };
    }

    return {
        label: "Returned",
        class: "border-slate-200 text-slate-500 dark:border-white/10 dark:text-gray-400",
    };
};

const summary = computed(() => {
    const days = leaves.value.reduce(
        (sum, leave) => sum + (dayCount(leave) ?? 0),
        0,
    );
    const count = leaves.value.length;

    return `${count} leave${count === 1 ? "" : "s"} · ${days} day${days === 1 ? "" : "s"} taken`;
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 backdrop-blur-sm sm:items-center sm:p-6"
            @click.self="emit('close')"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="leave-history-title"
                class="flex max-h-[85dvh] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl ring-1 ring-black/5 sm:rounded-3xl dark:bg-secondary"
            >
                <header
                    class="flex shrink-0 items-center gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                >
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <History class="h-5 w-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h3
                            id="leave-history-title"
                            class="truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            Leave history · {{ name }}
                        </h3>
                        <p
                            v-if="!loading && !loadError && leaves.length"
                            class="text-xs text-slate-400 dark:text-gray-500"
                        >
                            {{ summary }}
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Close"
                        class="shrink-0 rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div
                        v-if="loading"
                        class="flex items-center justify-center gap-2 py-12 text-sm text-slate-400"
                    >
                        <LoaderCircle class="h-4 w-4 animate-spin" />
                        Loading leave history…
                    </div>

                    <p
                        v-else-if="loadError"
                        class="py-12 text-center text-sm text-rose-600 dark:text-rose-300"
                    >
                        {{ loadError }}
                    </p>

                    <div
                        v-else-if="!leaves.length"
                        class="py-12 text-center"
                    >
                        <p class="text-sm font-semibold text-slate-700 dark:text-white">
                            No leave on record
                        </p>
                        <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
                            Leaves appear here once this employee is marked on
                            leave.
                        </p>
                    </div>

                    <ul v-else class="space-y-3">
                        <li
                            v-for="leave in leaves"
                            :key="leave.uuid"
                            class="rounded-xl border border-slate-200 p-4 dark:border-white/10"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-semibold text-primary dark:text-primary-300"
                                    >
                                        {{ formatLeaveType(leave.type) }}
                                    </span>
                                    <span
                                        class="text-sm font-semibold tabular-nums text-slate-800 dark:text-white"
                                    >
                                        {{ period(leave) }}
                                    </span>
                                    <span class="text-xs text-slate-400 dark:text-gray-500">
                                        · {{ daysLabel(leave) }}
                                    </span>
                                </div>

                                <span
                                    class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                    :class="outcome(leave).class"
                                >
                                    {{ outcome(leave).label }}
                                </span>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-gray-200">
                                {{ leave.reason }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
                                <template v-if="leave.recorded_by">
                                    Recorded by {{ leave.recorded_by }}
                                </template>
                                <template v-if="!leave.returned_at && leave.ends_on">
                                    · Back on {{ formatDay(leave.ends_on) }}
                                </template>
                                <template v-else-if="leave.returned_at && leave.returned_by">
                                    · Returned by {{ leave.returned_by }}
                                </template>
                                <template v-else-if="leave.returned_at">
                                    · Ended on its back-on date
                                </template>
                            </p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </Teleport>
</template>
