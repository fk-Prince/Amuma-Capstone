export interface AttentionDischarge {
    patient_uuid: string | null;
    patient_name: string | null;
    room_no: string | null;
    bed_no: string | null;
    discharge_at: string;
}

export interface AttentionService {
    service_uuid: string;
    service_name: string;
    category_name: string | null;
    performed_by: "both" | "nurse" | "caregiver" | null;
    type_formatted: string | null;
}

export interface AttentionSchedule {
    schedule_code: string;
    patient_name: string | null;
    scheduled_at: string;
    type: "adl" | "medical";
    services: string[];
}

export interface AttentionData {
    discharges: AttentionDischarge[];
    services: AttentionService[];
    schedules: AttentionSchedule[];
}

export type AttentionGroup = keyof AttentionData;
