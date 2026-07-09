import type { BranchRetrieve } from "~/types/branch";

export function generate24HourTimes(stepMinutes = 60): string[] {
    const times: string[] = [];

    for (let totalMinutes = 0; totalMinutes < 24 * 60; totalMinutes += stepMinutes) {
        const hour = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;

        const hStr = String(hour).padStart(2, "0");
        const mStr = String(minutes).padStart(2, "0");

        times.push(`${hStr}:${mStr}`);
    }

    return times;
}

export function getTimeZone() {
    return [
        { label: 'Asia / Manila', value: 'Asia/Manila' },
    ]
}

type TimeDisplay = {
    is24Hours: boolean;
    label: string;
    time: string | null;
};

export function format24To12(time: string) {
    if (!time) return "";

    const [hourStr, minute] = time.split(":");
    let hour = Number(hourStr);

    const ampm = hour >= 12 ? "PM" : "AM";
    hour = hour % 12 || 12;

    return `${hour}:${minute} ${ampm}`;
}

export function getBranchTimeDisplay(
    availability: BranchRetrieve["availability"]
): TimeDisplay {
    if (!availability?.opening_time || !availability?.closing_time) {
        return {
            is24Hours: false,
            label: "Not available",
            time: null,
        };
    }

    const is24Hours =
        availability.opening_time === "00:00" &&
        availability.closing_time === "00:00";

    if (is24Hours) {
        return {
            is24Hours: true,
            label: "Open 24 Hours",
            time: null,
        };
    }

    return {
        is24Hours: false,
        label: `${format24To12(availability.opening_time)} - ${format24To12(
            availability.closing_time
        )}`,
        time: `${availability.opening_time} - ${availability.closing_time}`,
    };
}


const rtf = new Intl.RelativeTimeFormat("en", { numeric: "auto" });

export const formatDate = (date: string | Date) => {
    const now = new Date();
    const target = new Date(date);

    const diff = target.getTime() - now.getTime();
    const seconds = Math.round(diff / 1000);

    const intervals = [
        { limit: 60, unit: "second", value: seconds },
        { limit: 3600, unit: "minute", value: Math.round(seconds / 60) },
        { limit: 86400, unit: "hour", value: Math.round(seconds / 3600) },
        { limit: 604800, unit: "day", value: Math.round(seconds / 86400) },
        { limit: 2629800, unit: "week", value: Math.round(seconds / 604800) },
        { limit: 31557600, unit: "month", value: Math.round(seconds / 2629800) },
        { limit: Infinity, unit: "year", value: Math.round(seconds / 31557600) },
    ];

    for (const interval of intervals) {
        if (Math.abs(seconds) < interval.limit) {
            return rtf.format(interval.value as number, interval.unit as Intl.RelativeTimeFormatUnit);
        }
    }
};