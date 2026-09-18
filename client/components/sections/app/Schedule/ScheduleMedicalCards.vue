<template>
    <div class="space-y-6 p-4 sm:p-5">
        <section v-for="day in dayGroups" :key="day.date">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <h3 class="font-semibold text-slate-800 dark:text-white">
                    {{ day.dateLabel }}
                </h3>

                <span
                    v-if="day.isToday"
                    class="flex items-center gap-1 rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-teal-600 dark:bg-teal-500/15 dark:text-teal-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-500" />
                    Today
                </span>

                <span
                    v-if="day.unassignedCount"
                    class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    {{ day.unassignedCount }} unassigned
                </span>
            </div>

            <p
                v-if="!schedulesForDay(day.date).length"
                class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
            >
                No visits scheduled for this day
            </p>

            <div
                v-else
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-3"
            >
                <article
                    v-for="schedule in schedulesForDay(day.date)"
                    :key="schedule.schedule_id"
                    class="flex cursor-pointer flex-col rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-white/5"
                    @click="emit('view-details', schedule)"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                            >
                                {{ schedule.patient?.full_name ?? "—" }}
                            </p>

                            <p
                                class="mt-0.5 font-mono text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                {{ schedule.schedule_code }}
                            </p>
                        </div>

                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="scheduleStatusTheme(schedule.status).badge"
                        >
                            {{ scheduleStatusLabel(schedule.status) }}
                        </span>
                    </div>

                    <div class="flex-1 space-y-2.5 px-4 py-3">
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-slate-600 dark:text-gray-400"
                        >
                            <span class="flex items-center gap-1 font-medium">
                                <Clock class="h-3.5 w-3.5 opacity-60" />
                                {{ schedule.start_time ?? "—" }}
                                <template v-if="schedule.end_time">
                                    – {{ schedule.end_time }}
                                </template>
                            </span>

                            <span
                                class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="
                                    schedule.category === 'Facility'
                                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300'
                                        : 'bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300'
                                "
                            >
                                {{
                                    schedule.category === "Facility"
                                        ? "Facility"
                                        : "Homecare"
                                }}
                            </span>
                        </div>

                        <p
                            v-if="locationLabel(schedule)"
                            class="flex items-center gap-1 truncate text-[11px] text-slate-400 dark:text-gray-500"
                            :title="locationLabel(schedule)"
                        >
                            <MapPin class="h-3.5 w-3.5 shrink-0 opacity-60" />
                            <span class="truncate">
                                {{ locationLabel(schedule) }}
                            </span>
                        </p>

                        <ul
                            v-if="schedule.services?.length"
                            class="space-y-1.5"
                        >
                            <li
                                v-for="(service, sIndex) in schedule.services"
                                :key="service.schedule_services_id ?? sIndex"
                                class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 dark:bg-white/5"
                            >
                                <span
                                    class="truncate text-[12px] font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{ service.service_name ?? "Service" }}
                                </span>

                                <span
                                    v-if="service.assignees?.length"
                                    class="flex shrink-0 items-center -space-x-1.5"
                                >
                                    <span
                                        v-for="assignee in service.assignees.slice(
                                            0,
                                            3,
                                        )"
                                        :key="assignee.employee_id"
                                        class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-primary text-[9px] font-bold text-white dark:border-secondary"
                                        :title="assignee.full_name"
                                    >
                                        <img
                                            v-if="assignee.avatar"
                                            :src="assignee.avatar"
                                            :alt="assignee.full_name"
                                            class="h-full w-full object-cover"
                                        />
                                        <template v-else>
                                            {{ initials(assignee.full_name) }}
                                        </template>
                                    </span>

                                    <span
                                        v-if="service.assignees.length > 3"
                                        class="flex h-6 items-center justify-center rounded-full border-2 border-white bg-slate-200 px-1.5 text-[9px] font-semibold text-slate-600 dark:border-secondary dark:bg-white/15 dark:text-gray-400"
                                    >
                                        +{{ service.assignees.length - 3 }}
                                    </span>
                                </span>

                                <span
                                    v-else
                                    class="shrink-0 text-[11px] italic text-rose-500 dark:text-rose-300"
                                >
                                    Unassigned
                                </span>
                            </li>
                        </ul>

                        <p
                            v-if="schedule.note"
                            class="line-clamp-2 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            Note: {{ schedule.note }}
                        </p>
                    </div>

                    <div
                        class="flex gap-2 border-t border-slate-100 px-4 py-2.5 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="flex-1 rounded-md border border-primary/20 bg-primary/[0.03] px-2 py-1.5 text-[11px] font-medium text-primary transition hover:bg-primary/10"
                            @click.stop="emit('view-details', schedule)"
                        >
                            Details
                        </button>

                        <button
                            v-if="canAssign(schedule)"
                            type="button"
                            class="flex-1 rounded-md border border-rose-600/20 bg-rose-50 px-2 py-1.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/15"
                            @click.stop="emit('assign', schedule)"
                        >
                            Assign
                        </button>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>

<script lang="ts" setup>
import { Clock, MapPin } from "lucide-vue-next";
import type { ScheduleItem } from "~/types/schedule";
import { initials } from "~/utils/user";
import { useSchedule } from "~/composables/useSchedule";

const props = withDefaults(
    defineProps<{
        schedules?: ScheduleItem[];
        loading?: boolean;
        date?: string;
        rangeEnd?: string;
    }>(),
    {
        schedules: () => [],
        loading: false,
    },
);

const emit = defineEmits<{
    (e: "view-details", schedule: ScheduleItem): void;
    (e: "assign", schedule: ScheduleItem): void;
}>();

const { dayGroups, schedulesForDay, scheduleStatusTheme, scheduleStatusLabel } =
    useSchedule(props);

const CLOSED_STATUSES = ["missed", "completed", "cancelled"];

function canAssign(schedule: ScheduleItem) {
    return (
        !CLOSED_STATUSES.includes(schedule.status?.toLowerCase() ?? "") &&
        !!schedule.services?.some((service) => !service.assignees?.length)
    );
}

function locationLabel(schedule: ScheduleItem) {
    const bed = schedule.patient?.admission?.bed;

    if (bed) {
        return [
            bed.room?.room_no ? `Room ${bed.room.room_no}` : "",
            bed.bed_no ? `Bed ${bed.bed_no}` : "",
        ]
            .filter(Boolean)
            .join(" • ");
    }

    return schedule.category === "Facility" ? "" : (schedule.address ?? "");
}
</script>
