import type { Location } from "./location";
import type { Bed } from "./bed";
import type { Room, RoomTransfer } from "./room";
import type { Contract } from "./contract";


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

    initial_medication: any;
    medication: any;
    vital: any;

    location?: {
        location_id: number;
        full_address: string;
    };

    admissions: Admission[];
    latest_admission: Admission;
}

export interface Admission {
    patient_admission_id: number;
    status: string;
    admitted_at: string;
    end_date?: string | null;
    bed?: Bed;
    room?: Room;
    invoices: InvoiceFacility[];
    current_contract: Contract
    latest_invoice: InvoiceFacility,
    room_transfers: RoomTransfer[]
}

export interface InvoiceFacility {
    invoice_facility_id: number;
    invoice_code: string;
    invoice_id: number;
    price: string;
    contract?: Contract | null;
    status: string;
}

