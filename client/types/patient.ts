import { z } from "zod";
import type { Location } from "./location";


export interface Patient {
    patient_id?: number;
    first_name: string;
    middle_name: string;
    last_name: string;
    gender: string;
    citizenship: string;
    occupation: string;
    date_of_birth: string;
    phone_number?: string;
    marital_status: string;
    height?: string;
    weight?: string;
    blood_type?: string;
    location?: Location
    address?: string
}

export const patientData = reactive<Patient>({
    first_name: "Juan",
    middle_name: "Dela",
    last_name: "Cruz",
    gender: "Male",
    citizenship: "Filipino",
    occupation: "Engineer",
    date_of_birth: "1995-06-15",
    phone_number: "+63 912 345 6789",
    marital_status: "Single",
    height: "175",
    weight: "70",
    blood_type: "O+",
    address: ""
});

const decimalString = z
    .string()
    .optional()
    .refine((val) => !val || /^\d+(\.\d+)?$/.test(val), {
        message: "Must be a valid number",
    });

export const patientSchema = z.object({
    first_name: z.string().min(1, "First name is required"),
    middle_name: z.string().optional(),
    last_name: z.string().min(1, "Last name is required"),
    gender: z.string().min(1, "Gender is required"),
    citizenship: z.string().min(1, "Citizenship is required"),
    occupation: z.string().min(1, "Occupation is required"),
    date_of_birth: z.string().min(1, "Date of birth is required"),
    phone_number: z.string().optional(),
    marital_status: z.string().min(1, "Marital status is required"),
    height: z.string()
        .optional()
        .refine((val) => !val || /^\d+(\.\d+)?$/.test(val), {
            message: "Must be a height number",
        }),
    weight: z.string()
        .optional()
        .refine((val) => !val || /^\d+(\.\d+)?$/.test(val), {
            message: "Must be a weight number",
        }),
    blood_type: z.string().optional(),
    // address: z.string().min(1, "Address is required"),
    address: z.string().optional(),
});


export interface Guardian {
    first_name: string,
    middle_name: string,
    last_name: string,
    phone_number?: string,
    email: string,
    relationship: string,
    occupation?: string,
    address?: string,
}

export const guardianData = reactive<Guardian>({
    first_name: "Maria",
    middle_name: "Santos",
    last_name: "Cruz",
    phone_number: "+63 917 888 9999",
    email: "maria.cruz@example.com",
    relationship: "Mother",
    occupation: "Teacher",
    address: "",
});

export const guardianSchema = z.object({
    first_name: z.string().min(1, "First name is required"),
    middle_name: z.string().min(1, "Middle name is required"),
    last_name: z.string().min(1, "Last name is required"),
    phone_number: z.string().min(1, "Phone number is required"),
    email: z.string().min(1, "Email is required").email("Enter a valid email"),
    relationship: z.string().min(1, "Relationship is required"),
    occupation: z.string().min(1, "Occupation is required"),
    address: z.string().min(1, "Address is required"),
});


export interface Assessment {
    diagnosis?: string;
    diagnosis_date?: string;
    diagnosis_notes?: string;
    diagnosis_file?: File;
    diagnosis_file_name?: string;


    //VITAL SIGNS
    blood_pressure?: string;
    pulse_rate?: string;
    temperature?: string;
    oxygen_saturation?: string;
    respiratory_rate?: string;


    //COMMUNICATION
    communication?: "Coherent & Logical" | "Impaired" | ""
    speech?: "clear" | "slurred" | "alphasic" | ""

    mental_state?: "alert" | "confused" | "unconscious";
    memory_issues?: "none" | "mild" | "dementia" | "alzheimers";
    mood?: "calm" | "anxious" | "aggressive";
}

export const assessmentData = reactive<Assessment>({
    diagnosis: "",
    diagnosis_date: "",
    diagnosis_notes: "",
    diagnosis_file: undefined,
    diagnosis_file_name: "",

    blood_pressure: "",
    pulse_rate: "",
    temperature: "",
    oxygen_saturation: "",
    respiratory_rate: "",

    communication: "Coherent & Logical",
    speech: "clear",

    mental_state: "alert",
    memory_issues: "none",
    mood: "calm",
});

