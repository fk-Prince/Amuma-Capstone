<template>
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">
                Medical Schedule
            </h3>
            <p class="mt-0.5 text-sm text-gray-400">
                Clinical visits and the medical staff assigned to them.
            </p>
        </div>

        <div v-if="loading" class="space-y-3 p-5">
            <div
                v-for="i in 3"
                :key="i"
                class="h-28 animate-pulse rounded-xl bg-gray-50"
            />
        </div>

        <div v-else-if="!logs.length" class="p-12 text-center">
            <span
                class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 text-gray-300"
            >
                <Stethoscope class="w-6 h-6" />
            </span>
            <p class="text-sm font-medium text-gray-600">
                No medical visits yet
            </p>
            <p class="mt-1 text-xs text-gray-400">
                Clinical/medical schedule entries will show up here.
            </p>
        </div>

        <div v-else class="p-5 space-y-6">
            <div v-if="upcomingSchedules.length">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <CalendarClock class="w-4 h-4 text-blue-500" />
                        <p class="text-sm font-semibold text-gray-800">
                            Scheduled Days
                        </p>
                    </div>
                    <span class="text-xs text-gray-400">
                        {{ upcomingSchedules.length }} upcoming
                    </span>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="schedule in upcomingSchedules"
                        :key="schedule.schedule_id"
                        class="flex flex-col gap-3 rounded-xl border border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-start gap-3 min-w-0">
                            <span
                                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-500"
                            >
                                <CalendarClock class="w-4 h-4" />
                            </span>

                            <div class="min-w-0">
                                <p
                                    class="text-sm font-semibold text-gray-800"
                                >
                                    {{ schedule.schedule_code }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ formatDate(getScheduleDate(schedule)) }}
                                    <span v-if="schedule.start_time">
                                        · {{ schedule.start_time }}
                                        <template v-if="schedule.end_time">
                                            - {{ schedule.end_time }}
                                        </template>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 flex-wrap pl-12 sm:pl-0 sm:shrink-0"
                        >
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-medium"
                                :class="scheduleStatusTheme(schedule.status).badge"
                            >
                                {{ scheduleStatusLabel(schedule.status) }}
                            </span>

                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600"
                            >
                                {{ formatDuration(schedule.total_hours) || "0 hrs" }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="historySchedules.length">
                <p class="text-sm font-semibold text-gray-800 mb-3">
                    History
                </p>

                <div class="space-y-4">
            <div
                v-for="schedule in historySchedules"
                :key="schedule.schedule_id"
                class="rounded-xl border border-gray-100 overflow-hidden"
            >
                <div
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between bg-gray-50/60"
                >
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            {{ schedule.schedule_code }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ formatDate(getScheduleDate(schedule)) }}
                            <span v-if="schedule.start_time">
                                · {{ schedule.start_time }}
                                <template v-if="schedule.end_time">
                                    - {{ schedule.end_time }}
                                </template>
                            </span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            class="px-2.5 py-1 rounded-full text-xs font-medium"
                            :class="scheduleStatusTheme(schedule.status).badge"
                        >
                            {{ scheduleStatusLabel(schedule.status) }}
                        </span>

                        <span
                            class="px-2.5 py-1 rounded-full text-xs font-medium bg-white border border-gray-200 text-gray-600"
                        >
                            {{ formatDuration(schedule.total_hours) }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="schedule.services?.length"
                    class="divide-y divide-gray-100"
                >
                    <div
                        v-for="service in schedule.services"
                        :key="service.schedule_services_id"
                        class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <template v-if="service.assignees?.length">
                                <img
                                    v-if="service.assignees[0]?.avatar"
                                    :src="service.assignees[0].avatar!"
                                    class="w-9 h-9 rounded-full object-cover shrink-0"
                                    alt=""
                                />
                                <span
                                    v-else
                                    class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-xs font-semibold shrink-0"
                                >
                                    {{ initials(service.assignees[0]?.full_name) }}
                                </span>
                            </template>
                            <span
                                v-else
                                class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 shrink-0"
                            >
                                <UserRound class="w-4 h-4" />
                            </span>

                            <div class="min-w-0">
                                <p
                                    class="text-sm font-medium text-gray-800 truncate"
                                >
                                    {{ service.service_name || "Service" }}
                                </p>
                                <p class="text-xs text-gray-400 truncate">
                                    <template v-if="service.assignees?.length">
                                        {{ service.assignees[0]?.full_name }}
                                        <span
                                            v-if="
                                                service.assignees[0]
                                                    ?.employee_role
                                            "
                                        >
                                            · {{ service.assignees[0]?.employee_role }}
                                        </span>
                                        <span
                                            v-if="service.assignees.length > 1"
                                        >
                                            +{{ service.assignees.length - 1 }}
                                            more
                                        </span>
                                    </template>
                                    <span v-else class="italic">
                                        Not assigned yet
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 pl-12 sm:pl-0">
                            <div
                                class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-1.5 text-right"
                            >
                                <p class="text-[10px] uppercase text-gray-400">
                                    Duration
                                </p>
                                <p class="text-xs font-semibold text-gray-700">
                                    {{
                                        formatDuration(
                                            (service.duration_minutes ?? 0) /
                                                60,
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="latestVisit(service)"
                                class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-right"
                            >
                                <p class="text-[10px] uppercase text-emerald-500">
                                    Last Visit
                                </p>
                                <p class="text-xs font-semibold text-emerald-700">
                                    {{ formatTimestamp(latestVisit(service)?.in_timestamp) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Stethoscope, UserRound, CalendarClock } from "lucide-vue-next";
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import { useSchedule } from "~/composables/useSchedule";
import { formatDuration } from "~/utils/time";

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

const { formatDate, getScheduleDate, scheduleStatusTheme, scheduleStatusLabel } =
    useSchedule();

// A pending/confirmed visit hasn't happened yet, so it belongs in its own
// "Scheduled Days" list rather than being mixed in with past visits.
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
