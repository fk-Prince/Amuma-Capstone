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
                <span
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-white/10 dark:text-gray-300"
                >
                    {{ rows.length }} staff
                </span>
                <span
                    v-for="chip in kindChips"
                    :key="chip.kind"
                    class="rounded-full px-2.5 py-1"
                    :class="chip.class"
                >
                    {{ chip.count }} {{ chip.label }}
                </span>
                <span
                    v-if="idleCount"
                    class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    {{ idleCount }} with nothing assigned
                </span>
            </template>
            <template #legend>
                <span
                    v-for="(meta, kind) in KIND_META"
                    :key="kind"
                    class="inline-flex items-center gap-1.5"
                >
                    <span class="h-2 w-2 rounded-full" :class="meta.dot" />
                    {{ meta.label }}
                </span>
            </template>
        </CalendarMonthBar>

        <div
            :class="
                wide
                    ? 'grid grid-cols-[minmax(0,1fr)_20rem] items-start gap-4'
                    : 'flex flex-col gap-4'
            "
        >
            <div
                class="relative overflow-x-auto rounded-2xl border border-slate-200 dark:border-white/10"
            >
                <div
                    v-if="loading"
                    class="absolute inset-x-0 top-0 z-20 h-0.5 animate-pulse bg-primary"
                />

                <div class="flex border-b border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/5">
                    <div
                        class="sticky left-0 z-10 w-48 shrink-0 border-r border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-secondary dark:text-gray-400"
                    >
                        Staff
                    </div>
                    <div class="flex shrink-0">
                        <button
                            v-for="iso in monthDays"
                            :key="iso"
                            type="button"
                            class="shrink-0 border-r border-slate-100 py-1 text-center leading-tight dark:border-white/[0.05]"
                            :style="{ width: `${DAY_W}px` }"
                            @click="selectedDate = iso"
                        >
                            <span class="block text-[9px] font-bold uppercase text-slate-400 dark:text-gray-500">
                                {{ formatIso(iso, { weekday: "short" }).slice(0, 2) }}
                            </span>
                            <span
                                class="mx-auto mt-0.5 flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold"
                                :class="
                                    iso === todayIso
                                        ? 'bg-primary text-white'
                                        : iso === selectedDate
                                          ? 'text-primary'
                                          : 'text-slate-600 dark:text-gray-300'
                                "
                            >
                                {{ parseIso(iso).getDate() }}
                            </span>
                        </button>
                    </div>
                </div>

                <p
                    v-if="!rows.length"
                    class="px-5 py-10 text-center text-sm text-slate-400 dark:text-gray-500"
                >
                    {{ loading ? "Loading staff…" : "No staff match your filters." }}
                </p>

                <div
                    v-for="row in rows"
                    :key="row.employee.employee_id"
                    class="flex border-b border-slate-100 last:border-b-0 dark:border-white/[0.06]"
                    :class="row.employee.employee_id === selectedEmployeeId ? 'bg-primary-50/50 dark:bg-primary-500/[0.06]' : ''"
                >
                    <button
                        type="button"
                        class="sticky left-0 z-10 flex w-48 shrink-0 items-center gap-2.5 border-r border-slate-200 px-3 text-left dark:border-white/10"
                        :class="
                            row.employee.employee_id === selectedEmployeeId
                                ? 'bg-primary-50 dark:bg-[#1c2a45]'
                                : 'bg-white dark:bg-secondary'
                        "
                        :style="{ minHeight: `${row.height}px` }"
                        @click="selectedEmployeeId = row.employee.employee_id"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-[11px] font-bold text-primary"
                        >
                            <img
                                v-if="row.employee.avatar"
                                :src="row.employee.avatar"
                                :alt="row.employee.full_name ?? ''"
                                class="h-full w-full object-cover"
                            />
                            <template v-else>{{ initials(row.employee.full_name ?? "") }}</template>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800 dark:text-white">
                                {{ row.employee.full_name }}
                            </span>
                            <span class="block truncate text-[11px] text-slate-400 dark:text-gray-500">
                                {{ row.employee.role_name }}
                            </span>
                        </span>
                    </button>

                    <div
                        class="relative shrink-0"
                        :style="{ width: `${DAY_W * monthDays.length}px`, minHeight: `${row.height}px` }"
                    >
                        <div class="absolute inset-0 flex">
                            <div
                                v-for="iso in monthDays"
                                :key="iso"
                                class="shrink-0 cursor-pointer border-r border-slate-100 dark:border-white/[0.05]"
                                :class="[
                                    !inRange(iso)
                                        ? 'calendar-unloaded'
                                        : iso === selectedDate
                                          ? 'bg-primary-100/40 dark:bg-primary-500/10'
                                          : [0, 6].includes(parseIso(iso).getDay())
                                            ? 'bg-slate-50/70 dark:bg-white/[0.02]'
                                            : '',
                                ]"
                                :style="{ width: `${DAY_W}px` }"
                                @click="pick(row.employee.employee_id, iso)"
                            />
                        </div>

                        <button
                            v-for="bar in row.bars"
                            :key="bar.item.key"
                            type="button"
                            class="absolute flex h-5 items-center gap-1 overflow-hidden border-l-[3px] px-1.5 text-left text-[11px] font-semibold transition hover:brightness-95"
                            :class="[
                                KIND_META[bar.item.kind].bar,
                                bar.continuesLeft ? 'rounded-l-none border-l-0' : 'rounded-l-md',
                                bar.continuesRight ? 'rounded-r-none' : 'rounded-r-md',
                                bar.span === 1 ? 'justify-center' : '',
                            ]"
                            :style="{
                                left: `${bar.offset * DAY_W + 3}px`,
                                width: `${bar.span * DAY_W - 6}px`,
                                top: `${6 + bar.lane * 26}px`,
                            }"
                            :title="`${bar.item.patient} · ${bar.item.time}`"
                            @click="pick(row.employee.employee_id, bar.firstVisible)"
                        >
                            <template v-if="bar.span === 1">{{ initials(bar.item.patient) }}</template>
                            <template v-else>
                                <ChevronLeft v-if="bar.continuesLeft" class="h-3 w-3 shrink-0 opacity-60" />
                                <span class="truncate">{{ bar.item.patient }}</span>
                                <span class="hidden truncate font-normal opacity-70 sm:inline">
                                    · {{ bar.item.time }}
                                </span>
                                <span class="ml-auto" />
                                <ChevronRight v-if="bar.continuesRight" class="h-3 w-3 shrink-0 opacity-60" />
                            </template>
                        </button>

                        <span
                            v-if="!row.items.length"
                            class="pointer-events-none absolute inset-0 flex items-center pl-4 text-xs italic text-slate-400 dark:text-gray-500"
                        >
                            Nothing assigned this month
                        </span>
                    </div>
                </div>
            </div>

            <aside
                v-if="selectedRow"
                class="rounded-2xl border border-slate-200 p-4 dark:border-white/10"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-xs font-bold text-primary"
                    >
                        <img
                            v-if="selectedRow.employee.avatar"
                            :src="selectedRow.employee.avatar"
                            :alt="selectedRow.employee.full_name ?? ''"
                            class="h-full w-full object-cover"
                        />
                        <template v-else>{{ initials(selectedRow.employee.full_name ?? "") }}</template>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-800 dark:text-white">
                            {{ selectedRow.employee.full_name }}
                        </p>
                        <p class="truncate text-xs text-slate-400 dark:text-gray-500">
                            {{ selectedRow.employee.role_name }} ·
                            {{ formatIso(selectedDate, { weekday: "long", month: "long", day: "numeric" }) }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="!dayItems.length"
                    class="mt-4 rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-white/10 dark:text-gray-500"
                >
                    Nothing on this day.
                </p>

                <ul v-else class="mt-4 space-y-2">
                    <li
                        v-for="item in dayItems"
                        :key="item.key"
                        class="rounded-xl border-l-[3px] px-3 py-2.5"
                        :class="KIND_META[item.kind].bar"
                    >
                        <div
                            class="flex items-center justify-between gap-2 text-[10px] font-bold uppercase tracking-wide opacity-80"
                        >
                            <span class="truncate">{{ KIND_META[item.kind].label }}</span>
                            <span v-if="itemTag(item, selectedDate)" class="shrink-0">
                                {{ itemTag(item, selectedDate) }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm font-semibold">{{ item.patient }}</p>
                        <p v-if="item.time" class="text-xs opacity-90">{{ item.time }}</p>
                        <p v-if="item.place" class="text-[11px] opacity-75">{{ item.place }}</p>
                        <div v-if="item.status || item.code" class="mt-1 flex items-center gap-1.5">
                            <span
                                v-if="item.status"
                                class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                :class="scheduleStatusTheme(item.status).badge"
                            >
                                {{ scheduleStatusLabel(item.status) }}
                            </span>
                            <span v-if="item.code" class="font-mono text-[10px] opacity-60">
                                {{ item.code }}
                            </span>
                        </div>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useElementSize } from "@vueuse/core";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import type { EmployeeScheduleRow } from "~/types/schedule";
import CalendarMonthBar from "~/components/sections/app/Schedule/CalendarMonthBar.vue";
import { daysBetween, formatIso, parseIso, useCalendarMonth } from "~/composables/useCalendarMonth";
import {
    KIND_META,
    itemTag,
    scheduleItem,
    shiftItem,
    type CalendarItemKind,
    type EmployeeCalendarItem,
} from "~/utils/employeeCalendar";
import { scheduleStatusLabel, scheduleStatusTheme } from "~/utils/schedule-status";
import { initials } from "~/utils/user";

const props = withDefaults(
    defineProps<{
        employees?: EmployeeScheduleRow[];
        loading?: boolean;
        date?: string;
        rangeEnd?: string;
        search?: string;
    }>(),
    { employees: () => [], loading: false, search: "" },
);

const emit = defineEmits<{
    (e: "change-range", range: { from: string; to: string }): void;
}>();

const DAY_W = 44;
const LANE_H = 26;

const root = ref<HTMLElement | null>(null);
const { width } = useElementSize(root);
const wide = computed(() => width.value >= 1100);

const {
    todayIso,
    visibleMonth,
    monthName,
    monthTitle,
    monthBounds,
    monthDays,
    partialMonth,
    inRange,
    shiftMonth,
    goToToday,
    showWholeMonth,
} = useCalendarMonth(props, (range) => emit("change-range", range));

const covers = (item: EmployeeCalendarItem, iso: string) =>
    item.start <= iso && (item.end === null || item.end >= iso);

const term = computed(() => props.search.trim().toLowerCase());

const rows = computed(() => {
    const { from, to } = monthBounds.value;

    return props.employees
        .map((employee) => {
            const items = [
                ...employee.shifts.map((shift) => shiftItem(shift, from)),
                ...employee.schedules.map(scheduleItem).filter((i): i is EmployeeCalendarItem => !!i),
            ]
                .filter((item) => item.start <= to && (item.end === null || item.end >= from))
                .filter(
                    (item) =>
                        !term.value ||
                        [employee.full_name, employee.role_name].some((v) => (v ?? "").toLowerCase().includes(term.value)) ||
                        item.patient.toLowerCase().includes(term.value),
                )
                .sort((a, b) => a.start.localeCompare(b.start));

            const lanes: string[] = [];
            const bars = items.map((item) => {
                const s = item.start > from ? item.start : from;
                const e = item.end === null || item.end > to ? to : item.end;
                let lane = lanes.findIndex((end) => end < s);
                if (lane === -1) {
                    lane = lanes.length;
                    lanes.push(e);
                } else {
                    lanes[lane] = e;
                }
                return {
                    item,
                    lane,
                    offset: daysBetween(from, s),
                    span: daysBetween(s, e) + 1,
                    firstVisible: s,
                    continuesLeft: item.start < s,
                    continuesRight: item.end === null || item.end > e,
                };
            });

            return {
                employee,
                items,
                bars,
                height: Math.max(1, lanes.length) * LANE_H + 12,
                matches:
                    !term.value ||
                    items.length > 0 ||
                    (employee.full_name ?? "").toLowerCase().includes(term.value),
            };
        })
        .filter((row) => row.matches);
});

const kindCount = (kind: CalendarItemKind) =>
    rows.value.reduce((n, row) => n + row.items.filter((i) => i.kind === kind).length, 0);

const kindChips = computed(() =>
    (["facility", "adl", "medical"] as CalendarItemKind[])
        .map((kind) => ({
            kind,
            count: kindCount(kind),
            label: kind === "facility" ? "facility shifts" : kind === "adl" ? "ADL" : "medical services",
            class:
                kind === "facility"
                    ? "bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300"
                    : kind === "adl"
                      ? "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                      : "bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300",
        }))
        .filter((chip) => chip.count),
);

const idleCount = computed(() => rows.value.filter((row) => !row.items.length).length);

function defaultDay() {
    const { from, to } = monthBounds.value;
    return todayIso.value >= from && todayIso.value <= to ? todayIso.value : from;
}

const selectedDate = ref(defaultDay());
const selectedEmployeeId = ref<number | null>(null);

watch(visibleMonth, () => {
    selectedDate.value = defaultDay();
});

const selectedRow = computed(
    () =>
        rows.value.find((row) => row.employee.employee_id === selectedEmployeeId.value) ??
        rows.value[0] ??
        null,
);

const dayItems = computed(() =>
    (selectedRow.value?.items ?? []).filter((item) => covers(item, selectedDate.value)),
);

function pick(employeeId: number, iso: string) {
    selectedEmployeeId.value = employeeId;
    selectedDate.value = iso;
}
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
