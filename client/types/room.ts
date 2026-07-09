import type { Bed } from "./bed"
import type { Patient } from "./patient";

export interface Room {
    room_id: number;
    room_no: string;
    floor: string;
    branch_id: number;
    room_type: "VIP" | "Common" | "";
    capacity: string;
    created_at: string;
    updated_at: string;
    beds: Bed[];
}