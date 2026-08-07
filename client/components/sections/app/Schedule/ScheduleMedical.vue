<template>
    <div
        v-for="(day, index) in dayGroups"
        :key="day.date"
        class="overflow-hidden bg-white"
    >
        <div
            class="flex cursor-pointer select-none items-center justify-between border-b border-slate-100 px-5 py-4"
            @click="toggleDay(day.date)"
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
                <span
                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500"
                >
                    {{ day.count }}
                </span>
                <span
                    v-if="day.unassignedCount"
                    class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-600"
                >
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
                    class="min-w-full"
                    :style="{
                        width: `max(100%, ${labelWidth + day.hours.length * hourWidth}px)`,
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
                            minHeight: '72px',
                        }"
                    >
                        <div
                            class="sticky left-0 z-10 shrink-0 px-2 py-1.5"
                            :style="{ width: `${labelWidth}px` }"
                            :class="rowTheme(rowIndex)"
                        >
                            <div
                                class="flex h-full flex-col justify-between gap-1"
                            >
                                <div>
                                    <p
                                        class="text-[14px] font-medium text-black"
                                    >
                                        {{ schedule.schedule_code }}
                                    </p>

                                    <p
                                        class="text-[14px] font-medium text-black"
                                    >
                                        {{
                                            schedule.category === "Facility"
                                                ? "Inhouse Facility"
                                                : "Homecare"
                                        }}
                                        {{ schedule.patient?.admission?.bed }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[12px] font-medium text-slate-500"
                                    >
                                        {{
                                            formatDate(schedule.scheduled_at) ||
                                            "—"
                                        }}
                                    </p>

                                    <p class="text-[12px] text-slate-400">
                                        {{ schedule.start_time }}
                                        <span v-if="schedule.end_time">
                                            – {{ schedule.end_time }}
                                        </span>
                                    </p>
                                </div>

                                <div class="flex gap-1">
                                    <button
                                        class="flex-1 rounded border border-primary/20 px-2 py-1 text-[10px] text-primary"
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
                                        class="flex-1 rounded bg-rose-50 px-2 py-1 text-[10px] font-medium text-rose-600"
                                        @click="$emit('assign', schedule)"
                                    >
                                        Assign
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
                                    v-for="(
                                        service, sIndex
                                    ) in schedule.services"
                                    :key="
                                        service.schedule_services_id ?? sIndex
                                    "
                                    class="group absolute flex cursor-pointer flex-col justify-center gap-0.5 rounded-lg border border-slate-200 px-2 py-1.5 text-[11px] shadow-sm"
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
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                v-if="service.assignees?.length"
                                                class="flex items-center -space-x-1.5"
                                            >
                                                <span
                                                    v-for="(
                                                        assignee, aIndex
                                                    ) in service.assignees.slice(
                                                        0,
                                                        3,
                                                    )"
                                                    :key="assignee.employee_id"
                                                    class="flex h-4.5 w-4.5 items-center justify-center overflow-hidden rounded-full border-2 border-white text-[8px] font-bold text-white"
                                                    :title="assignee.full_name"
                                                >
                                                    <img
                                                        v-if="assignee.avatar"
                                                        :src="assignee.avatar"
                                                        :alt="
                                                            assignee.full_name
                                                        "
                                                        class="h-6 w-6 object-cover"
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
                                                    class="flex items-center justify-center rounded-full border border-white bg-slate-200 px-2 py-0.5 text-[12px] font-semibold text-slate-600"
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
                                                    Medical Staff assigned
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
                                                assigned
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
                                                class="italic text-[11px] text-slate-400"
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
                                class="group absolute flex cursor-pointer flex-col justify-center gap-1 rounded-xl border-r border-t border-b border-slate-200/60 px-4 py-2.5 text-xs shadow-sm transition-all duration-150 hover:z-10 hover:-translate-y-0.5 hover:shadow-md"
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

// IMPORTANT: pass the reactive `props` object straight through. Building a
// new plain object here (e.g. `{ schedules: props.schedules, ... }`) takes a
// one-time snapshot of the values at setup time and is NOT reactive — the
// composable would never see later prop updates (like schedules arriving
// after an async fetch), so nothing would ever render.
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

function scrollToCurrentTime() {
    const todayIndex = dayGroups.value.findIndex((d) => d.isToday);

    if (todayIndex === -1) return;

    const todayGroup = dayGroups.value[todayIndex];

    if (!todayGroup || isCollapsed(todayGroup.date)) return;

    const offset = nowOffset(todayGroup);

    if (offset == null) return;

    const container = timelineContainers.value[todayIndex];

    if (!container) return;

    container.scrollLeft =
        labelWidth.value + offset - container.clientWidth / 2;
}

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
    scrollToCurrentTime();
});

watch(
    () => props.loading,
    async (loading) => {
        if (loading) return;

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
