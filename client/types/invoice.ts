// export interface InvoiceBranch {
//     branch_id: number;
//     name: string | null;
// }

// export interface InvoiceServiceLine {
//     schedule_services_id: number;
//     price: number;
//     note: string | null;
//     service_name: string | null;
// }

// export interface InvoiceAccommodationLine {
//     invoice_admission_id: number;
//     branch_contract_id: number;
//     price: number;
//     patient_admission_id: number;
//     patient_name: string;
// }

// export type RefundStatus =
//     | 'pending'
//     | 'processing'
//     | 'completed'
//     | 'failed'
//     | 'cancelled';

// export interface InvoiceRefund {
//     refund_id: number;
//     reference_id: string;
//     amount: number;
//     refund_method: string | null;
//     status: RefundStatus;
//     reason: string | null;
// }

// export interface InvoicePayment {
//     payment_id: number;
//     reference_id: string;
//     amount: number;
//     payment_method: string;
//     created_at: string | null;
//     refunds: InvoiceRefund[];
// }

// export interface InvoicePatient {
//     patient_id: string;
//     patient_uuid: string;
//     full_name: string | null;
//     first_name: string | null;
//     middle_name: string | null;
//     last_name: string | null;
//     gender: string | null;
//     date_of_birth: string | null;
//     age: number | null;
//     blood_type: string | null;
//     phone_number: string | null;
//     citizenship: string | null;
// }

// export type RefundSummaryStatus = 'none' | 'partially refunded' | 'full refunded';

// export interface InvoiceDetail {
//     invoice_id: number;
//     invoice_code: string;
//     schedule_code?: string | null;
//     total: number;
//     amount_paid: number;
//     refunded_amount: number;
//     refund_processing_amount: number;
//     balance_due: number;
//     status: string;
//     refund_status: RefundSummaryStatus;
//     created_at: string | null;
//     patient: InvoicePatient | null;
//     branch?: InvoiceBranch;
//     services?: InvoiceServiceLine[];
//     facilities?: InvoiceAccommodationLine[];
//     payments?: InvoicePayment[];
// }

// export interface PatientInvoiceItem {
//     invoice_id: number;
//     invoice_code: string;
//     schedule_code?: string | null;
//     total: number;
//     amount_paid: number;
//     refunded_amount: number;
//     refund_processing_amount: number;
//     balance_due: number;
//     status: string;
//     refund_status: RefundSummaryStatus;
//     created_at: string | null;
//     branch?: InvoiceBranch;
//     services?: InvoiceServiceLine[];
//     facilities?: InvoiceAccommodationLine[];
//     payments?: InvoicePayment[];
// }

// export interface PatientInvoiceSummary {
//     patient: InvoicePatient | null;
//     total_amount: number;
//     total_paid: number;
//     total_refunded: number;
//     total_refund_processing: number;
//     total_balance: number;
//     refund_status: RefundSummaryStatus;
//     status: string;
//     invoice_count: number;
//     latest_invoice: PatientInvoiceItem | null;
//     invoices: PatientInvoiceItem[];
// }

export interface InvoiceBranch {
    branch_id: number;
    name: string | null;
}

export interface InvoiceServiceLine {
    schedule_services_id: number;
    price: number;
    note: string | null;
    service_name: string | null;
    type?: string | null;
    hours_booked?: number | null;
}

export interface InvoiceAccommodationLine {
    invoice_admission_id: number;
    branch_contract_id: number;
    price: number;
    patient_admission_id: number;
    patient_name: string;
}

export type RefundStatus = "requested" | "completed" | "declined";

export type RefundSummaryStatus =
    "none" | "partially refunded" | "full refunded";

export interface InvoiceRefund {
    refund_id: number;
    refund_code: string | null;
    amount: number;
    refund_total?: number;
    refund_method: string | null;
    status: RefundStatus;
    declined_reason: string | null;
    masked_card_number: string | null;
    created_at: string | null;
}

// A refund read from the refunds table rather than through one payment's
// allocation, so it carries every invoice it touched.
export interface PatientRefund extends InvoiceRefund {
    invoice_codes: string[];
}

export interface PatientPayment {
    payment_id: number;
    receipt_no: string | null;
    reference_id: string | null;
    amount: number;
    payment_method: string | null;
    masked_card_number: string | null;
    payor_name: string | null;
    created_at: string | null;
    invoice_codes: string[];
}

export interface InvoiceAdjustmentDetail {
    invoice_adjustment_id: number;
    type: string;
    amount: number;
    reason: string | null;
    created_at: string | null;
}

export interface InvoicePayment {
    payment_id: number;
    allocation_id?: number;
    receipt_no?: string | null;
    reference_id: string;
    amount: number;
    description?: string | null;
    payment_method: string;
    masked_card_number?: string | null;
    created_at: string | null;
    refunds: InvoiceRefund[];
}

export interface InvoicePatient {
    patient_id: number;
    patient_uuid: string;
    full_name: string | null;
    first_name: string | null;
    middle_name: string | null;
    last_name: string | null;
    gender: string | null;
    date_of_birth: string | null;
    age: number | null;
    blood_type: string | null;
    phone_number: string | null;
    citizenship: string | null;
}

export interface AdmissionRoom {
    room_id: number;
    room_no: string;
}

export interface AdmissionBed {
    bed_id: number;
    bed_no: string;
}

export interface PatientAdmission {
    patient_admission_id: number;
    status: string;
    admission_date: string | null;
    discharge_date: string | null;
    // Same value as discharge_date, under the name the extend-stay modal reads.
    end_date: string | null;
    current_contract: {
        branch_contract_id: number;
        category: string | null;
        accommodation_type: string | null;
        billing_cycle: string | null;
        price: number | string;
    } | null;
    room: AdmissionRoom | null;
    bed: AdmissionBed | null;
    // Figures for the card. The invoices themselves are fetched when the stay
    // is opened, so they are not carried in the admissions list.
    invoice_count: number;
    total_amount: number;
    balance_due: number;
}

export interface DischargeCalculation {
    admission_id: number;
    eligible_for_refund: boolean;
    billing_cycle: string | null;
    admission_date: string | null;
    discharge_date: string | null;
    days_since_admission: number | null;
    contract_price: number;
    amount_paid: number;
    required_payment: number;
    fee_base_amount: number;
    days_stayed_amount: number;
    retained_amount: number;
    refund_amount: number;
    consumed_days: number;
    remaining_days: number;
    period_days: number;
    period_start: string | null;
    period_end: string | null;
    daily_rate: number;
    period_price: number;
    invoice_total: number;
    invoice_code: string | null;
    retained_half: number;
    policy: string;
    policy_title: string;
    policy_description: string;
    is_within_refund_window: boolean;
    is_under_required_payment: boolean;
    payment_shortfall: number;
}

export interface InvoiceDetail {
    invoice_id: number;
    invoice_code: string;
    schedule_code?: string | null;
    total: number;
    adjusted_total?: number;
    amount_paid: number;
    refunded_amount: number;
    refund_requested_amount: number;
    refundable_amount?: number;
    has_pending_refund?: boolean;
    balance_due: number;
    status: string;
    refund_status: RefundSummaryStatus;
    created_at: string | null;
    patient: InvoicePatient | null;
    branch?: InvoiceBranch;
    services?: InvoiceServiceLine[];
    facilities?: InvoiceAccommodationLine[];
    payments?: InvoicePayment[];
    adjustments?: InvoiceAdjustmentDetail[];
}

export interface PatientInvoiceItem {
    invoice_id: number;
    invoice_code: string;
    description?: string | null;
    schedule_code?: string | null;
    total: number;
    adjusted_total?: number;
    amount_paid: number;
    refunded_amount: number;
    refund_requested_amount: number;
    refundable_amount?: number;
    has_pending_refund?: boolean;
    balance_due: number;
    status: string;
    refund_status: RefundSummaryStatus;
    created_at: string | null;
    void_reason?: string | null;
    voided_at?: string | null;
    voided_by?: string | null;
    branch?: InvoiceBranch;
    services?: InvoiceServiceLine[];
    facilities?: InvoiceAccommodationLine[];
    accommodations?: {
        accommodation_type: string | null;
        billing_cycle: string | null;
        room_no: string | null;
        bed_no: string | null;
    }[];
    payments?: InvoicePayment[];
    adjustments?: InvoiceAdjustmentDetail[];
}

export interface PatientInvoiceSummary {
    patient: InvoicePatient | null;
    total_amount: number;
    total_paid: number;
    total_refunded: number;
    total_refund_requested: number;
    total_refundable: number;
    total_balance: number;
    refund_status: RefundSummaryStatus;
    status: string;
    invoice_count: number;
    latest_invoice: PatientInvoiceItem | null;
    invoices: PatientInvoiceItem[];
    voided_invoices: PatientInvoiceItem[];
    payments: PatientPayment[];
    refunds: PatientRefund[];
    admissions: PatientAdmission[];
    services: InvoiceServiceLine[];
    discharge_calculation: DischargeCalculation | null;
}

//  0000000000000000000000
export interface ScheduleInvoiceSummary {
    schedule: {
        schedule_id: number;
        schedule_code: string;
        status: string;
        type: string;
        hours_booked: number;
    } | null;
    patient: InvoicePatient | null;
    total_amount: number;
    total_paid: number;
    total_balance: number;
    status: string;
    invoice_count: number;
    latest_invoice: PatientInvoiceItem | null;
    invoices: PatientInvoiceItem[];
}

export interface InvoiceRow {
    invoice_code: string;
    patient: string;
    category: string;
    total: number | string;
    paid: number | string;
    refunded: number | string;
    amount: number | string;
    status: string;
    refund_status?: RefundSummaryStatus;
    created_at: string;
}

export interface BookingRow {
    reference_id: string;
    invoice_code?: number | null;
    patient: string;
    category: string;
    status: string;
    total: number | string;
    amount: number | string;
    created_at: string;
}

export interface PatientSummaryRow {
    patient: {
        patient_id: string;
        patient_uuid: string;
        full_name: string | null;
    } | null;
    total_amount: number | string;
    total_paid: number | string;
    total_refunded: number | string;
    total_refund_requested: number | string;
    total_refundable: number | string;
    total_balance: number | string;
    status: string;
    invoice_count: number;
    latest_invoice?: {
        invoice_code: string;
        created_at: string;
    } | null;
}

interface BookingServiceLine {
    service_id: number;
    service_name: string;
    price: number;
}

export interface BookingDetail {
    reference_id: string;
    invoice_code?: string;
    category: string;
    status: string;
    valid_until: string | null;
    total: number;
    amount_paid: number;
    balance_due: number;
    created_at: string | null;
    service: {
        type: string | null;
        date: string | null;
        preferred_time: string | null;
        address: string | null;
        time_span: number | null;
        plan: string | null;
        billing_cycle: string | null;
        admission_date: string | null;
        services: BookingServiceLine[];
    } | null;
    patient: {
        full_name: string | null;
        first_name: string | null;
        middle_name: string | null;
        last_name: string | null;
        gender: string | null;
        citizenship: string | null;
        date_of_birth: string | null;
        phone_number: string | null;
        blood_type: string | null;
    } | null;
    reserved: {
        room: {
            room_id: number | null;
            room_no: string | null;
            room_type: string | null;
            floor: string | null;
        };
        bed: {
            bed_id: number | null;
            bed_no: string | null;
            status: string | null;
        };
        billing_cycle: string | null;
        price: number;
        accommodation_type: string | null;
    } | null;
    payment: {
        paid: boolean;
        total_amount: number;
    } | null;
}

export const INVOICE_STATUS: Record<string, string> = {
    unpaid: "bg-amber-50 text-amber-700 border-amber-200",
    partially_paid: "bg-blue-50 text-blue-700 border-blue-200",
    paid: "bg-green-50 text-green-700 border-green-200",
    void: "bg-red-50 text-red-700 border-red-200",
};

export const INVOICE_STATUS_LABEL: Record<string, string> = {
    unpaid: "Unpaid",
    partially_paid: "Partially Paid",
    paid: "Paid",
    void: "Void",
};
