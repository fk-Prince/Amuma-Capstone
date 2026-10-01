<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import {
    AlertTriangle,
    BedDouble,
    Clock,
    HeartHandshake,
    UserPlus,
    Users,
} from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import CaregiverShiftModal from "~/components/sections/app/Admission/CaregiverShiftModal.vue";

import type {
    CaregiverShiftOutcome,
    ShiftBoard,
    ShiftBoardResident,
    ShiftBoardShift,
} from "~/types/caregiver-shift";

const props = defineProps<{
    board: ShiftBoard | null;
    loading?: boolean;
    mineOnly?: boolean;
    canAssign?: boolean;
    search?: string;
}>();

const emit = defineEmits<{
    (e: "update:board", board: ShiftBoard): void;
}>();

const route = useRoute();

const pickerOpen = ref(false);
const modalResident = ref<ShiftBoardResident | null>(null);

function openResident(resident: ShiftBoardResident) {
    pickerOpen.value = false;

    if (props.canAssign) {
        modalResident.value = resident;
        return;
    }

    const link = admissionLink(resident);

    if (link) navigateTo(link);
}

function applyOutcome(resident: ShiftBoardResident, outcome: CaregiverShiftOutcome) {
    const board = props.board;

    if (!board) return;

    const changed = outcome.shift;

    const shifts = board.shifts.filter(
        (shift) =>
            shift.caregiver_facility_shift_id !==
            changed.caregiver_facility_shift_id,
    );

    const visibleToMe =
        !props.mineOnly ||
        board.shifts.some(
            (shift) => shift.caregiver.employee_id === changed.caregiver_id,
        );

    if (changed.is_active && visibleToMe) {
        shifts.push({
            caregiver_facility_shift_id: changed.caregiver_facility_shift_id,
            start_time: changed.start_time,
            end_time: changed.end_time,
            note: changed.note,
            caregiver: {
                employee_id: changed.caregiver_id,
                full_name: changed.caregiver_name,
                avatar: changed.avatar,
            },
            resident,
        });
    }

    shifts.sort((a, b) => a.start_time.localeCompare(b.start_time));

    const others = board.uncovered.filter(
        (item) => item.admission_id !== resident.admission_id,
    );

    emit("update:board", {
        ...board,
        shifts,
        uncovered:
            outcome.active_count > 0 || props.mineOnly
                ? others
                : [...others, resident],
    });
}

const DAY = 1440;
const HOUR_MARKS = [0, 3, 6, 9, 12, 15, 18, 21, 24];

const nowMinutes = ref(currentMinutes());
let clock: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    clock = setInterval(() => {
        nowMinutes.value = currentMinutes();
    }, 60_000);
});

onBeforeUnmount(() => clearInterval(clock));

function currentMinutes() {
    const now = new Date();

    return now.getHours() * 60 + now.getMinutes();
}

function toMinutes(time: string) {
    const [hours = 0, minutes = 0] = time.split(":").map(Number);

    return hours * 60 + minutes;
}

function segments(shift: ShiftBoardShift): [number, number][] {
    const start = toMinutes(shift.start_time);
    const end = toMinutes(shift.end_time);

    if (end > start) return [[start, end]];

    return [[start, DAY], ...(end > 0 ? [[0, end] as [number, number]] : [])];
}

const LANE_HEIGHT = 36;
const LANE_GAP = 4;

function layoutBars(shifts: ShiftBoardShift[]) {
    const bars = shifts
        .flatMap((shift) =>
            segments(shift).map(([start, end], index) => ({
                key: `${shift.caregiver_facility_shift_id}-${index}`,
                shift,
                start,
                end,
                lane: 0,
            })),
        )
        .sort((a, b) => a.start - b.start || a.end - b.end);

    const laneEnds: number[] = [];

    for (const bar of bars) {
        let lane = laneEnds.findIndex((end) => end <= bar.start);

        if (lane === -1) lane = laneEnds.push(0) - 1;

        laneEnds[lane] = bar.end;
        bar.lane = lane;
    }

    return { bars, lanes: Math.max(laneEnds.length, 1) };
}

function trackHeight(lanes: number) {
    return lanes * LANE_HEIGHT + (lanes + 1) * LANE_GAP;
}

function isOnDuty(shift: ShiftBoardShift) {
    return segments(shift).some(
        ([start, end]) => nowMinutes.value >= start && nowMinutes.value < end,
    );
}

function dutyMinutes(shifts: ShiftBoardShift[]) {
    const spans = shifts.flatMap(segments).sort((a, b) => a[0] - b[0]);

    let total = 0;
    let reach = 0;

    for (const [start, end] of spans) {
        const from = Math.max(start, reach);

        if (end > from) total += end - from;

        reach = Math.max(reach, end);
    }

    return total;
}

function formatTime(time: string) {
    const minutes = toMinutes(time);
    const hours = Math.floor(minutes / 60) % 24;
    const suffix = hours < 12 ? "AM" : "PM";
    const display = hours % 12 || 12;

    return `${display}:${String(minutes % 60).padStart(2, "0")} ${suffix}`;
}

function hourLabel(hour: number) {
    const value = hour % 24;

    return `${value % 12 || 12} ${value < 12 ? "AM" : "PM"}`;
}

function formatHours(minutes: number) {
    const hours = Math.round((minutes / 60) * 10) / 10;

    return `${hours}h`;
}

function roomLabel(resident: ShiftBoardResident) {
    return [
        resident.room_no ? `Room ${resident.room_no}` : null,
        resident.bed_no ? `Bed ${resident.bed_no}` : null,
    ]
        .filter(Boolean)
        .join(" · ");
}

function admissionLink(resident: ShiftBoardResident) {
    return resident.patient_uuid
        ? {
              path: `/app/branches/${route.params.uuid}/patients/${resident.patient_uuid}`,
              query: { tab: "admissions" },
          }
        : undefined;
}

const searchTerm = computed(() => (props.search ?? "").trim().toLowerCase());

function matches(...values: (string | null | undefined)[]) {
    return values.some((value) =>
        (value ?? "").toLowerCase().includes(searchTerm.value),
    );
}

function residentMatches(resident: ShiftBoardResident) {
    return matches(resident.full_name, resident.room_no, roomLabel(resident));
}

const visibleShifts = computed(() =>
    (props.board?.shifts ?? []).filter(
        (shift) =>
            !searchTerm.value ||
            matches(shift.caregiver.full_name) ||
            residentMatches(shift.resident),
    ),
);

const caregivers = computed(() => {
    const groups = new Map<
        number,
        { caregiver: ShiftBoardShift["caregiver"]; shifts: ShiftBoardShift[] }
    >();

    for (const shift of visibleShifts.value) {
        const id = shift.caregiver.employee_id;

        if (!groups.has(id)) {
            groups.set(id, { caregiver: shift.caregiver, shifts: [] });
        }

        groups.get(id)!.shifts.push(shift);
    }

    return [...groups.values()]
        .map((group) => ({
            ...group,
            residents: new Set(group.shifts.map((s) => s.resident.admission_id))
                .size,
            layout: layoutBars(group.shifts),
            dutyMinutes: dutyMinutes(group.shifts),
            onDuty: group.shifts.some(isOnDuty),
        }))
        .sort((a, b) =>
            (a.caregiver.full_name ?? "").localeCompare(
                b.caregiver.full_name ?? "",
            ),
        );
});

const onDutyCount = computed(
    () => caregivers.value.filter((c) => c.onDuty).length,
);

const residentsCovered = computed(
    () =>
        new Set(visibleShifts.value.map((s) => s.resident.admission_id)).size,
);

const uncovered = computed(() =>
    (props.board?.uncovered ?? []).filter(
        (resident) => !searchTerm.value || residentMatches(resident),
    ),
);

const dutyLimitMinutes = computed(() => (props.board?.duty_limit ?? 12) * 60);

const nowLeft = computed(() => `${(nowMinutes.value / DAY) * 100}%`);

const nowLabel = computed(() =>
    formatTime(
        `${Math.floor(nowMinutes.value / 60)}:${nowMinutes.value % 60}`,
    ),
);

const isEmpty = computed(
    () => !caregivers.value.length && !uncovered.value.length,
);

const residents = computed(() => {
    const entries = new Map<
        number,
        { resident: ShiftBoardResident; caregivers: number }
    >();

    for (const shift of visibleShifts.value) {
        const entry = entries.get(shift.resident.admission_id) ?? {
            resident: shift.resident,
            caregivers: 0,
        };

        entry.caregivers += 1;
        entries.set(shift.resident.admission_id, entry);
    }

    for (const resident of uncovered.value) {
        if (!entries.has(resident.admission_id)) {
            entries.set(resident.admission_id, { resident, caregivers: 0 });
        }
    }

    return [...entries.values()].sort((a, b) =>
        a.resident.full_name.localeCompare(b.resident.full_name),
    );
});
</script>

<template>
    <div class="space-y-5 p-4 sm:p-5">
        <template v-if="loading">
            <div class="grid animate-pulse gap-3 sm:grid-cols-3">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="h-[74px] rounded-xl bg-slate-100 dark:bg-white/10"
                />
            </div>

            <div
                class="animate-pulse space-y-3 rounded-xl border border-slate-100 p-4 dark:border-white/10"
            >
                <div class="h-3 w-40 rounded bg-slate-200 dark:bg-white/15" />

                <div
                    v-for="n in 4"
                    :key="n"
                    class="flex items-center gap-4"
                >
                    <div
                        class="h-9 w-9 shrink-0 rounded-full bg-slate-200 dark:bg-white/15"
                    />
                    <div
                        class="h-3 w-32 shrink-0 rounded bg-slate-100 dark:bg-white/10"
                    />
                    <div
                        class="h-10 flex-1 rounded-lg bg-slate-100 dark:bg-white/5"
                    />
                </div>
            </div>
        </template>

        <div
            v-else-if="isEmpty"
            class="flex flex-col items-center justify-center gap-3 px-6 py-24 text-center"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
            >
                <HeartHandshake class="h-6 w-6" />
            </div>

            <p class="text-sm font-semibold text-slate-600 dark:text-gray-400">
                {{
                    searchTerm
                        ? "No shifts match your search"
                        : mineOnly
                          ? "You have no facility caregiver shifts"
                          : "No facility caregiver shifts yet"
                }}
            </p>

            <p class="max-w-xs text-sm text-slate-400 dark:text-gray-500">
                {{
                    searchTerm
                        ? "Try another resident, caregiver or room."
                        : "Caregivers are assigned to residents from the resident's Admission tab."
                }}
            </p>
        </div>

        <template v-else>
            <div
                v-if="canAssign && residents.length"
                class="flex items-center justify-between gap-3"
            >
                <p class="text-sm font-semibold text-slate-800 dark:text-white">
                    Facility caregiver shifts
                </p>

                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-600 active:scale-95"
                        :aria-expanded="pickerOpen"
                        @click="pickerOpen = !pickerOpen"
                    >
                        <UserPlus class="h-3.5 w-3.5" />
                        Assign caregiver
                    </button>

                    <div
                        v-if="pickerOpen"
                        class="fixed inset-0 z-30"
                        @click="pickerOpen = false"
                    />

                    <div
                        v-if="pickerOpen"
                        class="absolute right-0 top-full z-40 mt-2 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-secondary"
                    >
                        <p
                            class="border-b border-slate-100 px-3.5 py-2.5 text-xs font-semibold text-slate-500 dark:border-white/10 dark:text-gray-400"
                        >
                            Choose a resident
                        </p>

                        <ul class="max-h-72 overflow-y-auto py-1">
                            <li
                                v-for="entry in residents"
                                :key="entry.resident.admission_id"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left transition hover:bg-slate-50 dark:hover:bg-white/5"
                                    @click="openResident(entry.resident)"
                                >
                                    <span class="min-w-0">
                                        <span
                                            class="block truncate text-sm font-medium text-slate-800 dark:text-white"
                                        >
                                            {{ entry.resident.full_name }}
                                        </span>

                                        <span
                                            v-if="roomLabel(entry.resident)"
                                            class="block truncate text-[11px] text-slate-400 dark:text-gray-500"
                                        >
                                            {{ roomLabel(entry.resident) }}
                                        </span>
                                    </span>

                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                        :class="
                                            entry.caregivers
                                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-200'
                                                : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                                        "
                                    >
                                        {{
                                            entry.caregivers
                                                ? `${entry.caregivers} caregiver${entry.caregivers === 1 ? "" : "s"}`
                                                : "No caregiver"
                                        }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div
                    class="flex items-center gap-3 rounded-xl border border-slate-100 p-4 dark:border-white/10"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        <Clock class="h-5 w-5" />
                    </span>

                    <div>
                        <p class="text-xs text-slate-400 dark:text-gray-500">
                            On duty now
                        </p>
                        <p
                            class="text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ onDutyCount }}
                            <span
                                class="text-xs font-normal text-slate-400 dark:text-gray-500"
                            >
                                of {{ caregivers.length }} caregiver{{
                                    caregivers.length === 1 ? "" : "s"
                                }}
                            </span>
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 rounded-xl border border-slate-100 p-4 dark:border-white/10"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                    >
                        <Users class="h-5 w-5" />
                    </span>

                    <div>
                        <p class="text-xs text-slate-400 dark:text-gray-500">
                            Residents covered
                        </p>
                        <p
                            class="text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ residentsCovered }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="uncovered.length"
                    class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-500/20 dark:bg-amber-500/10"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300"
                    >
                        <AlertTriangle class="h-5 w-5" />
                    </span>

                    <div>
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            Without a caregiver
                        </p>
                        <p
                            class="text-lg font-semibold text-amber-800 dark:text-amber-200"
                        >
                            {{ uncovered.length }}
                        </p>
                    </div>
                </div>
            </div>

            <section
                v-if="caregivers.length"
                class="rounded-xl border border-slate-100 dark:border-white/10"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-white/10"
                >
                    <p
                        class="text-sm font-semibold text-slate-800 dark:text-white"
                    >
                        24-hour coverage
                    </p>

                    <span
                        class="inline-flex items-center gap-1.5 text-xs text-slate-400 dark:text-gray-500"
                    >
                        <span class="h-2 w-2 rounded-full bg-rose-500" />
                        Now {{ nowLabel }}
                    </span>
                </div>

                <div class="hidden p-4 md:block">
                    <div class="flex items-end gap-4 pb-2">
                        <div class="w-56 shrink-0" />

                        <div class="relative h-4 flex-1">
                            <span
                                v-for="hour in HOUR_MARKS"
                                :key="hour"
                                class="absolute -translate-x-1/2 whitespace-nowrap text-[10px] text-slate-400 dark:text-gray-500"
                                :class="
                                    hour === 0
                                        ? 'translate-x-0'
                                        : hour === 24
                                          ? '-translate-x-full'
                                          : ''
                                "
                                :style="{ left: `${(hour / 24) * 100}%` }"
                            >
                                {{ hourLabel(hour) }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="row in caregivers"
                            :key="row.caregiver.employee_id"
                            class="flex items-center gap-4"
                        >
                            <div class="flex w-56 shrink-0 items-center gap-2.5">
                                <div class="relative">
                                    <MessageAvatar
                                        :src="row.caregiver.avatar"
                                        :name="row.caregiver.full_name"
                                    />

                                    <span
                                        v-if="row.onDuty"
                                        class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500 dark:border-secondary"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                    >
                                        {{ row.caregiver.full_name ?? "Caregiver" }}
                                    </p>

                                    <p
                                        class="truncate text-[11px]"
                                        :class="
                                            row.dutyMinutes > dutyLimitMinutes
                                                ? 'text-rose-500 dark:text-rose-300'
                                                : 'text-slate-400 dark:text-gray-500'
                                        "
                                    >
                                        {{ row.residents }} resident{{
                                            row.residents === 1 ? "" : "s"
                                        }}
                                        · {{ formatHours(row.dutyMinutes) }} on
                                        duty
                                    </p>
                                </div>
                            </div>

                            <div
                                class="relative flex-1 overflow-hidden rounded-lg bg-slate-50 dark:bg-white/5"
                                :style="{ height: `${trackHeight(row.layout.lanes)}px` }"
                            >
                                <span
                                    v-for="hour in HOUR_MARKS.slice(1, -1)"
                                    :key="hour"
                                    class="absolute inset-y-0 w-px bg-slate-200/70 dark:bg-white/10"
                                    :style="{ left: `${(hour / 24) * 100}%` }"
                                />

                                <button
                                    v-for="bar in row.layout.bars"
                                    :key="bar.key"
                                    type="button"
                                    :title="`${bar.shift.resident.full_name} · ${formatTime(bar.shift.start_time)} – ${formatTime(bar.shift.end_time)}${bar.shift.note ? ` · ${bar.shift.note}` : ''}`"
                                    class="absolute flex min-w-0 items-center gap-1.5 overflow-hidden rounded-md px-2 text-left text-[11px] font-medium transition hover:brightness-95"
                                    :class="
                                        isOnDuty(bar.shift)
                                            ? 'bg-primary-500 text-white'
                                            : 'bg-primary-100 text-primary-800 dark:bg-primary-500/25 dark:text-primary-100'
                                    "
                                    :style="{
                                        top: `${LANE_GAP + bar.lane * (LANE_HEIGHT + LANE_GAP)}px`,
                                        height: `${LANE_HEIGHT}px`,
                                        left: `${(bar.start / DAY) * 100}%`,
                                        width: `calc(${((bar.end - bar.start) / DAY) * 100}% - 2px)`,
                                    }"
                                    @click="openResident(bar.shift.resident)"
                                >
                                    <span class="truncate">
                                        {{ bar.shift.resident.full_name }}
                                    </span>

                                    <span
                                        v-if="bar.shift.resident.room_no"
                                        class="shrink-0 opacity-75"
                                    >
                                        {{ bar.shift.resident.room_no }}
                                    </span>
                                </button>

                                <span
                                    class="pointer-events-none absolute inset-y-0 w-0.5 bg-rose-500"
                                    :style="{ left: nowLeft }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <ul
                    class="divide-y divide-slate-100 md:hidden dark:divide-white/10"
                >
                    <li
                        v-for="row in caregivers"
                        :key="row.caregiver.employee_id"
                        class="space-y-2.5 p-4"
                    >
                        <div class="flex items-center gap-2.5">
                            <MessageAvatar
                                :src="row.caregiver.avatar"
                                :name="row.caregiver.full_name"
                            />

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    {{ row.caregiver.full_name ?? "Caregiver" }}
                                </p>

                                <p
                                    class="text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    {{ row.residents }} resident{{
                                        row.residents === 1 ? "" : "s"
                                    }}
                                    · {{ formatHours(row.dutyMinutes) }} on duty
                                </p>
                            </div>

                            <span
                                v-if="row.onDuty"
                                class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                            >
                                On duty
                            </span>
                        </div>

                        <button
                            v-for="shift in row.shifts"
                            :key="shift.caregiver_facility_shift_id"
                            type="button"
                            class="block w-full rounded-lg border px-3 py-2 text-left transition"
                            :class="
                                isOnDuty(shift)
                                    ? 'border-primary-200 bg-primary-50 dark:border-primary-500/20 dark:bg-primary-500/10'
                                    : 'border-slate-100 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5'
                            "
                            @click="openResident(shift.resident)"
                        >
                            <p
                                class="text-xs font-semibold text-slate-800 dark:text-white"
                            >
                                {{ formatTime(shift.start_time) }} –
                                {{ formatTime(shift.end_time) }}
                            </p>

                            <p
                                class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                {{ shift.resident.full_name }}
                                <template v-if="roomLabel(shift.resident)">
                                    · {{ roomLabel(shift.resident) }}
                                </template>
                            </p>
                        </button>
                    </li>
                </ul>
            </section>

            <section
                v-if="uncovered.length"
                class="rounded-xl border border-amber-200 dark:border-amber-500/20"
            >
                <div
                    class="border-b border-amber-100 px-4 py-3 dark:border-amber-500/20"
                >
                    <p
                        class="text-sm font-semibold text-slate-800 dark:text-white"
                    >
                        Residents without a caregiver
                    </p>

                    <p class="text-xs text-slate-400 dark:text-gray-500">
                        Admitted residents with no active caregiver shift.
                    </p>
                </div>

                <ul class="divide-y divide-slate-100 dark:divide-white/10">
                    <li
                        v-for="resident in uncovered"
                        :key="resident.admission_id"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-slate-800 dark:text-white"
                            >
                                {{ resident.full_name }}
                            </p>

                            <p
                                v-if="roomLabel(resident)"
                                class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                <BedDouble class="h-3 w-3 shrink-0" />
                                {{ roomLabel(resident) }}
                            </p>
                        </div>

                        <button
                            v-if="canAssign"
                            type="button"
                            class="flex shrink-0 items-center gap-1.5 rounded-lg border border-primary-100 px-2.5 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-50 dark:border-primary-500/20 dark:text-primary-300 dark:hover:bg-primary-500/10"
                            @click="openResident(resident)"
                        >
                            <UserPlus class="h-3.5 w-3.5" />
                            Assign caregiver
                        </button>

                        <NuxtLink
                            v-else-if="admissionLink(resident)"
                            :to="admissionLink(resident)"
                            class="shrink-0 text-xs font-semibold text-primary-700 hover:underline dark:text-primary-300"
                        >
                            View admission
                        </NuxtLink>
                    </li>
                </ul>
            </section>
        </template>

        <CaregiverShiftModal
            :open="!!modalResident"
            :admission-id="modalResident?.admission_id ?? null"
            :patient-name="modalResident?.full_name"
            :branch-uuid="String(route.params.uuid)"
            @close="modalResident = null"
            @change="(outcome) => modalResident && applyOutcome(modalResident, outcome)"
        />
    </div>
</template>
