import { type Location } from "./location";

export interface Agency {
    id?: number;
    name: string;
    description: string;
    location: Location
}
