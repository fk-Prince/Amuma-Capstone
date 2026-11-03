import type { ScheduleAssignee, ScheduleItem } from "~/types/schedule";
import { addDays, daysBetween, isoOf } from "~/composables/useCalendarMonth";
import { formatDuration } from "~/utils/time";

export interface AdlPlan {
    schedule: ScheduleItem;
    patient: string;
    hours: number;
    days: number;
    startAt: number;
    start: string;
    end: string;
    label: string;
    caregivers: ScheduleAssignee[];
    needsStaff: boolean;
}

const OPEN = ["pending", "ongoing"];

export function adlPlanOf(schedule: ScheduleItem): AdlPlan | null {
    const service = (schedule.services ?? []).find((s) => s.type === "ADL");
    if (!service && schedule.type !== "adl") return null;

    const startAt = new Date(schedule.scheduled_at ?? `${schedule.scheduled_date}T00:00:00`);
    if (Number.isNaN(startAt.getTime())) return null;

    const hours = Number(service?.duration ?? schedule.total_hours ?? 0) || 0;
    const days = Math.max(1, Math.ceil(hours / 24));
    const start = isoOf(startAt);

    const end =
        hours >= 24
            ? addDays(start, days - 1)
            : isoOf(new Date(startAt.getTime() + Math.max(hours * 3_600_000 - 60_000, 0)));

    const caregivers = (service?.assignees ?? []).filter((a) => a.is_active !== false);

    return {
        schedule,
        patient: schedule.patient?.full_name ?? "Patient",
        hours,
        days,
        startAt: startAt.getTime(),
        start,
        end,
        label: formatDuration(hours) || `${hours} hrs`,
        caregivers,
        needsStaff: OPEN.includes(schedule.status?.toLowerCase() ?? "") && !caregivers.length,
    };
}

export function adlPlanTag(plan: AdlPlan, iso: string) {
    if (plan.hours < 24) return `${plan.hours} ${plan.hours === 1 ? "hr" : "hrs"}`;

    const day = Math.min(Math.max(daysBetween(plan.start, iso) + 1, 1), plan.days);
    return `Day ${day}/${plan.days}`;
}
