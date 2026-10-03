import type { Agency } from "./agency";
import type { Branch, BranchSettings } from "./branch";
import type { PlanType } from "~/utils/planType";

export interface SubscriptionPaymentRecord {
    subscription_payment_id: number;
    xendit_invoice_id?: string | null;
    payment_reference_id: string;
    plan_name?: string | null;
    plan_type?: string | null;
    masked_card_number: string | null;
    price: number;
    status: "paid" | "refunded";
    type: "subscription" | "renewal" | "upgrade" | "additional_branch";
    branch_name?: string | null;
    branch_uuid?: string | null;
    payment_method: "GCASH" | "CREDIT-CARD" | null;
    created_at: string | null;
}

export interface SubscriptionCoveredBranch {
    uuid: string;
    name: string;
    email?: string | null;
    contact_number?: string | null;
    address?: string | null;
    latitude?: number | string | null;
    longitude?: number | string | null;
    document?: string | null;
    image?: string | null;
    tin?: string | null;
    branch_status: "pending" | "verified" | "rejected";
    status: "pending" | "approved" | "rejected";
    type?: "included" | "additional";
}

export interface SubscriptionCardData {
    uuid: string;
    status: "pending" | "approved" | "active" | "inactive" | "expired" | "rejected" | "cancelled";
    start_date: string;
    rejection_reason?: string | null;
    rejected_at?: string | null;
    rejection_logs_count?: number;
    end_date: string;
    is_first_branch?: boolean;
    payments?: SubscriptionPaymentRecord[];

    branch: {
        branch_id: number;
        uuid: string;
        name: string;
        email: string;
        contact_number?: string | null;
        address: string | null;
        latitude?: number | string | null;
        longitude?: number | string | null;
        status: string;
        document: string | null;
        image?: string | null;
        tin?: string | null;

        agency: {
            agency_id: number;
            uuid: string;
            name: string;
            email: string;
            address: string | null;
            latitude?: number | string | null;
            longitude?: number | string | null;
            status: "pending" | "verified" | "rejected";
            image?: string | null;
            id_front: string | null;
            id_back: string | null;
            document: string | null;
            registered_by?: string | null;
        };
    };

    plan: {
        plan_id: number;
        name: string;
        plan_code: string;
        type: PlanType;
        branch_limit: number;
        yearly_total?: number;
        additional_branches?: number;
        additional_branch_price?: number;
    };

    subscription?: {
        status?: "pending" | "active" | "expired" | "rejected";
        branch_limit?: number;
        covered_branches: SubscriptionCoveredBranch[];
    };
}

export interface VerificationLogRecord {
    branch_uuid: string | null;
    branch_name: string | null;
    action: "approved" | "rejected";
    scope: "branch" | "agency" | "both";
    reason: string | null;
    reviewed_by: string | null;
    created_at: string | null;
}

export interface Subscription {
    plans: any[];
    selectedPlan: any;
    selectedPlanType: PlanType;
    payment_method: string;
    branch: Branch;
    agency: Agency;
    errors?: any;
    subscriptionPayload?: SubscriptionRequest | null;
    settings?: BranchSettings;
}

export interface SubscriptionRequest {
    token_id?: string;
    authentication_id?: string;
    plan_code: string;
    plan_type: PlanType;
    payment_method: string;

    branch_name: string;
    branch_description?: string;
    branch_street?: string;
    branch_city?: string;
    branch_province?: string;
    branch_country?: string;
    branch_contact_number?: string;
    branch_email?: string;
    branch_image?: File | string | null;
    branch_settings?: any;
    branch_full_address?: string;
    branch_latitude?: number | null;
    branch_longitude?: number | null;
    branch_document?: string | File;


    agency_id?: number;
    agency_name?: string;
    agency_description?: string;
    agency_street?: string;
    agency_city?: string;
    agency_province?: string;
    agency_country?: string;
    agency_full_address?: string;
    agency_latitude?: number | null;
    agency_longitude?: number | null;
    agency_image: File | string | null;
    agency_email?: string;
    agency_id_front?: string | File;
    agency_id_back?: string | File;
    agency_document?: string | File;
}