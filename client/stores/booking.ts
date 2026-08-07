import { defineStore } from "pinia";
import type { Service } from "~/types/service";
import type { BranchFacility, BranchHomecare } from "~/types/branch";
import type { HomecareBooking, FacilityBooking } from "~/types/booking";
import type { Patient, Guardian, Assessment } from "~/types/patient";
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
    assessment: {} as Assessment,
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
    category: string; // "Facility" | "Homecare"
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
    assessment: Record<string, any>;
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
    assessment: Assessment;
    reserved: Reserved,
    payment: {
        total_amount: number;
        paid: boolean;
    };
}


// export function mapBookingResponse(record: BookingRecord): MappedBooking;
// export function mapBookingResponse(records: BookingRecord[]): MappedBooking[];
// export function mapBookingResponse(
//     input: BookingRecord | BookingRecord[],
// ): MappedBooking | MappedBooking[] {
//     if (Array.isArray(input)) {
//         return input.map(mapSingleBooking);
//     }
//     return mapSingleBooking(input);
// }

// function mapSingleBooking(record: BookingRecord): MappedBooking {
//     const data = record.booking_data;
//     const category: "facility" | "homecare" =
//         (record.category || "").toLowerCase() === "homecare"
//             ? "homecare"
//             : "facility";

//     const reserved: Reserved = {
//         room: data.reserved?.room
//             ? {
//                 room_id: data.reserved.room.room_id ?? 0,
//                 room_no: data.reserved.room.room_no ?? "",
//                 floor: data.reserved.room.floor ?? "",
//                 branch_id: data.reserved.room.branch_id ?? 0,
//                 status: data.reserved.room.status ?? "",
//                 room_type: data.reserved.room.room_type ?? "",
//                 capacity: data.reserved.room.capacity ?? "",
//                 created_at: data.reserved.room.created_at ?? "",
//                 updated_at: data.reserved.room.updated_at ?? "",
//                 beds: data.reserved.room.beds ?? [],
//                 reserved_beds_count: data.reserved.room.reserved_beds_count,
//             }
//             : null,
//         bed: data.reserved?.bed
//             ? {
//                 bed_id: data.reserved.bed.bed_id ?? 0,
//                 room_id: data.reserved.bed.room_id ?? 0,
//                 status: data.reserved.bed.status ?? "",
//                 bed_no: data.reserved.bed.bed_no ?? "",
//                 created_at: data.reserved.bed.created_at ?? "",
//                 updated_at: data.reserved.bed.updated_at ?? "",
//                 current_admission: data.reserved.bed.current_admission ?? null,
//             }
//             : null,
//         contract_id: data.reserved?.contract_id ?? null,
//         billing_cycle:
//             (data.reserved?.billing_cycle as Reserved["billing_cycle"]) ??
//             "monthly",
//         price: Number(data.reserved?.price ?? 0),
//         accommodation_type: data.reserved?.accommodation_type,
//         admitted_at: data.reserved?.admitted_at
//     };

//     const patient: Patient = {
//         first_name: data.patient?.first_name ?? "",
//         middle_name: data.patient?.middle_name ?? "",
//         last_name: data.patient?.last_name ?? "",
//         gender: data.patient?.gender ?? "",
//         citizenship: data.patient?.citizenship ?? "",
//         occupation: data.patient?.occupation ?? "",
//         date_of_birth: data.patient?.date_of_birth ?? "",
//         phone_number: data.patient?.phone_number ?? "",
//         marital_status: data.patient?.marital_status ?? "",
//         height: data.patient?.height ?? "",
//         weight: data.patient?.weight ?? "",
//         blood_type: data.patient?.blood_type ?? "",
//         address: data.patient?.address ?? "",
//     };

//     const guardian: Guardian = {
//         first_name: data.guardian?.first_name ?? "",
//         middle_name: data.guardian?.middle_name ?? "",
//         last_name: data.guardian?.last_name ?? "",
//         phone_number: data.guardian?.phone_number ?? "",
//         email: data.guardian?.email ?? "",
//         relationship: data.guardian?.relationship ?? "",
//         occupation: data.guardian?.occupation ?? "",
//         address: data.guardian?.address ?? "",
//     };

//     const assessment: Assessment = {
//         diagnosis: data.assessment?.diagnosis ?? "",
//         diagnosis_date: data.assessment?.diagnosis_date ?? "",
//         diagnosis_notes: data.assessment?.diagnosis_notes ?? "",
//         diagnosis_file_name: data.assessment?.diagnosis_file_name ?? "",
//         blood_pressure: data.assessment?.blood_pressure ?? "",
//         pulse_rate: data.assessment?.pulse_rate ?? "",
//         temperature: data.assessment?.temperature ?? "",
//         oxygen_saturation: data.assessment?.oxygen_saturation ?? "",
//         respiratory_rate: data.assessment?.respiratory_rate ?? "",
//         communication: data.assessment?.communication ?? "",
//         speech: data.assessment?.speech ?? "",
//         mental_state: data.assessment?.mental_state ?? "alert",
//         memory_issues: data.assessment?.memory_issues ?? "none",
//         mood: data.assessment?.mood ?? "calm",
//     };

//     const mapped: MappedBooking = {
//         bookingId: record.booking_id,
//         referenceId: record.reference_id,
//         status: record.status,
//         validUntil: record.valid_until,
//         reserved,
//         category,
//         patient,
//         guardian,
//         assessment,
//         payment: {
//             total_amount: data.payment?.total_amount ?? 0,
//             paid: data.payment?.paid ?? false,
//         },
//     };

//     if (category === "facility") {
//         mapped.facility = {
//             type: (data.service?.type as FacilityBooking["type"]) ?? "",
//             plan: (data.service?.plan as FacilityBooking["plan"]) ?? "",
//             billing_cycle:
//                 (data.service?.billing_cycle as FacilityBooking["billing_cycle"]) ??
//                 "",
//             admission_date: data.service?.admission_date ?? "",
//         };
//     } else {
//         mapped.homecare = {
//             type: (data.service?.type as HomecareBooking["type"]) ?? "Medical",
//             date: data.service?.date ?? "",
//             prefered_time: data.service?.prefered_time ?? "",
//             time_span: data.service?.time_span ?? "",
//             address: data.service?.address ?? "",
//             services: data.service?.services ?? [],
//         };
//     }

//     return mapped;
// }