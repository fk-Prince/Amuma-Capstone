export interface EmployeeNote {
    uuid: string;
    note: string;
    is_private: boolean;
    employee_uuid: string | null;
    employee_name: string | null;
    patient_uuid: string | null;
    patient_name: string | null;
    schedule_code: string | null;
    scheduled_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface EmployeeNoteMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

export interface NotePatientOption {
    patient_uuid: string;
    patient_name: string;
}
