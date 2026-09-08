export interface AdmissionSlipPatient {
    patient_uuid: string | null;
    full_name: string | null;
    date_of_birth: string | null;
    gender: string | null;
    phone_number: string | null;
}

export interface AdmissionSlipAdmission {
    patient_admission_id: number;
    admitted_at: string | null;
    discharged_at: string | null;
    status: string | null;
    room: string | null;
    floor: string | null;
    bed: string | null;
    accommodation_type: string | null;
    billing_cycle: string | null;
}

export interface AdmissionSlipInvoice {
    invoice_code: string | null;
    total_amount: number;
}

export interface AdmissionSlipPortal {
    name: string | null;
    email: string | null;
    is_new_account: boolean;
    default_password: string | null;
}

export interface AdmissionSlip {
    patient: AdmissionSlipPatient;
    admission: AdmissionSlipAdmission;
    invoice: AdmissionSlipInvoice;
    portal: AdmissionSlipPortal;
}
