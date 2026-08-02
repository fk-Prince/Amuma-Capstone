<template>
    <div ref="rootEl" class="relative font-primary" :class="className">
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-semibold text-slate-700"
        >
            {{ label }}
            <span v-if="required" class="text-danger ml-0.5">*</span>
        </label>

        <!-- Trigger input -->
        <button
            type="button"
            class="flex w-full items-center justify-between rounded-lg border-[1.5px] bg-slate-50 px-3.5 py-2.5 text-left text-sm transition"
            :class="
                open
                    ? 'border-blue-500 ring-2 ring-blue-500/15'
                    : 'border-slate-200'
            "
            @click="open = !open"
        >
            <span :class="displayLabel ? 'text-slate-800' : 'text-slate-400'">
                {{ displayLabel || placeholder }}
            </span>
            <Calendar class="h-4 w-4 flex-shrink-0 text-slate-400" />
        </button>

        <!-- Popup -->
        <Transition name="popup">
            <div
                v-if="open"
                class="absolute left-0 top-full z-20 mt-2 w-[300px] rounded-2xl border border-slate-100 bg-white p-4 shadow-xl select-none"
            >
                <!-- Header -->
                <div class="mb-4 flex items-center justify-between">
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                        @click="goToPrevMonth"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button
                                type="button"
                                class="flex items-center gap-1 text-sm font-semibold text-slate-800 transition hover:text-blue-500"
                                @click="toggleYearMenu"
                            >
                                {{ viewYear }}
                                <ChevronDown
                                    class="h-3.5 w-3.5 text-slate-400"
                                />
                            </button>
                            <div
                                v-if="showYearMenu"
                                class="absolute left-1/2 top-full z-10 mt-1 max-h-48 w-20 -translate-x-1/2 overflow-y-auto rounded-lg border border-slate-100 bg-white py-1 shadow-lg"
                            >
                                <button
                                    v-for="y in yearOptions"
                                    :key="y"
                                    type="button"
                                    class="block w-full px-3 py-1.5 text-center text-sm hover:bg-slate-50"
                                    :class="
                                        y === viewYear
                                            ? 'text-blue-500 font-semibold'
                                            : 'text-slate-600'
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
                                class="flex items-center gap-1 text-sm font-semibold text-slate-800 transition hover:text-blue-500"
                                @click="toggleMonthMenu"
                            >
                                {{ monthNames[viewMonth] }}
                                <ChevronDown
                                    class="h-3.5 w-3.5 text-slate-400"
                                />
                            </button>
                            <div
                                v-if="showMonthMenu"
                                class="absolute left-1/2 top-full z-10 mt-1 max-h-48 w-24 -translate-x-1/2 overflow-y-auto rounded-lg border border-slate-100 bg-white py-1 shadow-lg"
                            >
                                <button
                                    v-for="(m, idx) in monthNames"
                                    :key="m"
                                    type="button"
                                    class="block w-full px-3 py-1.5 text-center text-sm hover:bg-slate-50"
                                    :class="
                                        idx === viewMonth
                                            ? 'text-blue-500 font-semibold'
                                            : 'text-slate-600'
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
                        class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                        @click="goToNextMonth"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <!-- Weekdays -->
                <div class="mb-1 grid grid-cols-7">
                    <span
                        v-for="d in weekdayLabels"
                        :key="d"
                        class="flex h-8 items-center justify-center text-xs font-semibold text-slate-500"
                    >
                        {{ d }}
                    </span>
                </div>

                <!-- Days -->
                <div class="grid grid-cols-7 gap-y-1">
                    <button
                        v-for="cell in calendarCells"
                        :key="cell.key"
                        type="button"
                        class="mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm transition disabled:hover:bg-transparent"
                        :class="cellClass(cell)"
                        :disabled="isDateDisabled(cell)"
                        @click="selectDay(cell)"
                    >
                        {{ cell.day }}
                    </button>
                </div>

                <!-- Footer -->
                <div class="mt-4 flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-full px-4 py-2.5 text-sm font-semibold text-blue-500 transition hover:bg-blue-50"
                        @click="selectToday"
                    >
                        Today
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-full bg-blue-500 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!selectedDate"
                        @click="applySelection"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import {
    computed,
    ref,
    watch,
    onMounted,
    onBeforeUnmount,
    h,
    type FunctionalComponent,
} from "vue";

defineOptions({ name: "DatePickerField" });

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    min: {
        type: String,
        default: "",
    },
    max: {
        type: String,
        default: "",
    },
    label: { type: String, default: "" },
    placeholder: { type: String, default: "Please select a period." },
    required: { type: Boolean, default: false },
    className: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue", "apply"]);

// ---- icons (inline, no external deps) ----
const icon =
    (d: string): FunctionalComponent<{ class?: string }> =>
    (p) =>
        h(
            "svg",
            {
                class: p.class,
                viewBox: "0 0 24 24",
                fill: "none",
                stroke: "currentColor",
                "stroke-width": "2.5",
                "stroke-linecap": "round",
                "stroke-linejoin": "round",
            },
            [h("path", { d })],
        );

const ChevronLeft = icon("M15 18l-6-6 6-6");
const ChevronRight = icon("M9 18l6-6-6-6");
const ChevronDown = icon("M6 9l6 6 6-6");
const Calendar: FunctionalComponent<{ class?: string }> = (p) =>
    h(
        "svg",
        {
            class: p.class,
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: "currentColor",
            "stroke-width": "2",
            "stroke-linecap": "round",
            "stroke-linejoin": "round",
        },
        [
            h("rect", { x: "3", y: "5", width: "18", height: "16", rx: "2" }),
            h("path", { d: "M16 3v4M8 3v4M3 10h18" }),
        ],
    );

// ---- state ----
const rootEl = ref<HTMLElement | null>(null);
const open = ref(false);

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

function toDate(val: string | null): Date | null {
    if (!val) return null;
    // Parse as local date (avoid UTC shift from `new Date("YYYY-MM-DD")`)
    const [y, m, d] = val.split("-").map(Number);
    if (!y || !m || !d) return null;
    const date = new Date(y, m - 1, d);
    return isNaN(date.getTime()) ? null : date;
}

function toValueString(date: Date | null): string {
    if (!date) return "";
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

const today = new Date();

// Default to today when no modelValue is passed in.
const selectedDate = ref<Date | null>(toDate(props.modelValue) ?? today);
const appliedDate = ref<Date | null>(toDate(props.modelValue) ?? today);

const viewYear = ref(selectedDate.value?.getFullYear() ?? today.getFullYear());
const viewMonth = ref(selectedDate.value?.getMonth() ?? today.getMonth());

const minDate = computed(() => toDate(props.min));
const maxDate = computed(() => toDate(props.max));

watch(
    () => props.modelValue,
    (val) => {
        const resolved = toDate(val) ?? today;
        selectedDate.value = resolved;
        appliedDate.value = resolved;
    },
);

// Let the parent's v-model know about the default immediately,
// so the field and the bound value start in sync.
onMounted(() => {
    if (!toDate(props.modelValue)) {
        emit("update:modelValue", toValueString(today));
    }
});

const displayLabel = computed(() => {
    if (!appliedDate.value) return "";
    return appliedDate.value.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
});

// close on outside click
function handleClickOutside(e: MouseEvent) {
    if (
        open.value &&
        rootEl.value &&
        !rootEl.value.contains(e.target as Node)
    ) {
        open.value = false;
    }
}
onMounted(() => document.addEventListener("mousedown", handleClickOutside));
onBeforeUnmount(() =>
    document.removeEventListener("mousedown", handleClickOutside),
);

// ---- month navigation ----
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
    } else viewMonth.value -= 1;
}
function goToNextMonth() {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value += 1;
    } else viewMonth.value += 1;
}

// ---- calendar grid ----
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
    for (let day = 1; day <= daysInMonth; day++) {
        cells.push({
            key: `cur-${day}`,
            day,
            month: viewMonth.value,
            year: viewYear.value,
            inCurrentMonth: true,
        });
    }
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
function isDateDisabled(cell: CalendarCell) {
    const cellDate = new Date(cell.year, cell.month, cell.day);
    if (minDate.value && cellDate < minDate.value) return true;
    if (maxDate.value && cellDate > maxDate.value) return true;
    return false;
}
function cellClass(cell: CalendarCell) {
    const isSelected = isSameDay(cell, selectedDate.value);
    if (isDateDisabled(cell)) {
        return "text-slate-200 cursor-not-allowed";
    }
    if (isSelected) return "bg-blue-500 text-white font-semibold";
    if (!cell.inCurrentMonth) return "text-slate-300 hover:bg-slate-50";
    if (isToday(cell)) return "text-blue-500 font-semibold hover:bg-blue-50";
    return "text-slate-700 hover:bg-slate-100";
}

function selectDay(cell: CalendarCell) {
    if (isDateDisabled(cell)) return;
    const date = new Date(cell.year, cell.month, cell.day);
    selectedDate.value = date;
    if (!cell.inCurrentMonth) {
        viewYear.value = cell.year;
        viewMonth.value = cell.month;
    }
    emit("update:modelValue", toValueString(date));
}

function selectToday() {
    if (minDate.value && today < minDate.value) return;
    if (maxDate.value && today > maxDate.value) return;
    selectedDate.value = today;
    viewYear.value = today.getFullYear();
    viewMonth.value = today.getMonth();
    emit("update:modelValue", toValueString(today));
}

function applySelection() {
    if (!selectedDate.value) return;
    appliedDate.value = selectedDate.value;
    emit("apply", toValueString(selectedDate.value));
    open.value = false;
}
</script>

<style scoped>
.popup-enter-active,
.popup-leave-active {
    transition:
        opacity 0.16s ease,
        transform 0.16s ease;
}
.popup-enter-from,
.popup-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}
.popup-enter-to,
.popup-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}
</style>
