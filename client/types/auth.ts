export interface SigninRequest {
    email: string;
    password: string;
}

export interface SignupRequest {
    email: String,
    password: String,
}

export interface User {
    email: String,
    first_name: string,
    last_name: string
}