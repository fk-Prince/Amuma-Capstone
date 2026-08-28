import type { Location } from "./location";
import type { Bed } from "./bed";
import type { Room, RoomTransfer } from "./room";
import type { Contract } from "./contract";
import type { DischargeCalculation } from "./invoice";


export interface Patient {
    patient_id?: number;
    uuid?: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    gender: string;
    citizenship: string;
    occupation: string;
    date_of_birth: string;
    phone_number?: string;
    marital_status: string;
    height?: string;
    weight?: string;
    blood_type?: string;
    location?: Location
    address?: string
    reference_id?: string;
    allergies?: string;
}



export interface Guardian {
    first_name: string,
    middle_name: string,
    last_name: string,
    phone_number?: string,
    email: string,
    relationship: string,
    occupation?: string,
    address?: string,
}


export interface Assessment {
    diagnosis?: string;
    diagnosis_date?: string;
    diagnosis_notes?: string;
    diagnosis_file?: File;
    diagnosis_file_name?: string;


    //VITAL SIGNS
    blood_pressure?: string;
    pulse_rate?: string;
    temperature?: string;
    oxygen_saturation?: string;
    respiratory_rate?: string;


    //COMMUNICATION
    communication?: "Coherent & Logical" | "Impaired" | ""
    speech?: "clear" | "slurred" | "alphasic" | ""

    mental_state?: "alert" | "confused" | "unconscious";
    memory_issues?: "none" | "mild" | "dementia" | "alzheimers";
    mood?: "calm" | "anxious" | "aggressive";
}



export interface PatientRetrieve {
    patient_id: number;
    uuid: string;
    full_name: string;
    first_name: string;
    middle_name?: string;
    last_name: string;
    gender: string;
    date_of_birth: string;
    age: string;
    blood_type?: string;
    height?: string;
    weight?: string;
    phone_number?: string;
    citizenship?: string;
    allergies?: string[];
    has_homecare?: boolean;
    assessment?: Assessment | Assessment[] | null;
    medications_count?: number;
    vitals_count?: number;
    location?: {
        location_id: number;
        full_address: string;
    };
    admissions: Admission[];
    current_admission?: Admission;
    latest_admission?: Admission;
}

export interface Admission {
    patient_admission_id: number;
    status: string;
    admitted_at: string;
    end_date?: string | null;
    note?: string | null;
    bed?: Bed;
    room?: Room;
    invoices: InvoiceAccommodation[];
    current_contract?: Contract | null;
    current_invoice?: InvoiceAccommodation | null;
    discharge_calculation?: DischargeCalculation | null;
    room_transfers?: RoomTransfer[];
}

export interface InvoiceAccommodation {
    invoice_accommodation_id: number;
    invoice_code: string;
    invoice_id: number;
    price: string;

    paid_amount: string;
    refunded_amount: string;
    net_paid_amount: string;
    refund_status: string;

    start_date?: string;
    end_date?: string | null;
    contract?: Contract | null;
    status: string;
}

