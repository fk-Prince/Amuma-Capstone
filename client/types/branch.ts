import { type Location } from "./location";
import type { Reviews } from "./review";

export interface Branch {
    name: string;
    contact_number: string;
    description: string;
    image: File | null;
    location: Location
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
