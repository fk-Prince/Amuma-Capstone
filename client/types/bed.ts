import type { Patient } from "./patient";

export interface Bed {
    bed_id: number;
    room_id: number;
    status: string;
    bed_no: string;
    created_at: string;
    updated_at: string;
    patient: Patient | null;
}