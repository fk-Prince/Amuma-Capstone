import type { User } from "./auth";
import { type Location } from "./location";
import type { Review } from "./review";

export interface Branch {
    uuid?: string;
    name: string;
    contact_number: string;
    description: string;
    image: File | string | null;
    location: Location
    settings?: BranchSettings | null
    plan?: BranchPlan[] | null;
    roles?: BranchRole[];
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

interface BranchRole {
    role_type: string;
    is_active: boolean;
}

export interface UserBranch {
    user: User;
    branches?: Branch[]
}


