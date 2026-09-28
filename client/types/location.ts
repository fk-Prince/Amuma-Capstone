export interface Location {
    uuid?: string;
    address?: string;
    street: string,
    city: string,
    province: string,
    country: string,
    full_address?: string,
    latitude?: number,
    longitude?: number
}