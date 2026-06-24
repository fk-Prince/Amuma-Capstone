import type { Location } from "./location";

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
    roles?: Roles
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