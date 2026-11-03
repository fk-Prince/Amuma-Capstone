/**
 * A comparable fingerprint of form values, used to tell whether anything was
 * changed (so Save can stay disabled until it is). Picked files are described
 * by name, size and date; coordinates are rounded so tiny map jitter doesn't
 * count as an edit.
 */
export function formSnapshot(value: unknown): string {
    return JSON.stringify(value ?? null, (_key, item) => {
        if (typeof File !== "undefined" && item instanceof File) {
            return `file:${item.name}:${item.size}:${item.lastModified}`;
        }
        if (typeof Blob !== "undefined" && item instanceof Blob) {
            return `blob:${item.size}:${item.type}`;
        }
        if (typeof item === "number" && !Number.isInteger(item)) {
            return Math.round(item * 1e6) / 1e6;
        }
        if (item === undefined || item === "") return null;
        return item;
    });
}
