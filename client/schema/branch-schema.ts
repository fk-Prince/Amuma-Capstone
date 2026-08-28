import { z } from "zod";

export const branchImageSchema = z.object({
    type: z
        .string()
        .min(1, "Image type is required"),

    description: z
        .string()
        .trim()
        .min(1, "Description is required")
        .max(1000, "Description must not exceed 1000 characters"),

    contact_number: z
        .string()
        .trim()
        .min(1, "Contact number is required")
        .regex(
            /^[0-9]{10,15}$/,
            "Enter a valid contact number"
        ),
    email:
        z.string()
            .trim()
            .min(1, "Email is required")
            .email("Invalid email address")
            .max(255, "Email must not exceed 255 characters"),
    image: z
        .instanceof(File, {
            message: "Please select an image.",
        })
        .refine(
            (file) =>
                [
                    "image/jpeg",
                    "image/jpg",
                    "image/png",
                ].includes(file.type),
            {
                message: "Only JPG and PNG images are allowed.",
            },
        )
        .refine(
            (file) => file.size <= 5 * 1024 * 1024,
            {
                message: "Image size must be less than 5MB.",
            },
        ),
});


export type BranchImageForm = z.infer<typeof branchImageSchema>;


export const branchSchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, "Branch name is required")
        .max(255),

    description: z
        .string()
        .trim()
        .min(1, "Description is required")
        .max(1000, "Description must not exceed 1000 characters"),

    contact_number: z
        .string()
        .trim()
        .min(1, "Contact number is required")
        .regex(
            /^[0-9]{10,15}$/,
            "Enter a valid contact number"
        ),

    email:
        z.string()
            .trim()
            .min(1, "Email is required")
            .email("Invalid email address")
            .max(255, "Email must not exceed 255 characters"),

    image: z
        .union([
            z.instanceof(File).refine(
                (file) =>
                    ["image/jpeg", "image/jpg", "image/png"].includes(file.type),
                { message: "Only JPG and PNG images are allowed." },
            ).refine(
                (file) => file.size <= 5 * 1024 * 1024,
                { message: "Image size must be less than 5MB." },
            ),
            z.string(),
        ])
        .optional()
        .nullable(),

    document: z.any().superRefine((val, ctx) => {
        if (val === null || val === undefined || val === "") {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: "Document is required",
            });
            return;
        }

        if (typeof val === "string") {
            return;
        }

        if (val instanceof File) {
            if (
                !["image/jpeg", "image/jpg", "image/png", "application/pdf"].includes(
                    val.type,
                )
            ) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: "Only JPG, PNG, and PDF files are allowed for Document.",
                });
            }

            if (val.size > 5 * 1024 * 1024) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: "Document size must be less than 5MB.",
                });
            }

            return;
        }

        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: "Invalid file for Document.",
        });
    }),
});

export const settingSchema = z.object({
    reserved_walkin_slots: z.preprocess(
        (value) => (value === "" ? undefined : value),
        z.coerce
            .number({ message: "Reserved walk-in slots is required." })
            .min(0, "Reserved walk-in slots cannot be negative."),
    ),

    enable_booking_pre_admission: z.boolean(),
    enable_booking_complete_admission: z.boolean(),

    minimum_adl_hours: z.preprocess(
        (value) => (value === "" ? undefined : value),
        z.coerce
            .number({ message: "Minimum homecare hours is required." })
            .min(1, "Minimum homecare hours must be at least 1."),
    ),

    // billing_due_date: z.preprocess(
    //     (value) => (value === "" ? undefined : value),
    //     z.coerce
    //         .number({ message: "Billing due date is required." })
    //         .min(1, "Billing due date must be between 1 and 31.")
    //         .max(31, "Billing due date must be between 1 and 31."),
    // ),

    termination_fee_percent: z.preprocess(
        (value) => (value === "" || value === null ? 0 : value),
        z.coerce
            .number({ message: "Termination fee must be a number." })
            .min(0, "Termination fee cannot be negative.")
            .max(100, "Termination fee cannot exceed 100%."),
    ),

    is_open: z.boolean(),

    currency: z.preprocess(
        (value) => value ?? "",
        z.string().min(1, "Currency is required."),
    ),

    time_zone: z.preprocess(
        (value) => value ?? "",
        z.string().min(1, "Time-zone is required."),
    ),

    opening: z.preprocess(
        (value) => value ?? "",
        z.string().min(1, "Opening time is required."),
    ),

    closing: z.preprocess(
        (value) => value ?? "",
        z.string().min(1, "Closing time is required."),
    ),
});

export type OperationSetting = z.infer<typeof settingSchema>;