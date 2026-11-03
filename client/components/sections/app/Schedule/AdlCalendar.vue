<template>
    <div ref="root" class="flex flex-col gap-4 p-3 sm:p-5">
        <CalendarMonthBar
            :title="monthTitle"
            :month-name="monthName"
            :partial="partialMonth"
            @prev="shiftMonth(-1)"
            @next="shiftMonth(1)"
            @today="goToToday(); selectedDate = todayIso"
            @whole-month="showWholeMonth"
        >
            <template #chips>
                <slot name="actions" />
                <span
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-white/10 dark:text-gray-300"
                >
                    {{ monthPlans.length }} ADL {{ monthPlans.length === 1 ? "plan" : "plans" }}
                </span>
                <span
                    v-if="needStaffCount"
                    class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    <UserX class="h-3 w-3" />
                    {{ needStaffCount }} need staff
                </span>
            </template>
            <template #legend>
                <span
                    v-for="item in LEGEND"
                    :key="item.label"
                    class="inline-flex items-center gap-1.5"
                >
                    <span class="h-2 w-2 rounded-full" :class="item.dot" />
                    {{ item.label }}
                </span>
            </template>
        </CalendarMonthBar>

        <div
            :class="
                wide
                    ? 'grid grid-cols-[minmax(0,1fr)_22rem] items-start gap-4'
                    : 'flex flex-col gap-4'
            "
        >
            <div class="overflow-x-auto">
                <div
                    class="relative min-w-[640px] overflow-hidden rounded-2xl border border-slate-200 dark:border-white/10"
                >
                    <div
                        v-if="loading"
                        class="absolute inset-x-0 top-0 z-10 h-0.5 animate-pulse bg-primary"
                    />

                    <div
                        class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/5"
                    >
                        <span
                            v-for="name in WEEKDAYS"
                            :key="name"
                            class="py-2 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-gray-400"
                        >
                            {{ name }}
                        </span>
                    </div>

                    <div
                        v-for="week in weeks"
                        :key="week.days[0]!.iso"
                        class="relative border-b border-slate-100 last:border-b-0 dark:border-white/[0.06]"
                    >
                        <div class="grid grid-cols-7">
                            <div
                                v-for="(cell, i) in week.days"
                                :key="cell.iso"
                                role="button"
                                tabindex="0"
                                :aria-pressed="cell.iso === selectedDate"
                                class="cursor-pointer border-r border-slate-100 p-1.5 outline-none transition focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary dark:border-white/[0.06]"
                                :class="[
                                    i === 6 ? 'border-r-0' : '',
                                    !cell.inMonth
                                        ? 'bg-slate-50/60 dark:bg-white/[0.02]'
                                        : !inRange(cell.iso)
                                          ? 'calendar-unloaded'
                                          : cell.iso === selectedDate
                                            ? 'bg-primary-50/70 dark:bg-primary-500/10'
                                            : 'hover:bg-slate-50 dark:hover:bg-white/[0.03]',
                                ]"
                                :style="{ minHeight: `${Math.max(112, 40 + week.lanes * 28)}px` }"
                                @click="selectDay(cell.iso)"
                                @keydown.enter.prevent="selectDay(cell.iso)"
                                @keydown.space.prevent="selectDay(cell.iso)"
                            >
                                <div class="flex items-center justify-between gap-1">
                                    <span
                                        class="flex h-6 min-w-[1.5rem] items-center justify-center rounded-full px-1 text-xs font-semibold tabular-nums"
                                        :class="
                                            !cell.inMonth
                                                ? 'text-slate-300 dark:text-gray-600'
                                                : cell.iso === todayIso
                                                  ? 'bg-primary text-white'
                                                  : 'text-slate-700 dark:text-gray-200'
                                        "
                                    >
                                        {{ cell.day }}
                                    </span>
                                    <span
                                        v-if="cell.inMonth && cell.active"
                                        class="text-[10px] font-semibold text-slate-400 dark:text-gray-500"
                                    >
                                        {{ cell.active }} active
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="pointer-events-none absolute inset-x-0 top-9 grid grid-cols-7 gap-y-1"
                        >
                            <button
                                v-for="bar in week.bars"
                                :key="bar.plan.schedule.schedule_id"
                                type="button"
                                class="pointer-events-auto mx-0.5 flex h-6 min-w-0 items-center gap-1 border-l-[3px] px-2 text-left text-[11px] font-semibold shadow-sm transition hover:brightness-95"
                                :class="[
                                    chipClass(bar.plan.schedule),
                                    bar.continuesLeft ? 'rounded-l-none border-l-0' : 'rounded-l-md',
                                    bar.continuesRight ? 'rounded-r-none' : 'rounded-r-md',
                                    selectedId === bar.plan.schedule.schedule_id
                                        ? 'ring-2 ring-primary ring-offset-1 dark:ring-offset-secondary'
                                        : '',
                                ]"
                                :style="{
                                    gridColumn: `${bar.col} / span ${bar.span}`,
                                    gridRow: bar.lane + 1,
                                }"
                                :title="`${bar.plan.patient} · ${bar.plan.label}`"
                                @click.stop="selectPlan(bar.plan)"
                            >
                                <ChevronLeft
                                    v-if="bar.continuesLeft"
                                    class="h-3 w-3 shrink-0 opacity-60"
                                />
                                <span class="truncate">{{ bar.plan.patient }}</span>
                                <span class="hidden truncate font-normal opacity-70 lg:inline">
                                    · {{ bar.plan.label }}
                                </span>
                                <UserX
                                    v-if="bar.plan.needsStaff"
                                    class="h-3 w-3 shrink-0 text-rose-500"
                                />
                                <span class="ml-auto" />
                                <ChevronRight
                                    v-if="bar.continuesRight"
                                    class="h-3 w-3 shrink-0 opacity-60"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="flex flex-col gap-3">
                <AdlPlanCard
                    v-if="selectedPlan"
                    :plan="selectedPlan"
                    :today="todayIso"
                    :can-assign="canAssignSchedule"
                    @details="emit('view-details', selectedPlan.schedule)"
                    @assign="emit('assign', selectedPlan.schedule)"
                />

                <section
                    class="rounded-2xl border border-slate-200 p-4 dark:border-white/10"
                >
                    <p class="text-sm font-bold text-slate-800 dark:text-white">
                        {{ formatIso(selectedDate, { weekday: "long", month: "long", day: "numeric" }) }}
                    </p>
                    <p class="text-xs text-slate-400 dark:text-gray-500">
                        {{ dayPlans.length }} ADL {{ dayPlans.length === 1 ? "plan" : "plans" }}
                    </p>

                    <p
                        v-if="!dayPlans.length"
                        class="mt-3 rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-white/10 dark:text-gray-500"
                    >
                        {{ loading ? "Loading…" : "No ADL plans on this day." }}
                    </p>

                    <ul v-else class="mt-3 space-y-2">
                        <li v-for="plan in dayPlans" :key="plan.schedule.schedule_id">
                            <button
                                type="button"
                                class="w-full rounded-xl border-l-[3px] px-3 py-2 text-left transition hover:brightness-95"
                                :class="chipClass(plan.schedule)"
                                @click="selectPlan(plan)"
                            >
                                <span class="flex items-center justify-between gap-2 text-xs font-bold">
                                    <span class="truncate">{{ plan.patient }}</span>
                                    <span class="shrink-0 font-semibold opacity-70">
                                        {{ planTag(plan, selectedDate) }}
                                    </span>
                                </span>
                                <span class="block truncate text-[11px] opacity-80">
                                    {{ plan.caregivers.map((c) => c.full_name).join(" · ") || "No caregiver yet" }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useElementSize } from "@vueuse/core";
import { ChevronLeft, ChevronRight, UserX } from "lucide-vue-next";
import type { ScheduleItem } from "~/types/schedule";
import CalendarMonthBar from "~/components/sections/app/Schedule/CalendarMonthBar.vue";
import AdlPlanCard from "~/components/sections/app/Schedule/AdlPlanCard.vue";
import {
    daysBetween,
    formatIso,
    isoOf,
    useCalendarMonth,
} from "~/composables/useCalendarMonth";
import { adlPlanOf, adlPlanTag, type AdlPlan } from "~/utils/adlPlan";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";

const props = withDefaults(
    defineProps<{
        schedules?: ScheduleItem[];
        loading?: boolean;
        date?: string;
        rangeEnd?: string;
    }>(),
    { schedules: () => [], loading: false },
);

const emit = defineEmits<{
    (e: "view-details", schedule: ScheduleItem): void;
    (e: "assign", schedule: ScheduleItem): void;
    (e: "change-range", range: { from: string; to: string }): void;
}>();

const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const LEGEND = [
    { label: "Pending", dot: "bg-violet-500" },
    { label: "Ongoing", dot: "bg-emerald-500" },
    { label: "Completed", dot: "bg-sky-500" },
    { label: "Missed", dot: "bg-rose-500" },
    { label: "Cancelled", dot: "bg-slate-400" },
];

const CHIP: Record<string, string> = {
    pending: "border-violet-400 bg-violet-50 text-violet-900 dark:bg-violet-500/15 dark:text-violet-100",
    ongoing: "border-emerald-400 bg-emerald-50 text-emerald-900 dark:bg-emerald-500/15 dark:text-emerald-100",
    completed: "border-sky-400 bg-sky-50 text-sky-900 dark:bg-sky-500/15 dark:text-sky-100",
    missed: "border-rose-400 bg-rose-50 text-rose-900 dark:bg-rose-500/15 dark:text-rose-100",
    cancelled: "border-slate-300 bg-slate-100 text-slate-500 line-through dark:bg-white/10 dark:text-gray-400",
};

const chipClass = (s: ScheduleItem) => CHIP[s.status?.toLowerCase() ?? ""] ?? CHIP.pending;

const { canAssign } = usePermissions();
const canAssignSchedule = computed(() => canAssign(Modules.Schedules));

const root = ref<HTMLElement | null>(null);
const { width } = useElementSize(root);
const wide = computed(() => width.value >= 1100);

const {
    todayIso,
    visibleMonth,
    monthName,
    monthTitle,
    monthBounds,
    partialMonth,
    inRange,
    shiftMonth,
    goToToday,
    showWholeMonth,
} = useCalendarMonth(props, (range) => emit("change-range", range));

const plans = computed(() =>
    props.schedules.map(adlPlanOf).filter((plan): plan is AdlPlan => !!plan),
);

const monthPlans = computed(() => {
    const { from, to } = monthBounds.value;
    return plans.value.filter((p) => p.start <= to && p.end >= from);
});

const needStaffCount = computed(() => monthPlans.value.filter((p) => p.needsStaff).length);

const weeks = computed(() => {
    const first = visibleMonth.value;
    const month = first.getMonth();
    const cursor = new Date(first);
    cursor.setDate(1 - first.getDay());
    const { from, to } = monthBounds.value;
    const total = Math.ceil((first.getDay() + daysBetween(from, to) + 1) / 7);

    return Array.from({ length: total }, () => {
        const days = Array.from({ length: 7 }, () => {
            const iso = isoOf(cursor);
            const inMonth = cursor.getMonth() === month;
            const day = cursor.getDate();
            cursor.setDate(cursor.getDate() + 1);
            return {
                iso,
                day,
                inMonth,
                active: inMonth ? plans.value.filter((p) => p.start <= iso && p.end >= iso).length : 0,
            };
        });

        const weekStart = days[0]!.iso;
        const weekEnd = days[6]!.iso;
        const lanes: string[] = [];

        const bars = monthPlans.value
            .filter((p) => p.start <= weekEnd && p.end >= weekStart)
            .sort((a, b) => a.start.localeCompare(b.start) || b.end.localeCompare(a.end))
            .map((plan) => {
                const s = [plan.start, weekStart, from].sort().at(-1)!;
                const e = [plan.end, weekEnd, to].sort()[0]!;
                let lane = lanes.findIndex((end) => end < s);
                if (lane === -1) {
                    lane = lanes.length;
                    lanes.push(e);
                } else {
                    lanes[lane] = e;
                }
                return {
                    plan,
                    lane,
                    col: daysBetween(weekStart, s) + 1,
                    span: daysBetween(s, e) + 1,
                    continuesLeft: plan.start < s,
                    continuesRight: plan.end > e,
                };
            })
            .filter((bar) => bar.span > 0);

        return { days, bars, lanes: lanes.length };
    });
});

function defaultSelection() {
    const { from, to } = monthBounds.value;
    const t = todayIso.value;
    return t >= from && t <= to ? t : from;
}

const selectedDate = ref(defaultSelection());
const selectedId = ref<number | null>(null);

const dayPlans = computed(() =>
    plans.value
        .filter((p) => p.start <= selectedDate.value && p.end >= selectedDate.value)
        .sort((a, b) => a.startAt - b.startAt),
);

const selectedPlan = computed(
    () =>
        plans.value.find((p) => p.schedule.schedule_id === selectedId.value) ??
        dayPlans.value[0] ??
        null,
);

watch(visibleMonth, () => {
    selectedDate.value = defaultSelection();
});

function selectDay(iso: string) {
    if (iso < monthBounds.value.from) return shiftMonth(-1);
    if (iso > monthBounds.value.to) return shiftMonth(1);
    selectedDate.value = iso;
    selectedId.value = null;
}

function selectPlan(plan: AdlPlan) {
    selectedId.value = plan.schedule.schedule_id;
    if (selectedDate.value >= plan.start && selectedDate.value <= plan.end) return;

    const { from } = monthBounds.value;
    selectedDate.value = plan.start > from ? plan.start : from;
}

const planTag = (plan: AdlPlan, iso: string) => adlPlanTag(plan, iso);
</script>

<style scoped>
.calendar-unloaded {
    background-image: repeating-linear-gradient(
        -45deg,
        rgb(148 163 184 / 0.08) 0 6px,
        transparent 6px 12px
    );
}
</style>
