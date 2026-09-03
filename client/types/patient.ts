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


export interface Diagnosis {
    diagnosis?: string;
    diagnosis_date?: string;
    diagnosis_notes?: string;
    diagnosis_file?: File;
    diagnosis_file_name?: string;
}

export interface Assessment {
    diagnoses?: Diagnosis[];

    condition?: "ambulatory" | "wheelchair" | "stretcher";

    communication?: "Coherent & Logical" | "Impaired" | ""
    speech?: "clear" | "slurred" | "aphasic" | ""

    mental_state?: "alert" | "drowsy" | "lethargic" | "forgetfulness";
    affect?: "cheerful" | "flat" | "tearful" | "depressed" | "angry";
    behavior?:
    | "cooperative"
    | "uncooperative"
    | "lack_of_interaction"
    | "communication_barrier";

    life_system_profile?: LifeSystemProfile;
}

export const LIFE_SYSTEM_ACTIVITIES = [
    "bathing",
    "transferring",
    "toileting",
    "grooming",
    "eating",
    "locomotion",
    "dressing",
] as const;

export type LifeSystemActivity = (typeof LIFE_SYSTEM_ACTIVITIES)[number];

export type LifeSystemScore = 0 | 1 | 2 | 3 | 4 | 5;

export type LifeSystemProfile = Partial<
    Record<LifeSystemActivity, LifeSystemScore>
>;

export const LIFE_SYSTEM_SCALE: { value: LifeSystemScore; label: string }[] = [
    { value: 5, label: "Within normal limits" },
    { value: 4, label: "Not normal, but without help" },
    { value: 3, label: "Uses a device" },
    { value: 2, label: "With assistance" },
    { value: 1, label: "Device and help" },
    { value: 0, label: "Dependent" },
];

export interface PortalDiagnosis {
    diagnosis: string | null;
    diagnosis_date: string | null;
    diagnosis_notes: string | null;
    diagnosis_file: string | null;
}

export interface PortalAssessment {
    recorded_at: string | null;
    condition: string | null;
    mental_state: string | null;
    affect: string | null;
    behavior: string | null;
    communication: string | null;
    speech: string | null;
    life_system_profile: LifeSystemProfile | null;
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

