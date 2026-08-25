import type { BranchRetrieve } from "~/types/branch";

// GENERATE 00:00 to 00:00
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

//TIME ZONE
export function getTimeZone() {
    return [
        { label: 'Asia / Manila', value: 'Asia/Manila' },
    ]
}

// FORMAT 00:00 to AM : PM
// export function format24To12(time: string | undefined | null) {
//     if (!time) return "";

//     const [hourStr, minute] = time.split(":");
//     let hour = Number(hourStr);

//     const ampm = hour >= 12 ? "PM" : "AM";
//     hour = hour % 12 || 12;

//     return `${hour}:${minute} ${ampm}`;
// }

export function format24To12(time: string | undefined | null) {
    if (!time) return "";

    const [hourStr, minute] = time.split(":");
    let hour = Number(hourStr);

    const ampm = hour >= 12 ? "PM" : "AM";

    if (hour === 0) {
        return `00:${minute} AM`;
    }

    hour = hour % 12 || 12;

    return `${String(hour).padStart(2, "0")}:${minute} ${ampm}`;
}

// DISPLAY TIME 
export function getBranchTimeDisplay(
    availability: BranchRetrieve["settings"] | undefined
): {
    is24Hours: boolean;
    isOpen: boolean;
    label: string;
    time: string | null;
} {

    const isOpen = Boolean(availability?.is_open);

    if (!availability?.opening || !availability?.closing) {
        return {
            is24Hours: false,
            isOpen,
            label: "Hours not available",
            time: null,
        };
    }

    const is24HourWindow =
        availability.opening === "00:00" && availability.closing === "00:00";

    if (!isOpen) {
        return {
            is24Hours: false,
            isOpen: false,
            label: "Closed",
            time: null,
        };
    }

    if (is24HourWindow) {
        return {
            is24Hours: true,
            isOpen: true,
            label: "Open 24 Hours",
            time: null,
        };
    }

    return {
        is24Hours: is24HourWindow,
        isOpen,
        label: `${format24To12(availability.opening)} - ${format24To12(
            availability.closing
        )}`,
        time: `${availability.opening} - ${availability.closing}`,
    };
}




// FOR TIME 00:00 - 23:59
export const parseHourString = (t: string | null | undefined,): number => {
    if (!t) return 0;
    const [h, m = "0"] = t.split(":");
    return Number(h) + Number(m) / 60;
};

// GENERATE TIME SLOTS 
// export const getTimeSlots = (
//     openingTime: string | null | undefined,
//     closingTime: string | null | undefined,
//     slotLengthHours: number,
// ) => {
//     let openingHour = parseHourString(openingTime);
//     let closingHour = parseHourString(closingTime);


//     if (openingTime === "00:00" && closingTime === "00:00") {
//         openingHour = 0;
//         closingHour = 24;
//     }
//     const slots = [];

//     for (
//         let h = openingHour;
//         h + slotLengthHours <= closingHour;
//         h += slotLengthHours
//     ) {

//         const end = h + slotLengthHours;
//         const displayEnd = end === 24 ? 23.9833 : end;
//         const displayStart = h === 0 ? 0 : h;
//         slots.push({
//             value: `${formatHour(displayStart)} - ${formatHour(displayEnd)}`,
//             label: `${formatHour(displayStart)} - ${formatHour(displayEnd)}`,
//             start: h,
//             end,
//         });
//     }

//     return slots;
// };

export const getTimeSlots = (
    openingTime: string | null | undefined,
    closingTime: string | null | undefined,
    slotLengthHours: number,
) => {
    let openingHour = parseHourString(openingTime);
    let closingHour = parseHourString(closingTime);

    if (openingTime === "00:00" && closingTime === "00:00") {
        openingHour = 0;
        closingHour = 24;
    }

    const slots = [];

    for (
        let h = openingHour;
        h + slotLengthHours <= closingHour;
        h += slotLengthHours
    ) {
        const end = h + slotLengthHours;
        const displayStart = h === 0 ? 0 : h;

        slots.push({
            value: formatHour1(displayStart),
            label: formatHour1(displayStart),
            start: h,
            end,
        });
    }

    return slots;
};
export const formatHour1 = (hours: number): string => {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);

    const period = h >= 12 ? "PM" : "AM";
    const hour12 = h % 12 || 12;

    return `${String(hour12).padStart(2, "0")}:${String(m).padStart(2, "0")} ${period}`;
};

export const formatHour = (hours: number): string => {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);


    if (h === 0) {
        return `00:${String(m).padStart(2, "0")} AM`;
    }


    const period = h >= 12 ? "PM" : "AM";
    const hour12 = h % 12 || 12;

    return `${String(hour12).padStart(2, "0")}:${String(m).padStart(2, "0")} ${period}`;
};



// GENERATE TIMESLOT PER DATE
// export const filterAvailableSlots = (
//     slots: any[],
//     selectedDate: string,
//     now: Date = new Date(),
// ): any[] => {
//     if (!selectedDate) return slots;

//     const isToday = selectedDate === getLocalDateStr(now);
//     if (!isToday) return slots;

//     const currentHour = now.getHours() + now.getMinutes() / 60;

//     return slots.filter((slot) => slot.start > currentHour);
// };

// GENERATE TIMESLOT PER DATE
export const filterAvailableSlots = (
    slots: any[],
    selectedDate: string,
    now: Date = new Date(),
): any[] => {

    if (!selectedDate) return slots;
    const isToday = selectedDate === getLocalDateStr(now);
    const today = getLocalDateStr(now);
    if (!isToday) return slots;
    const currentHour = now.getHours() + now.getMinutes() / 60;
    return slots.filter((slot) => slot.start > currentHour);
};




//TIME CONVERTER 00:30:00 TO 30 || 30 TO 00:30:00
export const timeConverter = (
    value: string | number,
    to: "minutes" | "time",
): string | number => {
    if (to === "time") {
        const minutes = Number(value);

        if (!Number.isFinite(minutes) || minutes < 0) {
            return "00:00:00";
        }

        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;

        return `${String(hours).padStart(2, "0")}:${String(mins).padStart(2, "0")}:00`;
    }

    const parts = String(value).split(":").map(Number);
    const hours = parts[0] ?? 0;
    const minutes = parts[1] ?? 0;
    const seconds = parts[2] ?? 0;

    if (
        !Number.isFinite(hours) ||
        !Number.isFinite(minutes) ||
        !Number.isFinite(seconds)
    ) {
        return 0;
    }

    return hours * 60 + minutes + Math.floor(seconds / 60);
};



export const getLocalDateStr = (d: Date): string => {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};








// ----------------- FINAL // 


// RESULT 2026-07-31
export function toLocalDateString(d: Date) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

// RESULT Jan 5, 2026
export function formatDate(value?: string | Date | null) {
    if (!value) return "—";
    const date = new Date(value);
    if (isNaN(date.getTime())) {
        return String(value);
    }
    return new Intl.DateTimeFormat(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(date);
}

// RESULT Jul 31, 2026, 10:30 AM
export const stringToDateTime = (
    date?: string | Date | null,
): string => {
    if (!date) return "";

    return new Intl.DateTimeFormat(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    }).format(new Date(date));
};


// RESULT 18:45 -> 6:45 PM
export function formatTime(time?: string | number | null): string {
    if (!time) return "";

    // return new Date(`1970-01-01T${time}`).toLocaleTimeString(undefined, {
    //     hour: "numeric",
    //     minute: "2-digit",
    //     hour12: true,
    // });


    const timeString =
        typeof time === "number"
            ? `${String(time).padStart(2, "0")}:00`
            : time;

    return new Date(`1970-01-01T${timeString}`).toLocaleTimeString(
        undefined,
        {
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
        }
    );
}


// RESULT 00:00 AM ── 11:59 PM
export function formatHourLabel(hour24: number): string {
    const minutes = hour24 === 23 ? 59 : 0;
    // if (hour24 === 0 || hour24 === 24) {
    //     return "00:00 AM";
    // }
    const date = new Date(1970, 0, 1, hour24, minutes);
    return date.toLocaleTimeString(undefined, {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

// RESULT 50hrs -> 2days and 2 hours
export const formatDuration = (hours: number) => {
    if (!hours || hours <= 0) return "";

    let remainingHours = hours;

    const months = Math.floor(remainingHours / (24 * 30));
    remainingHours %= 24 * 30;

    const days = Math.floor(remainingHours / 24);
    remainingHours %= 24;

    const roundedHours = Math.round(remainingHours * 100) / 100;

    const parts: string[] = [];

    if (months) {
        parts.push(`${months} month${months > 1 ? "s" : ""}`);
    }

    if (days) {
        parts.push(`${days} day${days > 1 ? "s" : ""}`);
    }

    if (roundedHours) {
        parts.push(
            `${roundedHours} hr${roundedHours > 1 ? "s" : ""}`
        );
    }

    return parts.join(" and ");
};