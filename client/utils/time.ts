export function generateAmPmTimes(stepMinutes = 60): string[] {
    const times: string[] = [];

    for (let totalMinutes = 0; totalMinutes < 24 * 60; totalMinutes += stepMinutes) {
        const hour24 = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;

        const ampm = hour24 >= 12 ? "PM" : "AM";
        const hour12 = hour24 % 12 === 0 ? 12 : hour24 % 12;

        const hStr = String(hour12);
        const mStr = String(minutes).padStart(2, "0");

        times.push(`${hStr}:${mStr} ${ampm}`);
    }

    return times;
}


export function getTimeZone() {
    return [
        { label: 'Asia / Manila', value: 'Asia/Manila' },
    ]
}