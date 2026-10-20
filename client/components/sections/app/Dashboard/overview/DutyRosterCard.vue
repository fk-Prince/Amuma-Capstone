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
                        Shifts, ADL and medical · {{ todayLabel }}
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
            No shifts or tasks assigned today.
        </p>

        <div v-else class="mt-3 overflow-x-auto">
            <div class="min-w-[620px]">
                <div class="grid items-end gap-2" :style="gridStyle">
                    <span
                        class="text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-400"
                    >
                        Staff
                    </span>
                    <div class="relative h-4">
                        <span
                            v-for="mark in hourMarks"
                            :key="mark.hour"
                            class="absolute -translate-x-1/2 text-[10px] text-muted dark:text-gray-400"
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
                            :style="{ height: `${trackHeight}px` }"
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
                                :class="segmentClass(segment.lane, row.onDuty)"
                                :style="{
                                    left: `${segment.left}%`,
                                    width: `${segment.width}%`,
                                    top: `${laneTop(segment.lane)}px`,
                                    height: `${LANE_HEIGHT}px`,
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
            class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-2 dark:border-white/10"
        >
            <span
                v-if="uncoveredCount"
                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
            >
                {{ uncoveredCount }}
                {{ uncoveredCount === 1 ? "resident has" : "residents have" }}
                no caregiver assigned
            </span>
            <span
                v-else-if="shifts.length"
                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                All residents have a caregiver
            </span>

            <span class="text-[10px] text-muted dark:text-gray-400">
                <span
                    class="mr-1 inline-block h-2 w-2 rounded-sm bg-accent align-middle"
                />On duty now
                <span
                    class="ml-2 mr-1 inline-block h-2 w-2 rounded-sm bg-primary-400 align-middle"
                />Scheduled
                <template v-if="hasAdl">
                    <span
                        class="ml-2 mr-1 inline-block h-2 w-2 rounded-sm bg-amber-500 align-middle"
                    />ADL
                </template>
                <template v-if="hasMedical">
                    <span
                        class="ml-2 mr-1 inline-block h-2 w-2 rounded-sm bg-violet-500 align-middle"
                    />Medical
                </template>
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

type Lane = "shift" | "adl" | "medical";

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

// Shifts, ADL and medical each get their own lane. ADL is duration based, so it
// reads better on a lane of its own than stacked on the shift bar.
const hasAdl = computed(() => tasks.value.some((t) => t.type === "ADL"));
const hasMedical = computed(() =>
    tasks.value.some((t) => t.type === "Medical"),
);

const lanes = computed<Lane[]>(() => [
    ...(shifts.value.length ? (["shift"] as Lane[]) : []),
    ...(hasAdl.value ? (["adl"] as Lane[]) : []),
    ...(hasMedical.value ? (["medical"] as Lane[]) : []),
]);

const LANE_HEIGHT = 18;
const LANE_GAP = 4;

const trackHeight = computed(
    () => Math.max(lanes.value.length, 1) * (LANE_HEIGHT + LANE_GAP) + LANE_GAP,
);

const laneTop = (lane: Lane) =>
    LANE_GAP + Math.max(lanes.value.indexOf(lane), 0) * (LANE_HEIGHT + LANE_GAP);

const segmentClass = (lane: Lane, onDuty: boolean) =>
    lane === "adl"
        ? "bg-amber-500"
        : lane === "medical"
          ? "bg-violet-500"
          : onDuty
            ? "bg-accent"
            : "bg-primary-400 dark:bg-primary-500/70";

interface Segment {
    left: number;
    width: number;
    label: string;
    title: string;
    lane: Lane;
}

const toSegments = (
    start: number,
    end: number,
    label: string,
    title: string,
    lane: Lane,
): Segment[] => {
    if (end > start) {
        return [
            {
                left: (start / 24) * 100,
                width: ((end - start) / 24) * 100,
                label,
                title,
                lane,
            },
        ];
    }

    // runs past midnight: split at midnight
    const parts: Segment[] = [
        {
            left: (start / 24) * 100,
            width: ((24 - start) / 24) * 100,
            label,
            title,
            lane,
        },
    ];

    if (end > 0) {
        parts.push({ left: 0, width: (end / 24) * 100, label, title, lane });
    }

    return parts;
};

const rows = computed(() => {
    const byStaff = new Map<
        number,
        {
            id: number;
            name: string;
            role: string | null;
            residents: Set<string>;
            taskCount: number;
            segments: Segment[];
            windows: { start: number; end: number }[];
            seenShift: Set<string>;
        }
    >();

    const entryFor = (id: number, name: string | null, role: string | null) => {
        if (!byStaff.has(id)) {
            byStaff.set(id, {
                id,
                name: name ?? "Unnamed staff",
                role,
                residents: new Set(),
                taskCount: 0,
                segments: [],
                windows: [],
                seenShift: new Set(),
            });
        }

        return byStaff.get(id)!;
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

        const start = toHour(shift.start_time);
        const end = toHour(shift.end_time);
        const label = `${formatTime(shift.start_time)} – ${formatTime(shift.end_time)}`;

        entry.windows.push({ start, end });
        entry.segments.push(
            ...toSegments(start, end, label, `Shift · ${label}`, "shift"),
        );
    }

    for (const task of tasks.value) {
        const entry = entryFor(task.employee_id, task.full_name, task.role);
        const start = toHour(task.start_time);
        const end = toHour(task.end_time);
        const time = `${formatTime(task.start_time)} – ${formatTime(task.end_time)}`;

        entry.role ??= task.role;
        entry.taskCount += 1;
        entry.windows.push({ start, end });
        entry.segments.push(
            ...toSegments(
                start,
                end,
                task.title,
                `${task.type} · ${task.title}${task.patient_name ? ` · ${task.patient_name}` : ""} · ${time}`,
                task.type === "ADL" ? "adl" : "medical",
            ),
        );
    }

    return [...byStaff.values()]
        .map((entry) => {
            const residentCount = entry.residents.size;
            const parts = [
                residentCount
                    ? `${residentCount} ${residentCount === 1 ? "resident" : "residents"}`
                    : (entry.role ?? "Staff"),
                entry.taskCount
                    ? `${entry.taskCount} ${entry.taskCount === 1 ? "task" : "tasks"}`
                    : null,
            ].filter(Boolean);

            return {
                id: entry.id,
                name: entry.name,
                subtitle: parts.join(" · "),
                segments: entry.segments,
                firstStart: Math.min(...entry.windows.map((w) => w.start)),
                onDuty: entry.windows.some((w) => isNowIn(w.start, w.end)),
            };
        })
        .sort(
            (a, b) =>
                Number(b.onDuty) - Number(a.onDuty) ||
                a.firstStart - b.firstStart ||
                a.name.localeCompare(b.name),
        );
});

const onDutyCount = computed(() => rows.value.filter((r) => r.onDuty).length);

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join("");
</script>
