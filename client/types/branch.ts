import type { Agency } from "./agency";
import type { User } from "./auth";
import { type Location } from "./location";
import type { Permissions } from "./permission";
import type { Review } from "./review";
import { z } from 'zod';
import type { Service } from "./service";


export interface Branch {
    uuid?: string;
    name: string;
    contact_number: string;
    description: string;
    image: File | string | null;
    location: Location
    settings?: BranchSettings | null
    plan?: BranchPlan[] | null;
    role_name?: string;
    permissions?: Permissions[];
    agency: Agency
    images: any[];
};


export interface BranchSettings {
    opening: string | null;
    closing: string | null;
    currency: string | null;
    time_zone: string | null;
    reserved_walkin_slots: number | null;
    enable_booking_pre_admission: boolean;
    enable_booking_complete_admission: boolean;
    minimum_adl_hours: number | null;
    // billing_due_date: number | null;
    is_open: boolean;
    status?: 'OPEN' | 'CLOSED';
}

export const getBranchImage = (image: File | string | null | undefined) => {
    if (!image) return "";


    return typeof image === "string"
        ? image
        : URL.createObjectURL(image);
};

export interface BranchRetrieve {
    branch_id: number;
    uuid: string;
    name: string;
    description: string | null;
    image: string | undefined;
    secondaryImage: string[] | undefined;
    settings: BranchSettings;
    averageRating: number | null;
    reviewCount: number;
    reviews: Review[];
    subscriptions: BranchSubscription[];
    location: Location;
    homecare: BranchHomecare,
    facility: BranchFacility[]
    services: Service[];
}

export interface BranchHomecare {
    adl_hourly_rate?: number;
    adl_min_hour?: number
    description?: string
}
export interface BranchFacility {
    available_slot: number
    accommodation_type: "VIP" | "COMMON";
    billing_cycle: "HOURLY" | "MONTHLY" | "YEARLY";
    price: number;
    description?: string;
}

// export interface BranchAvailability {
//     status: 'OPEN' | 'CLOSED' | 'AUTO';
//     is_open: boolean;
//     timezone: string;
//     opening: string | null;
//     closing: string | null;
// }

interface BranchSubscription {
    subscription_id: number;
    uuid: string;
    plans: BranchPlan;
}

interface BranchPlan {
    plan_code: "A" | "B" | "C";
    name: string;
    status: string;
}

export interface UserBranch {
    user: User;
    branches?: Branch[]
}




export const locationSchema = z.object({
    street: z
        .string()
        .trim()
        .min(1, "Street is required"),

    city: z
        .string()
        .trim()
        .min(1, "City is required"),

    province: z
        .string()
        .trim()
        .min(1, "Province is required"),

    country: z
        .string()
        .trim()
        .min(1, "Country is required"),

    latitude: z.coerce.number().optional(),
    longitude: z.coerce.number().optional(),
});

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
        .max(1000),

    contact_number: z
        .string()
        .trim()
        .min(1, "Contact number is required")
        .regex(
            /^\+?[0-9]{10,15}$/,
            "Enter a valid contact number"
        ),

    image: z
        .union([
            z.instanceof(File),
            z.string(),
            z.null(),
        ])
        .optional(),

    location: locationSchema,
});



