export interface PortalInvoiceService {
    type: string | null;
    schedule_services_id: number;
    price: number;
    hours_booked: number | null;
    service: {
        service_id: number;
        service_name: string;
        type: string;
    } | null;
    schedule: {
        schedule_id: number;
        schedule_code: string;
        scheduled_at: string | null;
    } | null;
}

export interface PortalInvoice {
    invoice_id: number;
    invoice_code: string;
    description: string;
    status: string;
    total: number;
    adjusted_total: number;
    amount_paid: number;
    net_paid: number;
    balance_due: number;
    refund_status: string;
    void_reason?: string | null;
    voided_at?: string | null;
    created_at: string;
    accommodation_type: string | null;
    billing_cycle: string | null;
    accommodation_status: string | null;
    source_type: string | null;
    services: PortalInvoiceService[];
    adjustments: Array<{
        invoice_adjustment_id?: number;
        type?: string;
        reason: string;
        amount: number;
        created_at?: string;
    }>;
}

export type PortalTransactionType =
    "invoice" | "payment" | "refund" | "adjustment";

export interface PortalTransaction {
    id: string;
    invoiceId?: number;
    invoiceCode: string;
    invoiceCodes?: string[];
    type: PortalTransactionType;
    label: string;
    reference?: string;
    date: string;
    amount: number;
    method?: string;
    status?: string;
    reason?: string;
    maskedCardNumber?: string | null;
    receiptNo?: string | null;
}
