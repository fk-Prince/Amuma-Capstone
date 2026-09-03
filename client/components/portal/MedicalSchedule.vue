<template>
    <div class="rounded-2xl bg-white dark:bg-secondary">
        <div v-if="loading" class="space-y-3">
            <div
                v-for="i in 3"
                :key="i"
                class="h-32 animate-pulse rounded-2xl bg-gray-50 dark:bg-white/5"
            />
        </div>

        <div
            v-else-if="!logs.length"
            class="rounded-2xl border border-dashed border-gray-200 p-12 text-center dark:border-white/10"
        >
            <span
                class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 text-gray-300 dark:bg-white/5 dark:text-gray-500"
            >
                <Stethoscope class="h-6 w-6" />
            </span>

            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                No medical visits found
            </p>

            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                Try a different status or month to see more clinical visits.
            </p>
        </div>

        <div v-else class="space-y-6">
            <section
                v-for="group in groups"
                :key="group.key"
                class="space-y-3"
            >
                <div
                    v-if="group.title"
                    class="flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4 text-primary-500 dark:text-primary-300" />

                        <p class="text-sm font-semibold text-gray-800 dark:text-white">
                            {{ group.title }}
                        </p>
                    </div>

                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                        {{ group.schedules.length }}
                        {{ group.schedules.length === 1 ? "visit" : "visits" }}
                    </span>
                </div>

                <article
                    v-for="schedule in group.schedules"
                    :key="schedule.schedule_id"
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white transition hover:border-gray-200 dark:border-white/10 dark:bg-secondary dark:hover:border-white/10"
                >
                    <div
                        class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-400 dark:bg-white/5 dark:text-gray-500"
                            >
                                <Stethoscope class="h-4 w-4" />
                            </span>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p
                                        class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ schedule.schedule_code }}
                                    </p>

                                    <span
                                        v-if="dayBadge(schedule)"
                                        class="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                                    >
                                        {{ dayBadge(schedule) }}
                                    </span>
                                </div>

                                <p
                                    class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-gray-400 dark:text-gray-500"
                                >
                                    <span class="whitespace-nowrap">
                                        {{
                                            formatDate(
                                                getScheduleDate(schedule),
                                            ) || "Date to be confirmed"
                                        }}
                                    </span>

                                    <template v-if="schedule.start_time">
                                        <span class="text-gray-300 dark:text-gray-500">·</span>

                                        <span class="whitespace-nowrap">
                                            {{ schedule.start_time }}
                                            <template v-if="schedule.end_time">
                                                – {{ schedule.end_time }}
                                            </template>
                                        </span>
                                    </template>

                                    <span class="text-gray-300 dark:text-gray-500">·</span>

                                    <span
                                        class="whitespace-nowrap"
                                        title="Estimated duration"
                                    >
                                        Est.
                                        {{ formatDurationShort(schedule.total_hours) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full px-2.5 py-1 text-[11px] font-semibold sm:self-auto"
                            :class="scheduleStatusTheme(schedule.status).badge"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                            />
                            {{ scheduleStatusLabel(schedule.status) }}
                        </span>
                    </div>

                    <div
                        v-if="schedule.services?.length"
                        class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/10 dark:border-white/10"
                    >
                        <div
                            v-for="service in schedule.services"
                            :key="service.schedule_services_id"
                            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <template v-if="service.assignees?.length">
                                    <img
                                        v-if="service.assignees[0]?.avatar"
                                        :src="service.assignees[0].avatar!"
                                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white"
                                        alt=""
                                    />

                                    <span
                                        v-else
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-bold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                                    >
                                        {{ initials(service.assignees[0]?.full_name) }}
                                    </span>
                                </template>

                                <span
                                    v-else
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-500 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    <UserRound class="h-4 w-4" />
                                </span>

                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-gray-800 dark:text-white"
                                    >
                                        {{ service.service_name || "Service" }}
                                    </p>

                                    <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                                        <template v-if="service.assignees?.length">
                                            {{ service.assignees[0]?.full_name }}

                                            <span
                                                v-if="service.assignees[0]?.employee_role"
                                                class="capitalize"
                                            >
                                                · {{ service.assignees[0]?.employee_role }}
                                            </span>

                                            <span v-if="service.assignees.length > 1">
                                                +{{ service.assignees.length - 1 }}
                                                more
                                            </span>
                                        </template>

                                        <span v-else class="text-amber-600 dark:text-amber-300">
                                            Not assigned yet
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex shrink-0 items-center gap-2 pl-12 text-[11px] sm:pl-0"
                            >
                                <span
                                    class="inline-flex items-center gap-1 whitespace-nowrap font-semibold text-gray-500 dark:text-gray-400"
                                    title="Estimated duration"
                                >
                                    <Clock class="h-3 w-3 text-gray-300 dark:text-gray-500" />
                                    Est.
                                    {{
                                        formatDurationShort(
                                            (service.duration_minutes ?? 0) / 60,
                                        )
                                    }}
                                </span>

                                <span
                                    v-if="latestVisit(service)"
                                    class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-emerald-50 px-2 py-1 font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    />
                                    {{ formatTimestamp(latestVisit(service)?.in_timestamp) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Stethoscope, UserRound, CalendarClock, Clock } from "lucide-vue-next";
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import { useSchedule } from "~/composables/useSchedule";
import { formatDurationShort } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        logs?: ScheduleItem[];
        loading?: boolean;
    }>(),
    {
        logs: () => [],
        loading: false,
    },
);

const {
    formatDate,
    getScheduleDate,
    scheduleStatusTheme,
    scheduleStatusLabel,
    today,
    toLocalDateString,
} = useSchedule();

const NOT_YET_DONE_STATUSES = ["pending", "confirmed"];

const upcomingSchedules = computed(() =>
    props.logs.filter((schedule) =>
        NOT_YET_DONE_STATUSES.includes(schedule.status),
    ),
);

const historySchedules = computed(() =>
    props.logs.filter(
        (schedule) => !NOT_YET_DONE_STATUSES.includes(schedule.status),
    ),
);

const groups = computed(() => {
    const list: {
        key: string;
        title: string | null;
        schedules: ScheduleItem[];
    }[] = [];

    if (upcomingSchedules.value.length) {
        list.push({
            key: "upcoming",
            title: "Scheduled Days",
            schedules: upcomingSchedules.value,
        });
    }

    if (historySchedules.value.length) {
        list.push({
            key: "history",
            title: upcomingSchedules.value.length ? "History" : null,
            schedules: historySchedules.value,
        });
    }

    return list;
});

function dayBadge(schedule: ScheduleItem) {
    const date = getScheduleDate(schedule);
    if (!date) return null;

    if (date === today()) return "Today";

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    return date === toLocalDateString(tomorrow) ? "Tomorrow" : null;
}

function latestVisit(service: ScheduleServiceItem) {
    const visits = (service.assignees ?? []).flatMap((a) => a.online ?? []);
    if (!visits.length) return null;

    return visits.reduce((latest, visit) => {
        if (!visit.in_timestamp) return latest;
        if (!latest?.in_timestamp) return visit;

        return new Date(visit.in_timestamp) > new Date(latest.in_timestamp)
            ? visit
            : latest;
    }, visits[0]);
}

function initials(name?: string | null) {
    if (!name) return "?";

    return name
        .split(" ")
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

function formatTimestamp(value?: string | null) {
    if (!value) return "—";

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}
</script>
