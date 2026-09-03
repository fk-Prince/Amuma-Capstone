import { defineStore } from "pinia";
import type { Service } from "~/types/service";
import type { BranchFacility, BranchHomecare } from "~/types/branch";
import type { HomecareBooking, FacilityBooking } from "~/types/booking";
import type { Patient, Guardian, Assessment, Diagnosis } from "~/types/patient";
import type { Reserved } from "~/types/contract";

const initialState = () => ({
    lastSubmittedId: "",
    contract: [] as any[],
    reserved: {} as Reserved,
    category: null as "homecare" | "facility" | null,
    homecare: {} as HomecareBooking,
    facility: {} as FacilityBooking,
    patient: {} as Patient,
    guardian: {} as Guardian,
    assessment: [] as Assessment[],
    diagnoses: [] as Diagnosis[],
    booking_type: "online",
    payment: {
        total_amount: 0,
        payment_status: '',
        invoice_code: ''
    },
    services: [] as Service[],
    branchHomecare: {} as BranchHomecare,
    branchFacility: [] as BranchFacility[],
});
export const useBookingStore = defineStore("booking", {
    state: initialState,
    actions: {
        clear() {
            Object.assign(this, initialState());
        },
    },
});

export interface BookingRecord {
    booking_id: number;
    reference_id: string;
    user_id: number;
    branch_id: number;
    booking_data: BookingData;
    category: string;
    status: string;
    valid_until: string;
    created_at: string;
    updated_at: string;
}

export interface BookingData {
    reserved?: Record<string, any>;
    service: Record<string, any>;
    patient: Record<string, any>;
    guardian: Record<string, any>;
    assessment: Record<string, any>[];
    payment?: {
        total_amount: number;
        paid: boolean;
    };
}

export interface MappedBooking {
    bookingId: number;
    referenceId: string;
    status: string;
    validUntil: string;
    category: "facility" | "homecare";
    facility?: FacilityBooking;
    homecare?: HomecareBooking;
    patient: Patient;
    guardian: Guardian;
    assessment: Assessment[];
    reserved: Reserved,
    payment: {
        total_amount: number;
        paid: boolean;
    };
}