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
    location?: Location;
    address?: string;
    reference_id?: string;
    allergies?: string;
}

export interface Guardian {
    first_name: string;
    middle_name: string;
    last_name: string;
    phone_number?: string;
    email: string;
    relationship: string;
    occupation?: string;
    address?: string;
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

    communication?: "Coherent & Logical" | "Impaired" | "";
    speech?: "clear" | "slurred" | "aphasic" | "";

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
    patient_code?: string | null;
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
    billing?: PatientBilling | null;
    family?: PatientFamilyMember[];
    admissions: Admission[];
    current_admission?: Admission;
    latest_admission?: Admission;
}

export interface PatientFamilyMember {
    patient_access_id: number;
    relationship_type: string | null;
    have_access: boolean;
    is_primary: boolean;
    client: {
        client_id: number;
        full_name: string | null;
        phone_number: string | null;
        email: string | null;
        occupation: string | null;
        avatar: string | null;
    } | null;
}

export interface PatientBilling {
    balance_due: number;
    total_paid: number;
    refundable: number;
    adjusted: number;
    accommodation_balance: number;
    service_balance: number;
    unpaid_invoice_count: number;
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
    current_period?: AdmissionPeriod | null;
    future_periods?: {
        count: number;
        charged_amount: number;
        // Grouped by each period's own billing cycle, which is not always the
        // one the current period is on (a yearly extension booked ahead of a
        // monthly stay, say).
        groups: {
            billing_cycle: string | null;
            count: number;
            charged_amount: number;
        }[];
        invoices: InvoiceAccommodation[];
    } | null;
    current_invoice?: InvoiceAccommodation | null;
    discharge_calculation?: DischargeCalculation | null;
    room_transfers?: RoomTransfer[];
}

export interface AdmissionPeriod {
    admission_period_id: number;
    status: "pending" | "active" | "inactive";
    reason: "admitted" | "extended" | "room_change" | "accommodation_change";
    note?: string | null;
    started_at?: string | null;
    ended_at?: string | null;
    charged_amount: number;
    contract?: Contract | null;
}

export interface InvoiceAccommodation {
    invoice_admission_id: number;
    invoice_code: string;
    invoice_id: number;
    price: string;

    paid_amount: string;
    refunded_amount: string;
    net_paid_amount: string;
    refund_status: string;

    admission_period_id?: number | null;
    period_code?: string | null;
    parent_admission_period_id?: number | null;
    accommodation_status?: string | null;
    accommodation_reason?: string | null;
    period_start?: string | null;
    period_end?: string | null;
    moved_at?: string | null;
    contract?: Contract | null;
    status: string;
}
