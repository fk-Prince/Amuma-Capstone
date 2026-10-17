export interface ChargeSlipLine {
    id: string | number;
    type_label: string;
    description: string;
    // How it is billed, when it is billed again every month.
    billed?: string | null;
    amount: number;
}

export interface ChargeSlip {
    // Defaults to "Charge Slip" and "Charges".
    title?: string;
    section?: string;
    branch_name: string | null;
    patient_name: string | null;
    prepared_by: string | null;
    invoice_code: string | null;
    // A wide, A4-like slip for the diagnosis cases, set out as a table.
    wide?: boolean;
    hide_cashier_note?: boolean;
    total_label?: string;
    total: number;
    charges: ChargeSlipLine[];
}
