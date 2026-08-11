export interface OnlineScheduleAssignment {
    qr_in: string | null;
    qr_out: string | null;
    in_timestamp: string | null;
    out_timestamp: string | null;
    notes: string | null;
}


export interface ScheduleAssignee {
    employee_id: number;
    full_name: string;
    avatar: string | null;
    role: string | null;
    online: OnlineScheduleAssignment[];
    is_active: boolean
    employee_role: string | null;
}


export interface ScheduleServiceItem {
    schedule_services_id: number;
    service_id: number;
    service_name: string | null;
    hours_booked: number | null;
    duration_minutes: number;
    end_service: string;
    type: string | null;
    assignees?: ScheduleAssignee[];
}


export interface PatientAdmissionBedRoom {
    room_id: number;
    room_no: string;
    floor: string | null;
    room_type: string | null;
}


export interface PatientAdmissionBed {
    bed_id: number;
    bed_no: string;
    room: PatientAdmissionBedRoom | null;
}


export interface PatientAdmission {
    status: string;
    admitted_at: string | null;
    end_date: string | null;
    bed: PatientAdmissionBed | null;
}


export interface SchedulePatient {
    patient_uuid: string;
    patient_id: number;
    full_name: string;
    address: string | null;

    is_admitted: boolean;
    admission: PatientAdmission | null;
}


export interface ScheduleItem {
    schedule_id: number;
    schedule_code: string;
    status: string;
    category: string | null;

    scheduled_date: string | null;
    scheduled_at?: string | null;

    start_time?: string | null;
    end_time?: string | null;

    total_hours: number;
    total_duration_minutes: number;

    patient?: SchedulePatient | null;

    services?: ScheduleServiceItem[];

    type?: "adl" | "medical";
}


export interface Block {
    scheduleId: number;
    scheduleServiceId: number;
    patientName: string;
    serviceName: string;
    category: string | null;
    status: string;
    startLabel: string;
    endLabel: string;
    offsetPercent: number;
    widthPercent: number;
    lane: number;
    isUnassigned: boolean;
}

export interface ConflictItem {
    employee_id: number;
    employee_name: string;
    schedule_services_id: number | null;
    service_name: string | null;
    conflict_schedule_codes: string[];
}

export type ConflictSource = "assignment" | "schedule";

export const conflictConfirm = ref<{
    open: boolean;
    source: ConflictSource | null;
    conflicts: ConflictItem[];
    pendingPayload: any;
}>({
    open: false,
    source: null,
    conflicts: [],
    pendingPayload: null,
});

export interface AuditRow {
    schedule_id: number;
    schedule_code: string;
    scheduled_at?: string | null;
    total_hours: number;
    status: string;

    is_active: boolean;
    employee_id: number | null;
    full_name: string | null;
    avatar: string | null;
    role: string | null;
    address: string | null;
    patient_uuid: string;
    patient_full_name: string;
    total_worked_minutes: number;

    schedule_services_id: number;

    assignees: {
        employee_role: string | null;
        employee_id: number;
        full_name: string | null;
        avatar: string | null;
        role: string | null;
    }[];

    assigned?: {
        employee_id: number;
        is_active: boolean;
    }[];

    online_logs: {
        employee_id?: number | null;
        employee_name?: string | null;
        employee_avatar?: string | null;
        in_timestamp: string | null;
        out_timestamp: string | null;
        notes: string | null;
    }[];
}