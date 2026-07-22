// NOTIFIOCTION TIME 12hrs ago etc.
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

