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
export function format24To12(time: string) {
    if (!time) return "";

    const [hourStr, minute] = time.split(":");
    let hour = Number(hourStr);

    const ampm = hour >= 12 ? "PM" : "AM";
    hour = hour % 12 || 12;

    return `${hour}:${minute} ${ampm}`;
}

// DISPLAY TIME 
export function getBranchTimeDisplay(
    availability: BranchRetrieve["settings"]
): any {
    if (!availability?.opening || !availability?.closing) {
        return {
            is24Hours: false,
            label: "Not available",
            time: null,
        };
    }

    const is24Hours =
        availability.opening === "00:00" &&
        availability.closing === "00:00";

    if (is24Hours) {
        return {
            is24Hours: true,
            label: "Open 24 Hours",
            time: null,
        };
    }

    return {
        is24Hours: false,
        label: `${format24To12(availability.opening)} - ${format24To12(
            availability.closing
        )}`,
        time: `${availability.opening} - ${availability.closing}`,
    };
}


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


// FOR TIME 00:00 - 23:59
export const parseHourString = (t: string | null | undefined,): number => {
    if (!t) return 0;
    const [h, m = "0"] = t.split(":");
    return Number(h) + Number(m) / 60;
};

// GENERATE TIME SLOTS 
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
        const displayEnd = end === 24 ? 23.9833 : end;
        const displayStart = h === 0 ? 0 : h;
        slots.push({
            value: `${formatHour(displayStart)} - ${formatHour(displayEnd)}`,
            label: `${formatHour(displayStart)} - ${formatHour(displayEnd)}`,
            start: h,
            end,
        });
    }

    return slots;
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
    if (!isToday) return slots;
    const currentHour = now.getHours() + now.getMinutes() / 60;
    return slots.filter((slot) => slot.start > currentHour);
};


export const getLocalDateStr = (d: Date): string => {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

