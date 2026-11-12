import { z } from "zod";
import type { Permissions } from "./permission";
import { phoneNumber } from "~/schema/phone-schema";

export interface EmployeeDocument {
    label: string;
    file?: File | null;
    url?: string | null;
}

export interface EmployeeAchievement {
    label: string;
    file?: File | null;
    url?: string | null;
    date?: string | null;
}

export interface Employee {
    employee_id: string;
    uuid: string;
    employee_code?: string | null;
    email: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    avatar: string;
    documents?: EmployeeDocument[];
    achievements?: EmployeeAchievement[];
    achievement_titles?: string[];
    location: Location;
    birth_date: string;
    phone_number: string;
    role_name: string;
    assignment_type: string;
    formatted_assignment_type: string;
    status: string;
    leave?: EmployeeCurrentLeave | null;
    permissions: Permissions[];
    hired_date?: string;
    assigned: EmployeeService[];
    full_name?: string;

    is_busy?: boolean;
    is_assigned?: boolean;
    conflict_count?: number;
    conflict_schedules?: {
        schedule_code: string;
        scheduled_at: string;
        status: string;
        category: string;
        duration_minutes: number | null;
        remaining_minutes: number | null;
        estimated_end: string | null;
        checked_in_at?: string | null;
    }[];
    last_schedule?: LastSchedule | null;
}

export interface LastSchedule {
    schedule_code: string;
    scheduled_at: string;
    status: string;
    category: "medical" | "adl";
    service_name: string | null;
    duration_minutes: number | null;
    ends_at: string | null;
    last_time_out?: string | null;
}

export interface EmployeeService {
    service_id: string;
    is_assigned: boolean;
}

export interface EmployeePayload {
    email: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    avatar: File | string | null;
    documents: EmployeeDocument[];
    achievements: EmployeeAchievement[];
    location: {
        street: string;
        city: string;
        province: string;
        country: string;
    };
    birth_date: string;
    phone_number: string;
    role_name: string;
    assignment_type: string;
    status: EmployeeStatus;
    auto_assign_services: boolean;
    password?: string;
    password_confirmation?: string;
}

export type EmployeeStatus = "active" | "inactive" | "on_leave";

export type LeaveType = "sick" | "vacation" | "emergency" | "personal" | "other";

export const leaveTypes: { label: string; value: LeaveType }[] = [
    { label: "Sick", value: "sick" },
    { label: "Vacation", value: "vacation" },
    { label: "Emergency", value: "emergency" },
    { label: "Personal", value: "personal" },
    { label: "Other", value: "other" },
];

export const formatLeaveType = (type?: string | null) =>
    leaveTypes.find((item) => item.value === type)?.label ?? "Leave";

export interface EmployeeCurrentLeave {
    type: LeaveType;
    starts_on: string;
    ends_on: string | null;
}

export interface EmployeeLeave {
    uuid: string;
    type: LeaveType;
    reason: string;
    starts_on: string;
    ends_on: string | null;
    returned_at: string | null;
    recorded_by: string | null;
    returned_by: string | null;
}

export interface LeaveDetails {
    type: LeaveType;
    reason: string;
    ends_on: string | null;
    unassign_work: boolean;
}

export interface WorkloadItem {
    kind: "schedule" | "shift";
    title: string;
    patient: string | null;
    scheduled_at?: string | null;
    room?: string | null;
    in_progress: boolean;
    on_duty?: boolean;
}

export interface EmployeeWorkload {
    in_progress: WorkloadItem[];
    upcoming: WorkloadItem[];
}

// On leave is a flavour of active — the employee still has access, they are
// just away, so the form's switch reads it as on.
export const isActiveStatus = (status?: string | null) => status !== "inactive";

export const formatEmployeeStatus = (status?: string | null) => {
    switch (status) {
        case "on_leave":
            return "On leave";
        case "inactive":
            return "Inactive";
        default:
            return "Active";
    }
};

export const createEmployee = (): EmployeePayload => ({
    email: "",
    first_name: "",
    middle_name: "",
    last_name: "",
    avatar: null,
    documents: [],
    achievements: [],
    location: {
        street: "",
        city: "",
        province: "",
        country: "",
    },
    birth_date: "",
    phone_number: "",
    role_name: "branch_manager",
    assignment_type: "both",
    status: "active",
    auto_assign_services: true,
});

export const employeeSchema = z.object({
    first_name: z.string().min(1, "First name is required"),
    middle_name: z.string().optional(),
    last_name: z.string().min(1, "Last name is required"),
    // birth_date: z
    //     .string()
    //     .min(1, "Birth date is required")
    //     .refine((val) => !isNaN(Date.parse(val)), "Enter a valid birth date"),
    birth_date: z
        .string()
        .nullable()
        .refine((val) => !!val, "Birth date is required")
        .refine(
            (val) => !isNaN(Date.parse(val as string)),
            "Enter a valid birth date",
        ),
    phone_number: phoneNumber(),
    email: z
        .string()
        .min(1, "Email is required")
        .email("Enter a valid email address"),
    location: z.object({
        street: z.string().min(1, "Street address is required"),
        city: z.string().min(1, "City is required"),
        province: z.string().min(1, "Province is required"),
        country: z.string().min(1, "Country is required"),
    }),
});

export type EmployeeFormData = z.infer<typeof employeeSchema>;

export const unFilteredEmployeeAssignmentTypes = [
    { label: "Homecare and Inhouse Facility", value: "both" },
    { label: "Homecare", value: "online" },
    { label: "Inhouse Facility", value: "facility" },
];

export const employeeAssignmentTypes = computed(
    () => unFilteredEmployeeAssignmentTypes,
);

export const employeePositions = [
    { label: "Branch Manager", value: "branch_manager" },
    { label: "Admission", value: "admission" },
    { label: "Cashier", value: "cashier" },
    { label: "Nurse", value: "nurse" },
    { label: "Caregiver", value: "caregiver" },
];

export const formatAssignmentType = (type?: string | null) => {
    switch (type?.toLowerCase()) {
        case "both":
            return "Homecare & Inhouse Facility";
        case "homecare":
        case "online":
            return "Homecare";
        case "facility":
            return "Inhouse Facility";
        default:
            return null;
    }
};
