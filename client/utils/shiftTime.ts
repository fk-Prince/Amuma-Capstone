type Shift = { start_time: string; end_time: string };

function toMinutes(time: string) {
    const [hours = 0, minutes = 0] = time.split(":").map(Number);

    return hours * 60 + minutes;
}

function spans(start: string, end: string): [number, number][] {
    const from = toMinutes(start);
    const to = toMinutes(end);

    return to > from ? [[from, to]] : [[from, 1440], [0, to]];
}

export function shiftMinutes(start: string, end: string) {
    const minutes = toMinutes(end) - toMinutes(start);

    return minutes > 0 ? minutes : minutes + 1440;
}

export function shiftsOverlap(a: Shift, b: Shift) {
    return spans(a.start_time, a.end_time).some(([aStart, aEnd]) =>
        spans(b.start_time, b.end_time).some(
            ([bStart, bEnd]) => aStart < bEnd && bStart < aEnd,
        ),
    );
}
