import { z } from "zod";
import type { PatientAdmission } from "./admission";


export interface Bed {
    bed_id: number;
    room_id: number;
    status: string;
    bed_no: string;
    created_at: string;
    updated_at: string;
    current_admission: PatientAdmission | null;
}


export const bedSchema = z.object({
    bed_id: z.number().optional(),
    bed_no: z
        .string()
        .min(1, "Bed number is required")
        .max(50, "Bed number must not exceed 50 characters"),

    status: z
        .string()
        .min(1, "Status is required"),
});


export const bedForm = (): BedForm => ({
    bed_no: "",
    status: "Available",
});

export type BedForm = z.infer<typeof bedSchema>;