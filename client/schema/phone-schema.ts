import { z } from "zod";

// The +63 prefix is rendered by the input, so the stored value excludes it.
export const PH_MOBILE_PATTERN = /^9\d{2}[\s-]?\d{3}[\s-]?\d{4}$/;

export const PH_MOBILE_MESSAGE =
    "Enter a valid PH mobile number (e.g. 912 345 6789)";

export const phoneNumber = (required = "Phone number is required") =>
    z
        .string()
        .trim()
        .min(1, required)
        .regex(PH_MOBILE_PATTERN, PH_MOBILE_MESSAGE);

export const optionalPhoneNumber = () =>
    z
        .string()
        .trim()
        .optional()
        .refine((value) => !value || PH_MOBILE_PATTERN.test(value), {
            message: PH_MOBILE_MESSAGE,
        });
