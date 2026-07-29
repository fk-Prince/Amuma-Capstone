import { z } from "zod";


export interface Service {
    branch_uuid: string;
    service_uuid?: string;
    service_id?: number;
    categories?: Category;
    category_id?: number | null;
    category_name?: string | null;
    price: number;
    service_name: string;
    maximum_duration: string;
    is_available?: boolean;
    type: 'online' | 'facility' | 'both';
    type_formatted?: "Homecare Services" | "Inhouse Services" | 'Homecare and Inhouse Services';
}

interface Category {
    branch_uuid?: string;
    category_id?: number | null;
    category_name?: string | null;
}

export const createServiceForm = (): Service => ({
    branch_uuid: "",
    category_id: null,
    category_name: "",
    price: 1,
    service_name: "",
    maximum_duration: "",
    is_available: true,
    type: "online",
});

export const serviceSchema = z
    .object({
        service_name: z.string().min(1, "Service name is required"),
        category_name: z
            .string()
            .min(1, "Category name is required"),
        type: z
            .string()
            .min(1, "Type is required"),
        price: z.coerce
            .number("Invalid Price")
            .min(1, "Price cannot be negative or empty"),
        durationType: z.enum(["minutes", "time"]),
        maximum_duration: z.string().min(1, "Maximum duration is required"),
        is_available: z.boolean().optional(),
    });


// const createDurationSchema = (durationType: "minutes" | "time") =>
//     z.object({
//         maximum_duration:
//             durationType === "minutes"
//                 ? z
//                     .union([z.string(), z.number()])
//                     .transform((val) => String(val))
//                     .refine((val) => /^\d+$/.test(val), {
//                         message:
//                             "Duration must be a whole number of minutes",
//                     })
//                     .refine((val) => Number(val) > 0, {
//                         message:
//                             "Duration must be greater than 0",
//                     })
//                 : z
//                     .string()
//                     .regex(
//                         /^([0-1]\d|2[0-3]):([0-5]\d):([0-5]\d)$/,
//                         "Duration must be HH:mm:ss",
//                     ),
//     });

// export const createServiceSchema = (
//     durationType: "minutes" | "time",
// ) =>
//     serviceSchema.extend(
//         createDurationSchema(durationType).shape,
//     );

const createDurationSchema = (durationType: "minutes" | "time") =>
    z.object({

        durationType: z.literal(durationType),
        maximum_duration:
            durationType === "minutes"
                ? z
                    .union([z.string(), z.number()])
                    .transform((val) => String(val))
                    .refine((val) => /^\d+$/.test(val), {
                        message: "Duration must be a whole number of minutes",
                    })
                    .refine((val) => Number(val) > 0, {
                        message: "Duration must be greater than 0",
                    })
                : z
                    .string()
                    .regex(
                        /^([0-1]\d|2[0-3]):([0-5]\d):([0-5]\d)$/,
                        "Duration must be HH:mm:ss",
                    ),
    });

export const createServiceSchema = (
    durationType: "minutes" | "time",
) =>
    serviceSchema
        .omit({
            maximum_duration: true,
        })
        .extend(createDurationSchema(durationType).shape);