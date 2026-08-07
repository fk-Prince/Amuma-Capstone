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
                total schedule{{ totalCount !== 1 ? "s" : "" }}
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
                class="overflow-hidden rounded-2xl border mb-5 border-slate-200 bg-white shadow-sm"
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
                    class="relative overflow-x-auto"
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
                                <div
                                    class="flex h-full flex-col justify-between gap-3"
                                >
                                    <div
                                        class="space-y-4 flex gap-3 items-center"
                                    >
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
                                                v-if="schedule.scheduled_at"
                                                class="mt-1 text-sm text-slate-800"
                                            >
                                                {{
                                                    formatDate(
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

                                <template v-if="schedule.services?.length">
                                    <div
                                        v-for="(
                                            service, sIndex
                                        ) in schedule.services"
                                        :key="
                                            service.schedule_services_id ??
                                            sIndex
                                        "
                                        class="group absolute flex cursor-pointer flex-col justify-center gap-1 rounded-xl border-r border-t border-b border-slate-200/60 px-4 py-2.5 text-xs shadow-sm transition-all duration-150 hover:z-10 hover:-translate-y-0.5 hover:shadow-md"
                                        :class="
                                            scheduleStatusTheme(schedule.status)
                                                .card
                                        "
                                        :style="{
                                            left: `${getServiceLeft(schedule, sIndex, day)}px`,
                                            width: `${getServiceWidth(service)}px`,
                                            top: `${8 + sIndex * 110}px`,
                                            height: '90px',
                                        }"
                                        @click="$emit('view-details', schedule)"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="truncate text-[13px] font-semibold text-slate-800"
                                            >
                                                {{ service.service_name }}
                                            </span>

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
                                                    class="flex h-4.5 w-4.5 items-center justify-center rounded-full border-2 border-white bg-slate-200 text-[8px] font-bold text-slate-600"
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
                                                class="truncate text-[11px] font-medium"
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
                                    </div>
                                </template>

                                <div
                                    v-else
                                    class="group absolute flex cursor-pointer flex-col justify-center gap-1 rounded-xl border-r border-t border-b border-slate-200/60 px-4 py-2.5 text-xs shadow-sm transition-all duration-150 hover:z-10 hover:-translate-y-0.5 hover:shadow-md"
                                    :class="
                                        scheduleStatusTheme(schedule.status)
                                            .card
                                    "
                                    :style="{
                                        left: `${getScheduleLeft(schedule, day)}px`,
                                        width: `${getScheduleWidth(schedule)}px`,
                                        top: '8px',
                                        height: '90px',
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
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, nextTick, onMounted, onBeforeUnmount, watch } from "vue";
import type { ScheduleItem } from "~/types/schedule";
import BaseInput from "~/components/ui/BaseInput.vue";
import { ChevronRight } from "lucide-vue-next";
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
        maxRowsHeight: 200,
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
    dayGroups,
    hasAnySchedules,
    totalCount,
    unassignedCount,
    selectedDate,
    rangeEnd,
    jumpToToday,
    formatDate,
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

watch(
    [selectedDate, rangeEnd],
    ([from, to]) => {
        emit("update-range", { from, to });
    },
    { immediate: true },
);

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
