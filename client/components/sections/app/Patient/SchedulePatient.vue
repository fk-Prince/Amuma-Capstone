<template>
    <div class="flex flex-col gap-5">
        <div
            class="flex flex-col items-center gap-4 rounded-2xl bg-white p-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="flex flex-col items-center gap-3 sm:flex-row sm:gap-4">
                <BaseInput
                    v-model="selectedDate"
                    mode="date"
                    label="From"
                    class-name="w-full sm:max-w-[180px]"
                />

                <div
                    class="mt-6 hidden h-10 w-10 items-center justify-center text-slate-500 sm:flex"
                >
                    <ChevronRight class="h-5 w-5" />
                </div>

                <BaseInput
                    v-model="rangeEnd"
                    mode="date"
                    label="To"
                    class-name="w-full sm:max-w-[180px]"
                />

                <button
                    type="button"
                    class="h-11 self-end rounded-lg bg-primary/80 px-10 text-sm font-medium uppercase text-white transition hover:bg-primary"
                    @click="jumpToToday"
                >
                    Today
                </button>
            </div>

            <div
                v-if="!loading"
                class="flex items-center gap-4 text-sm text-slate-500"
            >
                <span class="font-medium text-slate-700">{{ totalCount }}</span>
                schedule{{ totalCount !== 1 ? "s" : "" }}
                <template v-if="unassignedCount > 0">
                    <span class="h-1 w-1 rounded-full bg-slate-300" />
                    <span
                        class="flex items-center gap-1.5 font-medium text-rose-600"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                        {{ unassignedCount }} need{{
                            unassignedCount === 1 ? "s" : ""
                        }}
                        assignment
                    </span>
                </template>
            </div>
        </div>

        <div v-if="loading" class="space-y-4">
            <div
                v-for="i in 3"
                :key="i"
                class="animate-pulse rounded-2xl bg-white p-5"
            >
                <div class="mb-4 h-4 w-40 rounded bg-slate-100" />
                <div class="space-y-3">
                    <div class="h-16 rounded-xl bg-slate-100" />
                    <div class="h-16 rounded-xl bg-slate-100" />
                </div>
            </div>
        </div>

        <div
            v-else-if="!hasAnySchedules"
            class="flex flex-col items-center gap-3 rounded-2xl bg-white p-12 text-center"
        >
            <div
                class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
            >
                <svg
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M3 10h18M8 3v4M16 3v4" />
                </svg>
            </div>
            <div>
                <p class="font-medium text-slate-700">
                    No schedules in this range
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    Try widening your date range above.
                </p>
            </div>
        </div>

        <div v-else class="space-y-5">
            <div
                v-for="(day, index) in dayGroups"
                :key="day.date"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                >
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-slate-800">
                            {{ day.dateLabel }}
                        </h3>
                        <span
                            v-if="day.isToday"
                            class="rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-teal-600"
                        >
                            Today
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-slate-400">
                        <span
                            >{{ day.count }} schedule{{
                                day.count !== 1 ? "s" : ""
                            }}</span
                        >
                        <template v-if="day.unassignedCount > 0">
                            <span class="h-1 w-1 rounded-full bg-slate-300" />
                            <span class="font-medium text-rose-500"
                                >{{ day.unassignedCount }} unassigned</span
                            >
                        </template>
                    </div>
                </div>

                <div
                    :ref="
                        (el) => {
                            if (el)
                                timelineContainers[index] = el as HTMLElement;
                        }
                    "
                    class="overflow-x-auto"
                >
                    <div
                        :style="{
                            width: `${labelWidth + day.hours.length * hourWidth}px`,
                        }"
                    >
                        <div class="sticky top-0 z-20 flex h-10 bg-white">
                            <div
                                class="sticky left-0 z-30 shrink-0 border-b border-r border-slate-100 bg-white"
                                :style="{ width: `${labelWidth}px` }"
                            />

                            <div class="flex border-b border-slate-100">
                                <div
                                    v-for="hour in day.hours"
                                    :key="hour.value"
                                    class="flex h-10 shrink-0 items-center justify-center border-r border-slate-100 text-xs font-medium text-slate-400"
                                    :style="{ width: `${hourWidth}px` }"
                                >
                                    {{ hour.label }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-for="(schedule, rowIndex) in schedulesForDay(
                                day.date,
                            )"
                            :key="schedule.schedule_id"
                            class="relative flex border-b border-slate-100 last:border-b-0 transition"
                            :class="rowTheme(rowIndex)"
                            :style="{
                                minHeight: `${Math.max(
                                    110,
                                    (schedule.services?.length || 1) * 110 + 8,
                                )}px`,
                            }"
                        >
                            <div
                                class="sticky left-0 z-10 shrink-0 p-3"
                                :style="{ width: `${labelWidth}px` }"
                                :class="rowTheme(rowIndex)"
                            >
                                <div
                                    class="flex h-full flex-col justify-between gap-3"
                                >
                                    <div class="space-y-4">
                                        <div>
                                            <p
                                                class="text-xs font-medium uppercase tracking-wide text-slate-400"
                                            >
                                                Schedule
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-semibold text-slate-800"
                                            >
                                                {{
                                                    schedule.schedule_code ||
                                                    "—"
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p
                                                class="text-xs uppercase tracking-wide text-slate-400"
                                            >
                                                Date & Time
                                            </p>

                                            <p
                                                class="mt-1 text-sm text-slate-800"
                                            >
                                                {{
                                                    stringToDate(
                                                        schedule.scheduled_at,
                                                    ) || "—"
                                                }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-slate-500"
                                            >
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
                                            @click="
                                                $emit('view-details', schedule)
                                            "
                                        >
                                            View details
                                        </button>

                                        <button
                                            v-if="
                                                schedule.status?.toLowerCase() !==
                                                    'missed' &&
                                                schedule.status?.toLowerCase() !==
                                                    'completed' &&
                                                schedule.services?.some(
                                                    (service) =>
                                                        !service.assignees
                                                            ?.length,
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
                                :style="{
                                    width: `${day.hourColumnCount * hourWidth}px`,
                                }"
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
                                    v-if="
                                        day.isToday && nowOffset(day) !== null
                                    "
                                    class="absolute top-0 z-10 h-full w-px bg-teal-400"
                                    :style="{ left: `${nowOffset(day)}px` }"
                                >
                                    <div
                                        class="absolute -left-1 -top-1 h-2 w-2 rounded-full bg-teal-500"
                                    />
                                </div>

                                <div
                                    v-for="(
                                        service, index
                                    ) in schedule.services"
                                    :key="service.service_id"
                                    class="group absolute flex cursor-pointer flex-col rounded-lg border px-3 py-2 text-xs shadow-sm transition hover:z-10 hover:shadow-md"
                                    :class="
                                        scheduleStatusTheme(schedule.status)
                                            .card
                                    "
                                    :style="{
                                        left: `${getScheduleLeft(schedule, day)}px`,
                                        width: `${getScheduleWidth(schedule)}px`,
                                        top: `${8 + index * 110}px`,
                                        height: '90px',
                                    }"
                                    @click="$emit('view-details', schedule)"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="truncate font-semibold text-slate-800"
                                        >
                                            {{ service.service_name }}
                                        </span>

                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                            :class="
                                                scheduleStatusTheme(
                                                    schedule.status,
                                                ).badge
                                            "
                                        >
                                            {{
                                                schedule.status?.toLowerCase() ===
                                                "completed"
                                                    ? "Complete"
                                                    : schedule.status?.toLowerCase() ===
                                                        "missed"
                                                      ? "Missed"
                                                      : "Pending"
                                            }}
                                        </span>
                                    </div>
                                    <div class="flex gap-2 items-center">
                                        <span
                                            v-if="service.assignees?.length"
                                            class="mt-2 truncate text-slate-600"
                                        >
                                            <span
                                                class="font-medium text-slate-500"
                                                >Assigned:</span
                                            >
                                            {{
                                                service.assignees[0]
                                                    ?.employee_name
                                            }}
                                        </span>

                                        <span
                                            v-else
                                            class="mt-2 truncate text-slate-600"
                                            >Not assigned yet.</span
                                        >
                                    </div>

                                    <div
                                        class="mt-auto flex items-center gap-1 text-[11px] text-slate-400"
                                    >
                                        <svg
                                            width="11"
                                            height="11"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>

                                        {{ schedule.start_time }} –
                                        {{ schedule.end_time }}
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

<script lang="ts" setup>
import { ref, computed, nextTick, onMounted, watch } from "vue";
import type { ScheduleItem } from "~/types/schedule";
import BaseInput from "~/components/ui/BaseInput.vue";
import { ChevronRight } from "lucide-vue-next";
import { stringToDate, formatHour } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        schedules?: ScheduleItem[];
        loading?: boolean;
        date?: string;
        rangeEnd?: string;
        startHour?: number;
        endHour?: number;
        maxRowsHeight?: number;
    }>(),
    {
        schedules: () => [],
        loading: false,
        startHour: 0,
        endHour: 24,
        maxRowsHeight: 420,
    },
);

defineEmits<{
    (e: "view-details", schedule: ScheduleItem): void;
    (e: "assign", schedule: ScheduleItem): void;
}>();

const selectedDate = ref(props.date ?? today());
const rangeEnd = ref(props.rangeEnd ?? props.date ?? today());
const labelWidth = computed(() => 220);
const hourWidth = 200;
const timelineContainers = ref<HTMLElement[]>([]);

function today() {
    return toLocalDateString(new Date());
}

function jumpToToday() {
    const t = today();
    selectedDate.value = t;
    rangeEnd.value = t;
}

function scheduleStatusTheme(status?: string | null) {
    switch (status?.toLowerCase()) {
        case "completed":
            return {
                card: "border-primary/20 bg-primary/5",
                badge: "bg-primary/10 text-primary",
            };

        case "missed":
            return {
                card: "border-rose-200 bg-rose-50",
                badge: "bg-rose-100 text-rose-600",
            };

        case "pending":
        default:
            return {
                card: "border-amber-200 bg-amber-50",
                badge: "bg-amber-100 text-amber-700",
            };
    }
}

function toLocalDateString(d: Date) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function schedulesForDay(date: string) {
    return props.schedules.filter((s) => getScheduleDate(s) === date);
}

function scrollToCurrentTime() {
    const todayGroup = dayGroups.value.find((d) => d.isToday);
    if (!todayGroup) return;

    const offset = nowOffset(todayGroup);
    if (offset == null) return;

    timelineContainers.value.forEach((container) => {
        container.scrollLeft =
            labelWidth.value + offset - container.clientWidth / 2;
    });
}

const activeRange = computed(() => {
    const start = selectedDate.value;
    const end = rangeEnd.value || selectedDate.value;
    return start <= end ? { start, end } : { start: end, end: start };
});

const dateList = computed(() => {
    const { start, end } = activeRange.value;
    if (!start) return [];
    const list: string[] = [];
    const cursor = new Date(`${start}T00:00:00`);
    const endDate = new Date(`${end}T00:00:00`);
    while (cursor <= endDate) {
        list.push(toLocalDateString(cursor));
        cursor.setDate(cursor.getDate() + 1);
    }
    return list;
});

const dayGroups = computed(() => {
    const todayStr = today();

    return dateList.value
        .map((date) => {
            const daySchedules = schedulesForDay(date);

            if (!daySchedules.length) {
                return undefined;
            }

            let minStart = Infinity;
            let maxEnd = -Infinity;
            let unassignedCount = 0;

            for (const schedule of daySchedules) {
                const times = getScheduleTimes(schedule);
                const startMin = parseTimeToMinutes(times.start);

                if (startMin === null) continue;

                const maxServiceDuration = Math.max(
                    0,
                    ...(schedule.services ?? []).map(
                        (s) => s.duration_minutes ?? 0,
                    ),
                );

                minStart = Math.min(minStart, startMin);
                maxEnd = Math.max(maxEnd, startMin + maxServiceDuration);

                // unassignedCount += (schedule.services ?? []).filter(
                //     (s) => !s.assignees?.length,
                // ).length;
            }

            const hasRange =
                Number.isFinite(minStart) && Number.isFinite(maxEnd);

            const visibleStartHour = hasRange
                ? Math.max(props.startHour, Math.floor(minStart / 60))
                : props.startHour;

            const visibleEndHour = hasRange
                ? Math.min(
                      props.endHour,
                      Math.max(
                          Math.ceil(maxEnd / 60) + 1,
                          visibleStartHour + 1,
                      ),
                  )
                : props.endHour;

            const hours = [];

            for (let h = visibleStartHour; h <= visibleEndHour; h++) {
                hours.push({
                    value: h,
                    label: formatHourLabel(h),
                });
            }

            return {
                date,
                dateLabel: formatDate(date),
                count: daySchedules.length,
                unassignedCount,
                isToday: date === todayStr,
                hours,
                hourColumnCount: Math.max(hours.length - 1, 1),
            };
        })
        .filter((day): day is NonNullable<typeof day> => Boolean(day));
});

function nowOffset(day: { hours: { value: number }[] }) {
    const first = day.hours[0];
    const last = day.hours[day.hours.length - 1];
    if (!first || !last) return null;

    const now = new Date();
    const nowMin = now.getHours() * 60 + now.getMinutes();
    const dayStart = first.value * 60;
    const dayEnd = last.value * 60;
    if (nowMin < dayStart || nowMin > dayEnd) return null;

    return ((nowMin - dayStart) / 60) * hourWidth;
}

function getScheduleLeft(schedule: ScheduleItem, day: any) {
    const start = parseTimeToMinutes(schedule.start_time);

    if (start === null) return 0;

    const dayStart = day.hours[0].value * 60;

    return ((start - dayStart) / 60) * hourWidth;
}

function getScheduleWidth(schedule: ScheduleItem) {
    const start = parseTimeToMinutes(schedule.start_time);
    const end = parseTimeToMinutes(schedule.end_time);

    if (start === null || end === null) {
        return hourWidth;
    }

    const duration = end - start;

    return Math.max((duration / 60) * hourWidth, 60);
}

function getScheduleTimes(schedule: ScheduleItem) {
    if (schedule.start_time && schedule.end_time) {
        return { start: schedule.start_time, end: schedule.end_time };
    }
    if (schedule.scheduled_at) {
        const date = new Date(schedule.scheduled_at);
        const hour = date.getHours();
        return { start: formatTime(hour, 0), end: formatTime(hour + 1, 0) };
    }
    return { start: "00:00 AM", end: "11:59 PM" };
}

function formatTime(hour24: number, minute: number) {
    const period = hour24 >= 12 ? "PM" : "AM";
    const hour = hour24 % 12 === 0 ? 12 : hour24 % 12;
    return `${hour}:${String(minute).padStart(2, "0")} ${period}`;
}

function formatDate(dateStr: string) {
    const d = new Date(`${dateStr}T00:00:00`);
    return d.toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "numeric",
    });
}

onMounted(async () => {
    await nextTick();
    scrollToCurrentTime();
});

watch(dayGroups, async () => {
    await nextTick();
    scrollToCurrentTime();
});

function getScheduleDate(schedule: ScheduleItem): string | null {
    const scheduledDate = schedule.scheduled_date?.trim();
    if (scheduledDate) return scheduledDate;

    if (schedule.scheduled_at) {
        const parsed = new Date(schedule.scheduled_at);
        if (!Number.isNaN(parsed.getTime())) return toLocalDateString(parsed);
    }

    return null;
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

function formatHourLabel(hour24: number) {
    const period = hour24 >= 12 ? "PM" : "AM";
    const display = hour24 % 12 === 0 ? 12 : hour24 % 12;
    return `${display} ${period}`;
}

const hasAnySchedules = computed(() => {
    return props.schedules.some((s) => {
        const date = getScheduleDate(s);
        return date && dateList.value.includes(date);
    });
});

function rowTheme(index: number) {
    return index % 2 === 1 ? "bg-slate-50" : "bg-white";
}

const totalCount = computed(
    () =>
        props.schedules.filter((s) => {
            const date = getScheduleDate(s);
            return date && dateList.value.includes(date);
        }).length,
);

const unassignedCount = computed(() =>
    dayGroups.value.reduce((sum, day) => sum + day.unassignedCount, 0),
);
</script>
