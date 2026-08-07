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
    has_booking?: string,
    // is_active: boolean,
    // is_verified: boolean
    isEmployee?: false,
    isClient?: false,
    isSystemOwner?: false,
}

