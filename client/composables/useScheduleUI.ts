import { computed, ref, watch } from "vue";
import { stringToDate } from "~/utils/time";
import type { ScheduleItem } from "~/types/schedule";

export interface Block {
    scheduleId: number;
    patientName: string;
    category: string | null;
    status: string;
    startLabel: string;
    endLabel: string;
    offsetPercent: number;
    widthPercent: number;
    serviceCount: number;
    lane: number;
}

export interface Row {
    employeeId: number;
    employeeName: string;
    isUnassigned: boolean;
    blocks: Block[];
    laneCount: number;
}

interface ScheduleProps {
    schedules?: ScheduleItem[];
    date?: string;
    rangeEnd?: string;
    startHour?: number;
    endHour?: number;
}

interface UseScheduleOptions {
    onDateChange?: (value: string) => void;
    onRangeEndChange?: (value: string) => void;
}

export function useSchedule(
    props: ScheduleProps,
    options: UseScheduleOptions = {},
) {
    const labelWidth = 140;

    const LANE_HEIGHT = 80;
    const LANE_GAP = 6;
    const MIN_ROW_HEIGHT = 56;

    const selectedDate = ref(
        props.date ?? stringToDate(new Date()),
    );

    const rangeEnd = ref(
        props.rangeEnd ?? props.date ?? stringToDate(new Date()),
    );

    watch(
        () => props.date,
        (value) => {
            if (value && value !== selectedDate.value) {
                selectedDate.value = value;
            }
        },
    );

    watch(
        () => props.rangeEnd,
        (value) => {
            if (value && value !== rangeEnd.value) {
                rangeEnd.value = value;
            }
        },
    );


    const activeRange = computed(() => {
        const start = selectedDate.value;
        const end = rangeEnd.value || selectedDate.value;

        return start <= end
            ? { start, end }
            : { start: end, end: start };
    });

    const dateList = computed(() => {
        const { start, end } = activeRange.value;

        if (!start) return [];

        const dates: string[] = [];
        const cursor = new Date(`${start}T00:00:00`);
        const last = new Date(`${end}T00:00:00`);

        while (cursor <= last) {
            dates.push(stringToDate(cursor));
            cursor.setDate(cursor.getDate() + 1);
        }

        return dates;
    });

    function toLocalDateString(d: Date) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }
    function formatHourLabel(hour24: number) {
        const period = hour24 >= 12 ? "PM" : "AM";
        const display = hour24 % 12 === 0 ? 12 : hour24 % 12;

        return `${display} ${period}`;
    }

    function formatDate(date: string) {
        return new Date(`${date}T00:00:00`).toLocaleDateString(
            "en-US",
            {
                weekday: "long",
                month: "long",
                day: "numeric",
            },
        );
    }

    function parseTimeToMinutes(label: string | null) {
        if (!label) return null;

        const match = label.match(
            /(\d{1,2}):(\d{2})\s?(AM|PM)/i,
        );

        if (!match) return null;

        let hour = Number(match[1]);
        const minute = Number(match[2]);
        const period = match[3]?.toUpperCase();

        if (period === "PM" && hour !== 12) {
            hour += 12;
        }

        if (period === "AM" && hour === 12) {
            hour = 0;
        }

        return hour * 60 + minute;
    }

    function formatTime(hour24: number, minute: number) {
        const period = hour24 >= 12 ? "PM" : "AM";
        const hour = hour24 % 12 === 0 ? 12 : hour24 % 12;

        return `${hour}:${String(minute).padStart(
            2,
            "0",
        )} ${period}`;
    }

    function getScheduleTimes(schedule: ScheduleItem) {
        if (schedule.start_time && schedule.end_time) {
            return {
                start: schedule.start_time,
                end: schedule.end_time,
            };
        }

        if (schedule.scheduled_at) {
            const date = new Date(schedule.scheduled_at);
            const hour = date.getHours();

            return {
                start: formatTime(hour, 0),
                end: formatTime(hour + 1, 0),
            };
        }

        return {
            start: "00:00 AM",
            end: "11:59 PM",
        };
    }

    function minutesToPercent(
        minutes: number,
        startMin: number,
        endMin: number,
    ) {
        const clamped = Math.min(
            Math.max(minutes, startMin),
            endMin,
        );

        const span = endMin - startMin || 1;

        return ((clamped - startMin) / span) * 100;
    }

    function assignLanes(blocks: Block[]): number {
        const sorted = [...blocks].sort(
            (a, b) => a.offsetPercent - b.offsetPercent,
        );

        const laneEnds: number[] = [];

        for (const block of sorted) {
            let placed = false;

            for (let lane = 0; lane < laneEnds.length; lane++) {
                if (laneEnds[lane]! <= block.offsetPercent) {
                    block.lane = lane;
                    laneEnds[lane] =
                        block.offsetPercent + block.widthPercent;
                    placed = true;
                    break;
                }
            }

            if (!placed) {
                block.lane = laneEnds.length;
                laneEnds.push(
                    block.offsetPercent + block.widthPercent,
                );
            }
        }

        return laneEnds.length;
    }

    function buildRows(
        schedules: ScheduleItem[],
        startMin: number,
        endMin: number,
    ): Row[] {
        const rowMap = new Map<number, Row>();

        for (const schedule of schedules) {
            const times = getScheduleTimes(schedule);

            const scheduleStartMin = parseTimeToMinutes(
                times.start,
            );

            const scheduleEndMin = parseTimeToMinutes(
                times.end,
            );

            if (
                scheduleStartMin === null ||
                scheduleEndMin === null
            ) {
                continue;
            }

            const startPercent = minutesToPercent(
                scheduleStartMin,
                startMin,
                endMin,
            );

            const endPercent = minutesToPercent(
                scheduleEndMin,
                startMin,
                endMin,
            );

            const widthPercent = Math.max(
                endPercent - startPercent,
                0.5,
            );

            const assignees = schedule.assignees?.length
                ? schedule.assignees
                : [
                    {
                        employee_id: 0,
                        employee_name: "Unassigned",
                        role: null,
                    },
                ];

            for (const assignee of assignees) {
                if (!rowMap.has(assignee.employee_id)) {
                    rowMap.set(assignee.employee_id, {
                        employeeId: assignee.employee_id,
                        employeeName:
                            assignee.employee_name ??
                            "Unassigned",
                        isUnassigned:
                            assignee.employee_id === 0,
                        blocks: [],
                        laneCount: 1,
                    });
                }

                rowMap
                    .get(assignee.employee_id)!
                    .blocks.push({
                        scheduleId: schedule.schedule_id,
                        patientName:
                            schedule.patient?.full_name ??
                            "Unknown patient",
                        category: schedule.category,
                        status: schedule.status,
                        startLabel: times.start,
                        endLabel: times.end,
                        offsetPercent: startPercent,
                        widthPercent,
                        serviceCount:
                            schedule.services?.length ?? 1,
                        lane: 0,
                    });
            }
        }

        const result = Array.from(rowMap.values());

        for (const row of result) {
            row.laneCount = Math.max(
                1,
                assignLanes(row.blocks),
            );
        }

        return result.sort((a, b) => {
            if (a.isUnassigned !== b.isUnassigned) {
                return a.isUnassigned ? 1 : -1;
            }

            return a.employeeName.localeCompare(
                b.employeeName,
            );
        });
    }

    function rowHeight(row: Row) {
        const height =
            row.laneCount * LANE_HEIGHT +
            (row.laneCount - 1) * LANE_GAP;

        return Math.max(height, MIN_ROW_HEIGHT);
    }

    const dayGroups = computed(() => {
        return dateList.value.map((date) => {
            const daySchedules = (
                props.schedules ?? []
            ).filter(
                (schedule) =>
                    getScheduleDate(schedule) === date,
            );

            let minStart = Infinity;
            let maxEnd = -Infinity;

            for (const schedule of daySchedules) {
                const times = getScheduleTimes(schedule);

                const startMin = parseTimeToMinutes(
                    times.start,
                );

                const endMin = parseTimeToMinutes(
                    times.end,
                );

                if (
                    startMin === null ||
                    endMin === null
                ) {
                    continue;
                }

                minStart = Math.min(minStart, startMin);
                maxEnd = Math.max(maxEnd, endMin);
            }

            const hasRange =
                Number.isFinite(minStart) &&
                Number.isFinite(maxEnd);

            const visibleStartHour = hasRange
                ? Math.max(
                    props.startHour ?? 0,
                    Math.floor(minStart / 60),
                )
                : props.startHour ?? 0;

            const visibleEndHour = hasRange
                ? Math.min(
                    props.endHour ?? 24,
                    Math.max(
                        Math.ceil(maxEnd / 60) + 1,
                        visibleStartHour + 1,
                    ),
                )
                : props.endHour ?? 24;

            const hours: {
                value: number;
                label: string;
            }[] = [];

            for (
                let hour = visibleStartHour;
                hour <= visibleEndHour;
                hour++
            ) {
                hours.push({
                    value: hour,
                    label: formatHourLabel(hour),
                });
            }

            const hourColumnCount = Math.max(
                hours.length - 1,
                1,
            );

            const rows = buildRows(
                daySchedules,
                visibleStartHour * 60,
                visibleEndHour * 60,
            );

            return {
                date,
                dateLabel: formatDate(date),
                count: daySchedules.length,
                hours,
                hourColumnCount,
                rows,
            };
        });
    });

    const populatedDayGroups = computed(() =>
        dayGroups.value.filter(
            (group) => group.rows.length > 0,
        ),
    ); const THEMES = [
        {
            container: "border-sky-200 bg-sky-50",
            title: "text-sky-900",
            subtitle: "text-sky-500",
        },
        {
            container: "border-violet-200 bg-violet-50",
            title: "text-violet-900",
            subtitle: "text-violet-500",
        },
        {
            container: "border-rose-200 bg-rose-50",
            title: "text-rose-900",
            subtitle: "text-rose-500",
        },
        {
            container: "border-amber-200 bg-amber-50",
            title: "text-amber-900",
            subtitle: "text-amber-500",
        },
        {
            container: "border-emerald-200 bg-emerald-50",
            title: "text-emerald-900",
            subtitle: "text-emerald-500",
        },
    ];

    function blockTheme(category: string | null) {
        const key = category ?? "default";

        let hash = 0;

        for (let i = 0; i < key.length; i++) {
            hash = (hash << 5) - hash + key.charCodeAt(i);
            hash |= 0;
        }

        return THEMES[Math.abs(hash) % THEMES.length]!;
    }

    function statusDot(status: string) {
        const value = status.toLowerCase();

        if (value.includes("pending")) {
            return "bg-amber-400";
        }

        if (value.includes("complete") || value.includes("done")) {
            return "bg-emerald-500";
        }

        if (value.includes("cancel")) {
            return "bg-red-400";
        }

        if (value.includes("progress") || value.includes("active")) {
            return "bg-sky-500";
        }

        return "bg-slate-300";
    }

    return {
        labelWidth,
        LANE_HEIGHT,
        LANE_GAP,
        selectedDate,
        rangeEnd,
        populatedDayGroups,
        rowHeight,
        blockTheme,
        statusDot,
    };
}