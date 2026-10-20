<template>
    <div
        class="rounded-xl border border-slate-200/70 bg-white p-3 shadow-sm ring-1 ring-slate-100/60 transition-all hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:ring-white/10"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary-100 to-primary-50 text-primary-600 dark:from-primary-500/20 dark:to-primary-500/10 dark:text-primary-300"
                >
                    <CalendarClock class="h-4 w-4" />
                </div>
                <div>
                    <h2
                        class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                    >
                        Staff on duty
                    </h2>
                    <p class="text-[11px] text-muted dark:text-gray-400">
                        Facility Shifts, Activities of Daily Living, Medical Services · {{ todayLabel }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span
                    v-if="!loading && onDutyCount"
                    class="inline-flex items-center gap-1 rounded-full bg-accent-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.1em] text-accent-700 dark:text-accent-300"
                >
                    <span
                        class="h-1 w-1 animate-pulse rounded-full bg-accent-500"
                    />
                    {{ onDutyCount }} on duty now
                </span>

                <NuxtLink
                    :to="`/app/branches/${branchUuid}/schedules`"
                    class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300"
                >
                    Manage schedule →
                </NuxtLink>
            </div>
        </div>

        <div v-if="loading" class="mt-3 space-y-2">
            <div
                v-for="n in 3"
                :key="n"
                class="h-9 animate-pulse rounded-lg bg-slate-100 dark:bg-white/5"
            />
        </div>

        <p
            v-else-if="!rows.length"
            class="py-10 text-center text-[11px] text-muted dark:text-gray-400"
        >
            Nothing scheduled today.
        </p>

        <div v-else class="mt-3 overflow-x-auto">
            <div class="min-w-[680px]">
                <div class="grid items-end gap-2" :style="gridStyle">
                    <span
                        class="text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-400"
                    >
                        Staff
                    </span>
                    <div class="relative h-4">
                        <span
                            v-for="(mark, index) in hourMarks"
                            :key="mark.hour"
                            class="absolute whitespace-nowrap text-[10px] text-muted dark:text-gray-400"
                            :class="
                                index === 0
                                    ? ''
                                    : index === hourMarks.length - 1
                                      ? '-translate-x-full'
                                      : '-translate-x-1/2'
                            "
                            :style="{ left: `${mark.percent}%` }"
                        >
                            {{ mark.label }}
                        </span>
                    </div>
                </div>

                <div class="relative mt-1">
                    <div
                        class="pointer-events-none absolute bottom-0 top-0 z-10 w-px bg-rose-500"
                        :style="{ left: `calc(${labelWidth} + 0.5rem + (100% - ${labelWidth} - 0.5rem) * ${nowPercent / 100})` }"
                    >
                        <span
                            class="absolute -top-1 left-1/2 -translate-x-1/2 rounded bg-rose-500 px-1 py-0.5 text-[9px] font-bold text-white"
                        >
                            Now
                        </span>
                    </div>

                    <div
                        v-for="row in rows"
                        :key="row.id"
                        class="grid items-center gap-2 border-t border-slate-100 py-2 dark:border-white/10"
                        :style="gridStyle"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                :class="
                                    row.onDuty
                                        ? 'bg-accent'
                                        : 'bg-slate-400 dark:bg-slate-600'
                                "
                            >
                                {{ initials(row.name) }}
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="truncate text-[11px] font-semibold text-secondary dark:text-white"
                                >
                                    {{ row.name }}
                                </p>
                                <p
                                    class="truncate text-[10px] text-muted dark:text-gray-400"
                                >
                                    {{ row.subtitle }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="relative rounded-md bg-slate-50 dark:bg-white/5"
                            :style="{ height: `${row.height}px` }"
                        >
                            <div
                                v-for="mark in hourMarks"
                                :key="mark.hour"
                                class="absolute bottom-0 top-0 w-px bg-slate-200/70 dark:bg-white/10"
                                :style="{ left: `${mark.percent}%` }"
                            />

                            <div
                                v-for="(segment, index) in row.segments"
                                :key="index"
                                class="absolute flex items-center overflow-hidden rounded px-1.5 text-[10px] font-semibold text-white"
                                :class="
                                    segment.active
                                        ? 'bg-accent ring-1 ring-accent-300'
                                        : KIND_STYLE[segment.kind].bar
                                "
                                :style="{
                                    left: `${segment.left}%`,
                                    width: `${segment.width}%`,
                                    top: `${segment.top}px`,
                                    height: `${BAR_HEIGHT}px`,
                                }"
                                :title="segment.title"
                            >
                                <span class="truncate">{{ segment.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="!loading"
            class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-100 pt-2 text-[10px] text-muted dark:border-white/10 dark:text-gray-400"
        >
            <span
                v-if="uncoveredCount"
                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
            >
                {{ uncoveredCount }}
                {{ uncoveredCount === 1 ? "resident has" : "residents have" }}
                no caregiver assigned
            </span>
            <span
                v-else-if="shifts.length"
                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                All residents have a caregiver
            </span>

            <span
                v-for="kind in legendKinds"
                :key="kind"
                class="inline-flex items-center gap-1"
            >
                <span
                    class="inline-block h-2 w-2 rounded-sm"
                    :class="KIND_STYLE[kind].dot"
                />
                {{ KIND_STYLE[kind].label }}
            </span>

            <span class="inline-flex items-center gap-1">
                <span class="inline-block h-2 w-2 rounded-sm bg-accent" />
                On duty now
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { CalendarClock } from "lucide-vue-next";

import { caregiverShiftService } from "~/api/caregiver-shift/CaregiverShiftService";

const props = defineProps<{ branchUuid: string }>();

interface BoardShift {
    caregiver_shift_id: number;
    start_time: string;
    end_time: string;
    caregiver: { employee_id: number; full_name: string | null };
    resident: { full_name: string };
}

interface BoardTask {
    employee_id: number;
    full_name: string | null;
    role: string;
    type: "ADL" | "Medical";
    title: string;
    patient_name: string | null;
    start_time: string;
    end_time: string;
}

type Kind = "facility" | "adl" | "medical";

const KIND_STYLE: Record<Kind, { label: string; bar: string; dot: string }> = {
    facility: {
        label: "Facility Shifts",
        bar: "bg-primary-400 dark:bg-primary-500/70",
        dot: "bg-primary-400",
    },
    adl: {
        label: "Activities of Daily Living",
        bar: "bg-amber-500",
        dot: "bg-amber-500",
    },
    medical: {
        label: "Medical Services",
        bar: "bg-violet-500",
        dot: "bg-violet-500",
    },
};

const loading = ref(true);
const shifts = ref<BoardShift[]>([]);
const tasks = ref<BoardTask[]>([]);
const uncoveredCount = ref(0);

const load = async () => {
    if (!props.branchUuid) return;

    loading.value = true;

    try {
        const res: any = await caregiverShiftService.board({
            branch_uuid: props.branchUuid,
            include_tasks: 1,
        });

        shifts.value = res?.data?.shifts ?? [];
        tasks.value = res?.data?.tasks ?? [];
        uncoveredCount.value = (res?.data?.uncovered ?? []).length;
    } catch (err) {
        console.error("Failed to fetch duty roster:", err);
        shifts.value = [];
        tasks.value = [];
        uncoveredCount.value = 0;
    } finally {
        loading.value = false;
    }
};

watch(() => props.branchUuid, load, { immediate: true });

const now = ref(new Date());
let timer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    timer = setInterval(() => (now.value = new Date()), 30000);
});

onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
});

const todayLabel = computed(() =>
    now.value.toLocaleDateString("en-US", {
        weekday: "long",
        month: "short",
        day: "numeric",
    }),
);

const nowHour = computed(
    () => now.value.getHours() + now.value.getMinutes() / 60,
);
const nowPercent = computed(() => (nowHour.value / 24) * 100);

const labelWidth = "9rem";
const gridStyle = { gridTemplateColumns: `${labelWidth} 1fr` };

const hourLabel = (hour: number) => {
    const h = hour % 24;
    const period = h >= 12 ? "PM" : "AM";
    const h12 = h % 12 === 0 ? 12 : h % 12;

    return `${h12} ${period}`;
};

const hourMarks = Array.from({ length: 7 }, (_, i) => ({
    hour: i * 4,
    percent: (i * 4 * 100) / 24,
    label: hourLabel(i * 4),
}));

const toHour = (time: string) => {
    const [h, m] = time.split(":");

    return (Number(h) || 0) + (Number(m) || 0) / 60;
};

const formatTime = (time: string) => {
    const hour = toHour(time);
    const h24 = Math.floor(hour);
    const m = Math.round((hour - h24) * 60);
    const period = h24 >= 12 ? "PM" : "AM";
    const h12 = h24 % 12 === 0 ? 12 : h24 % 12;

    return `${h12}:${String(m).padStart(2, "0")} ${period}`;
};

const isNowIn = (start: number, end: number) =>
    end > start
        ? nowHour.value >= start && nowHour.value < end
        : nowHour.value >= start || nowHour.value < end;

const BAR_HEIGHT = 20;
const BAR_GAP = 4;

interface RawSegment {
    kind: Kind;
    start: number;
    end: number;
    label: string;
    title: string;
}

interface StaffEntry {
    id: number;
    name: string;
    role: string | null;
    residents: Set<string>;
    taskCount: number;
    items: RawSegment[];
    seenShift: Set<string>;
}

// Everyone scheduled today, with each shift and service as one labelled bar.
const staff = computed(() => {
    const byId = new Map<number, StaffEntry>();

    const entryFor = (id: number, name: string | null, role: string | null) => {
        if (!byId.has(id)) {
            byId.set(id, {
                id,
                name: name ?? "Unnamed staff",
                role,
                residents: new Set(),
                taskCount: 0,
                items: [],
                seenShift: new Set(),
            });
        }

        const entry = byId.get(id)!;

        entry.role ??= role;

        return entry;
    };

    for (const shift of shifts.value) {
        const entry = entryFor(
            shift.caregiver.employee_id,
            shift.caregiver.full_name,
            null,
        );
        const key = `${shift.start_time}-${shift.end_time}`;

        entry.residents.add(shift.resident.full_name);

        if (entry.seenShift.has(key)) continue;

        entry.seenShift.add(key);

        const time = `${formatTime(shift.start_time)} – ${formatTime(shift.end_time)}`;

        entry.items.push({
            kind: "facility",
            start: toHour(shift.start_time),
            end: toHour(shift.end_time),
            label: time,
            title: `Facility shift · ${time}`,
        });
    }

    for (const task of tasks.value) {
        const entry = entryFor(task.employee_id, task.full_name, task.role);
        const time = `${formatTime(task.start_time)} – ${formatTime(task.end_time)}`;

        entry.taskCount += 1;
        entry.items.push({
            kind: task.type === "ADL" ? "adl" : "medical",
            start: toHour(task.start_time),
            end: toHour(task.end_time),
            label: `${time} · ${task.title}`,
            title: `${KIND_STYLE[task.type === "ADL" ? "adl" : "medical"].label} · ${task.title}${task.patient_name ? ` · ${task.patient_name}` : ""} · ${time}`,
        });
    }

    return [...byId.values()];
});

interface Segment {
    left: number;
    width: number;
    top: number;
    label: string;
    title: string;
    kind: Kind;
    active: boolean;
}

// Bars that overlap in time are stacked on separate lines; otherwise a row
// stays a single slim bar.
const placeSegments = (items: RawSegment[]) => {
    const pieces: (Omit<Segment, "top"> & { right: number })[] = [];

    for (const item of items) {
        const active = isNowIn(item.start, item.end);
        const base = { label: item.label, title: item.title, kind: item.kind, active };

        if (item.end > item.start) {
            pieces.push({
                ...base,
                left: (item.start / 24) * 100,
                width: ((item.end - item.start) / 24) * 100,
                right: (item.end / 24) * 100,
            });
        } else {
            // runs past midnight: split at midnight
            pieces.push({
                ...base,
                left: (item.start / 24) * 100,
                width: ((24 - item.start) / 24) * 100,
                right: 100,
            });

            if (item.end > 0) {
                pieces.push({
                    ...base,
                    left: 0,
                    width: (item.end / 24) * 100,
                    right: (item.end / 24) * 100,
                });
            }
        }
    }

    pieces.sort((a, b) => a.left - b.left);

    const laneEnds: number[] = [];
    const segments: Segment[] = [];

    for (const piece of pieces) {
        let lane = laneEnds.findIndex((end) => end <= piece.left + 0.01);

        if (lane === -1) {
            lane = laneEnds.length;
            laneEnds.push(0);
        }

        laneEnds[lane] = piece.right;

        const { right: _right, ...rest } = piece;

        segments.push({ ...rest, top: BAR_GAP + lane * (BAR_HEIGHT + BAR_GAP) });
    }

    return {
        segments,
        height: Math.max(laneEnds.length, 1) * (BAR_HEIGHT + BAR_GAP) + BAR_GAP,
    };
};

const rows = computed(() =>
    staff.value
        .map((entry) => {
            const residentCount = entry.residents.size;
            const taskLabel = entry.taskCount
                ? `${entry.taskCount} ${entry.taskCount === 1 ? "task" : "tasks"}`
                : null;
            const { segments, height } = placeSegments(entry.items);

            return {
                id: entry.id,
                name: entry.name,
                subtitle: [
                    residentCount
                        ? `${residentCount} ${residentCount === 1 ? "resident" : "residents"}`
                        : entry.role,
                    taskLabel,
                ]
                    .filter(Boolean)
                    .join(" · "),
                segments,
                height,
                firstStart: Math.min(...entry.items.map((item) => item.start)),
                onDuty: entry.items.some((item) => isNowIn(item.start, item.end)),
            };
        })
        .sort(
            (a, b) =>
                Number(b.onDuty) - Number(a.onDuty) ||
                a.firstStart - b.firstStart ||
                a.name.localeCompare(b.name),
        ),
);

const onDutyCount = computed(() => rows.value.filter((r) => r.onDuty).length);

// Only list the kinds that actually appear today.
const legendKinds = computed<Kind[]>(() => {
    const present = new Set<Kind>();

    for (const entry of staff.value) {
        for (const item of entry.items) present.add(item.kind);
    }

    return (["facility", "adl", "medical"] as Kind[]).filter((kind) =>
        present.has(kind),
    );
});

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join("");
</script>
