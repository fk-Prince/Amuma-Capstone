<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
    Check,
    ChevronLeft,
    ChevronRight,
    Clock,
    X,
    Plus,
} from "lucide-vue-next";
import { formatDate } from "~/utils/time";
import {
    ROUTE_LABELS,
    DOSAGE_UNIT_LABELS,
    dayLetters,
    months,
} from "~/utils/medication";
import type {
    Medication,
    MedicationSchedule,
    MarkDosePayload,
} from "~/types/medication";
import { frequencyOptions } from "~/types/medication";

import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";

interface DayColumn {
    label: string;
    date: number;
    fullDate: string;
}

interface PendingDose {
    med: Medication;
    time: string;
    date: string;
}

const props = withDefaults(
    defineProps<{
        medications?: Medication[];
        disabled?: boolean;
        savingDose?: boolean;
        view?: "client" | "provider";
        variant?: "calendar" | "preview";
    }>(),
    {
        medications: () => [],
        disabled: false,
        savingDose: false,
        view: "provider",
        variant: "calendar",
    },
);

const isProvider = computed(() => props.view === "provider");
const latestMedication = computed(() => props.medications[0] ?? null);

const scheduleKind = ref<"Scheduled" | "PRN">("Scheduled");

const now = new Date();
const currentMonthIndex = ref(now.getMonth());
const currentYear = ref(now.getFullYear());

const currentMonthLabel = computed(
    () => `${months[currentMonthIndex.value]} ${currentYear.value}`,
);

function shiftMonth(delta: number) {
    const date = new Date(
        currentYear.value,
        currentMonthIndex.value + delta,
        1,
    );

    currentMonthIndex.value = date.getMonth();
    currentYear.value = date.getFullYear();
}

const visibleMedications = computed(() =>
    props.medications.filter((med) => {
        if (med.kind !== scheduleKind.value) {
            return false;
        }

        if (!med.recorded_date) {
            return false;
        }

        const created = new Date(med.recorded_date);

        return (
            created.getMonth() === currentMonthIndex.value &&
            created.getFullYear() === currentYear.value
        );
    }),
);

function getDays(med: Medication): DayColumn[] {
    if (!med?.startDate) {
        return [];
    }

    const startDate = new Date(`${med.startDate}T00:00:00`);

    const duration = Number(med.durationLabel?.replace(" Days", "") || 1);

    if (Number.isNaN(duration) || duration <= 0) {
        return [];
    }

    return Array.from({ length: duration }, (_, index): DayColumn => {
        const current = new Date(startDate);

        current.setDate(startDate.getDate() + index);

        const year = current.getFullYear();
        const month = String(current.getMonth() + 1).padStart(2, "0");
        const day = String(current.getDate()).padStart(2, "0");

        return {
            label: dayLetters[current.getDay()] ?? "",
            date: current.getDate(),
            fullDate: `${year}-${month}-${day}`,
        };
    });
}

function isToday(dateStr: string) {
    const today = new Date().toISOString().split("T")[0];
    return dateStr === today;
}

function formatDosage(amount: string, unit: string) {
    const label = DOSAGE_UNIT_LABELS[unit] ?? unit;
    const number = Number(amount);

    return `${amount} ${number === 1 ? label : `${label}s`}`;
}

function formatFrequency(value: string) {
    return (
        frequencyOptions.find((option) => option.value === value)?.label ??
        value
    );
}

const FREQUENCY_INTERVAL_DAYS: Record<string, number> = {
    everyday: 1,
    every_2_days: 2,
    every_3_days: 3,
    every_week: 7,
};

function frequencyIntervalDays(frequency: string) {
    return FREQUENCY_INTERVAL_DAYS[frequency] ?? 1;
}

// Non-daily medications (e.g. "every 3 days") only have a dose due on
// every Nth day from their start date — the days in between are skipped
// rather than shown as an empty/missed dose.
function isDoseDay(med: Medication, dateStr: string) {
    if (!med.startDate) return true;

    const interval = frequencyIntervalDays(med.frequency);
    if (interval <= 1) return true;

    const start = new Date(`${med.startDate}T00:00:00`);
    const current = new Date(`${dateStr}T00:00:00`);

    const diffDays = Math.round(
        (current.getTime() - start.getTime()) / 86400000,
    );

    return diffDays >= 0 && diffDays % interval === 0;
}

function formatTime(value: string) {
    const [hourText, minute] = value.split(":");
    const hour = Number(hourText);
    const period = hour >= 12 ? "pm" : "am";
    const display = hour % 12 === 0 ? 12 : hour % 12;

    return `${display}:${minute}${period}`;
}

function findSchedule(
    med: Medication,
    time: string,
    date: string,
): MedicationSchedule | undefined {
    return med.schedules?.find(
        (item) => item.date === date && item.time === time,
    );
}

function isTaken(med: Medication, time: string, date: string) {
    return findSchedule(med, time, date)?.status === "taken";
}

function isMissed(med: Medication, time: string, date: string) {
    const schedule = findSchedule(med, time, date);
    if (schedule?.status === "missed") {
        return true;
    }
    if (schedule?.status === "taken") {
        return false;
    }
    const now = new Date();
    const dateParts = date.split("-");
    const timeParts = time.split(":");
    const year = Number(dateParts[0]);
    const month = Number(dateParts[1]);
    const day = Number(dateParts[2]);
    const hour = Number(timeParts[0]);
    const minute = Number(timeParts[1]);

    if (
        Number.isNaN(year) ||
        Number.isNaN(month) ||
        Number.isNaN(day) ||
        Number.isNaN(hour) ||
        Number.isNaN(minute)
    ) {
        return false;
    }
    const doseDateTime = new Date(year, month - 1, day, hour, minute);
    return doseDateTime < now;
}

function getEndDate(startDate: string, durationLabel: string) {
    const start = new Date(startDate);

    const duration = Number(durationLabel.replace(" Days", ""));

    if (Number.isNaN(duration) || duration <= 0) {
        return "";
    }

    start.setDate(start.getDate() + duration - 1);

    return start.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

const pendingDose = ref<PendingDose | null>(null);

const pendingDoseIsUndo = computed(() => {
    if (!pendingDose.value) return false;
    const { med, time, date } = pendingDose.value;
    return isTaken(med, time, date);
});

const confirmTitle = computed(() =>
    pendingDoseIsUndo.value ? "Undo this dose?" : "Mark dose as taken?",
);

const confirmMessage = computed(() => {
    if (!pendingDose.value) return "";
    const { med, time, date } = pendingDose.value;
    const dateLabel = new Date(date).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
    });
    return `${med.name} · ${formatTime(time)} · ${dateLabel}`;
});

const confirmDescription = computed(() =>
    pendingDoseIsUndo.value
        ? "This will unmark the dose as taken."
        : "This confirms the dose was given at this time.",
);

const confirmLabel = computed(() =>
    pendingDoseIsUndo.value ? "Undo" : "Confirm",
);
const confirmVariant = computed(() =>
    pendingDoseIsUndo.value ? "danger" : "default",
);

function requestToggle(med: Medication, time: string, date: string) {
    if (props.disabled || !isProvider.value) return;
    pendingDose.value = { med, time, date };
}

function confirmToggle() {
    if (!pendingDose.value) return;
    const { med, time, date } = pendingDose.value;
    const existingSchedule = findSchedule(med, time, date);
    const wasTaken = existingSchedule?.status === "taken";
    emit("mark-dose", {
        medication_id: med.id,
        schedule_id: wasTaken ? existingSchedule?.id : undefined,
        date,
        time,
        status: wasTaken ? "removed" : "taken",
    });

    // pendingDose.value = null;
}
watch(
    () => props.savingDose,
    (loading, wasLoading) => {
        if (wasLoading && !loading) {
            pendingDose.value = null;
        }
    },
);

function cancelToggle() {
    pendingDose.value = null;
}

const emit = defineEmits<{
    (e: "add-medication"): void;
    (e: "mark-dose", payload: MarkDosePayload): void;
}>();
</script>

<template>
    <div v-if="variant === 'preview'" class="space-y-3">
        <div
            v-if="!latestMedication"
            class="py-8 text-center text-sm text-gray-400 dark:text-gray-500"
        >
            No medications recorded.
        </div>

        <div
            v-else
            class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div class="flex items-start justify-between gap-3 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ latestMedication.name }}
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        {{ latestMedication.strength }} ·
                        {{
                            formatDosage(
                                latestMedication.dosageAmount,
                                latestMedication.dosageUnit,
                            )
                        }}
                        ·
                        {{
                            ROUTE_LABELS[latestMedication.route] ??
                            latestMedication.route
                        }}
                    </p>
                </div>

                <span
                    class="shrink-0 rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600 dark:border-white/10 dark:text-gray-400"
                >
                    {{ formatFrequency(latestMedication.frequency) }}
                </span>
            </div>

            <div
                class="grid grid-cols-2 gap-3 border-t border-gray-50 px-5 py-4 sm:grid-cols-3 dark:border-white/10"
            >
                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Instructions
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ latestMedication.instructions || "—" }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Taken for
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ latestMedication.takenFor || "—" }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Duration
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ latestMedication.durationLabel }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <div
                    class="flex items-center rounded-xl border bg-white px-2 py-1.5 shadow-sm dark:bg-secondary dark:border-white/10"
                >
                    <button
                        class="p-2 text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-400"
                        @click="shiftMonth(-1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <span class="min-w-[130px] text-center text-sm font-medium">
                        {{ currentMonthLabel }}
                    </span>

                    <button
                        class="p-2 text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-400"
                        @click="shiftMonth(1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <div class="rounded-full border bg-white p-1 dark:bg-secondary dark:border-white/10">
                    <button
                        class="rounded-full px-4 py-1.5 text-sm"
                        :class="
                            scheduleKind === 'Scheduled'
                                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'text-gray-500 dark:text-gray-400'
                        "
                        @click="scheduleKind = 'Scheduled'"
                    >
                        Scheduled
                    </button>

                    <button
                        class="rounded-full px-4 py-1.5 text-sm"
                        :class="
                            scheduleKind === 'PRN'
                                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'text-gray-500 dark:text-gray-400'
                        "
                        @click="scheduleKind = 'PRN'"
                    >
                        PRN
                    </button>
                </div>
            </div>

            <button
                v-if="isProvider"
                class="rounded-xl bg-primary flex items-center gap-3 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary/50"
                @click="$emit('add-medication')"
            >
                <Plus class="h-4 w-4" />

                Add Medication
            </button>
        </div>

        <div
            v-for="med in visibleMedications"
            :key="med.id"
            class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div
                class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-50 px-5 py-4 dark:border-white/10"
            >
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ med.name }}
                    </h2>

                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        {{ med.strength }} ·
                        {{ formatDosage(med.dosageAmount, med.dosageUnit) }}
                        · {{ ROUTE_LABELS[med.route] ?? med.route }}
                    </p>

                    <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">
                        Prescribed at {{ formatDate(med.recorded_date) }}
                    </p>
                </div>

                <div class="flex flex-col items-end gap-1">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        This will run through
                        {{ formatDate(med.startDate) }}
                        -
                        {{ getEndDate(med.startDate, med.durationLabel) }}
                    </p>
                    <label
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 dark:border-white/10 dark:text-gray-400"
                    >
                        {{ med.durationLabel }}
                    </label>
                </div>
            </div>

            <div
                class="grid grid-cols-1 gap-3 border-b border-gray-50 px-5 py-4 sm:grid-cols-5 dark:border-white/10"
            >
                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Medication
                    </p>

                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ med.instructions }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Taken Every
                    </p>

                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ formatFrequency(med.frequency) }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Taken for
                    </p>

                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ med.takenFor }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Dosage
                    </p>

                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ formatDosage(med.dosageAmount, med.dosageUnit) }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                    >
                        Route
                    </p>

                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white">
                        {{ ROUTE_LABELS[med.route] ?? med.route }}
                    </p>
                </div>
            </div>

            <div v-if="med.kind === 'Scheduled'" class="px-3 sm:px-5 py-4">
                <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-white/10">
                    <div class="min-w-max">
                        <div
                            class="flex border-b border-gray-100 bg-gray-50/80 dark:border-white/10 dark:bg-white/5"
                        >
                            <div
                                class="sticky left-0 z-10 w-24 shrink-0 border-r border-gray-100 bg-gray-50/80 px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:border-white/10 dark:bg-white/5 dark:text-gray-500"
                            >
                                Time
                            </div>

                            <div class="flex">
                                <div
                                    v-for="day in getDays(med)"
                                    :key="day.fullDate"
                                    class="flex w-11 shrink-0 flex-col items-center gap-0.5 py-2.5"
                                    :class="
                                        isToday(day.fullDate)
                                            ? 'bg-emerald-50 dark:bg-emerald-500/10'
                                            : ''
                                    "
                                >
                                    <span
                                        class="text-[10px] font-medium text-gray-400 dark:text-gray-500"
                                    >
                                        {{ day.label }}
                                    </span>

                                    <span
                                        class="flex h-5 w-5 items-center justify-center rounded-full text-xs font-semibold"
                                        :class="
                                            isToday(day.fullDate)
                                                ? 'bg-emerald-500 text-white'
                                                : 'text-gray-700 dark:text-gray-200'
                                        "
                                    >
                                        {{ day.date }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-for="(time, rowIndex) in med.times"
                            :key="time"
                            class="flex items-center"
                            :class="rowIndex % 2 ? 'bg-gray-50/60 dark:bg-white/5' : 'bg-white dark:bg-secondary'"
                        >
                            <div
                                class="sticky left-0 z-10 flex w-24 shrink-0 items-center gap-1.5 border-r border-gray-100 px-3 py-2.5 text-xs font-medium text-gray-600 dark:border-white/10 dark:text-gray-400"
                                :class="
                                    rowIndex % 2 ? 'bg-gray-50 dark:bg-white/5' : 'bg-white dark:bg-secondary'
                                "
                            >
                                <Clock class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" />
                                {{ formatTime(time) }}
                            </div>

                            <div class="flex">
                                <div
                                    v-for="day in getDays(med)"
                                    :key="`${time}-${day.fullDate}`"
                                    class="flex w-11 justify-center py-2"
                                    :class="
                                        isToday(day.fullDate)
                                            ? 'bg-emerald-50/50 dark:bg-emerald-500/10'
                                            : ''
                                    "
                                >
                                    <button
                                        v-if="isDoseDay(med, day.fullDate)"
                                        type="button"
                                        class="flex h-7 w-7 items-center justify-center rounded-full border transition disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10"
                                        :class="
                                            isTaken(med, time, day.fullDate)
                                                ? 'border-emerald-500 bg-emerald-500 text-white'
                                                : isMissed(
                                                        med,
                                                        time,
                                                        day.fullDate,
                                                    )
                                                  ? 'border-rose-300 bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-300'
                                                  : 'border-gray-200 bg-white hover:border-emerald-400 hover:bg-emerald-50 dark:border-white/10 dark:bg-secondary dark:hover:bg-emerald-500/10'
                                        "
                                        :disabled="disabled || !isProvider"
                                        :aria-label="
                                            (isTaken(med, time, day.fullDate)
                                                ? 'Mark as not taken: '
                                                : isMissed(
                                                        med,
                                                        time,
                                                        day.fullDate,
                                                    )
                                                  ? 'Missed dose: '
                                                  : 'Mark as taken: ') +
                                            formatTime(time) +
                                            ' on day ' +
                                            day.date
                                        "
                                        @click="
                                            requestToggle(
                                                med,
                                                time,
                                                day.fullDate,
                                            )
                                        "
                                    >
                                        <Check
                                            v-if="
                                                isTaken(med, time, day.fullDate)
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                        <X
                                            v-else-if="
                                                isMissed(
                                                    med,
                                                    time,
                                                    day.fullDate,
                                                )
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                    </button>

                                    <span
                                        v-else
                                        class="flex h-7 w-7 items-center justify-center text-xs text-gray-300 dark:text-gray-500"
                                        aria-hidden="true"
                                    >
                                        –
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-3 flex items-center gap-4 text-[11px] text-gray-400 dark:text-gray-500"
                >
                    <div class="flex items-center gap-1.5">
                        <span
                            class="h-3 w-3 rounded-full border border-emerald-500 bg-emerald-500"
                        ></span>
                        Taken
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span
                            class="h-3 w-3 rounded-full border border-rose-300 bg-rose-50 dark:bg-rose-500/10"
                        ></span>
                        Missed
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span
                            class="h-3 w-3 rounded-full border border-gray-200 bg-white dark:border-white/10 dark:bg-secondary"
                        ></span>
                        Not yet given
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="!visibleMedications.length"
            class="rounded-2xl border border-dashed border-gray-200 bg-white py-14 text-center text-sm text-gray-400 dark:border-white/10 dark:bg-secondary dark:text-gray-500"
        >
            No {{ scheduleKind.toLowerCase() }} medications created in
            {{ currentMonthLabel }}.
        </div>

        <ConfirmDialog
            :open="!!pendingDose"
            :title="confirmTitle"
            :message="confirmMessage"
            :description="confirmDescription"
            :confirm-label="confirmLabel"
            :variant="confirmVariant"
            :loading="savingDose"
            @confirm="confirmToggle"
            @cancel="cancelToggle"
        />
    </div>
</template>
