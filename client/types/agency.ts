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
    id_selfie?: string | File | null;
    face_scan?: import("~/utils/faceMatch").FaceScanResult | null;
    document?: string | File;
    status: "pending" | "verified" | "rejected";
    registered_by?: { name: string | null; email: string } | null;
}
