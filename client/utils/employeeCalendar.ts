import type { EmployeeScheduleEntry, EmployeeShiftEntry } from "~/types/schedule";
import { addDays, daysBetween, isoOf } from "~/composables/useCalendarMonth";
import { format24To12, formatDurationShort, formatTime } from "~/utils/time";

export type CalendarItemKind = "facility" | "adl" | "medical";

export interface EmployeeCalendarItem {
    key: string;
    kind: CalendarItemKind;
    patient: string;
    place: string;
    time: string;
    start: string;
    end: string | null;
    hours: number;
    status: string | null;
    code: string | null;
}

export const KIND_META: Record<CalendarItemKind, { label: string; bar: string; dot: string }> = {
    facility: {
        label: "Facility shift",
        bar: "border-teal-500 bg-teal-50 text-teal-900 dark:bg-teal-500/15 dark:text-teal-100",
        dot: "bg-teal-500",
    },
    adl: {
        label: "Activities of Daily Living (ADL)",
        bar: "border-amber-500 bg-amber-50 text-amber-900 dark:bg-amber-500/15 dark:text-amber-100",
        dot: "bg-amber-500",
    },
    medical: {
        label: "Medical Services",
        bar: "border-violet-500 bg-violet-50 text-violet-900 dark:bg-violet-500/15 dark:text-violet-100",
        dot: "bg-violet-500",
    },
};

const hoursRange = (start?: string | null, end?: string | null) =>
    start && end ? `${format24To12(start)} – ${format24To12(end)}` : "";

export function shiftItem(shift: EmployeeShiftEntry, fallbackStart: string): EmployeeCalendarItem {
    return {
        key: `shift-${shift.caregiver_shift_id}`,
        kind: "facility",
        patient: shift.resident_name,
        place: [shift.room_no ? `Room ${shift.room_no}` : "", shift.bed_no ? `Bed ${shift.bed_no}` : ""]
            .filter(Boolean)
            .join(" · "),
        time: [shift.note?.trim(), hoursRange(shift.start_time, shift.end_time)].filter(Boolean).join(" · "),
        start: isoOf(shift.admitted_at) || fallbackStart,
        end: shift.discharged_at ? isoOf(shift.discharged_at) : null,
        hours: 0,
        status: null,
        code: null,
    };
}

export function scheduleItem(entry: EmployeeScheduleEntry): EmployeeCalendarItem | null {
    const start = isoOf(entry.scheduled_at);
    if (!start) return null;

    const hours = (entry.duration_minutes ?? 0) / 60;
    const startAt = new Date(entry.scheduled_at!).getTime();
    const timedOutAt = entry.type !== "adl" ? entry.timed_out_at : null;
    const end = timedOutAt
        ? isoOf(timedOutAt)
        : hours >= 24
          ? addDays(start, Math.ceil(hours / 24) - 1)
          : isoOf(new Date(startAt + Math.max(hours * 3_600_000 - 60_000, 0)));

    const window = timedOutAt
        ? `${formatTime(entry.scheduled_at)} – ${formatTime(timedOutAt)} · timed out`
        : hoursRange(entry.start_time, entry.end_time) ||
          (entry.ends_at && hours < 24
              ? `${formatTime(entry.scheduled_at)} – ${formatTime(entry.ends_at)}`
              : formatTime(entry.scheduled_at));

    return {
        key: `schedule-${entry.schedule_id}`,
        kind: entry.type === "adl" ? "adl" : "medical",
        patient: entry.patient_name ?? "Patient",
        place: entry.services.join(", "),
        time: [entry.note?.trim(), window].filter(Boolean).join(" · "),
        start,
        end,
        hours,
        status: entry.status,
        code: entry.schedule_code,
    };
}

export function itemTag(item: EmployeeCalendarItem, iso: string) {
    if (item.kind === "facility") return "";

    if (item.kind === "adl" && item.hours >= 24) {
        const days = Math.ceil(item.hours / 24);
        const day = Math.min(Math.max(daysBetween(item.start, iso) + 1, 1), days);
        return `Day ${day}/${days}`;
    }

    if (item.kind === "adl") {
        const h = Math.round(item.hours * 10) / 10;
        return `${h} ${h === 1 ? "hr" : "hrs"}`;
    }

    return item.hours ? formatDurationShort(item.hours) : "";
}
