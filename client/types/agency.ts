import { type Location } from "./location";

export interface Agency {
    id?: number;
    agency_name: string;
    agency_description: string;
    location: Location;
}



