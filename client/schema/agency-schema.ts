import { z } from "zod";
import { locationSchema } from "~/types/branch";

const imageOrString = (label: string) =>
    z.any().superRefine((val, ctx) => {
        if (val === null || val === undefined || val === "") {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: `${label} is required`,
            });
            return;
        }

        if (typeof val === "string") {
            return;
        }

        if (val instanceof File) {
            if (!["image/jpeg", "image/jpg", "image/png"].includes(val.type)) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: `Only JPG and PNG images are allowed for ${label}.`,
                });
            }

            if (val.size > 5 * 1024 * 1024) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: `${label} size must be less than 5MB.`,
                });
            }

            return;
        }

        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `Invalid file for ${label}.`,
        });
    });

const documentOrString = (label: string) =>
    z.any().superRefine((val, ctx) => {
        if (val === null || val === undefined || val === "") {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: `${label} is required`,
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
                    message: `Only JPG, PNG, and PDF files are allowed for ${label}.`,
                });
            }

            if (val.size > 5 * 1024 * 1024) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: `${label} size must be less than 5MB.`,
                });
            }

            return;
        }

        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `Invalid file for ${label}.`,
        });
    });

export const agencySchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, "Agency name is required")
        .max(255),

    email: z
        .string()
        .trim()
        .email("Invalid email address")
        .max(255),

    description: z
        .string()
        .trim()
        .min(1, "Description is required")
        .max(1000),

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

    id_front: imageOrString("ID Front"),

    id_back: imageOrString("ID Back"),

    document: documentOrString("Document"),

    location: locationSchema,
});