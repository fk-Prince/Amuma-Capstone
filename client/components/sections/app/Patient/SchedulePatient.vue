<template>
    <div class="flex flex-col gap-5">
        <div v-if="loading" class="space-y-4">
            <div
                v-for="i in 3"
                :key="i"
                class="animate-pulse rounded-2xl bg-white p-5 dark:bg-secondary"
            >
                <div class="mb-4 h-4 w-40 rounded bg-slate-100 dark:bg-white/10" />
                <div class="space-y-3">
                    <div class="h-16 rounded-xl bg-slate-100 dark:bg-white/10" />
                    <div class="h-16 rounded-xl bg-slate-100 dark:bg-white/10" />
                </div>
            </div>
        </div>

        <div
            v-else-if="!hasAnySchedules"
            class="flex flex-col items-center gap-3 rounded-2xl bg-white p-12 text-center dark:bg-secondary"
        >
            <div
                class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
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
                <p class="font-medium text-slate-700 dark:text-gray-400">
                    No schedules in this range
                </p>
                <p class="mt-1 text-sm text-slate-400 dark:text-gray-500">
                    Try widening your date range above.
                </p>
            </div>
        </div>

        <div v-else class="space-y-5">
            <div
                v-for="(day, index) in dayGroups"
                :key="day.date"
                class="overflow-hidden rounded-2xl border mb-5 border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-slate-800 dark:text-white">
                            {{ day.dateLabel }}
                        </h3>
                        <span
                            v-if="day.isToday"
                            class="rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-teal-600 dark:bg-teal-500/15 dark:text-teal-300"
                        >
                            Today
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-slate-400 dark:text-gray-500">
                        <span
                            >{{ day.count }} schedule{{
                                day.count !== 1 ? "s" : ""
                            }}</span
                        >
                        <template v-if="day.unassignedCount > 0">
                            <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-white/20" />
                            <span class="font-medium text-rose-500 dark:text-rose-300"
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
                    class="relative overflow-x-auto"
                >
                    <div
                        class="relative min-w-full"
                        :style="{
                            width: `max(100%, ${labelWidth + day.hours.length * hourWidth}px)`,
                        }"
                    >
                        <!-- One continuous marker for the whole day rather than
                             one per row, and below the sticky label column so it
                             slides under it when scrolled. -->
                        <div
                            v-if="day.isToday && nowOffset(day) !== null"
                            class="pointer-events-none absolute bottom-0 z-[5] w-px bg-teal-400"
                            :style="{
                                left: `${labelWidth + (nowOffset(day) ?? 0)}px`,
                                top: '2.5rem',
                            }"
                        >
                            <div
                                class="absolute -left-1 -top-1 h-2 w-2 rounded-full bg-teal-500"
                            />
                        </div>

                        <div class="sticky top-0 z-20 flex h-10 bg-white dark:bg-secondary">
                            <!-- The pill lives in the hour ruler rather than
                                 above the line: anchored to the line's top it
                                 rendered underneath this sticky header. -->
                            <div
                                v-if="day.isToday && nowOffset(day) !== null"
                                class="pointer-events-none absolute top-1/2 z-20 -translate-x-1/2 -translate-y-1/2 whitespace-nowrap rounded-full bg-teal-500 px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm"
                                :style="{
                                    left: `${labelWidth + (nowOffset(day) ?? 0)}px`,
                                }"
                            >
                                Now
                            </div>

                            <div
                                class="sticky left-0 z-30 shrink-0 border-b border-r border-slate-100 bg-white dark:border-white/10 dark:bg-secondary"
                                :style="{ width: `${labelWidth}px` }"
                            />

                            <div class="flex border-b border-slate-100 dark:border-white/10">
                                <div
                                    v-for="hour in day.hours"
                                    :key="hour.value"
                                    class="flex h-10 shrink-0 items-center justify-center border-r border-slate-100 text-xs font-medium text-slate-400 dark:border-white/10 dark:text-gray-500"
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
                            class="relative flex border-b border-slate-100 last:border-b-0 transition dark:border-white/10"
                            :class="rowTheme(rowIndex)"
                            :style="{
                                minHeight: rem(rowHeight(schedule)),
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
                                    <div class="min-w-0 space-y-1">
                                        <p
                                            class="text-[11px] font-medium uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                        >
                                            Schedule
                                        </p>

                                        <p
                                            class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                        >
                                            {{ schedule.schedule_code || "—" }}
                                        </p>

                                        <!-- The day header above already states
                                             the date, so the row only needs the
                                             time. -->
                                        <button
                                            type="button"
                                            title="Jump to this time on the timeline"
                                            class="-mx-1 mt-1 flex items-center gap-1 rounded-md px-1 py-0.5 text-left text-xs font-medium text-slate-600 transition hover:bg-primary/10 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:text-gray-400 dark:hover:text-primary"
                                            @click.stop="
                                                scrollToSchedule(schedule, day, index)
                                            "
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

                                            {{ schedule.start_time ?? "—" }}
                                            <span
                                                v-if="schedule.end_time"
                                                class="text-slate-400 dark:text-gray-500"
                                            >
                                                – {{ schedule.end_time }}
                                            </span>
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="flex-1 rounded-md border border-primary/20 bg-white px-2.5 py-1.5 text-[11px] font-medium text-primary transition hover:bg-primary/5 dark:bg-secondary"
                                            @click="
                                                $emit('view-details', schedule)
                                            "
                                        >
                                            View details
                                        </button>

                                        <button
                                            v-if="
                                                canAssignSchedule &&
                                                ![
                                                    'missed',
                                                    'completed',
                                                    'cancelled',
                                                ].includes(
                                                    schedule.status?.toLowerCase() ??
                                                        '',
                                                ) &&
                                                schedule.services?.some(
                                                    (service) =>
                                                        !service.assignees
                                                            ?.length,
                                                )
                                            "
                                            type="button"
                                            class="flex-1 rounded-md border border-rose-600/20 bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/15"
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

                                <template v-if="schedule.services?.length">
                                    <div
                                        v-for="(
                                            service, sIndex
                                        ) in schedule.services"
                                        :key="
                                            service.schedule_services_id ??
                                            sIndex
                                        "
                                        class="absolute flex cursor-pointer flex-col justify-between rounded-md border border-slate-300 bg-slate-100 px-3 py-2 shadow-sm transition hover:border-primary/50 hover:shadow-md dark:border-white/25 dark:bg-white/[0.04] dark:shadow-none dark:hover:border-white/50 dark:hover:shadow-none"
                                        :class="[
                                            isCancelled(schedule)
                                                ? 'opacity-60'
                                                : '',
                                            highlightedScheduleId === schedule.schedule_id
                                                ? 'ring-2 ring-primary/60'
                                                : '',
                                        ]"
                                        :style="{
                                            left: `${getServiceLeft(schedule, sIndex, day)}px`,
                                            width: `${getServiceWidth(service)}px`,
                                            top: rem(8 + sIndex * SERVICE_STRIDE),
                                            height: rem(SERVICE_HEIGHT),
                                        }"
                                        @click="$emit('view-details', schedule)"
                                    >
                                        <div>
                                            <p
                                                v-if="schedule.scheduled_date"
                                                class="text-[10px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400"
                                            >
                                                {{ formatDate(schedule.scheduled_date) }}
                                            </p>

                                            <div class="flex items-center justify-between gap-2">
                                                <span class="truncate text-[12px] font-semibold tabular-nums text-slate-800 dark:text-white">
                                                    {{ schedule.start_time }} – {{ schedule.end_time }}
                                                </span>

                                                <span
                                                    class="flex shrink-0 items-center gap-1.5 text-[11px] font-medium"
                                                    :class="scheduleStatusTheme(schedule.status).accent"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full"
                                                        :class="scheduleStatusTheme(schedule.status).dot"
                                                    />
                                                    {{ scheduleStatusLabel(schedule.status) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            v-if="service.assignees?.length"
                                            class="flex min-w-0 items-center gap-2"
                                        >
                                            <span class="flex shrink-0 items-center -space-x-1.5">
                                                <span
                                                    v-for="assignee in service.assignees.slice(0, 3)"
                                                    :key="assignee.employee_id"
                                                    class="flex h-5 w-5 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white bg-primary text-[8px] font-bold text-white dark:border-secondary"
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
                                            </span>

                                            <span class="truncate text-[12px] text-slate-600 dark:text-gray-300">
                                                {{ service.assignees[0]?.full_name }}
                                                <span
                                                    v-if="service.assignees.length > 1"
                                                    class="text-slate-400 dark:text-gray-500"
                                                >
                                                    +{{ service.assignees.length - 1 }}
                                                </span>
                                            </span>
                                        </div>

                                        <p
                                            v-else
                                            class="text-[12px] font-medium text-amber-600 dark:text-amber-400"
                                        >
                                            Unassigned
                                        </p>
                                    </div>
                                </template>

                                <div
                                    v-else
                                    class="absolute flex cursor-pointer flex-col justify-between rounded-md border border-slate-300 bg-slate-100 px-3 py-2 shadow-sm transition hover:border-primary/50 hover:shadow-md dark:border-white/25 dark:bg-white/[0.04] dark:shadow-none dark:hover:border-white/50 dark:hover:shadow-none"
                                    :class="[
                                        isCancelled(schedule) ? 'opacity-60' : '',
                                        highlightedScheduleId === schedule.schedule_id
                                            ? 'ring-2 ring-primary/60'
                                            : '',
                                    ]"
                                    :style="{
                                        left: `${getScheduleLeft(schedule, day)}px`,
                                        width: `${getScheduleWidth(schedule)}px`,
                                        top: '0.5rem',
                                        height: '5.625rem',
                                    }"
                                    @click="$emit('view-details', schedule)"
                                >
                                    <div>
                                        <p
                                            v-if="schedule.scheduled_date"
                                            class="text-[10px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400"
                                        >
                                            {{ formatDate(schedule.scheduled_date) }}
                                        </p>

                                        <div class="flex items-center justify-between gap-2">
                                            <span class="truncate text-[12px] font-semibold tabular-nums text-slate-800 dark:text-white">
                                                {{ schedule.start_time }} – {{ schedule.end_time }}
                                            </span>

                                            <span
                                                class="flex shrink-0 items-center gap-1.5 text-[11px] font-medium"
                                                :class="scheduleStatusTheme(schedule.status).accent"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full"
                                                    :class="scheduleStatusTheme(schedule.status).dot"
                                                />
                                                {{ scheduleStatusLabel(schedule.status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <p class="truncate text-[12px] text-slate-600 dark:text-gray-300">
                                        {{ schedule.patient?.full_name ?? schedule.category ?? "Schedule" }}
                                    </p>
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
import {
    ref,
    computed,
    nextTick,
    onMounted,
    onBeforeUnmount,
    watch,
} from "vue";
import type { ScheduleItem } from "~/types/schedule";
import { initials } from "~/utils/user";
import { useSchedule } from "~/composables/useSchedule";
import { formatDate } from "~/utils/time";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";
import { rem } from "~/utils/rem";

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
        maxRowsHeight: 200,
    },
);

defineEmits<{
    (e: "view-details", schedule: ScheduleItem): void;
    (e: "assign", schedule: ScheduleItem): void;
}>();

const { canAssign } = usePermissions();
const canAssignSchedule = computed(() => canAssign(Modules.Schedules));

const isCancelled = (schedule: ScheduleItem) =>
    schedule.status?.toLowerCase() === "cancelled";

const {
    labelWidth,
    hourWidth,
    dayGroups,
    hasAnySchedules,
    selectedDate,
    rangeEnd,
    schedulesForDay,
    getServiceLeft,
    getServiceWidth,
    getScheduleLeft,
    getScheduleWidth,
    nowOffset,
    startNowTicker,
    stopNowTicker,
    rowTheme,
    scheduleStatusTheme,
    scheduleStatusLabel,
} = useSchedule(props);

watch(
    () => props.date,
    (value) => {
        if (value) selectedDate.value = value;
    },
    { immediate: true },
);

watch(
    () => props.rangeEnd,
    (value) => {
        if (value) rangeEnd.value = value;
    },
    { immediate: true },
);

/**
 * Service cards are absolutely positioned, so the row has to reserve exactly
 * the space they occupy. The stride and the card height were previously
 * hardcoded to different values (110 vs 130), which left a dead gap under the
 * last card in every row.
 */
const SERVICE_HEIGHT = 96;
const SERVICE_STRIDE = 110;
const ROW_PADDING = 16;

function rowHeight(schedule: ScheduleItem) {
    const count = schedule.services?.length || 1;

    return (count - 1) * SERVICE_STRIDE + SERVICE_HEIGHT + ROW_PADDING;
}

const timelineContainers = ref<HTMLElement[]>([]);

const highlightedScheduleId = ref<number | null>(null);
let highlightTimer: ReturnType<typeof setTimeout> | undefined;

function scrollToSchedule(
    schedule: ScheduleItem,
    day: (typeof dayGroups.value)[number],
    dayIndex: number,
) {
    const container = timelineContainers.value[dayIndex];

    if (!container) return;

    container.scrollTo({
        left: Math.max(
            0,
            labelWidth.value +
                getScheduleLeft(schedule, day) -
                container.clientWidth / 2 +
                getScheduleWidth(schedule) / 2,
        ),
        behavior: "smooth",
    });

    highlightedScheduleId.value = schedule.schedule_id;
    clearTimeout(highlightTimer);
    highlightTimer = setTimeout(() => {
        highlightedScheduleId.value = null;
    }, 1600);
}

function scrollToCurrentTime() {
    const todayIndex = dayGroups.value.findIndex((d) => d.isToday);
    if (todayIndex === -1) return;

    const todayGroup = dayGroups.value[todayIndex];
    if (!todayGroup) return;

    const offset = nowOffset(todayGroup);
    if (offset == null) return;

    const container = timelineContainers.value[todayIndex];
    if (!container) return;

    container.scrollLeft =
        labelWidth.value + offset - container.clientWidth / 2;
}

watch(dayGroups, async () => {
    await nextTick();
    scrollToCurrentTime();
});

watch(
    () => props.loading,
    async (isLoading) => {
        if (isLoading) return;
        await nextTick();
        scrollToCurrentTime();
    },
);

watch(
    () => props.schedules,
    async () => {
        await nextTick();
        scrollToCurrentTime();
    },
    { deep: true },
);

onMounted(async () => {
    await nextTick();
    scrollToCurrentTime();
    window.addEventListener("resize", scrollToCurrentTime);
    startNowTicker();
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", scrollToCurrentTime);
    stopNowTicker();
});
</script>
