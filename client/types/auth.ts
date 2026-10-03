import type { Location } from "./location";

export interface SigninRequest {
    email?: string;
    employee_code?: string;
    password: string;
}

export interface ForgotPasswordRequest {
    email: string;
}

export interface ResetLinkRequest {
    token: string;
    user: string;
}

export interface ResetPasswordRequest extends ResetLinkRequest {
    password: string;
    password_confirmation: string;
}

export interface SignupRequest {
    first_name: String,
    last_name: String,
    email: String,
    password: String,
}

export interface Onboarding {
    portal?: Record<string, string>,
    dashboard?: Record<string, string>,
}

export interface User {
    user_id?: string,
    uuid: string,
    email: string,
    first_name: string,
    middle_name?: string,
    last_name: string
    avatar: string
    location?: Location,
    phone_number?: string,
    birth_date?: string,
    occupation?: string,
    address?: string,
    hasBooking?: boolean,
    hasPatient?: boolean,
    onboarding?: Onboarding | null,
    // is_active: boolean,
    // is_verified: boolean
    isEmployee?: false,
    isClient?: false,
    isSystemOwner?: false,
}

