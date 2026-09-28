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
    return TIN_PATTERN.test(value);
}

export const tin = (required = "TIN is required") =>
    z.preprocess(
        (value) => (typeof value === "string" ? value : ""),
        z.string().trim().min(1, required).refine(isValidTin, TIN_MESSAGE),
    );
