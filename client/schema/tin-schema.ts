import { z } from "zod";

export const TIN_PATTERN = /^\d{3}-\d{3}-\d{3}-(\d{3}|\d{5})$/;

export const TIN_MESSAGE = "Enter a valid 12-digit TIN (e.g. 123-456-789-000)";

export function formatTin(input: string) {
    const digits = input.replace(/\D/g, "").slice(0, 14);

    return digits
        .replace(/^(\d{3})(\d)/, "$1-$2")
        .replace(/^(\d{3})-(\d{3})(\d)/, "$1-$2-$3")
        .replace(/^(\d{3})-(\d{3})-(\d{3})(\d)/, "$1-$2-$3-$4");
}

export const tin = (required = "TIN is required") =>
    z.string().trim().min(1, required).regex(TIN_PATTERN, TIN_MESSAGE);
