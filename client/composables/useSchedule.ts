import { computed, ref } from "vue";
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";

export interface UseScheduleProps {
    schedules?: ScheduleItem[];
    date?: string;
    rangeEnd?: string;
    startHour?: number;
    endHour?: number;
}

export interface DayGroup {
    date: string;
    dateLabel: string;
    count: number;
    unassignedCount: number;
    isToday: boolean;
    hours: { value: number; label: string }[];
    hourColumnCount: number;
}

const HOUR_WIDTH = 500;

export function useSchedule(props: UseScheduleProps = {}) {
    function toLocalDateString(d: Date) {
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(
            2,
            "0",
        )}-${String(d.getDate()).padStart(2, "0")}`;
    }

    function today() {
        return toLocalDateString(new Date());
    }

    const selectedDate = ref(props.date ?? today());
    const rangeEnd = ref(props.rangeEnd ?? props.date ?? today());

    function jumpToToday() {
        const t = today();
        selectedDate.value = t;
        rangeEnd.value = t;
    }

    function formatHourLabel(hour24: number) {
        const period = hour24 >= 12 ? "PM" : "AM";
        const display = hour24 % 12 === 0 ? 12 : hour24 % 12;

        return `${display} ${period}`;
    }

    function formatDate(date?: string | null) {
        if (!date) return "";

        const parsed = new Date(
            date.includes("T")
                ? date
                : `${date}T00:00:00`,
        );

        if (Number.isNaN(parsed.getTime())) {
            return "";
        }

        return parsed.toLocaleDateString("en-US", {
            weekday: "long",
            month: "long",
            day: "numeric",
        });
    }

    function parseTimeToMinutes(label: string | null | undefined) {
        if (!label) return null;

        const match = label.match(/(\d{1,2}):(\d{2})\s?(AM|PM)/i);

        if (!match) return null;

        let hour = Number(match[1]);
        const minute = Number(match[2]);
        const period = match[3]?.toUpperCase();

        if (period === "PM" && hour !== 12) hour += 12;
        if (period === "AM" && hour === 12) hour = 0;

        return hour * 60 + minute;
    }

    function getScheduleTimes(schedule: ScheduleItem) {
        if (schedule.start_time) {
            return {
                start: schedule.start_time,
                end: schedule.end_time ?? schedule.start_time,
            };
        }

        return {
            start: "00:00 AM",
            end: "11:59 PM",
        };
    }

    function getScheduleDate(schedule: ScheduleItem): string | null {
        const scheduledDate = schedule.scheduled_date?.trim();
        if (scheduledDate) return scheduledDate;

        if (schedule.scheduled_at) {
            const parsed = new Date(schedule.scheduled_at);
            if (!Number.isNaN(parsed.getTime())) {
                return toLocalDateString(parsed);
            }
        }

        return null;
    }

    const activeRange = computed(() => {
        const start = selectedDate.value;
        const end = rangeEnd.value || selectedDate.value;

        return start <= end ? { start, end } : { start: end, end: start };
    });

    const dateList = computed(() => {
        const { start, end } = activeRange.value;

        const list: string[] = [];

        if (start) {
            const cursor = new Date(`${start}T00:00:00`);
            const endDate = new Date(`${end}T00:00:00`);

            while (cursor <= endDate) {
                list.push(toLocalDateString(cursor));
                cursor.setDate(cursor.getDate() + 1);
            }
        }

        for (const schedule of props.schedules ?? []) {
            const date = getScheduleDate(schedule);
            if (date && !list.includes(date)) {
                list.push(date);
            }
        }

        return list.sort();
    });
    // const dateList = computed(() => {
    //     const { start, end } = activeRange.value;

    //     if (!start) return [];

    //     const list: string[] = [];
    //     const cursor = new Date(`${start}T00:00:00`);
    //     const endDate = new Date(`${end}T00:00:00`);

    //     while (cursor <= endDate) {
    //         list.push(toLocalDateString(cursor));
    //         cursor.setDate(cursor.getDate() + 1);
    //     }

    //     return list;
    // });

    function schedulesForDay(date: string) {
        return (props.schedules ?? []).filter(
            (s) => getScheduleDate(s) === date,
        );
    }

    const dayGroups = computed<DayGroup[]>(() => {
        const todayStr = today();
        const startHour = props.startHour ?? 0;
        const endHour = props.endHour ?? 24;

        return dateList.value
            .map((date) => {
                const daySchedules = schedulesForDay(date);

                if (!daySchedules.length) return undefined;

                let minStart = Infinity;
                let maxEnd = -Infinity;
                let unassignedCount = 0;

                for (const schedule of daySchedules) {
                    const needsAssignment = schedule.services?.some(
                        (service) => !service.assignees?.length,
                    );

                    if (needsAssignment) unassignedCount++;

                    const times = getScheduleTimes(schedule);
                    const startMin = parseTimeToMinutes(times.start);

                    if (startMin === null) continue;

                    const endMin = parseTimeToMinutes(times.end);

                    minStart = Math.min(minStart, startMin);

                    if (endMin !== null) {
                        maxEnd = Math.max(maxEnd, endMin);
                    }
                }

                const hours: { value: number; label: string }[] = [];

                for (let h = startHour; h <= endHour; h++) {
                    hours.push({ value: h, label: formatHourLabel(h) });
                }

                return {
                    date,
                    dateLabel: formatDate(date),
                    count: daySchedules.length,
                    unassignedCount,
                    isToday: date === todayStr,
                    hours,
                    hourColumnCount: Math.max(hours.length - 1, 1),
                };
            })
            .filter((day): day is DayGroup => Boolean(day))
            .sort((a, b) => {
                if (a.isToday) return -1;
                if (b.isToday) return 1;
                return a.date.localeCompare(b.date);
            });
    });

    const hasAnySchedules = computed(() => {
        return (props.schedules ?? []).some((s) => {
            const date = getScheduleDate(s);
            return date !== null && dateList.value.includes(date);
        });
    });

    const totalCount = computed(
        () =>
            (props.schedules ?? []).filter((s) => {
                const date = getScheduleDate(s);
                return date !== null && dateList.value.includes(date);
            }).length,
    );

    const unassignedCount = computed(() =>
        dayGroups.value.reduce((sum, day) => sum + day.unassignedCount, 0),
    );

    function getServiceLeft(
        schedule: ScheduleItem,
        serviceIndex: number,
        day: { hours: { value: number }[] },
    ) {
        const scheduleStart = parseTimeToMinutes(schedule.start_time);

        if (scheduleStart === null) return 0;

        const dayStart = day.hours[0]!.value * 60;

        const serviceStart =
            schedule.services
                ?.slice(0, serviceIndex)
                .reduce(
                    (total, service) =>
                        total + (service.duration_minutes ?? 0),
                    scheduleStart,
                ) ?? scheduleStart;

        return ((serviceStart - dayStart) / 60) * HOUR_WIDTH;
    }

    function getServiceWidth(service: ScheduleServiceItem) {
        const duration = service.duration_minutes ?? 0;
        if (!duration) return HOUR_WIDTH;
        return Math.max((duration / 60) * HOUR_WIDTH, 240);
    }

    function getScheduleLeft(
        schedule: ScheduleItem,
        day: { hours: { value: number }[] },
    ) {
        const start = parseTimeToMinutes(schedule.start_time);
        if (start === null) return 0;

        const dayStart = day.hours[0]!.value * 60;

        return ((start - dayStart) / 60) * HOUR_WIDTH;
    }

    function getScheduleWidth(schedule: ScheduleItem) {
        const start = parseTimeToMinutes(schedule.start_time);
        const end = parseTimeToMinutes(schedule.end_time);

        if (
            typeof schedule.total_duration_minutes === "number" &&
            schedule.total_duration_minutes > 0
        ) {
            return Math.max(
                (schedule.total_duration_minutes / 60) * HOUR_WIDTH,
                140,
            );
        }

        if (start === null || end === null) return HOUR_WIDTH;

        const duration = end - start;

        return Math.max((duration / 60) * HOUR_WIDTH, 240);
    }

    const tick = ref(0);
    let nowTicker: ReturnType<typeof setInterval> | null = null;

    function nowOffset(day: { hours: { value: number }[] }) {
        tick.value;

        const first = day.hours[0];
        const last = day.hours[day.hours.length - 1];
        if (!first || !last) return null;

        const now = new Date();
        const nowMin = now.getHours() * 60 + now.getMinutes();
        const dayStart = first.value * 60;
        const dayEnd = last.value * 60;

        if (nowMin < dayStart || nowMin > dayEnd) return null;

        return ((nowMin - dayStart) / 60) * HOUR_WIDTH;
    }

    function startNowTicker() {
        stopNowTicker();
        nowTicker = setInterval(() => {
            tick.value++;
        }, 60_000);
    }

    function stopNowTicker() {
        if (nowTicker) {
            clearInterval(nowTicker);
            nowTicker = null;
        }
    }

    function rowTheme(index: number) {
        return index % 2 === 1 ? "bg-slate-50" : "bg-white";
    }

    function statusDot(status: string) {
        const value = status.toLowerCase();

        if (value.includes("pending")) return "bg-amber-400";
        if (value.includes("complete") || value.includes("done"))
            return "bg-emerald-500";
        if (value.includes("cancel")) return "bg-red-400";
        if (value.includes("progress") || value.includes("active"))
            return "bg-sky-500";

        return "bg-slate-300";
    }

    function scheduleStatusTheme(status?: string | null) {
        switch (status?.toLowerCase()) {
            case "completed":
                return {
                    card: "border-l-4 border-l-sky-400 bg-sky-50/70",
                    badge: "bg-sky-100 text-sky-700",
                    accent: "text-sky-700",
                };

            case "ongoing":
                return {
                    card: "border-l-4 border-l-emerald-400 bg-emerald-50/70",
                    badge: "bg-emerald-100 text-emerald-700",
                    accent: "text-emerald-700",
                };

            case "confirmed":
                return {
                    card: "border-l-4 border-l-blue-400 bg-blue-50/70",
                    badge: "bg-blue-100 text-blue-700",
                    accent: "text-blue-700",
                };

            case "missed":
                return {
                    card: "border-l-4 border-l-rose-400 bg-rose-50/70",
                    badge: "bg-rose-100 text-rose-600",
                    accent: "text-rose-600",
                };

            case "cancelled":
                return {
                    card: "border-l-4 border-l-slate-300 bg-slate-50/70 opacity-70",
                    badge: "bg-slate-200 text-slate-500",
                    accent: "text-slate-500",
                };

            case "pending":
            default:
                return {
                    card: "border-l-4 border-l-violet-400 bg-violet-50/70",
                    badge: "bg-violet-100 text-violet-700",
                    accent: "text-violet-700",
                };
        }
    }

    function scheduleStatusLabel(status?: string | null) {
        switch (status?.toLowerCase()) {
            case "completed":
                return "Complete";
            case "ongoing":
                return "Ongoing";
            case "confirmed":
                return "Confirmed";
            case "missed":
                return "Missed";
            case "cancelled":
                return "Cancelled";
            case "pending":
            default:
                return "Pending";
        }
    }

    const labelWidth = computed(() => 220);



    const statusItems = [
        {
            label: "Pending",
            value: "pending",
        },
        {
            label: "Confirmed",
            value: "confirmed",
        },
        {
            label: "Ongoing",
            value: "ongoing",
        },
        {
            label: "Completed",
            value: "completed",
        },
        {
            label: "Missed",
            value: "missed",
        },
        {
            label: "Cancelled",
            value: "cancelled",
        },
    ];
    return {
        labelWidth,
        hourWidth: HOUR_WIDTH,

        selectedDate,
        rangeEnd,
        activeRange,
        dateList,

        dayGroups,
        hasAnySchedules,
        totalCount,
        unassignedCount,

        today,
        toLocalDateString,
        jumpToToday,
        formatDate,
        formatHourLabel,
        parseTimeToMinutes,

        schedulesForDay,
        getScheduleDate,
        getScheduleTimes,

        getServiceLeft,
        getServiceWidth,
        getScheduleLeft,
        getScheduleWidth,

        nowOffset,
        startNowTicker,
        stopNowTicker,

        statusItems,
        rowTheme,
        statusDot,
        scheduleStatusTheme,
        scheduleStatusLabel,
    };
}