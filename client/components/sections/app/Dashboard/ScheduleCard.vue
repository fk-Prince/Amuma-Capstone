<template>
    <div
        class="rounded-2xl bg-white border border-muted-light/70 shadow-sm p-5 font-sans w-full dark:bg-secondary dark:border-white/10"
    >
        <!-- header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start gap-3">
                <div
                    class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0 dark:bg-primary-500/10"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="text-primary"
                    >
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <path d="M16 2v4M8 2v4M3 10h18" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-secondary text-[15px] font-medium dark:text-white">
                        {{ title }}
                    </h3>
                    <div
                        class="flex items-center gap-2 mt-1 text-xs text-muted dark:text-gray-400"
                    >
                        <button
                            class="hover:text-secondary dark:hover:text-white"
                            @click="shiftDay(-1)"
                        >
                            ‹
                        </button>
                        <span>{{ dateLabel }}</span>
                        <button
                            class="hover:text-secondary dark:hover:text-white"
                            @click="shiftDay(1)"
                        >
                            ›
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    class="text-xs font-medium text-primary border border-primary-200 rounded-full px-3 py-1.5 hover:bg-primary-50 dark:border-primary-500/20 dark:hover:bg-primary-500/10"
                >
                    View full schedule
                </button>
                <button
                    class="text-muted hover:text-secondary text-lg leading-none px-1 dark:text-gray-400 dark:hover:text-white"
                >
                    ⋮
                </button>
            </div>
        </div>

        <!-- table -->
        <div class="relative">
            <!-- column headers -->
            <div class="grid gap-2 mb-3" :style="gridTemplate">
                <span class="text-[11px] font-medium text-muted tracking-wide dark:text-gray-400"
                    >STAFF ON DUTY</span
                >
                <div class="relative h-4">
                    <span
                        v-for="mark in hourMarks"
                        :key="mark.hour"
                        class="absolute -translate-x-1/2 text-[11px] text-muted dark:text-gray-400"
                        :style="{ left: `${mark.percent}%` }"
                    >
                        {{ mark.label }}
                    </span>
                </div>
            </div>

            <!-- now line -->
            <div
                v-if="showNowLine"
                class="absolute top-8 bottom-8 w-px bg-primary z-10"
                :style="{ left: `calc(${staffColWidth} + ${nowPercent}%)` }"
            >
                <span
                    class="absolute -top-5 left-1/2 -translate-x-1/2 bg-primary text-white text-[10px] rounded px-1.5 py-0.5"
                >
                    Now
                </span>
            </div>

            <div
                v-for="branch in branches"
                class="grid gap-2 items-center py-3 border-t border-muted-light dark:border-white/10"
                :style="gridTemplate"
            >
                <div class="flex items-center">
                    <div class="flex -space-x-2">
                        <div
                            v-for="(initial, i) in branch.staffInitials.slice(
                                0,
                                3,
                            )"
                            :key="i"
                            class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center text-[10px] font-medium text-white"
                            :style="{ backgroundColor: avatarColor(i) }"
                        >
                            {{ initial }}
                        </div>
                    </div>
                    <span
                        v-if="branch.extraStaff > 0"
                        class="ml-1.5 text-[11px] text-muted font-medium dark:text-gray-400"
                    >
                        +{{ branch.extraStaff }}
                    </span>
                </div>

                <div class="relative h-10">
                    <div
                        v-for="shift in branch.shifts"
                        :key="shift.name"
                        class="absolute top-1 h-8 rounded-md px-2.5 flex flex-col justify-center overflow-hidden"
                        :style="{
                            left: `${hourToPercent(shift.start)}%`,
                            width: `${hourToPercent(shift.end) - hourToPercent(shift.start)}%`,
                            backgroundColor: roleColor(shift.role, 'bg'),
                        }"
                    >
                        <span
                            class="text-[11px] font-medium leading-tight truncate"
                            :style="{ color: roleColor(shift.role, 'text') }"
                        >
                            {{ shift.name }}
                        </span>
                        <span
                            class="text-[10px] leading-tight truncate opacity-70"
                            :style="{ color: roleColor(shift.role, 'text') }"
                        >
                            {{ formatHour(shift.start) }} -
                            {{ formatHour(shift.end) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- legend -->
        <div
            class="flex items-center gap-5 mt-4 pt-3 border-t border-muted-light dark:border-white/10"
        >
            <div
                v-for="role in roleLegend"
                :key="role.key"
                class="flex items-center gap-1.5"
            >
                <span
                    class="w-4 h-0.5 rounded"
                    :style="{ backgroundColor: roleColor(role.key, 'line') }"
                />
                <span class="text-[11px] text-muted dark:text-gray-400">{{ role.label }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

export type StaffRole = "doctor" | "nurse" | "caregiver";

export interface Shift {
    name: string;
    role: StaffRole;
    start: number; // decimal hour, e.g. 8.5 = 8:30 AM
    end: number;
}

export interface Branch {
    staffInitials: string[];
    extraStaff: number;
    shifts: Shift[];
}

const props = withDefaults(
    defineProps<{
        title?: string;
        dateLabel?: string;
        branches: Branch[];
        rangeStart?: number;
        rangeEnd?: number;
        nowHour?: number | null;
    }>(),
    {
        title: "Schedule & staff allocation",
        dateLabel: "Today",
        rangeStart: 8,
        rangeEnd: 18,
        nowHour: null,
    },
);

const emit = defineEmits<{ (e: "change-day", direction: 1 | -1): void }>();

function shiftDay(direction: 1 | -1) {
    emit("change-day", direction);
}

const gridTemplate = computed(() => ({
    gridTemplateColumns: `${staffColWidth} 1fr`,
}));
const staffColWidth = "110px";

const totalHours = computed(() => props.rangeEnd - props.rangeStart);

function hourToPercent(hour: number) {
    return ((hour - props.rangeStart) / totalHours.value) * 100;
}

const nowPercent = computed(() =>
    props.nowHour != null ? hourToPercent(props.nowHour) : 0,
);
const showNowLine = computed(
    () =>
        props.nowHour != null &&
        props.nowHour >= props.rangeStart &&
        props.nowHour <= props.rangeEnd,
);

function formatHour(hour: number) {
    const h24 = Math.floor(hour);
    const m = Math.round((hour - h24) * 60);
    const period = h24 >= 12 ? "PM" : "AM";
    const h12 = h24 % 12 === 0 ? 12 : h24 % 12;
    return `${h12}:${m.toString().padStart(2, "0")} ${period}`;
}

const hourMarks = computed(() => {
    const marks = [];
    for (let h = props.rangeStart; h <= props.rangeEnd; h += 2) {
        marks.push({
            hour: h,
            percent: hourToPercent(h),
            label: formatHour(h).replace(":00", ""),
        });
    }
    return marks;
});

const roleLegend: { key: StaffRole; label: string }[] = [
    { key: "doctor", label: "Doctor" },
    { key: "nurse", label: "Nurse" },
    { key: "caregiver", label: "Caregiver" },
];

const rolePalette: Record<
    StaffRole,
    { bg: string; text: string; line: string }
> = {
    doctor: { bg: "#D5E5FD", text: "#0F397B", line: "#3182ED" },
    nurse: { bg: "#E9E4FB", text: "#3A2E7A", line: "#7C6AE8" },
    caregiver: { bg: "#FDE2E2", text: "#7B1F1F", line: "#f87171" },
};

function roleColor(role: StaffRole, variant: "bg" | "text" | "line") {
    return rolePalette[role][variant];
}

const avatarPalette = ["#3182ED", "#0E7C7B", "#7C6AE8", "#f87171"];
function avatarColor(index: number) {
    return avatarPalette[index % avatarPalette.length];
}
</script>
