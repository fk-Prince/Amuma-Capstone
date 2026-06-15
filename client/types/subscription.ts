import type { Agency } from "./agency";
import type { Branch } from "./branch";

export interface Subscription {
    plans: any[];
    selectedPlan: any;
    selectedInterval: "" | "monthly" | "yearly";
    payment_method: string;
    branch: Branch;
    agency: Agency;
    errors?: any;
    subscriptionPayload?: SubscriptionRequest | null;
    settings?: any;
}

export interface SubscriptionRequest {
    token_id?: string;
    authentication_id?: string;
    plan_code: string;
    billing_interval: string;
    payment_method: string;

    branch_name: string;
    branch_description?: string;
    branch_street?: string;
    branch_city?: string;
    branch_province?: string;
    branch_country?: string;
    branch_contact_number?: string;
    branch_image?: File | null;
    branch_settings?: any;
    branch_latitude?: number | null;
    branch_longitude?: number | null;

    agency_id?: number;
    agency_name?: string;
    agency_description?: string;
    agency_street?: string;
    agency_city?: string;
    agency_province?: string;
    agency_country?: string;
    agency_latitude?: number | null;
    agency_longitude?: number | null;
}