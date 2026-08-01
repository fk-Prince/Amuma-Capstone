





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
    online: OnlineScheduleAssignment[]
}

export interface ScheduleServiceItem {
    schedule_services_id: number;
    service_id: number;
    service_name: string | null;
    hours_booked: number | null;
    duration_minutes: number;
    end_service: string,
    status: string;
    type: string | null;
    assignees?: ScheduleAssignee[];
}

export interface ScheduleItem {
    schedule_id: number;
    schedule_code: string;
    status: string;
    category: string | null;
    scheduled_date: string | null;
    scheduled_at?: string | null;
    start_time?: string | null;
    total_hours: number;
    total_duration_minutes: number;
    end_time?: string | null;
    patient?: {
        patient_id: number;
        full_name: string;
        address: string,
    } | null;

    services?: ScheduleServiceItem[];
    type?: 'adl' | 'medical'
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

