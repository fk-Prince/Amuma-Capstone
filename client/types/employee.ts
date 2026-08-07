import { z } from "zod";
import type { Permissions } from "./permission";
import { useBranchPlan } from "~/composables/useBranchPlan";

const { hasPlan } = useBranchPlan();

export interface Employee {
    employee_id: string
    uuid: string,
    email: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    avatar: string;
    location: Location;
    birth_date: string;
    phone_number: string;
    role_name: string;
    assignment_type: string;
    formatted_assignment_type: string;
    status: string;
    permissions: Permissions[]
    hired_date?: string;
    assigned: EmployeeService[]
    full_name?: string;



    is_busy?: boolean;
    is_assigned?: boolean;
    conflict_count?: number
    conflict_schedules?: {
        schedule_code: string;
        scheduled_at: string;
        status: string;
        category: string;
        duration_minutes: number | null;
    }[]
}

export interface EmployeeService {
    service_id: string;
    is_assigned: boolean
}



export interface EmployeePayload {
    email: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    avatar: File | string | null;
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
}

export const createEmployee = (): EmployeePayload => ({
    email: "",
    first_name: "",
    middle_name: "",
    last_name: "",
    avatar: null,
    location: {
        street: "",
        city: "",
        province: "",
        country: "",
    },
    birth_date: "",
    phone_number: "",
    role_name: "administrator",
    assignment_type: "both",
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
        .refine((val) => !isNaN(Date.parse(val as string)), "Enter a valid birth date"),
    phone_number: z
        .string()
        .min(1, "Phone number is required")
        .regex(/^[0-9+\-\s()]{7,20}$/, "Enter a valid phone number"),
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
    { label: "All", value: "both", },
    { label: "Homecare", value: "homecare" },
    { label: "Facility", value: "facility" },
];

export const employeeAssignmentTypes = computed(() => {
    return unFilteredEmployeeAssignmentTypes.filter((type) => {
        switch (type.value) {
            case "both":
                return true;
            case "homecare":
                return hasPlan("A");
            case "facility":
                return hasPlan("B");
            default:
                return false;
        }
    });
});

export const employeePositions = [
    { label: "Administrator", value: "administrator" },
    { label: "Admission", value: "admission" },
    { label: "Accounting", value: "accounting" },
    { label: "Nurse", value: "nurse" },
    { label: "Caregiver", value: "caregiver" },
];

export const formatAssignmentType = (type?: string | null) => {
    switch (type?.toLowerCase()) {
        case "both":
            return "Homecare + Inhouse Facility";
        case "homecare":
            return "Homecare";
        case "facility":
            return "Inhouse Facility";
        default:
            return null;
    }
}


