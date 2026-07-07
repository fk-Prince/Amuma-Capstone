// import { reactive } from "vue";
// import { z } from "zod";

// export interface FacilityBooking {
//     type: "Complete" | "Pre-Admission" | "";
//     plan: "VIP" | "Common" | "";
//     billing_interval: "Monthly" | "Yearly" | "";
//     admission_date: string;
// }

// export interface BookedService {
//     service_id: number;
//     service_name: string;
//     price: number;
// }

// export interface HomecareBooking {
//     services?: BookedService[];
//     type: 'Medical' | 'ADL';
//     date: string;
//     prefered_time: string;
//     time_span?: string;
//     address: string;
// }

// export const facilityData = reactive<FacilityBooking>({
//     plan: "Common",
//     billing_interval: "Monthly",
//     admission_date: "",
//     type: ""
// });

// export const homecareData = reactive<HomecareBooking>({
//     type: "Medical",
//     date: "",
//     prefered_time: "",
//     address: "",
//     time_span: "",
//     services: [],
// });

// const baseSchema = z.object({
//     date: z.string().min(1, "Date is required"),
//     prefered_time: z.string().min(1, "Preferred time is required"),
//     address: z.string().min(1, "Address is required"),
// });

// const bookedServiceSchema = z.object({
//     service_id: z.number(),
//     service_name: z.string(),
//     price: z.number(),
// });

// const medicalSchema = baseSchema.extend({
//     type: z.literal("Medical"),
//     services: z.array(bookedServiceSchema).min(1, "Service is required"),
//     time_span: z.string().optional(),
// });

// const adlSchema = baseSchema.extend({
//     type: z.literal("ADL"),
//     time_span: z.string().min(1, "Time span is required"),
//     services: z.array(bookedServiceSchema).optional(),
// });

// export const homecareBookingSchema = z.discriminatedUnion("type", [
//     medicalSchema,
//     adlSchema,
// ]);

// export const facilityBookingSchema = z.object({
//     plan: z.enum(["Monthly", "Annually"]),
//     type: z.enum(["VIP", "Common"]),
//     admission_date: z.string().min(1, "Admission date is required"),
// });
import { reactive } from "vue";
import { z } from "zod";

export interface FacilityBooking {
    type: "Complete" | "Pre-Admission" | "";
    plan: "VIP" | "Common" | "";
    billing_interval: "Monthly" | "Yearly" | "";
    admission_date: string;
}

export interface BookedService {
    service_id: number;
    service_name: string;
    price: number;
}

export interface HomecareBooking {
    services?: BookedService[];
    type: "Medical" | "ADL";
    date: string;
    prefered_time: string;
    time_span?: string;
    address: string;
}

export const facilityData = reactive<FacilityBooking>({
    type: "Pre-Admission",
    plan: "Common",
    billing_interval: "",
    admission_date: "",
});

export const homecareData = reactive<HomecareBooking>({
    type: "Medical",
    date: "",
    prefered_time: "",
    address: "",
    time_span: "",
    services: [],
});

const bookedServiceSchema = z.object({
    service_id: z.number(),
    service_name: z.string(),
    price: z.number(),
});

const baseSchema = z.object({
    date: z.string().min(1, "Date is required"),
    prefered_time: z.string().min(1, "Preferred time is required"),
    address: z.string().min(1, "Address is required"),
});

const medicalSchema = baseSchema.extend({
    type: z.literal("Medical"),
    services: z.array(bookedServiceSchema).min(1, "At least one service is required"),
    time_span: z.string().optional(),
});

const adlSchema = baseSchema.extend({
    type: z.literal("ADL"),
    time_span: z.string().min(1, "Duration is required"),
    services: z.array(bookedServiceSchema).optional(),
});

export const homecareBookingSchema = z.discriminatedUnion("type", [
    medicalSchema,
    adlSchema,
]);

export const facilityBookingSchema = z
    .object({
        type: z.enum(["Complete", "Pre-Admission"]),
        plan: z.enum(["VIP", "Common"]).optional(),
        billing_interval: z.enum(["Monthly", "Yearly"]).optional(),
        admission_date: z.string().min(1, "Admission date is required"),
    })
    .superRefine((data, ctx) => {
        if (data.type === "Complete") {
            if (!data.plan) {
                ctx.addIssue({
                    code: "custom",
                    path: ["plan"],
                    message: "Room type is required for Complete Admission",
                });
            }

            if (!data.billing_interval) {
                ctx.addIssue({
                    code: "custom",
                    path: ["billing_interval"],
                    message: "Plan is required for Complete Admission",
                });
            }
        }

        if (data.type === "Pre-Admission") {
            if (data.plan || data.billing_interval) {
                ctx.addIssue({
                    code: "custom",
                    path: ["type"],
                    message: "Pre-Admission should not include plan or billing interval",
                });
            }
        }
    });