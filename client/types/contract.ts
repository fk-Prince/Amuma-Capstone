import { z } from "zod";
import type { Room } from "./room";
import type { Bed } from "./bed";

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
    branch_contract_id?: string;
    category: string;
    accommodation_type: string;
    billing_cycle: string;
    price: number;
}

export interface RoomContract {
    rooms: Room[],
    accommodation_type: "Common" | "VIP"
    available_beds_count: number,
    billing_cycle: "MONTHLY" | "YEARLY"
    contract_id: number;
    price: number
}

export const reserved = ref<{
    room: Room | null;
    bed: Bed | null;
    billing_cycle: "monthly" | "yearly";
    contract_id: number | null;
    price: number;
    accommodation_type: "Common" | "VIP";
    admitted_at: string,
}>({
    room: null,
    bed: null,
    contract_id: null,
    billing_cycle: "monthly",
    price: 0,
    accommodation_type: "Common",
    admitted_at: ''
});

export interface Reserved {
    room: Room | null;
    bed: Bed | null;
    billing_cycle: "monthly" | "yearly";
    contract_id: number | null;
    price: number;
    accommodation_type: "Common" | "VIP";
    admitted_at: string,
    // discharge_at: string
}

export const reservedSchema = z.object({
    room: z
        .custom<Room>((val) => !!val, { message: "Please select a room." })
        .nullable()
        .refine((val) => val !== null, { message: "Please select a room." }),
    bed: z
        .custom<Bed>((val) => !!val, { message: "Please select a bed." })
        .nullable()
        .refine((val) => val !== null, { message: "Please select a bed." }),
    billing_cycle: z.enum(["monthly", "yearly"], {
        message: "Please select a billing cycle.",
    }),
    contract_id: z
        .number()
        .nullable()
        .refine((val) => val !== null && val > 0, {
            message: "Missing contract information — please reselect a room.",
        }),

    accommodation_type: z.enum(["Common", "VIP"], {
        message: "Please select an accommodation type.",
    }),
    admitted_at: z
        .string()
        .min(1, "Please select an admission date.")
        .refine((val) => !isNaN(Date.parse(val)), {
            message: "Admission date is invalid.",
        }),
});

export type ReservedInput = z.infer<typeof reservedSchema>;