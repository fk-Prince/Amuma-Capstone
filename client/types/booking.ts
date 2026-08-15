import { reactive } from "vue";
import { z } from "zod";
import { getLocalDateStr } from "~/utils/time";
import type { Assessment, Guardian, Patient } from "./patient";
import { reserved, type Reserved } from './contract';
import type { User } from "./auth";

//FACILITY
export interface FacilityBooking {
    type: "Complete" | "Pre-Admission" | "" | "Walk-in";
    plan: "VIP" | "Common" | "";
    billing_cycle: "Monthly" | "Yearly" | "";
    admission_date: string;
}

export interface HomecareBooking {
    services?: BookedService[];
    type: "Medical" | "ADL";
    date: string;
    prefered_time: string;
    time_span?: string;
    address: string;
}

export interface BookedService {
    service_id: number;
    service_name: string;
    price: number;
}

export interface BookingRetrieve {
    booking_id: number;
    reference_id: string;
    category: "facility" | "homecare";
    booking_type: 'online' | 'walk-in';
    status: "pending" | "approved" | "cancelled" | "rejected" | "expired";
    homecare: HomecareBooking;
    facility: FacilityBooking;
    patient: Patient;
    guardian: Guardian;
    assessment: Assessment;
    payment: {
        total_amount: number,
        paid: boolean,
        xendit_invoice_id: string,
        payment_status: string
    };
    reserved: Reserved | null;
    created_at: string;
    updated_at: string;
    assignments?: SavedAssignment[];
}


export interface SavedAssignment {
    employee_id: number;
    service_id: number | null;
    employee_name: string;
    role_name?: string;
    avatar?: string;
}



export const typeFilters = [
    { label: "All Category", value: "all" },
    { label: "In-house Facility", value: "facility" },
    { label: "Homecare", value: "homecare" },
];


export function formatStatus(status?: string) {
    const s = (status ?? "").toLowerCase();

    return matchStatus(s);
}

function matchStatus(status: string) {
    switch (status) {
        case "in_progress":
        case "in-progress":
            return "In-Progress";
        case "pending":
            return "Pending";
        case "approved":
            return "Approved";
        case "completed":
            return "Completed";
        case "rejected":
            return "Rejected";
        case "cancelled":
            return "Cancelled";
        case "expired":
            return "Expired";
        default:
            return status;
    }
}
export function statusClasses(status?: string) {
    const s = (status ?? "").toLowerCase();

    switch (s) {
        case "approved":
            return "bg-[#E4F4EE] text-[#1F7A4D]";

        case "in_progress":
        case "in-progress":
            return "bg-[#E6F1FA] text-[#2563A6]";

        case "completed":
            return "bg-[#EAF4F2] text-[#0E7C7B]";

        case "rejected":
        case "cancelled":
            return "bg-[#FBE8E6] text-[#B3402F]";

        case "expired":
            return "bg-gray-100 text-gray-500";

        case "pending":
        default:
            return "bg-[#FDF3DE] text-[#966B1F]";
    }
}

export function statusDotClasses(status?: string) {
    const s = (status ?? "").toLowerCase().replace("-", "_");
    switch (s) {
        case "approved":
            return "bg-[#1F7A4D]";

        // case "in_progress":
        //     return "bg-[#2563A6]";

        // case "completed":
        //     return "bg-[#0E7C7B]";

        case "rejected":
        case "cancelled":
            return "bg-[#B3402F]";

        case "expired":
            return "bg-gray-400";

        case "pending":
        default:
            return "bg-[#966B1F]";
    }
}

// ------------------------------------------ CHANGE






export const statusFilters = [
    { label: "All", value: "all" },
    { label: "Pending", value: "pending" },
    { label: "Approved", value: "approved" },
    { label: "Rejected", value: "rejected" },
    { label: "Missed", value: "missed" },
    { label: "Expired", value: "expired" },
    { label: "Cancelled", value: "cancelled" },
];

