import type { Bed } from "./bed"
import type { Patient } from "./patient";

export interface Room {
    room_id: number;
    room_no: string;
    floor: string;
    branch_id: number;
    status: 'Available' | 'Occupied' | 'Maintenance' | ''
    room_type: "VIP" | "Common" | "";
    capacity: string;
    created_at: string;
    updated_at: string;
    beds: Bed[];
}

export interface RoomForm {
    room_no: string;
    floor: string;
    branch_uuid: string;
    room_type: "VIP" | "Common" | "";
    capacity: string;
    status: 'Available' | 'Occupied' | 'Maintenance' | ''
    room_id?: string
}

export const createRoomForm = (): RoomForm => ({
    room_no: "",
    floor: "",
    branch_uuid: "",
    room_type: "Common",
    capacity: "",
    status: "Available",
    room_id: ''
});