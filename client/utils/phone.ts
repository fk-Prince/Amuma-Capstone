// Numbers are stored bare (9171234503) because PhoneInput supplies the +63
// prefix, so display has to put it back.
export function formatPhone(value?: string | number | null): string {
    const raw = String(value ?? "").trim();

    if (!raw) return "";

    const digits = raw.replace(/\D/g, "");

    const local = digits.startsWith("63")
        ? digits.slice(2)
        : digits.replace(/^0/, "");

    if (local.length !== 10) return raw;

    return `+63 ${local.slice(0, 3)} ${local.slice(3, 6)} ${local.slice(6)}`;
}
