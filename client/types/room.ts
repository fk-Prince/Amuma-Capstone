import type { Bed } from "./bed"
import { z } from "zod";

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
    status: 'Available' | 'Occupied' | 'Maintenance'
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

export const floorSchema = z.string().refine((value) => {
    const match = value.match(/^(\d+)(st|nd|rd|th)$/i);
    if (!match) return false;

    const num = Number(match[1]);
    const suffix = match[2]?.toLowerCase();

    if (num % 100 >= 11 && num % 100 <= 13) {
        return suffix === "th";
    }

    switch (num % 10) {
        case 1:
            return suffix === "st";
        case 2:
            return suffix === "nd";
        case 3:
            return suffix === "rd";
        default:
            return suffix === "th";
    }
}, {
    message: "Floor must be a valid ordinal (e.g. 1st, 2nd, 3rd, 4th, 21st).",
});



export const roomSchema = z.object({
    room_no: z
        .string()
        .trim()
        .min(1, "Room number is required")
        .max(20, "Room number must not exceed 20 characters"),

    floor: floorSchema,


    room_type: z.enum(["VIP", "Common"], {
        error: "Room type is required",
    }),

    capacity: z
        .string()
        .trim()
        .min(1, "Capacity is required")
        .refine(
            (value) => {
                const num = Number(value);
                return Number.isInteger(num) && num > 0;
            },
            {
                message: "Capacity must be a positive whole number",
            }
        ),

    status: z.enum(["Available", "Occupied", "Maintenance"], {
        error: "Status is required",
    }),

    room_id: z.string().optional(),
});


export type RoomSchema = z.infer<typeof roomSchema>;



export type Overview = {
    total_rooms: {
        value: number;
        secondary: string;
        trend: string;
    };
    available: {
        value: number;
        secondary: string;
        trend: string;
    };
    occupied: {
        value: number;
        secondary: string;
        trend: string;
    };
    maintenance: {
        value: number;
        secondary: string;
        trend: string;
    };
};