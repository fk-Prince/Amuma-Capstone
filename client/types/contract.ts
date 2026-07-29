import { z } from "zod";

// export const facilityPlanSchema = z.object({
//     branch_contract_id: z.number().optional(),
//     category: z.enum(["Homecare", "Facility"]),
//     accommodation_type: z.enum(["ADL", "VIP", "COMMON"]),
//     price: z.number().min(1, "Price must be greater than 0"),
//     billing_cycle: z.enum(["MONTHLY", "YEARLY", "OPEN"]),
//     description: z.string().max(500, "Description must not exceed 500 characters").optional().nullable(),
// });

export const facilityPlanSchema = z.object({
    branch_contract_id: z.number().optional(),

    category: z.enum(["Homecare", "Facility"], {
        message: "Category is required",
    }),

    accommodation_type: z.enum(["ADL", "VIP", "COMMON"], {
        message: "Accommodation type is required",
    }),

    price: z.number().min(1, "Price must be greater than 0"),

    billing_cycle: z.enum(["MONTHLY", "YEARLY", "OPEN"], {
        message: "Billing cycle is required",
    }),

    description: z.string()
        .max(500, "Description must not exceed 500 characters")
        .optional()
        .nullable(),
});

export type FacilityPlan = z.infer<typeof facilityPlanSchema>;


// export const facilityPlanForm = () => ({
//     category: "Facility",
//     type: "VIP",
//     price: 50000,
//     billing_cycle: "MONTHLY",
//     is_active: true,
//     description: "",
// });

export const facilityPlanForm = () => ({
    category: "Facility",
    accommodation_type: undefined,
    price: 1,
    billing_cycle: undefined,
    description: "",
});

export const homecarePlanSchema = z.object({
    category: z.literal("Homecare", {
        message: "Category must be Homecare",
    }),

    accommodation_type: z.literal("ADL", {
        message: "Accommodation type must be ADL",
    }),
    price: z.number().min(1, "Price must be greater than 0"),
    billing_cycle: z.literal("HOURLY", {
        message: "Billing cycle must be HOURLY",
    }),
    description: z.string()
        .max(500, "Description must not exceed 500 characters")
        .optional()
        .nullable(),
});

export type HomecarePlan = z.infer<typeof homecarePlanSchema>;

export const homecarePlanForm = () => ({
    category: "Homecare",
    accommodation_type: "ADL",
    price: 500,
    billing_cycle: "HOURLY",
    is_active: true,
    description: "",
});

export interface Contract {
    category: string;
    accommodation_type: string;
    billing_cycle: string;
    price: number;
}