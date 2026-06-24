import type { User } from "./auth";
import { type Location } from "./location";
import type { Reviews } from "./review";

export interface Branch {
    uuid?: string;
    name: string;
    contact_number: string;
    description: string;
    image: File | null;
    location: Location
    settings?: BranchSettings | null
    plan?: BranchPlan[] | null;
    roles?: BranchRole[];
};

export interface BranchSettings {
    opening: string,
    closing: string,
    currency: string,
    online_additional_fee: number,
    time_zone: string
}


export interface BranchRetrieve {
    branch_id: number;
    uuid: string;
    name: string;
    description: string | null;
    image: string | null;
    availability: BranchAvailability;
    averageRating: number | null;
    reviewCount: number;
    reviews: Reviews[];
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
    status: string;
    plan_code: string | null;
    plan_name: string | null;
}

interface BranchPlan {
    plan_code: string;
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

