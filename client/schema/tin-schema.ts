import { z } from "zod";

export const TIN_PATTERN = /^\d{3}-\d{3}-\d{3}-\d{3}$/;

export const TIN_MESSAGE = "Enter a valid 12-digit TIN (e.g. 004-512-873-000)";

export function formatTin(input: string) {
    const digits = input.replace(/\D/g, "").slice(0, 12);

    return digits
        .replace(/^(\d{3})(\d)/, "$1-$2")
        .replace(/^(\d{3})-(\d{3})(\d)/, "$1-$2-$3")
        .replace(/^(\d{3})-(\d{3})-(\d{3})(\d)/, "$1-$2-$3-$4");
}

export function isValidTin(value: string) {
    if (!TIN_PATTERN.test(value)) return false;

    const [a, b, c] = value.split("-");
    const base = `${a}${b}${c}`;

    if (/^(\d)\1{8}$/.test(base)) return false;
    if (base === "123456789" || base === "987654321") return false;

    return !(a === b && b === c);
}

export const tin = (required = "TIN is required") =>
    z.string().trim().min(1, required).refine(isValidTin, TIN_MESSAGE);
