<template>
    <div
        class="w-[300px] rounded-2xl bg-white shadow-xl border border-slate-100 p-4 font-primary select-none dark:bg-secondary dark:border-white/10"
    >
        <!-- Header: prev / year / month / next -->
        <div class="flex items-center justify-between mb-4">
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                @click="goToPrevMonth"
                aria-label="Previous month"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-1 text-sm font-semibold text-slate-800 hover:text-blue-500 transition dark:text-white dark:hover:text-blue-300"
                        @click="toggleYearMenu"
                    >
                        {{ viewYear }}
                        <ChevronDown class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500" />
                    </button>

                    <div
                        v-if="showYearMenu"
                        class="absolute left-1/2 top-full z-10 mt-1 max-h-48 w-20 -translate-x-1/2 overflow-y-auto rounded-lg border border-slate-100 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-secondary"
                    >
                        <button
                            v-for="y in yearOptions"
                            :key="y"
                            type="button"
                            class="block w-full px-3 py-1.5 text-center text-sm hover:bg-slate-50 dark:hover:bg-white/5"
                            :class="
                                y === viewYear
                                    ? 'text-blue-500 font-semibold dark:text-blue-300'
                                    : 'text-slate-600 dark:text-gray-400'
                            "
                            @click="selectYear(y)"
                        >
                            {{ y }}
                        </button>
                    </div>
                </div>

                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-1 text-sm font-semibold text-slate-800 hover:text-blue-500 transition dark:text-white dark:hover:text-blue-300"
                        @click="toggleMonthMenu"
                    >
                        {{ monthNames[viewMonth] }}
                        <ChevronDown class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500" />
                    </button>

                    <div
                        v-if="showMonthMenu"
                        class="absolute left-1/2 top-full z-10 mt-1 max-h-48 w-24 -translate-x-1/2 overflow-y-auto rounded-lg border border-slate-100 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-secondary"
                    >
                        <button
                            v-for="(m, idx) in monthNames"
                            :key="m"
                            type="button"
                            class="block w-full px-3 py-1.5 text-center text-sm hover:bg-slate-50 dark:hover:bg-white/5"
                            :class="
                                idx === viewMonth
                                    ? 'text-blue-500 font-semibold dark:text-blue-300'
                                    : 'text-slate-600 dark:text-gray-400'
                            "
                            @click="selectMonth(idx)"
                        >
                            {{ m }}
                        </button>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                @click="goToNextMonth"
                aria-label="Next month"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
        </div>

        <!-- Weekday labels -->
        <div class="grid grid-cols-7 mb-1">
            <span
                v-for="d in weekdayLabels"
                :key="d"
                class="flex h-8 items-center justify-center text-xs font-semibold text-slate-500 dark:text-gray-400"
            >
                {{ d }}
            </span>
        </div>

        <!-- Date grid -->
        <div class="grid grid-cols-7 gap-y-1">
            <button
                v-for="cell in calendarCells"
                :key="cell.key"
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-full text-sm transition mx-auto"
                :class="cellClass(cell)"
                @click="selectDay(cell)"
            >
                {{ cell.day }}
            </button>
        </div>

        <!-- Apply -->
        <button
            type="button"
            class="mt-4 w-full rounded-full bg-blue-500 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!selectedDate"
            @click="applySelection"
        >
            Apply
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, h, type FunctionalComponent } from "vue";

defineOptions({ name: "DatePicker" });

// Lightweight inline icons so this component has no external icon
// dependency. Swap these for your project's icon set if you have one
// (e.g. lucide-vue-next's ChevronLeft / ChevronRight / ChevronDown).
const ChevronLeft: FunctionalComponent<{ class?: string }> = (props) =>
    h(
        "svg",
        {
            class: props.class,
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2.5",
            "stroke-linecap": "round",
            "stroke-linejoin": "round",
        },
        [h("path", { d: "M15 18l-6-6 6-6" })],
    );

const ChevronRight: FunctionalComponent<{ class?: string }> = (props) =>
    h(
        "svg",
        {
            class: props.class,
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2.5",
            "stroke-linecap": "round",
            "stroke-linejoin": "round",
        },
        [h("path", { d: "M9 18l6-6-6-6" })],
    );

const ChevronDown: FunctionalComponent<{ class?: string }> = (props) =>
    h(
        "svg",
        {
            class: props.class,
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2.5",
            "stroke-linecap": "round",
            "stroke-linejoin": "round",
        },
        [h("path", { d: "M6 9l6 6 6-6" })],
    );

const props = defineProps({
    modelValue: {
        type: [Date, String, null] as unknown as () => Date | string | null,
        default: null,
    },
});

const emit = defineEmits(["update:modelValue", "apply"]);

const monthNames = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
];
const weekdayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

function toDate(val: Date | string | null): Date | null {
    if (!val) return null;
    const d = val instanceof Date ? val : new Date(val);
    return isNaN(d.getTime()) ? null : d;
}

const selectedDate = ref<Date | null>(toDate(props.modelValue));
const today = new Date();

const viewYear = ref(selectedDate.value?.getFullYear() ?? today.getFullYear());
const viewMonth = ref(selectedDate.value?.getMonth() ?? today.getMonth());

watch(
    () => props.modelValue,
    (val) => {
        selectedDate.value = toDate(val);
    },
);

const showYearMenu = ref(false);
const showMonthMenu = ref(false);

function toggleYearMenu() {
    showYearMenu.value = !showYearMenu.value;
    showMonthMenu.value = false;
}
function toggleMonthMenu() {
    showMonthMenu.value = !showMonthMenu.value;
    showYearMenu.value = false;
}

const yearOptions = computed(() => {
    const base = today.getFullYear();
    const years = [];
    for (let y = base - 5; y <= base + 5; y++) years.push(y);
    return years;
});

function selectYear(y: number) {
    viewYear.value = y;
    showYearMenu.value = false;
}
function selectMonth(idx: number) {
    viewMonth.value = idx;
    showMonthMenu.value = false;
}

function goToPrevMonth() {
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value -= 1;
    } else {
        viewMonth.value -= 1;
    }
}
function goToNextMonth() {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value += 1;
    } else {
        viewMonth.value += 1;
    }
}

interface CalendarCell {
    key: string;
    day: number;
    month: number;
    year: number;
    inCurrentMonth: boolean;
}

const calendarCells = computed<CalendarCell[]>(() => {
    const firstOfMonth = new Date(viewYear.value, viewMonth.value, 1);
    const startWeekday = firstOfMonth.getDay();
    const daysInMonth = new Date(
        viewYear.value,
        viewMonth.value + 1,
        0,
    ).getDate();
    const daysInPrevMonth = new Date(
        viewYear.value,
        viewMonth.value,
        0,
    ).getDate();

    const cells: CalendarCell[] = [];

    // leading days from previous month
    for (let i = startWeekday - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        const month = viewMonth.value === 0 ? 11 : viewMonth.value - 1;
        const year =
            viewMonth.value === 0 ? viewYear.value - 1 : viewYear.value;
        cells.push({
            key: `prev-${day}`,
            day,
            month,
            year,
            inCurrentMonth: false,
        });
    }

    // current month days
    for (let day = 1; day <= daysInMonth; day++) {
        cells.push({
            key: `cur-${day}`,
            day,
            month: viewMonth.value,
            year: viewYear.value,
            inCurrentMonth: true,
        });
    }

    // trailing days to complete the final week (grid always multiple of 7)
    const remainder = cells.length % 7;
    if (remainder !== 0) {
        const trailing = 7 - remainder;
        const month = viewMonth.value === 11 ? 0 : viewMonth.value + 1;
        const year =
            viewMonth.value === 11 ? viewYear.value + 1 : viewYear.value;
        for (let day = 1; day <= trailing; day++) {
            cells.push({
                key: `next-${day}`,
                day,
                month,
                year,
                inCurrentMonth: false,
            });
        }
    }

    return cells;
});

function isSameDay(cell: CalendarCell, date: Date | null) {
    if (!date) return false;
    return (
        cell.day === date.getDate() &&
        cell.month === date.getMonth() &&
        cell.year === date.getFullYear()
    );
}

function isToday(cell: CalendarCell) {
    return isSameDay(cell, today);
}

function cellClass(cell: CalendarCell) {
    const isSelected = isSameDay(cell, selectedDate.value);

    if (isSelected) {
        return "bg-blue-500 text-white font-semibold";
    }
    if (!cell.inCurrentMonth) {
        return "text-slate-300 hover:bg-slate-50 dark:text-gray-500 dark:hover:bg-white/5";
    }
    if (isToday(cell)) {
        return "text-blue-500 font-semibold hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-500/10";
    }
    return "text-slate-700 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10";
}

function selectDay(cell: CalendarCell) {
    const date = new Date(cell.year, cell.month, cell.day);
    selectedDate.value = date;

    if (!cell.inCurrentMonth) {
        viewYear.value = cell.year;
        viewMonth.value = cell.month;
    }

    emit("update:modelValue", date);
}

function applySelection() {
    if (selectedDate.value) {
        emit("apply", selectedDate.value);
    }
}
</script>
