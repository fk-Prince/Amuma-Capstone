export interface PrintBranch {
    name: string | null;
    image?: string | null;
    address?: string | null;
    contact_number?: string | null;
    email?: string | null;
    tin?: string | null;
    agency_name?: string | null;
}

export interface StatementInvoice {
    invoice_code: string;
    created_at: string | null;
    description: string;
    status: string;
    original_total: number;    total: number;
    paid: number;
    refunded: number;
    written_off: number;
    balance: number;
}

export interface PrintLine {
    label: string;
    value?: string | null;
    strong?: boolean;
}
