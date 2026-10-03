export interface ChargeSlipLine {
    id: string | number;
    type_label: string;
    description: string;
    amount: number;
    diagnosis?: string | null;
    diagnosis_case?: string | null;
}

export interface ChargeSlip {
    branch_name: string | null;
    patient_name: string | null;
    prepared_by: string | null;
    invoice_code: string | null;
    total: number;
    charges: ChargeSlipLine[];
}
