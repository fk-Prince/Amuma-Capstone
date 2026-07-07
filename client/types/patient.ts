import { z } from "zod";
import type { Location } from "./location";
export interface Patient {
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
    location: {
        address: "123 Main Street, Barangay 5",
        street: "Main Street",
        city: "Davao City",
        province: "Davao del Sur",
        country: "Philippines",
        latitude: 7.1907,
        longitude: 125.4553,
    },
});
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
//     location: undefined,
// });

export const patientSchema = z.object({
    first_name: z.string().min(1),
    middle_name: z.string().optional(),
    last_name: z.string().min(1),
    gender: z.string().min(1),
    citizenship: z.string().min(1),
    occupation: z.string().min(1),
    date_of_birth: z.string().min(1),
    phone_number: z.string().optional(),
    marital_status: z.string().min(1),
    height: z.string().optional(),
    weight: z.string().optional(),
    blood_type: z.string().optional(),
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
