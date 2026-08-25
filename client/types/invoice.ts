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

// export interface InvoiceFacilityLine {
//     invoice_facility_id: number;
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
//     is_collected: boolean;
//     status: string;
//     refund_status: RefundSummaryStatus;
//     created_at: string | null;
//     patient: InvoicePatient | null;
//     branch?: InvoiceBranch;
//     services?: InvoiceServiceLine[];
//     facilities?: InvoiceFacilityLine[];
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
//     is_collected: boolean;
//     status: string;
//     refund_status: RefundSummaryStatus;
//     created_at: string | null;
//     branch?: InvoiceBranch;
//     services?: InvoiceServiceLine[];
//     facilities?: InvoiceFacilityLine[];
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

export interface InvoiceFacilityLine {
    invoice_facility_id: number;
    branch_contract_id: number;
    price: number;
    patient_admission_id: number;
    patient_name: string;
}

export type RefundStatus =
    | 'pending'
    | 'processing'
    | 'completed'
    | 'failed'
    | 'cancelled';

export type RefundSummaryStatus =
    | 'none'
    | 'partially refunded'
    | 'full refunded';

export interface InvoiceRefund {
    refund_id: number;
    reference_id: string | null;
    amount: number;
    refund_method: string | null;
    status: RefundStatus;
    reason: string | null;
    masked_card_number: string | null;
    created_at: string | null;
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
    reference_id: string;
    amount: number;
    payment_method: string;
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
    room: AdmissionRoom | null;
    bed: AdmissionBed | null;
    invoices: PatientInvoiceItem[];
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
    retention_amount: number;
    termination_fee_percent: number;
    termination_fee_amount: number;
    refund_amount: number;
    policy: string;
    policy_title: string;
    policy_description: string;
    is_within_termination_fee_window: boolean;
    is_within_yearly_half_refund_window: boolean;
    is_under_required_payment: boolean;
    payment_shortfall: number;
}

export interface InvoiceDetail {
    invoice_id: number;
    invoice_code: string;
    schedule_code?: string | null;
    total: number;
    amount_paid: number;
    refunded_amount: number;
    refund_processing_amount: number;
    balance_due: number;
    is_collected: boolean;
    status: string;
    refund_status: RefundSummaryStatus;
    created_at: string | null;
    patient: InvoicePatient | null;
    branch?: InvoiceBranch;
    services?: InvoiceServiceLine[];
    facilities?: InvoiceFacilityLine[];
    payments?: InvoicePayment[];
    adjustments?: InvoiceAdjustmentDetail[];
}

export interface PatientInvoiceItem {
    invoice_id: number;
    invoice_code: string;
    schedule_code?: string | null;
    total: number;
    amount_paid: number;
    refunded_amount: number;
    refund_processing_amount: number;
    balance_due: number;
    is_collected: boolean;
    status: string;
    refund_status: RefundSummaryStatus;
    created_at: string | null;
    branch?: InvoiceBranch;
    services?: InvoiceServiceLine[];
    facilities?: InvoiceFacilityLine[];
    payments?: InvoicePayment[];
}

export interface PatientInvoiceSummary {
    patient: InvoicePatient | null;
    total_amount: number;
    total_paid: number;
    total_refunded: number;
    total_refund_processing: number;
    total_balance: number;
    refund_status: RefundSummaryStatus;
    status: string;
    invoice_count: number;
    latest_invoice: PatientInvoiceItem | null;
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
    total_refund_processing: number | string;
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
    pending: "bg-amber-50 text-amber-700 border-amber-200",
    partial: "bg-blue-50 text-blue-700 border-blue-200",
    paid: "bg-green-50 text-green-700 border-green-200",
    void: "bg-red-50 text-red-700 border-red-200",
    // refunded: "bg-purple-50 text-purple-700 border-purple-200",
};
