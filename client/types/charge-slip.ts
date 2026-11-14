import type { PrintBranch } from "~/types/print";

export interface ChargeSlipLine {
    id: string | number;
    type_label: string;
    description: string;
    billed?: string | null;
    amount: number;
}

export type ChargeSlipKind = "charge" | "diagnosis" | "balance";

export interface ChargeSlip {
    kind?: ChargeSlipKind;
    title?: string;
    section?: string;
    branch: PrintBranch;
    patient_name: string | null;
    patient_code?: string | null;
    prepared_by: string | null;
    invoice_code: string | null;
    hide_cashier_note?: boolean;
    total_label?: string;
    column_labels?: [string, string];
    total: number;
    charges: ChargeSlipLine[];
}
