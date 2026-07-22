import { reactive } from "vue";
import { z } from "zod";


//FACILITY
export interface FacilityBooking {
    type: "Complete" | "Pre-Admission" | "";
    plan: "VIP" | "Common" | "";
    billing_interval: "Monthly" | "Yearly" | "";
    admission_date: string;
}

export const facilityData = reactive<FacilityBooking>({
    type: "Pre-Admission",
    plan: "Common",
    billing_interval: "",
    admission_date: "",
});


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



//HOMECARE
export interface HomecareBooking {
    services?: BookedService[];
    type: "Medical" | "ADL";
    date: string;
    prefered_time: string;
    time_span?: string;
    address: string;
}

export interface BookedService {
    service_id: number;
    service_name: string;
    price: number;
}

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
    service_name: z.string().min(1),
    price: z.number().nonnegative(),
});

const isoDate = z
    .string()
    .min(1, "Date is required")
    .regex(/^\d{4}-\d{2}-\d{2}$/, "Enter a valid date")
    .refine((val) => !Number.isNaN(new Date(val).getTime()), {
        message: "Enter a valid date",
    })
    .refine(
        (val) => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            return new Date(val) >= today;
        },
        {
            message: "Date cannot be in the past",
        },
    );

const baseSchema = z.object({
    date: isoDate,

    prefered_time: z
        .string()
        .min(1, "Preferred time is required"),

    address: z
        .string()
        .min(5, "Enter a complete address"),
});


const medicalSchema = baseSchema.extend({
    type: z.literal("Medical"),

    services: z
        .array(bookedServiceSchema)
        .min(1, "Select at least one service"),

    time_span: z.string().optional(),
});


const createAdlSchema = (minAdlHours: number) =>
    baseSchema.extend({
        type: z.literal("ADL"),

        time_span: z
            .union([z.string(), z.number()])
            .transform((val) => String(val))

            .refine((val) => val.length > 0, {
                message: "Duration is required",
            })

            .refine((val) => /^\d+(\.\d+)?$/.test(val), {
                message: "Enter a valid number of hours",
            })

            .refine((val) => Number(val) >= minAdlHours, {
                message: `Minimum duration is ${minAdlHours} hours`,
            }),

        services: z
            .array(bookedServiceSchema)
            .optional(),
    });


export const createHomecareBookingSchema = (
    minAdlHours: number,
) =>
    z.discriminatedUnion("type", [
        medicalSchema,
        createAdlSchema(minAdlHours),
    ]);
