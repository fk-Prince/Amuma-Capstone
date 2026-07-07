import type { Location } from "./location";
import { z } from "zod";

export interface SigninRequest {
    email: string;
    password: string;
}

export interface SignupRequest {
    email: String,
    password: String,
}

export interface User {
    uuid: string,
    email: string,
    first_name: string,
    last_name: string
    avatar: string
    location?: Location,
    phone_number?: string,
    is_active: boolean,
    is_verified: boolean
    address: string,
    roles?: Roles[]
}


interface Roles {
    role: string,
    branch: string,
    is_active: boolean
}

export type RoleType =
    | 'owner'
    | 'branch_owner'
    | 'administrator'
    | 'accounting'
    | 'admission'
    | 'nurse'
    | 'caregiver';


export interface Guardian {
    first_name: string,
    middle_name: string,
    last_name: string,
    phone_number?: string,
    email: string,
    relationship: string,
    occupation?: string,
}

// export const guardianData = reactive<Guardian>({
//     first_name: "",
//     middle_name: "",
//     last_name: "",
//     phone_number: "",
//     email: "",
//     relationship: "",
//     occupation: "",
// });

export const guardianData = reactive<Guardian>({
    first_name: "Maria",
    middle_name: "Santos",
    last_name: "Cruz",
    phone_number: "+63 917 888 9999",
    email: "maria.cruz@example.com",
    relationship: "Mother",
    occupation: "Teacher",
});

export const guardianSchema = z.object({
    first_name: z.string().min(1),
    middle_name: z.string().optional(),
    last_name: z.string().min(1),
    phone_number: z.string().min(1),
    email: z.string().email().optional(),
    relationship: z.string().min(1),
    occupation: z.string().optional(),
});