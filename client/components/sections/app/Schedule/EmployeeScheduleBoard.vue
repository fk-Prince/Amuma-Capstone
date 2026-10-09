<script setup lang="ts">
import { computed, ref } from "vue";
import { CalendarDays, ChevronDown, Clock, Users } from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";

import type {
    EmployeeScheduleEntry,
    EmployeeScheduleRow,
} from "~/types/schedule";
import { formatDate, formatDurationShort, formatTime } from "~/utils/time";
import {
    scheduleStatusLabel,
    scheduleStatusTheme,
} from "~/utils/schedule-status";

const props = defineProps<{
    employees: EmployeeScheduleRow[];
    loading?: boolean;
    search?: string;
}>();

const PREVIEW_COUNT = 4;

const expanded = ref(new Set<number>());

function toggle(employeeId: number) {
    const next = new Set(expanded.value);

    if (!next.delete(employeeId)) next.add(employeeId);

    expanded.value = next;
}

const searchTerm = computed(() => (props.search ?? "").trim().toLowerCase());

function matches(...values: (string | null | undefined)[]) {
    return values.some((value) =>
        (value ?? "").toLowerCase().includes(searchTerm.value),
    );
}

function entryMatches(entry: EmployeeScheduleEntry) {
    return (
        matches(entry.patient_name, entry.schedule_code) ||
        entry.services.some((service) => matches(service))
    );
}

const rows = computed(() => {
    if (!searchTerm.value) return props.employees;

    return props.employees.flatMap((employee) => {
        if (matches(employee.full_name, employee.role_name, employee.email)) {
            return [employee];
        }

        const schedules = employee.schedules.filter(entryMatches);

        return schedules.length ? [{ ...employee, schedules }] : [];
    });
});

const totalSchedules = computed(() =>
    rows.value.reduce((sum, employee) => sum + employee.schedules.length, 0),
);

const busyCount = computed(
    () => rows.value.filter((employee) => employee.schedules.length).length,
);

function visibleSchedules(employee: EmployeeScheduleRow) {
    return expanded.value.has(employee.employee_id)
        ? employee.schedules
        : employee.schedules.slice(0, PREVIEW_COUNT);
}

function sameDay(a: string, b: string) {
    return new Date(a).toDateString() === new Date(b).toDateString();
}

function dateLabel(entry: EmployeeScheduleEntry) {
    const start = formatDate(entry.scheduled_at);

    if (!entry.scheduled_at || !entry.ends_at) return start;

    return sameDay(entry.scheduled_at, entry.ends_at)
        ? start
        : `${start} – ${formatDate(entry.ends_at)}`;
}

function timeLabel(entry: EmployeeScheduleEntry) {
    if (entry.start_time && entry.end_time) {
        return `${formatTime(entry.start_time)} – ${formatTime(entry.end_time)}`;
    }

    const start = formatTime(entry.scheduled_at);

    if (!entry.scheduled_at || !entry.ends_at) return start;

    return sameDay(entry.scheduled_at, entry.ends_at)
        ? `${start} – ${formatTime(entry.ends_at)}`
        : start;
}
</script>

<template>
    <div class="space-y-4 p-4 sm:p-5">
        <template v-if="loading">
            <div
                v-for="n in 4"
                :key="n"
                class="animate-pulse space-y-3 rounded-xl border border-slate-100 p-4 dark:border-white/10"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 shrink-0 rounded-full bg-slate-200 dark:bg-white/15"
                    />

                    <div class="flex-1 space-y-2">
                        <div
                            class="h-3 w-1/3 rounded bg-slate-200 dark:bg-white/15"
                        />
                        <div
                            class="h-2.5 w-1/4 rounded bg-slate-100 dark:bg-white/10"
                        />
                    </div>
                </div>

                <div class="h-10 rounded-lg bg-slate-100 dark:bg-white/5" />
            </div>
        </template>

        <div
            v-else-if="!rows.length"
            class="flex flex-col items-center justify-center gap-3 px-6 py-24 text-center"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
            >
                <Users class="h-6 w-6" />
            </div>

            <p class="text-sm font-semibold text-slate-600 dark:text-gray-400">
                {{
                    searchTerm
                        ? "No employees match your search"
                        : "No employees to show"
                }}
            </p>

            <p class="max-w-xs text-sm text-slate-400 dark:text-gray-500">
                {{
                    searchTerm
                        ? "Try another employee, patient or schedule code."
                        : "Active nurses and caregivers appear here with their schedules."
                }}
            </p>
        </div>

        <template v-else>
            <div
                class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 dark:text-gray-400"
            >
                <p class="text-sm font-semibold text-slate-800 dark:text-white">
                    Employee schedules
                </p>

                <p>
                    {{ busyCount }} of {{ rows.length }} employee{{
                        rows.length === 1 ? "" : "s"
                    }}
                    scheduled · {{ totalSchedules }} schedule{{
                        totalSchedules === 1 ? "" : "s"
                    }}
                </p>
            </div>

            <article
                v-for="employee in rows"
                :key="employee.employee_id"
                class="rounded-xl border border-slate-100 dark:border-white/10"
            >
                <div class="flex items-center gap-3 px-4 py-3">
                    <MessageAvatar
                        :src="employee.avatar"
                        :name="employee.full_name"
                    />

                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                        >
                            {{ employee.full_name ?? "Employee" }}
                        </p>

                        <p
                            class="truncate text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            {{ employee.role_name }}
                        </p>

                        <p
                            v-if="employee.email"
                            class="truncate text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            {{ employee.email }}
                        </p>
                    </div>

                    <span
                        class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                        :class="
                            employee.schedules.length
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-200'
                                : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-400'
                        "
                    >
                        {{
                            employee.schedules.length
                                ? `${employee.schedules.length} schedule${employee.schedules.length === 1 ? "" : "s"}`
                                : "No schedules"
                        }}
                    </span>
                </div>

                <ul
                    v-if="employee.schedules.length"
                    class="divide-y divide-slate-100 border-t border-slate-100 dark:divide-white/10 dark:border-white/10"
                >
                    <li
                        v-for="entry in visibleSchedules(employee)"
                        :key="entry.schedule_id"
                        class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                    >
                        <div class="min-w-0 space-y-1">
                            <div
                                class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] font-medium text-slate-700 dark:text-gray-300"
                            >
                                <span class="flex items-center gap-1">
                                    <CalendarDays
                                        class="h-3.5 w-3.5 opacity-60"
                                    />
                                    {{ dateLabel(entry) }}
                                </span>

                                <span class="flex items-center gap-1">
                                    <Clock class="h-3.5 w-3.5 opacity-60" />
                                    {{ timeLabel(entry) }}
                                </span>

                                <span
                                    v-if="entry.duration_minutes"
                                    class="text-[11px] font-normal text-slate-400 dark:text-gray-500"
                                >
                                    {{
                                        formatDurationShort(
                                            entry.duration_minutes / 60,
                                        )
                                    }}
                                </span>
                            </div>

                            <p
                                class="truncate text-sm text-slate-800 dark:text-white"
                            >
                                {{ entry.patient_name ?? "—" }}
                                <span
                                    class="text-[12px] text-slate-400 dark:text-gray-500"
                                >
                                    · {{ entry.services.join(", ") }}
                                </span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="font-mono text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                {{ entry.schedule_code }}
                            </span>

                            <span
                                class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="
                                    entry.category === 'Facility'
                                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300'
                                        : 'bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300'
                                "
                            >
                                {{
                                    entry.category === "Facility"
                                        ? "Facility"
                                        : "Homecare"
                                }}
                            </span>

                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                :class="scheduleStatusTheme(entry.status).badge"
                            >
                                {{ scheduleStatusLabel(entry.status) }}
                            </span>
                        </div>
                    </li>
                </ul>

                <button
                    v-if="employee.schedules.length > PREVIEW_COUNT"
                    type="button"
                    class="flex w-full items-center justify-center gap-1.5 border-t border-slate-100 px-4 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-50 dark:border-white/10 dark:text-primary-300 dark:hover:bg-primary-500/10"
                    :aria-expanded="expanded.has(employee.employee_id)"
                    @click="toggle(employee.employee_id)"
                >
                    {{
                        expanded.has(employee.employee_id)
                            ? "Show less"
                            : `Show ${employee.schedules.length - PREVIEW_COUNT} more`
                    }}
                    <ChevronDown
                        class="h-3.5 w-3.5 transition-transform"
                        :class="
                            expanded.has(employee.employee_id)
                                ? 'rotate-180'
                                : ''
                        "
                    />
                </button>
            </article>
        </template>
    </div>
</template>
