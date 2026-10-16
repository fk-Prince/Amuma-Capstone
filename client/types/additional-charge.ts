export type AdditionalChargeType =
    | "medication"
    | "supplies"
    | "additional_charges";

export interface AdditionalCharge {
    additional_charge_id: number;
    patient_admission_id: number;
    admitted_at: string | null;
    admission_status: string | null;
    type: AdditionalChargeType;
    type_label: string;
    description: string;
    amount: number;
    invoice_code: string | null;
    invoice_status: string | null;
    invoice_total: number | null;
    created_at: string | null;
}

export const ADDITIONAL_CHARGE_TYPES: { label: string; value: AdditionalChargeType }[] = [
    { label: "Medication", value: "medication" },
    { label: "Supplies", value: "supplies" },
    { label: "Additional Charges", value: "additional_charges" },
];

export interface DiagnosisCase {
    uuid: string;
    title: string;
    description: string | null;
    price: number;
}

export interface DiagnosisFeeSelection {
    diagnoses: {
        patient_diagnosis_uuid: string;
        diagnosis_case_uuid: string;
    }[];
    fee: number;
}
