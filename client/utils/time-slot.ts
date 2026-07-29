export function generateAmPmTimes(stepMinutes = 60) {
    const times: { label: string; value: string }[] = [];

    for (
        let totalMinutes = 0;
        totalMinutes < 24 * 60;
        totalMinutes += stepMinutes
    ) {
        const hour24 = Math.floor(totalMinutes / 60);
        const minute = totalMinutes % 60;

        const period = hour24 >= 12 ? "PM" : "AM";

        const displayHour =
            hour24 === 0
                ? "00"
                : String(hour24 > 12 ? hour24 - 12 : hour24).padStart(2, "0");

        times.push({
            label: `${displayHour}:${String(minute).padStart(2, "0")} ${period}`,
            value: `${String(hour24).padStart(2, "0")}:${String(minute).padStart(2, "0")}`,
        });
    }

    return times;
}


// GENERATE AM/PM TIMES 
export function generateAvailableAmPmTimes(
    selectedDate: string,
    stepMinutes = 60,
    now: Date = new Date(),
) {
    const times = generateAmPmTimes(stepMinutes);

    if (!selectedDate) return times;

    const isToday = selectedDate === getLocalDateStr(now);

    if (!isToday) return times;

    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    return times.filter((time) => {
        const [hour = 0, minute = 0] = time.value.split(":").map(Number);

        return hour * 60 + minute > currentMinutes;
    });
}


// GENERATE AM/PM TIMES WITH OPENING AND CLOSING TIME + DATE FILTER
export function generateAvailableAmPmTimesBySchedule(
    openingTime: string,
    closingTime: string,
    selectedDate: string,
    stepMinutes = 60,
    now: Date = new Date(),
) {
    let openingMinutes = 0;
    let closingMinutes = 24 * 60;

    // Not 24 hours
    if (!(openingTime === "00:00" && closingTime === "00:00")) {
        const [openHour = 0, openMinute = 0] =
            openingTime.split(":").map(Number);

        const [closeHour = 0, closeMinute = 0] =
            closingTime.split(":").map(Number);

        openingMinutes = openHour * 60 + openMinute;
        closingMinutes = closeHour * 60 + closeMinute;
    }

    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    const isToday = selectedDate === getLocalDateStr(now);

    const times: { label: string; value: string }[] = [];

    for (
        let totalMinutes = openingMinutes;
        totalMinutes < closingMinutes;
        totalMinutes += stepMinutes
    ) {
        if (isToday && totalMinutes <= currentMinutes) {
            continue;
        }

        const hour24 = Math.floor(totalMinutes / 60);
        const minute = totalMinutes % 60;

        const period = hour24 >= 12 ? "PM" : "AM";

        const displayHour =
            hour24 === 0
                ? "00"
                : String(hour24 > 12 ? hour24 - 12 : hour24).padStart(2, "0");

        times.push({
            label: `${displayHour}:${String(minute).padStart(2, "0")} ${period}`,
            value: `${String(hour24).padStart(2, "0")}:${String(minute).padStart(2, "0")}`,
        });
    }
    return times;
}