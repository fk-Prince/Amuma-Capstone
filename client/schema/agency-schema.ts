import { z } from "zod";
import { locationSchema } from "~/types/branch";

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
    location: locationSchema,
});
