
import { z } from "zod";
import type { Assessment, Guardian, Patient } from "~/types/patient";

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
                (val) => !val || /^9\d{2}[\s-]?\d{3}[\s-]?\d{4}$/.test(val),
                {
                    message: "Enter a valid PH mobile number (e.g. 912 345 6789)",
                },
            ),
        marital_status: z.string().min(1, "Marital status is required"),
        height: z.coerce
            .number()
            .positive("Height must be greater than 0")
            .optional(),

        weight: z.coerce
            .number()
            .positive("Weight must be greater than 0")
            .optional(),
        blood_type: z.string().optional(),
        allergies: z.string().optional(),
        address: z
            .string()
            .min(1, "Address is required"),
    });


export const guardianSchema = z.object({
    first_name: z.string().min(1, "First name is required"),
    middle_name: z.string().optional(),
    last_name: z.string().min(1, "Last name is required"),
    phone_number: z
        .string()
        .min(1, "Phone number is required")
        .regex(
            /^9\d{2}[\s-]?\d{3}[\s-]?\d{4}$/,
            "Enter a valid PH mobile number (e.g. 912 345 6789)",
        ),
    email: z.string().min(1, "Email is required").email("Enter a valid email"),
    relationship: z.string().min(1, "Relationship is required"),
    occupation: z.string().min(1, "Occupation is required"),
    address: z.string().min(1, "Address is required"),
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



// export const patientData = reactive<Patient>({
//     first_name: "",
//     middle_name: "",
//     last_name: "",
//     gender: "",
//     citizenship: "",
//     occupation: "",
//     date_of_birth: "",
//     phone_number: "",
//     marital_status: "",
//     height: "",
//     weight: "",
//     blood_type: "",
//     address: ""
// });


// export const guardianData = reactive<Guardian>({
//     first_name: "",
//     middle_name: "",
//     last_name: "",
//     phone_number: "",
//     email: "",
//     relationship: "",
//     occupation: "",
//     address: "",
// });

export const patientData = reactive<Patient>({
    first_name: "Juan",
    middle_name: "Dela",
    last_name: "Cruz",
    gender: "Male",
    citizenship: "vasd",
    occupation: "Engineer",
    date_of_birth: "1995-06-15",
    phone_number: "912 345 6789",
    marital_status: "Single",
    height: "175",
    weight: "70",
    blood_type: "O+",
    address: "",
    allergies: "",
});


export const guardianData = reactive<Guardian>({
    first_name: "Maria",
    middle_name: "Santos",
    last_name: "Cruz",
    phone_number: "977 117 1913",
    email: "maria.cruz@example.com",
    relationship: "Mother",
    occupation: "Engineer",
    address: "dfg",
});



export const assessmentData = reactive<Assessment[]>([
    {
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
    },
]);