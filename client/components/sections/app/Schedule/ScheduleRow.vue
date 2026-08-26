<template>
    <div
        class="relative flex border-b border-slate-100 last:border-b-0 transition"
        :class="rowTheme(rowIndex)"
        :style="{
            minHeight: `${Math.max(
                130,
                (schedule.services?.length || 1) * 130 + 8,
            )}px`,
        }"
    >
        <div
            class="sticky left-0 z-10 shrink-0 p-3"
            :style="{ width: `${labelWidth}px` }"
            :class="rowTheme(rowIndex)"
        >
            <div class="flex h-full flex-col justify-between gap-3">
                <div class="space-y-4 flex gap-3 items-center">
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400"
                        >
                            Schedule
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ schedule.schedule_code || "—" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs uppercase tracking-wide text-slate-400"
                        >
                            Date & Time
                        </p>
                        <p class="mt-1 text-sm text-slate-800">
                            {{ formatDate(schedule.scheduled_at) || "—" }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ schedule.start_time }}
                            <span v-if="schedule.end_time">
                                – {{ schedule.end_time }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-md border border-primary/20 bg-white px-2.5 py-1.5 text-[11px] font-medium text-primary transition hover:bg-primary/5"
                        @click="$emit('view-details', schedule)"
                    >
                        View details
                    </button>

                    <button
                        v-if="
                            !['missed', 'completed', 'cancelled'].includes(
                                schedule.status?.toLowerCase() ?? '',
                            ) &&
                            schedule.services?.some(
                                (service) => !service.assignees?.length,
                            )
                        "
                        type="button"
                        class="flex-1 rounded-md bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-100"
                        @click="$emit('assign', schedule)"
                    >
                        Assign now
                    </button>
                </div>
            </div>
        </div>

        <div
            class="relative flex-1"
            :style="{ width: `${day.hourColumnCount * hourWidth}px` }"
        >
            <div class="absolute inset-0 flex">
                <div
                    v-for="hour in day.hours"
                    :key="hour.value"
                    class="h-full shrink-0"
                    :style="{ width: `${hourWidth}px` }"
                />
            </div>

            <div
                v-if="day.isToday && nowOffset(day) !== null"
                class="absolute top-0 z-10 h-full w-px bg-teal-400"
                :style="{ left: `${nowOffset(day)}px` }"
            >
                <div
                    class="absolute -left-1 -top-1 h-2 w-2 rounded-full bg-teal-500"
                />
            </div>

            <template v-if="schedule.services?.length">
                <div
                    v-for="(service, sIndex) in schedule.services"
                    :key="service.schedule_services_id ?? sIndex"
                    class="group absolute flex cursor-pointer flex-col justify-center gap-1 rounded-xl border-r border-t border-b border-slate-200/60 px-4 py-2.5 text-xs shadow-sm transition-all duration-150 hover:z-10 hover:-translate-y-0.5 hover:shadow-md"
                    :class="scheduleStatusTheme(schedule.status).card"
                    :style="{
                        left: `${getServiceLeft(schedule, sIndex, day)}px`,
                        width: `${getServiceWidth(service)}px`,
                        top: `${8 + sIndex * 110}px`,
                        height: '90px',
                    }"
                    @click="$emit('view-details', schedule)"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="truncate text-[13px] font-semibold text-slate-800"
                        >
                            {{ service.service_name }}
                        </span>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="scheduleStatusTheme(schedule.status).badge"
                        >
                            {{ scheduleStatusLabel(schedule.status) }}
                        </span>
                    </div>

                    <div
                        class="flex items-center gap-1.5 text-[11px] text-slate-500"
                    >
                        <svg
                            width="11"
                            height="11"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="shrink-0 opacity-60"
                        >
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 3" />
                        </svg>
                        <span class="truncate">
                            {{ schedule.start_time }} – {{ schedule.end_time }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span
                            v-if="service.assignees?.length"
                            class="flex items-center -space-x-1.5"
                        >
                            <span
                                v-for="(
                                    assignee, aIndex
                                ) in service.assignees.slice(0, 3)"
                                :key="assignee.employee_id"
                                class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-primary text-[9px] font-bold text-white"
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
                                v-if="(service.assignees?.length ?? 0) > 3"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-white bg-slate-200 text-[9px] font-bold text-slate-600"
                                :title="
                                    service.assignees
                                        .slice(3)
                                        .map((a) => a.full_name)
                                        .join(', ')
                                "
                            >
                                +{{ (service.assignees?.length ?? 0) - 3 }}
                            </span>
                        </span>

                        <span
                            v-if="service.assignees?.length"
                            class="truncate text-[11px] font-medium"
                            :class="scheduleStatusTheme(schedule.status).accent"
                        >
                            {{ service.assignees[0]?.full_name }}
                            <span
                                v-if="(service.assignees?.length ?? 0) > 1"
                                class="text-slate-500"
                            >
                                +{{ (service.assignees?.length ?? 0) - 1 }}
                            </span>
                        </span>

                        <span
                            v-if="!service.assignees?.length"
                            class="italic text-[11px] text-slate-400"
                        >
                            Not assigned yet
                        </span>
                    </div>
                </div>
            </template>

            <div
                v-else
                class="group absolute flex cursor-pointer flex-col justify-center gap-1 rounded-xl border-r border-t border-b border-slate-200/60 px-4 py-2.5 text-xs shadow-sm transition-all duration-150 hover:z-10 hover:-translate-y-0.5 hover:shadow-md"
                :class="scheduleStatusTheme(schedule.status).card"
                :style="{
                    left: `${getScheduleLeft(schedule, day)}px`,
                    width: `${getScheduleWidth(schedule)}px`,
                    top: '8px',
                    height: '90px',
                }"
                @click="$emit('view-details', schedule)"
            >
                <div class="flex items-center justify-between gap-2">
                    <span
                        class="truncate text-[13px] font-semibold text-slate-800"
                    >
                        {{ schedule.category || schedule.type || "Schedule" }}
                    </span>
                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                        :class="scheduleStatusTheme(schedule.status).badge"
                    >
                        {{ scheduleStatusLabel(schedule.status) }}
                    </span>
                </div>

                <div
                    class="flex items-center gap-1.5 text-[11px] text-slate-500"
                >
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="shrink-0 opacity-60"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 3" />
                    </svg>
                    <span class="truncate">
                        {{ schedule.start_time }} – {{ schedule.end_time }}
                    </span>
                </div>

                <div
                    v-if="schedule.patient?.full_name"
                    class="truncate text-[11px] font-medium text-slate-600"
                >
                    {{ schedule.patient.full_name }}
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import { formatDate } from "~/utils/time";
import { initials } from "~/utils/user";

const props = defineProps<{
    schedule: ScheduleItem;
    rowIndex: number;
    day: any;
    hourWidth: number;
    labelWidth: number;
}>();

defineEmits<{
    (e: "view-details", schedule: ScheduleItem): void;
    (e: "assign", schedule: ScheduleItem): void;
}>();

function rowTheme(index: number) {
    return index % 2 === 1 ? "bg-slate-50" : "bg-white";
}

function scheduleStatusTheme(status?: string | null) {
    switch (status?.toLowerCase()) {
        case "completed":
            return {
                card: "border-l-4 border-l-sky-400 bg-sky-50/70",
                badge: "bg-sky-100 text-sky-700",
                accent: "text-sky-700",
            };
        case "ongoing":
            return {
                card: "border-l-4 border-l-emerald-400 bg-emerald-50/70",
                badge: "bg-emerald-100 text-emerald-700",
                accent: "text-emerald-700",
            };
        case "missed":
            return {
                card: "border-l-4 border-l-rose-400 bg-rose-50/70",
                badge: "bg-rose-100 text-rose-600",
                accent: "text-rose-600",
            };
        case "cancelled":
            return {
                card: "border-l-4 border-l-slate-300 bg-slate-50/70 opacity-70",
                badge: "bg-slate-200 text-slate-500",
                accent: "text-slate-500",
            };
        case "pending":
        default:
            return {
                card: "border-l-4 border-l-violet-400 bg-violet-50/70",
                badge: "bg-violet-100 text-violet-700",
                accent: "text-violet-700",
            };
    }
}

function scheduleStatusLabel(status?: string | null) {
    switch (status?.toLowerCase()) {
        case "completed":
            return "Complete";
        case "ongoing":
            return "Ongoing";
        case "missed":
            return "Missed";
        case "cancelled":
            return "Cancelled";
        case "pending":
        default:
            return "Pending";
    }
}

function parseTimeToMinutes(label: string | null | undefined) {
    if (!label) return null;
    const match = label.match(/(\d{1,2}):(\d{2})\s?(AM|PM)/i);
    if (!match) return null;
    let hour = Number(match[1]);
    const minute = Number(match[2]);
    const period = match[3]?.toUpperCase();
    if (period === "PM" && hour !== 12) hour += 12;
    if (period === "AM" && hour === 12) hour = 0;
    return hour * 60 + minute;
}

function getServiceLeft(
    schedule: ScheduleItem,
    serviceIndex: number,
    day: any,
) {
    const scheduleStart = parseTimeToMinutes(schedule.start_time);
    if (scheduleStart === null) return 0;

    const dayStart = day.hours[0].value * 60;

    const serviceStart =
        schedule.services
            ?.slice(0, serviceIndex)
            .reduce(
                (total, service) => total + (service.duration_minutes ?? 0),
                scheduleStart,
            ) ?? scheduleStart;

    return ((serviceStart - dayStart) / 60) * props.hourWidth;
}

function getServiceWidth(service: ScheduleServiceItem) {
    const duration = service.duration_minutes ?? 0;
    if (!duration) return props.hourWidth;
    return Math.max((duration / 60) * props.hourWidth, 240);
}

function getScheduleLeft(schedule: ScheduleItem, day: any) {
    const start = parseTimeToMinutes(schedule.start_time);
    if (start === null) return 0;
    const dayStart = day.hours[0].value * 60;
    return ((start - dayStart) / 60) * props.hourWidth;
}

function getScheduleWidth(schedule: ScheduleItem) {
    const start = parseTimeToMinutes(schedule.start_time);
    const end = parseTimeToMinutes(schedule.end_time);

    if (
        typeof schedule.total_duration_minutes === "number" &&
        schedule.total_duration_minutes > 0
    ) {
        return Math.max(
            (schedule.total_duration_minutes / 60) * props.hourWidth,
            140,
        );
    }

    if (start === null || end === null) return props.hourWidth;

    const duration = end - start;
    return Math.max((duration / 60) * props.hourWidth, 240);
}

function nowOffset(day: { hours: { value: number }[]; isToday?: boolean }) {
    const first = day.hours[0];
    const last = day.hours[day.hours.length - 1];
    if (!first || !last) return null;

    const now = new Date();
    const nowMin = now.getHours() * 60 + now.getMinutes();
    const dayStart = first.value * 60;
    const dayEnd = last.value * 60;
    if (nowMin < dayStart || nowMin > dayEnd) return null;

    return ((nowMin - dayStart) / 60) * props.hourWidth;
}
</script>
