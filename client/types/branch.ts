import type { Agency } from "./agency";
import type { User } from "./auth";
import { type Location } from "./location";
import type { Permissions } from "./permission";
import type { Review } from "./review";
import { z } from 'zod';


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
    online_additional_fee: number;
    time_zone: string | null;
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
    availability: BranchAvailability;
    averageRating: number | null;
    reviewCount: number;
    reviews: Review[];
    subscriptions: BranchSubscription[];
    location: Location;
}

interface BranchAvailability {
    status: 'OPEN' | 'CLOSED' | 'AUTO';
    is_open: boolean;
    timezone: string;
    opening_time: string | null;
    closing_time: string | null;
}

interface BranchSubscription {
    subscription_id: number;
    uuid: string;
    status: string;
    plans: BranchPlan;
}

interface BranchPlan {
    plan_code: "A" | "B" | "C";
    name: string;
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
        .max(500),

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



