

// export type TimeSlot = {
//     value: string;
//     label: string;
//     start: number;
//     end: number;
// };

// export const formatHour = (h: number): string => {
//     const period = h >= 12 ? "PM" : "AM";
//     const hour12 = h % 12 === 0 ? 12 : h % 12;
//     return `${String(hour12).padStart(2, "0")}:00 ${period}`;
// };

// export const getTimeSlots = (
//     openingHour: number,
//     closingHour: number,
//     slotLengthHours: number,
// ): TimeSlot[] => {
//     const slots: TimeSlot[] = [];

//     for (
//         let h = openingHour;
//         h + slotLengthHours <= closingHour;
//         h += slotLengthHours
//     ) {
//         const start = h;
//         const end = h + slotLengthHours;
//         slots.push({
//             value: `${start}-${end}`,
//             label: `${formatHour(start)} - ${formatHour(end)}`,
//             start,
//             end,
//         });
//     }

//     return slots;
// };

// export const getLocalDateStr = (d: Date): string => {
//     const year = d.getFullYear();
//     const month = String(d.getMonth() + 1).padStart(2, "0");
//     const day = String(d.getDate()).padStart(2, "0");
//     return `${year}-${month}-${day}`;
// };

// export const filterAvailableSlots = (
//     slots: TimeSlot[],
//     selectedDate: string,
//     now: Date = new Date(),
// ): TimeSlot[] => {
//     if (!selectedDate) return slots;

//     const isToday = selectedDate === getLocalDateStr(now);
//     if (!isToday) return slots;

//     const currentHour = now.getHours() + now.getMinutes() / 60;

//     return slots.filter((slot) => slot.start > currentHour);
// };