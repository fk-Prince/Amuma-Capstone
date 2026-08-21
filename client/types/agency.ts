import { type Location } from "./location";

export interface Agency {
    agency_id?: number;
    name: string;
    description: string;
    email: string,
    image: File | string | null;
    location: Location;
    id_front?: string | File;
    id_back?: string | File;
    document?: string | File;
    is_verified: boolean
}
