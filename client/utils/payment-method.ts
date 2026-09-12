export function methodLabel(method?: string | null, fallback = "Unknown") {
    const value = (method ?? "").trim();
    return value ? value.toUpperCase() : fallback;
}
