import { z } from "zod";
import type { Location } from "./location";
import type { Bed } from "./bed";
import type { Room } from "./room";
import type { Contract } from "./contract";


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

export const createPatientSchema = (
    category: "homecare" | "facility" | "",
) =>
    z.object({
        first_name: z.string().min(1, "First name is required"),
        middle_name: z.string().optional(),
        last_name: z.string().min(1, "Last name is required"),
        gender: z.string().min(1, "Gender is required"),
        citizenship: z.string().min(1, "Citizenship is required"),
        occupation: z.string().min(1, "Occupation is required"),
        date_of_birth: z.string().min(1, "Date of birth is required"),
        phone_number: z
            .string()
            .optional()
            .refine(
                (val) =>
                    !val ||
                    /^(?:\+?\d)(?:[\d\s-]{6,17})$/.test(val),
                {
                    message: "Enter a valid phone number",
                },
            ),
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

        address:
            category === "facility"
                ? z.string().min(1, "Address is required")
                : z.string().optional(),
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
    phone_number: "09771171913",
    email: "maria.cruz@example.com",
    relationship: "Mother",
    occupation: "Engineer",
    address: "dfg",
});

export const guardianSchema = z.object({
    first_name: z.string().min(1, "First name is required"),
    middle_name: z.string().min(1, "Middle name is required"),
    last_name: z.string().min(1, "Last name is required"),
    phone_number: z
        .string()
        .min(1, "Phone number is required")
        .regex(
            /^(?:\+?\d)(?:[\d\s-]{6,17})$/,
            "Enter a valid phone number",
        ),

    // phone_number: z
    //     .string()
    //     .optional()
    //     .refine(
    //         (val) =>
    //             !val ||
    //             /^(?:\+63\s?|63|0)9\d{2}\s?\d{3}\s?\d{4}$/.test(val),
    //         {
    //             message: "Enter a valid Philippine mobile number",
    //         },
    //     ),
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

const MAX_DIAGNOSIS_LENGTH = 200;
const MAX_NOTES_LENGTH = 1000;
const MAX_FILE_SIZE = 10 * 1024 * 1024;
const ACCEPTED_FILE_TYPES = ["application/pdf", "image/png", "image/jpeg"];


const dateStringSchema = z
    .string()
    .refine((val) => val === "" || !isNaN(Date.parse(val)), {
        message: "Enter a valid date",
    })
    .refine(
        (val) => val === "" || new Date(val) <= new Date(),
        { message: "Diagnosis date cannot be in the future" }
    );

const diagnosisFileSchema = z
    .instanceof(File)
    .optional()
    .refine((file) => !file || file.size <= MAX_FILE_SIZE, {
        message: "File must be 10MB or smaller",
    })
    .refine((file) => !file || ACCEPTED_FILE_TYPES.includes(file.type), {
        message: "File must be a PDF, PNG, or JPG",
    });
export const assessmentSchema = z
    .object({
        diagnosis: z
            .string()
            .trim()
            .max(MAX_DIAGNOSIS_LENGTH, `Diagnosis must be ${MAX_DIAGNOSIS_LENGTH} characters or fewer`),
        diagnosis_date: dateStringSchema,
        diagnosis_notes: z
            .string()
            .trim()
            .max(MAX_NOTES_LENGTH, `Notes must be ${MAX_NOTES_LENGTH} characters or fewer`),
        diagnosis_file: diagnosisFileSchema,
        diagnosis_file_name: z.string().optional(),
    })
    .superRefine((data, ctx) => {
        const anyFieldFilled =
            data.diagnosis.length > 0 ||
            !!data.diagnosis_date ||
            data.diagnosis_notes.trim().length > 0 ||
            !!data.diagnosis_file;

        if (!anyFieldFilled) return;

        if (data.diagnosis.length === 0) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: "Primary Diagnosis is required if other diagnosis fields are filled in",
                path: ["diagnosis"],
            });
        }

        if (!data.diagnosis_date) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: "Date Diagnosed is required if other diagnosis fields are filled in",
                path: ["diagnosis_date"],
            });
        }
    });

export type DiagnosisInput = z.infer<typeof assessmentSchema>;




export interface PatientRetrieve {
    patient_id: number;
    uuid: string;

    full_name: string;

    first_name: string;
    middle_name?: string;
    last_name: string;

    gender: string;
    date_of_birth: string;
    age: string;

    blood_type?: string;
    height?: string;
    weight?: string;

    phone_number?: string;
    citizenship?: string;

    initial_medication: any,
    medication: any;
    vital: any;
    location?: {
        location_id: number;
        full_address: string;
    };
    admission?: {
        patient_admission_id: number;
        status: string;
        admitted_at: string;
        end_date?: string;
        bed?: Bed;
        room?: Room;
        contract?: Contract;
    }[];
}