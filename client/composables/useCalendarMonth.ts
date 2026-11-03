import { computed, ref, watch } from "vue";
import { toLocalDateString } from "~/utils/time";

export type CalendarRange = { from: string; to: string };

export const parseIso = (iso: string) => new Date(`${iso}T00:00:00`);

export const isoOf = (value?: string | Date | null) => {
    if (!value) return "";
    const date = value instanceof Date ? value : new Date(value);
    return Number.isNaN(date.getTime()) ? "" : toLocalDateString(date);
};

export const addDays = (iso: string, days: number) => {
    const date = parseIso(iso);
    date.setDate(date.getDate() + days);
    return toLocalDateString(date);
};

export const daysBetween = (from: string, to: string) =>
    Math.round((parseIso(to).getTime() - parseIso(from).getTime()) / 86_400_000);

export const formatIso = (iso: string, options: Intl.DateTimeFormatOptions) =>
    parseIso(iso).toLocaleDateString("en-US", options);

export function useCalendarMonth(
    props: { date?: string; rangeEnd?: string },
    onRange: (range: CalendarRange) => void,
) {
    const todayIso = ref(toLocalDateString(new Date()));

    function inRange(iso: string) {
        if (props.date && iso < props.date) return false;
        if (props.rangeEnd && iso > props.rangeEnd) return false;
        return true;
    }

    const monthStartOf = (iso: string) => {
        const d = parseIso(iso);
        return new Date(d.getFullYear(), d.getMonth(), 1);
    };

    const initialMonth = () =>
        monthStartOf(inRange(todayIso.value) ? todayIso.value : props.date || todayIso.value);

    const visibleMonth = ref<Date>(initialMonth());

    const monthName = computed(() =>
        visibleMonth.value.toLocaleDateString("en-US", { month: "long" }),
    );

    const monthTitle = computed(() =>
        visibleMonth.value.toLocaleDateString("en-US", { month: "long", year: "numeric" }),
    );

    const monthBounds = computed<CalendarRange>(() => {
        const start = visibleMonth.value;
        const end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
        return { from: toLocalDateString(start), to: toLocalDateString(end) };
    });

    const monthDays = computed(() => {
        const { from, to } = monthBounds.value;
        return Array.from({ length: daysBetween(from, to) + 1 }, (_, i) => addDays(from, i));
    });

    const partialMonth = computed(() => {
        const { from, to } = monthBounds.value;
        const startsLate = !!props.date && props.date > from;
        const endsEarly = !!props.rangeEnd && props.rangeEnd < to;

        if (!startsLate && !endsEarly) return "";
        if (props.date && props.rangeEnd && (props.date > to || props.rangeEnd < from)) {
            return `Your date filter doesn't include ${monthName.value}.`;
        }

        const pieces: string[] = [];
        if (startsLate) pieces.push(`from ${formatIso(props.date!, { month: "short", day: "numeric" })}`);
        if (endsEarly) pieces.push(`until ${formatIso(props.rangeEnd!, { month: "short", day: "numeric" })}`);
        return `Showing ${pieces.join(" ")} only; greyed-out days aren't loaded.`;
    });

    function showWholeMonth() {
        onRange(monthBounds.value);
    }

    function shiftMonth(step: number) {
        const d = visibleMonth.value;
        visibleMonth.value = new Date(d.getFullYear(), d.getMonth() + step, 1);
        onRange(monthBounds.value);
    }

    function goToToday() {
        const target = monthStartOf(todayIso.value);
        const changed = target.getTime() !== visibleMonth.value.getTime();
        visibleMonth.value = target;
        if (changed || !inRange(todayIso.value)) onRange(monthBounds.value);
    }

    watch(
        () => [props.date, props.rangeEnd],
        () => {
            const { from, to } = monthBounds.value;
            const overlaps =
                (!props.date || props.date <= to) && (!props.rangeEnd || props.rangeEnd >= from);
            if (!overlaps) visibleMonth.value = initialMonth();
        },
    );

    return {
        todayIso,
        visibleMonth,
        monthName,
        monthTitle,
        monthBounds,
        monthDays,
        partialMonth,
        inRange,
        shiftMonth,
        goToToday,
        showWholeMonth,
    };
}
