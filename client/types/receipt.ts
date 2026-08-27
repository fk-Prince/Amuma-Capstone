export interface ReceiptLine {
    line_no: number;
    invoice_id: number;
    payment_id: number | null;
    payment_reference: string | null;
    invoice_code: string;
    invoice_date: string | null;
    prior_balance: number;
    amount_applied: number;
    new_balance: number;
}

export type ReceiptChannel = "portal" | "counter";

export interface PaymentReceipt {
    receipt_no: string;
    channel: ReceiptChannel;
    issued_at: string | null;
    is_voided: boolean;
    voided_at: string | null;
    void_reason: string | null;

    issuer: {
        branch_name: string | null;
        logo: string | null;
        address: string | null;
        contact: string | null;
        email: string | null;
        tin: string | null;
        permit_no: string | null;
    };

    payor: {
        name: string | null;
    };

    // Null for portal payments — those are self-service, with no cashier.
    issued_by: string | null;

    patient: {
        patient_uuid: string | null;
        full_name: string | null;
    };

    payment: {
        method: string | null;
        masked_account: string | null;
        amount_tendered: number;
        amount_applied: number;
        change_due: number;
        amount_in_words: string;
    };

    account: {
        balance_before: number;
        balance_after: number;
    };

    lines: ReceiptLine[];
}
