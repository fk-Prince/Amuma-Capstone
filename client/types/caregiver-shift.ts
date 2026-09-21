export interface CaregiverShift {
    caregiver_facility_shift_id: number;
    caregiver_id: number;
    admission_id: number;
    caregiver_name: string | null;
    avatar: string | null;
    phone_number: string | null;
    note: string | null;
    start_time: string;
    end_time: string;
    is_active: boolean;
}

export interface FacilityCaregiver {
    employee_id: number;
    full_name: string;
    avatar: string | null;
    phone_number: string | null;
    active_residents: number;
    over_limit: boolean;
    duty_windows: { start_time: string; end_time: string }[];
    duty_hours: number;
}

export interface CaregiverShiftList {
    shifts: CaregiverShift[];
    caregivers: FacilityCaregiver[];
    limit: number;
    duty_limit: number;
}

export interface CaregiverShiftOutcome {
    shift: CaregiverShift;
    caregiver: Pick<
        FacilityCaregiver,
        | "employee_id"
        | "active_residents"
        | "over_limit"
        | "duty_windows"
        | "duty_hours"
    >;
    active_count: number;
}
