<template>
    <div
        v-for="(day, index) in dayGroups"
        :key="day.date"
        class="overflow-hidden bg-white"
    >
        <div
            class="flex cursor-pointer select-none items-center justify-between border-b border-slate-100 px-5 py-4 transition-colors hover:bg-slate-50/60"
            @click="toggleDay(day.date)"
        >
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-semibold text-slate-800">
                    {{ day.dateLabel }}
                </h3>

                <span
                    v-if="day.isToday"
                    class="flex items-center gap-1 rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-teal-600"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-500" />
                    Today
                </span>

                <!-- <span
                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500"
                >
                    {{ day.count }} {{ day.count === 1 ? "visit" : "visits" }}
                </span> -->

                <span
                    v-if="day.unassignedCount"
                    class="flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-600"
                >
                    <svg
                        width="10"
                        height="10"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                    >
                        <path
                            d="M12 2 1 21h22L12 2Zm0 15a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Zm-1-2v-5h2v5h-2Z"
                        />
                    </svg>
                    {{ day.unassignedCount }} unassigned
                </span>
            </div>

            <button
                type="button"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                :aria-label="
                    isCollapsed(day.date) ? 'Expand day' : 'Collapse day'
                "
                @click.stop="toggleDay(day.date)"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="transition-transform duration-200"
                    :class="isCollapsed(day.date) ? '-rotate-90' : ''"
                >
                    <path d="M6 9l6 6 6-6" />
                </svg>
            </button>
        </div>

        <Transition
            name="collapse"
            @before-enter="onBeforeEnter"
            @enter="onEnter"
            @after-enter="onAfterEnter"
            @before-leave="onBeforeLeave"
            @leave="onLeave"
        >
            <div
                v-show="!isCollapsed(day.date)"
                :ref="
                    (el) => {
                        if (el) timelineContainers[index] = el as HTMLElement;
                    }
                "
                class="overflow-x-auto"
            >
                <div
                    v-if="!schedulesForDay(day.date).length"
                    class="flex flex-col items-center justify-center gap-2 px-5 py-10 text-center"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                    >
                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="5" width="18" height="16" rx="2" />
                            <path d="M3 10h18M8 3v4M16 3v4" />
                        </svg>
                    </div>

                    <p class="text-sm font-medium text-slate-500">
                        No visits scheduled for this day
                    </p>
                </div>

                <div
                    v-else
                    class="min-w-full border border-secondary/20"
                    :style="{
                        width: `max(100%, ${labelWidth + day.hours.length * hourWidth}px)`,
                    }"
                >
                    <div
                        class="sticky top-0 z-20 flex h-10 bg-white shadow-[0_1px_0_0_rgba(0,0,0,0.04)]"
                    >
                        <div
                            class="sticky left-0 z-30 shrink-0 border-b border-r border-slate-100 bg-white"
                            :style="{ width: `${labelWidth}px` }"
                        />

                        <div class="flex border-b border-slate-100">
                            <div
                                v-for="hour in day.hours"
                                :key="hour.value"
                                class="flex h-10 shrink-0 items-center justify-center border-r border-secondary/20 text-xs font-medium text-slate-400"
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
                        class="relative flex border-b last:border-b-0 transition"
                        :class="rowTheme(rowIndex)"
                        :style="{
                            minHeight: '72px',
                        }"
                    >
                        <div
                            class="sticky left-0 z-21 shrink-0 border-r border-slate-100/80 px-3 py-2"
                            :style="{ width: `${labelWidth}px` }"
                            :class="rowTheme(rowIndex)"
                        >
                            <div
                                class="flex h-full flex-col justify-between gap-1.5"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-[13.5px] font-semibold text-slate-800"
                                    >
                                        {{ schedule.schedule_code }}
                                    </p>

                                    <p
                                        class="truncate text-[12px] font-medium text-slate-500"
                                    >
                                        {{
                                            schedule.category === "Facility"
                                                ? "Inhouse Facility"
                                                : "Homecare"
                                        }}
                                        <span
                                            v-if="
                                                schedule.patient?.admission?.bed
                                            "
                                        >
                                            •
                                            {{ schedule.patient.admission.bed }}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="flex items-center gap-1 text-[12px] font-medium text-slate-600"
                                    >
                                        <svg
                                            width="11"
                                            height="11"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            class="shrink-0 opacity-50"
                                        >
                                            <rect
                                                x="3"
                                                y="5"
                                                width="18"
                                                height="16"
                                                rx="2"
                                            />
                                            <path d="M3 10h18M8 3v4M16 3v4" />
                                        </svg>
                                        {{
                                            formatDate(schedule.scheduled_at) ||
                                            "—"
                                        }}
                                    </p>

                                    <p
                                        class="flex items-center gap-1 text-[12px] text-slate-400"
                                    >
                                        <svg
                                            width="11"
                                            height="11"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            class="shrink-0 opacity-50"
                                        >
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>
                                        {{ schedule.start_time }}
                                        <span v-if="schedule.end_time">
                                            – {{ schedule.end_time }}
                                        </span>
                                    </p>
                                </div>

                                <div class="flex gap-1.5">
                                    <button
                                        class="flex-1 rounded-md border border-primary/20 bg-primary/[0.03] px-2 py-1 text-[10.5px] font-medium text-primary transition hover:bg-primary/10"
                                        @click="$emit('view-details', schedule)"
                                    >
                                        Details
                                    </button>

                                    <button
                                        v-if="
                                            ![
                                                'missed',
                                                'completed',
                                                'cancelled',
                                            ].includes(
                                                schedule.status?.toLowerCase() ??
                                                    '',
                                            ) &&
                                            schedule.services?.some(
                                                (s) => !s.assignees?.length,
                                            )
                                        "
                                        class="flex-1 rounded-md bg-rose-50 px-2 py-1 text-[10.5px] font-semibold text-rose-600 border-rose-600/20 border transition hover:bg-rose-100"
                                        @click="$emit('assign', schedule)"
                                    >
                                        Assign
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            class="relative flex-1 border border-secondary/20"
                            :style="{
                                width: `${day.hourColumnCount * hourWidth}px`,
                            }"
                        >
                            <div class="absolute inset-0 flex">
                                <div
                                    v-for="hour in day.hours"
                                    :key="hour.value"
                                    class="h-full shrink-0 border-r border-secondary/20 last:border-b-0"
                                    :style="{ width: `${hourWidth}px` }"
                                />
                            </div>

                            <div
                                v-if="day.isToday && nowOffset(day) !== null"
                                class="absolute top-0 z-20 h-full w-0.5 bg-teal-500 shadow-[0_0_6px_rgba(20,184,166,0.5)]"
                                :style="{ left: `${nowOffset(day)}px` }"
                            >
                                <div
                                    class="absolute -left-[5px] -top-1 h-2.5 w-2.5 rounded-full bg-teal-500 ring-2 ring-white"
                                />

                                <div
                                    class="absolute -left-5 -top-6 whitespace-nowrap rounded-full bg-teal-500 px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm"
                                >
                                    Now
                                </div>
                            </div>

                            <template v-if="schedule.services?.length">
                                <div
                                    v-for="(
                                        service, sIndex
                                    ) in schedule.services"
                                    :key="
                                        service.schedule_services_id ?? sIndex
                                    "
                                    class="group absolute flex cursor-pointer flex-col justify-center gap-1 rounded-lg border border-slate-200/80 px-2.5 py-1.5 text-[11px] shadow-sm transition-all duration-150 hover:z-10 hover:-translate-y-0.5 hover:shadow-md"
                                    :class="
                                        scheduleStatusTheme(schedule.status)
                                            .card
                                    "
                                    :style="{
                                        left: `${getServiceLeft(schedule, sIndex, day)}px`,
                                        width: `${getServiceWidth(service)}px`,
                                        top: '4px',
                                        height: '60px',
                                    }"
                                    @click="$emit('view-details', schedule)"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-1.5"
                                        >
                                            <span
                                                v-if="service.assignees?.length"
                                                class="flex shrink-0 items-center -space-x-1.5"
                                            >
                                                <span
                                                    v-for="(
                                                        assignee, aIndex
                                                    ) in service.assignees.slice(
                                                        0,
                                                        3,
                                                    )"
                                                    :key="assignee.employee_id"
                                                    class="flex h-[32px] w-[32px] shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-primary text-[8px] font-bold text-white"
                                                    :title="assignee.full_name"
                                                >
                                                    <img
                                                        v-if="assignee.avatar"
                                                        :src="assignee.avatar"
                                                        :alt="
                                                            assignee.full_name
                                                        "
                                                        class="h-full w-full object-cover"
                                                    />
                                                    <template v-else>
                                                        {{
                                                            initials(
                                                                assignee.full_name,
                                                            )
                                                        }}
                                                    </template>
                                                </span>
                                                <span
                                                    v-if="
                                                        (service.assignees
                                                            ?.length ?? 0) > 3
                                                    "
                                                    class="flex items-center justify-center rounded-full border-2 border-white bg-slate-200 px-1.5 py-0.5 text-[9px] font-semibold text-slate-600"
                                                    :title="
                                                        service.assignees
                                                            .slice(3)
                                                            .map(
                                                                (a) =>
                                                                    a.full_name,
                                                            )
                                                            .join(', ')
                                                    "
                                                >
                                                    +{{
                                                        (service.assignees
                                                            ?.length ?? 0) - 3
                                                    }}
                                                </span>
                                            </span>

                                            <span
                                                v-if="service.assignees?.length"
                                                class="truncate text-[12px] font-medium"
                                                :class="
                                                    scheduleStatusTheme(
                                                        schedule.status,
                                                    ).accent
                                                "
                                            >
                                                {{
                                                    service.assignees[0]
                                                        ?.full_name
                                                }}
                                                <span
                                                    v-if="
                                                        (service.assignees
                                                            ?.length ?? 0) > 1
                                                    "
                                                    class="text-slate-500"
                                                >
                                                    +{{
                                                        (service.assignees
                                                            ?.length ?? 0) - 1
                                                    }}
                                                    more
                                                </span>
                                            </span>

                                            <span
                                                v-if="
                                                    !service.assignees?.length
                                                "
                                                class="truncate italic text-[11px] text-slate-400"
                                            >
                                                Not assigned yet
                                            </span>
                                        </div>

                                        <span
                                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                            :class="
                                                scheduleStatusTheme(
                                                    schedule.status,
                                                ).badge
                                            "
                                        >
                                            {{
                                                scheduleStatusLabel(
                                                    schedule.status,
                                                )
                                            }}
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
                                            {{ schedule.start_time }} –
                                            {{ schedule.end_time }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <div
                                v-else
                                class="group absolute flex cursor-pointer flex-col justify-center gap-1 rounded-xl border border-slate-200/60 px-4 py-2.5 text-xs shadow-sm transition-all duration-150 hover:z-10 hover:-translate-y-0.5 hover:shadow-md"
                                :class="
                                    scheduleStatusTheme(schedule.status).card
                                "
                                :style="{
                                    left: `${getScheduleLeft(schedule, day)}px`,
                                    width: `${getScheduleWidth(schedule)}px`,
                                    top: '4px',
                                    height: '60px',
                                }"
                                @click="$emit('view-details', schedule)"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="truncate text-[13px] font-semibold text-slate-800"
                                    >
                                        {{
                                            schedule.category ||
                                            schedule.type ||
                                            "Schedule"
                                        }}
                                    </span>

                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="
                                            scheduleStatusTheme(schedule.status)
                                                .badge
                                        "
                                    >
                                        {{
                                            scheduleStatusLabel(schedule.status)
                                        }}
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
                                        {{ schedule.start_time }} –
                                        {{ schedule.end_time }}
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
                </div>
            </div>
        </Transition>
    </div>
</template>

<script lang="ts" setup>
import { ref, nextTick, onMounted, onBeforeUnmount, watch } from "vue";

import type { ScheduleItem } from "~/types/schedule";
import { initials } from "~/utils/user";
import { useSchedule } from "~/composables/useSchedule";

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
        maxRowsHeight: 150,
    },
);

const emit = defineEmits<{
    (e: "view-details", schedule: ScheduleItem): void;
    (e: "assign", schedule: ScheduleItem): void;
    (e: "update-range", payload: { from: string; to: string }): void;
}>();

const {
    labelWidth,
    hourWidth,

    selectedDate,
    rangeEnd,

    dayGroups,

    today,

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

const timelineContainers = ref<HTMLElement[]>([]);
const expandedDays = ref<Set<string>>(new Set([today()]));

function isCollapsed(date: string) {
    return !expandedDays.value.has(date);
}

function toggleDay(date: string) {
    const next = new Set(expandedDays.value);

    if (next.has(date)) {
        next.delete(date);
    } else {
        next.add(date);
    }

    expandedDays.value = next;
}

function scrollToCurrentTime(smooth = true) {
    const todayIndex = dayGroups.value.findIndex((d) => d.isToday);

    if (todayIndex === -1) return;

    const todayGroup = dayGroups.value[todayIndex];

    if (!todayGroup || isCollapsed(todayGroup.date)) return;

    const offset = nowOffset(todayGroup);

    if (offset == null) return;

    const container = timelineContainers.value[todayIndex];

    if (!container) return;

    const target = labelWidth.value + offset - container.clientWidth / 2;

    container.scrollTo({
        left: Math.max(0, target),
        behavior: smooth ? "smooth" : "auto",
    });
}

const handleResize = () => scrollToCurrentTime(false);

function onBeforeEnter(el: Element) {
    const e = el as HTMLElement;
    e.style.height = "0px";
    e.style.overflow = "hidden";
}

function onEnter(el: Element, done: () => void) {
    const e = el as HTMLElement;

    requestAnimationFrame(() => {
        e.style.transition = "height .25s ease";
        e.style.height = `${e.scrollHeight}px`;
    });

    const onEnd = () => {
        e.style.height = "";
        e.style.overflow = "";
        e.style.transition = "";
        e.removeEventListener("transitionend", onEnd);
        done();
    };

    e.addEventListener("transitionend", onEnd);
}

function onAfterEnter(el: Element) {
    const e = el as HTMLElement;
    e.style.height = "";
    e.style.overflow = "";
    e.style.transition = "";
}

function onBeforeLeave(el: Element) {
    const e = el as HTMLElement;
    e.style.height = `${e.scrollHeight}px`;
    e.style.overflow = "hidden";
}

function onLeave(el: Element, done: () => void) {
    const e = el as HTMLElement;

    requestAnimationFrame(() => {
        e.style.transition = "height .25s ease";
        e.style.height = "0px";
    });

    const onEnd = () => {
        e.removeEventListener("transitionend", onEnd);
        done();
    };

    e.addEventListener("transitionend", onEnd);
}

watch(
    [selectedDate, rangeEnd],
    ([from, to]) => {
        emit("update-range", {
            from,
            to,
        });
    },
    { immediate: true },
);

watch(dayGroups, async () => {
    await nextTick();
    scrollToCurrentTime(false);
});

watch(
    () => props.loading,
    async (loading) => {
        if (loading) return;

        await nextTick();
        scrollToCurrentTime(false);
    },
);

watch(
    () => props.schedules,
    async () => {
        await nextTick();
        scrollToCurrentTime(false);
    },
    { deep: true },
);

onMounted(async () => {
    await nextTick();

    scrollToCurrentTime(false);

    requestAnimationFrame(() => {
        setTimeout(() => scrollToCurrentTime(true), 150);
    });

    window.addEventListener("resize", handleResize);

    startNowTicker();
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleResize);

    stopNowTicker();
});
</script>
