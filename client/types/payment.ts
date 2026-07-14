import { z } from "zod";

export const paymentSchema = z.object({
    number: z
        .string()
        .transform((val) => val.replace(/\D/g, ""))
        .refine(
            (val) => val.length >= 13 && val.length <= 19,
            "Enter a valid card number."
        ),

    expMonth: z
        .string()
        .refine(
            (val) => {
                const month = Number(val);
                return month >= 1 && month <= 12;
            },
            "Enter a valid month (01–12)."
        ),

    expYear: z
        .string()
        .regex(/^\d{2,4}$/, "Enter a valid year."),

    cvc: z
        .string()
        .transform((val) => val.replace(/\D/g, ""))
        .refine(
            (val) => val.length >= 3 && val.length <= 4,
            "Enter a valid CVC."
        ),

    firstName: z
        .string()
        .min(1, "First name is required."),

    lastName: z
        .string()
        .min(1, "Last name is required."),

    email: z
        .string()
        .email("Enter a valid email address."),
});

export type PaymentSchema = z.infer<typeof paymentSchema>;