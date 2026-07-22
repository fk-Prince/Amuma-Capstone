import { z } from "zod";

export const facilityPlanSchema = z.object({
    branch_contract_id: z.number().optional(),
    category: z.enum(["Homecare", "Facility"]),
    type: z.enum(["ADL", "VIP", "COMMON"]),
    price: z.number().min(1, "Price must be greater than 0"),
    billing_interval: z.enum(["MONTHLY", "YEARLY", "OPEN"]),
    description: z.string().max(500, "Description must not exceed 500 characters").optional().nullable(),
});
export type FacilityPlan = z.infer<typeof facilityPlanSchema>;


// export const facilityPlanForm = () => ({
//     category: "Facility",
//     type: "VIP",
//     price: 50000,
//     billing_interval: "MONTHLY",
//     is_active: true,
//     description: "",
// });
export const facilityPlanForm = () => ({
    category: "Facility",
    type: "",
    price: 1,
    billing_interval: "",
    description: "",
});


export const homecarePlanSchema = z.object({
    category: z.literal("Homecare"),
    type: z.literal("ADL"),
    price: z.number().min(1, "Price must be greater than 0"),
    billing_interval: z.literal("HOURLY"),
    description: z.string().max(500, "Description must not exceed 500 characters").optional().nullable(),
});

export type HomecarePlan = z.infer<typeof homecarePlanSchema>;

export const homecarePlanForm = () => ({
    category: "Homecare",
    type: "ADL",
    price: 500,
    billing_interval: "HOURLY",
    is_active: true,
    description: "",
});