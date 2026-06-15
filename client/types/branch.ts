import { type Location } from "./location";
import type { Reviews } from "./review";

export interface Branch {
    name: string;
    contact_number: string;
    description: string;
    image: File | null;
    location: Location
};




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
    subscription_id: number;
    billing_interval: string;
    status: string;
    plan_code: string | null;
    plan_name: string | null;
}
