import { computed, ref } from "vue";
import type { ScheduleItem } from "~/types/schedule";
import { useRemScale } from "~/composables/useRemScale";

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
const MINUTES_PER_DAY = 24 * 60;

type DayLike = { date?: string; hours: { value: number }[] };

export function useSchedule(props: UseScheduleProps = {}) {
    const scale = useRemScale();
    const hourWidth = computed(() => HOUR_WIDTH * scale.value);

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

    function scheduleTotalMinutes(schedule: ScheduleItem) {
        if (schedule.total_duration_minutes > 0) {
            return schedule.total_duration_minutes;
        }

        if (schedule.total_hours > 0) return schedule.total_hours * 60;

        return null;
    }

    function daysBetween(from: string, to: string) {
        const start = new Date(`${from}T00:00:00`);
        const end = new Date(`${to}T00:00:00`);

        return Math.round((end.getTime() - start.getTime()) / 86_400_000);
    }

    /**
     * A schedule's booked duration can span multiple calendar days (e.g. a
     * 24hr+ homecare shift that starts late one day and runs into the
     * next), so "which day does this schedule belong to" has to be a
     * range, not just its literal start date.
     */
    function scheduleDateSpan(
        schedule: ScheduleItem,
    ): { start: string; end: string } | null {
        const start = getScheduleDate(schedule);
        if (!start) return null;

        const spanDays = Math.max(1, Math.ceil((schedule.total_hours ?? 0) / 24));

        const endDate = new Date(`${start}T00:00:00`);
        endDate.setDate(endDate.getDate() + spanDays - 1);

        return { start, end: toLocalDateString(endDate) };
    }

    function scheduleCoversDate(schedule: ScheduleItem, dateStr: string): boolean {
        const span = scheduleDateSpan(schedule);
        if (!span) return false;

        return dateStr >= span.start && dateStr <= span.end;
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
            const span = scheduleDateSpan(schedule);
            if (!span) continue;

            const cursor = new Date(`${span.start}T00:00:00`);
            const spanEnd = new Date(`${span.end}T00:00:00`);

            while (cursor <= spanEnd) {
                const iso = toLocalDateString(cursor);
                if (!list.includes(iso)) list.push(iso);
                cursor.setDate(cursor.getDate() + 1);
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
        return (props.schedules ?? []).filter((s) => scheduleCoversDate(s, date));
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
        return (props.schedules ?? []).some((s) =>
            dateList.value.some((d) => scheduleCoversDate(s, d)),
        );
    });

    const totalCount = computed(
        () =>
            (props.schedules ?? []).filter((s) =>
                dateList.value.some((d) => scheduleCoversDate(s, d)),
            ).length,
    );

    const unassignedCount = computed(() =>
        dayGroups.value.reduce((sum, day) => sum + day.unassignedCount, 0),
    );


    function clipToDay(
        schedule: ScheduleItem,
        day: DayLike,
        startMin: number,
        durationMin: number,
        minWidth: number,
    ) {
        const first = day.hours[0]!.value * 60;
        const last = day.hours[day.hours.length - 1]!.value * 60;
        const scheduleDate = getScheduleDate(schedule);

        const shift =
            day.date && scheduleDate
                ? daysBetween(scheduleDate, day.date) * MINUTES_PER_DAY
                : 0;

        const start = Math.max(startMin - shift, first);
        const end = Math.min(startMin - shift + durationMin, last);

        const room = ((last - start) / 60) * hourWidth.value;
        const natural = ((end - start) / 60) * hourWidth.value;

        return {
            left: ((start - first) / 60) * hourWidth.value,
            width: Math.max(Math.min(Math.max(natural, minWidth), room), 0),
            visible: end > start,
        };
    }

    function scheduleBox(schedule: ScheduleItem, day: DayLike) {
        const start = parseTimeToMinutes(schedule.start_time);

        if (start === null) {
            return { left: 0, width: hourWidth.value, visible: true };
        }

        const total = scheduleTotalMinutes(schedule);

        if (total !== null) {
            return clipToDay(schedule, day, start, total, 140 * scale.value);
        }

        const end = parseTimeToMinutes(schedule.end_time);

        if (end === null) {
            return clipToDay(schedule, day, start, 60, 0);
        }

        const duration =
            end > start ? end - start : end - start + MINUTES_PER_DAY;

        return clipToDay(schedule, day, start, duration, 240 * scale.value);
    }

    function getScheduleLeft(schedule: ScheduleItem, day: DayLike) {
        return scheduleBox(schedule, day).left;
    }

    function getScheduleWidth(schedule: ScheduleItem, day: DayLike) {
        return scheduleBox(schedule, day).width;
    }

    function getServiceBox(
        schedule: ScheduleItem,
        serviceIndex: number,
        day: DayLike,
    ) {
        const scheduleStart = parseTimeToMinutes(schedule.start_time);
        const duration = schedule.services?.[serviceIndex]?.duration_minutes ?? 0;

        if (scheduleStart === null) {
            return { left: "0px", width: `${hourWidth.value}px` };
        }

        const serviceStart =
            schedule.services
                ?.slice(0, serviceIndex)
                .reduce(
                    (total, item) => total + (item.duration_minutes ?? 0),
                    scheduleStart,
                ) ?? scheduleStart;

        const box = clipToDay(
            schedule,
            day,
            serviceStart,
            duration || 60,
            duration ? 240 * scale.value : 0,
        );

        return {
            left: `${box.left}px`,
            width: `${box.width}px`,
            ...(box.visible ? {} : { display: "none" }),
        };
    }

    // "Oct 14, 2026", or "Oct 14 – 15, 2026" when the booking runs past
    // midnight into the next day.
    function scheduleDateLabel(schedule: ScheduleItem) {
        const start = getScheduleDate(schedule);
        if (!start) return "";

        const startMinutes = parseTimeToMinutes(schedule.start_time);
        const totalMinutes = scheduleTotalMinutes(schedule);

        const extraDays =
            startMinutes !== null && totalMinutes !== null
                ? Math.max(
                      0,
                      Math.ceil((startMinutes + totalMinutes) / MINUTES_PER_DAY) -
                          1,
                  )
                : 0;

        const startDate = new Date(`${start}T00:00:00`);
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + extraDays);

        const format = new Intl.DateTimeFormat(undefined, {
            year: "numeric",
            month: "short",
            day: "numeric",
        });

        return extraDays > 0
            ? format.formatRange(startDate, endDate)
            : format.format(startDate);
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

        return ((nowMin - dayStart) / 60) * hourWidth.value;
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
        return index % 2 === 1 ? "bg-slate-50 dark:bg-white/5" : "bg-white dark:bg-secondary";
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
                    card: "border-l-4 border-l-sky-400 bg-sky-50/70 dark:bg-sky-500/10",
                    badge: "bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300",
                    accent: "text-sky-700 dark:text-sky-300",
                    dot: "bg-sky-500",
                };

            case "ongoing":
                return {
                    card: "border-l-4 border-l-emerald-400 bg-emerald-50/70 dark:bg-emerald-500/10",
                    badge: "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300",
                    accent: "text-emerald-700 dark:text-emerald-300",
                    dot: "bg-emerald-500",
                };

            case "missed":
                return {
                    card: "border-l-4 border-l-rose-400 bg-rose-50/70 dark:bg-rose-500/10",
                    badge: "bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300",
                    accent: "text-rose-600 dark:text-rose-300",
                    dot: "bg-rose-500",
                };

            case "cancelled":
                return {
                    card: "border-l-4 border-l-slate-300 bg-slate-50/70 opacity-70 dark:bg-white/5",
                    badge: "bg-slate-200 text-slate-500 dark:bg-white/15 dark:text-gray-400",
                    accent: "text-slate-500 dark:text-gray-400",
                    dot: "bg-slate-400",
                };

            case "pending":
            default:
                return {
                    card: "border-l-4 border-l-violet-400 bg-violet-50/70 dark:bg-violet-500/10",
                    badge: "bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300",
                    accent: "text-violet-700 dark:text-violet-300",
                    dot: "bg-violet-500",
                };
        }
    }

    function scheduleStatusLabel(status?: string | null) {
        switch (status?.toLowerCase()) {
            case "completed":
                return "Complete";
            case "ongoing":
                return "Ongoing";
            case "missed":
                return "Missed";
            case "cancelled":
                return "Cancelled";
            case "pending":
            default:
                return "Pending";
        }
    }

    const labelWidth = computed(() => 220 * scale.value);



    const statusItems = [
        {
            label: "Pending",
            value: "pending",
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
        hourWidth,

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
        scheduleDateSpan,
        scheduleCoversDate,

        getServiceBox,
        getScheduleLeft,
        getScheduleWidth,
        scheduleDateLabel,

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