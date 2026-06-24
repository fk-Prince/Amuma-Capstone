import { z } from "zod";

export interface Service {
    branch_uuid: string;
    service_uuid?: string;
    category_id?: number | null;
    category_name?: string | null;
    price_id?: string;
    price: number;
    service_name: string;
    maximum_duration: string;
    is_available?: boolean;
    type: 'online' | 'facility' | 'both';
}

export const createServiceForm = (): Service => ({
    branch_uuid: "",
    category_id: null,
    category_name: "",
    price: 0.00,
    service_name: "",
    maximum_duration: "",
    is_available: true,
    type: "online",
});


export const serviceSchema = z.object({
    service_name: z.string().min(1, "Service name is required"),
    category: z.string().min(1, "Category name is required"),
    type: z.string().min(1, "Type is required"),
    price: z.coerce
        .number('Invalid Price')
        .min(1, "Price cannot be negative or empty"),
    duration: z
        .string()
        .regex(
            /^([0-1]\d|2[0-3]):([0-5]\d):([0-5]\d)$/,
            "Maximum duration must be HH:mm:ss"
        )
});


