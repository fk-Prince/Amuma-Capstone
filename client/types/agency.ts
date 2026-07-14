import { type Location } from "./location";
import { z } from 'zod';
import { locationSchema } from "./branch";

export interface Agency {
    id?: number;
    agency_name: string;
    agency_description: string;
    location: Location;
}



export const agencySchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, "Branch name is required")
        .max(255),

    description: z
        .string()
        .trim()
        .min(1, "Description is required")
        .max(500),


    location: locationSchema,
});
export const agencySchema2 = z.object({
    agency_name: z
        .string()
        .trim()
        .min(1, "Agency name is required")
        .max(255),

    agency_description: z
        .string()
        .trim()
        .min(1, "Description is required")
        .max(500),


    location: locationSchema,
});