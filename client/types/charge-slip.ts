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
    patient_code?: string | null;
    prepared_by: string | null;
    invoice_code: string | null;
    // An A4-like slip, and the diagnosis cases set out as a table.
    wide?: boolean;
    diagnosis_table?: boolean;
    hide_cashier_note?: boolean;
    total_label?: string;
    // Headings for the description and amount columns of the list.
    column_labels?: [string, string];
    total: number;
    charges: ChargeSlipLine[];
}
