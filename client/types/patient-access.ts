import type { Branch } from "./branch";
import type { ScheduleItem } from "./schedule";

export interface PatientAccess {
    relationship_type: string;
    have_access: boolean;
}

export interface PatientInfo {
    patient_id: number;
    first_name: string;
    middle_name: string;
    last_name: string;
    gender: string;
    date_of_birth: string | null;
    phone_number: string;
    blood_type: string | null;
    full_name?: string;
    full_address?: string;
    medication?: any[] | null;
}

export interface Organization {
    branch_id: number;
    name: string | null;
    location_id: number;
    full_address: string | null;
}

export interface AdmissionInfo {
    patient_admission_id: number;
    status: string;
    admitted_at: string | null;
    end_date: string | null;
}

export interface BedInfo {
    bed_no: string | null;
    status: string | null;
}

export interface RoomInfo {
    room_no: string;
    room_type: string;
    floor: string;
}

export interface LocationContextFacility {
    type: "facility" | "admission_fallback";
    status: string;
    admission: AdmissionInfo;
    bed: BedInfo;
    room: RoomInfo | null;
}

export interface LocationContextHomecare {
    type: "homecare";
    status: string | null;
    adl: ScheduleItem | null;
    medical: ScheduleItem | null;
}

export interface LocationContextNone {
    type: "none";
    status: string;
    note?: string;
}

export type LocationContext = LocationContextFacility | LocationContextHomecare | LocationContextNone;

export interface ContractInfo {
    branch_contract_id: number;
    category: string;
    accommodation_type: string;
    billing_cycle: string;
    price: number;
}

export interface FacilitySource {
    type: "Facility Admission";
    patient_admission_id: number;
    start_date: string | null;
    end_date: string | null;
    admission_status: string | null;
    admitted_at: string | null;
    contract: ContractInfo | null;
}

export interface ServiceInfo {
    service_id: number;
    service_name: string;
    type: string;
}

export interface ScheduleInfo {
    schedule_id: number;
    schedule_code: string;
    scheduled_at: string | null;
}

export interface InvoiceServiceItem {
    type: string;
    schedule_services_id: number;
    price: number;
    hours_booked: number | null;
    service: ServiceInfo | null;
    schedule: ScheduleInfo | null;
}

export interface ServiceSource {
    services: InvoiceServiceItem[];
}

export type InvoiceSource = FacilitySource | ServiceSource | null;

export interface RefundInfo {
    refund_id: number;
    amount: number;
    refund_method: string;
    reference_id: string | null;
    status: string;
    reason: string;
    created_at: string | null;
}

export interface PaymentInfo {
    payment_id: number;
    reference_id: string;
    amount: number;
    payment_method: string;
    created_at: string | null;
    refunds: RefundInfo[];
}

export interface InvoiceAdjustment {
    invoice_adjustment_id: number;
    type: string;
    amount: number;
    reason: string;
    created_at: string | null;
}

export interface InvoiceItem {
    invoice_id: number;
    invoice_code: string;
    status: string;
    total: number;
    adjusted_total: number;
    amount_paid: number;
    balance_due: number;
    refund_status: string;
    is_collected: boolean;
    created_at: string | null;
    source: InvoiceSource;
    payments: PaymentInfo[];
    adjustments: InvoiceAdjustment[];
}

export type { ScheduleItem, ScheduleServiceItem, ScheduleAssignee, OnlineScheduleAssignment } from "./schedule";

export interface ScheduleContext {
    adl: ScheduleItem | null;
    medical: ScheduleItem | null;
}

export interface ClientInfo {
    client_id: number;
    user_id: number;
    location_id: number;
    first_name: string;
    last_name: string;
    phone_number: string;
    avatar: string | null;
    is_verified: boolean;
    created_at: string;
    updated_at: string;
}

export interface PatientPortalData {
    access: PatientAccess;
    patient: PatientInfo;
    patient_balance: number;
    patient_refundable: number;
    patient_adjusted: number;
    branch: Branch;
    location_context: LocationContext;
    latest_invoice: InvoiceItem | null;
    invoices: InvoiceItem[];
    client: ClientInfo | null;
    schedule: ScheduleContext;
}

export interface PatientPortalResponse {
    total: number;
    data: PatientPortalData[];
}

export interface PatientDetailResponse extends PatientPortalData { }

export interface PatientBalanceResponse {
    patient_id: number;
    patient_balance: number;
    patient_refundable: number;
    patient_adjusted: number;
    latest_invoice: InvoiceItem | null;
    invoices: InvoiceItem[];
}