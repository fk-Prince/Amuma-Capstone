import { z } from "zod";
import type { User } from "./auth";

export const medicationSchema = z
    .object({
        name: z
            .string()
            .trim()
            .min(1, "Medication name is required"),

        strength: z
            .string()
            .trim()
            .min(1, "Strength is required"),

        dosageAmount: z
            .string()
            .trim()
            .min(1, "Dosage amount is required")
            .refine(
                (value) => !isNaN(Number(value)) && Number(value) > 0,
                "Enter a dosage amount greater than 0",
            ),

        dosageUnit: z
            .string()
            .min(1, "Select a dosage unit"),

        route: z
            .string()
            .min(1, "Select a route of administration"),

        instructions: z
            .string()
            .trim()
            .min(1, "Instructions are required"),

        takenFor: z.string().optional(),

        duration: z
            .string()
            .min(1, "Duration is required"),

        frequency: z
            .string()
            .min(1, "Select how often this is taken"),

        startDate: z
            .string()
            .min(1, "Start date is required"),

        kind: z.enum(["Scheduled", "PRN"]),

        times: z.array(z.string()),
    })
    .superRefine((data, ctx) => {
        if (data.kind === "Scheduled" && data.times.length === 0) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ["times"],
                message: "Add at least one time for a scheduled medication",
            });
        }
    });

export type MedicationForm = z.infer<typeof medicationSchema>;


export interface Medication {
    id: string;
    category: string;
    name: string;
    strength: string;
    dosageAmount: string;
    dosageUnit: string;
    route: string;
    instructions: string;
    takenFor: string;
    duration: string;
    durationLabel: string;
    frequency: string;
    kind: ScheduleKind;
    times: string[];
    startDate: string;
    recorded_date: string;
    schedules?: MedicationSchedule[]
}

export type ScheduleKind = "Scheduled" | "PRN";

export interface MedicationSchedule {
    id: string;
    marked_by?: User;
    date: string;
    time: string;
    status: "taken" | "missed" | "removed";
}

export interface MarkDosePayload {
    medication_id: string;
    schedule_id?: string;
    date: string;
    time: string;
    status: "taken" | "removed";
}

export const vitalSchema = z
    .object({
        bloodPressureSystolic: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 40 && Number(v) <= 260), {
                message: "Systolic must be between 40 and 260",
            }),

        bloodPressureDiastolic: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 20 && Number(v) <= 200), {
                message: "Diastolic must be between 20 and 200",
            }),

        heartRate: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 20 && Number(v) <= 250), {
                message: "Heart rate must be between 20 and 250 bpm",
            }),

        respiratoryRate: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 4 && Number(v) <= 60), {
                message: "Respiratory rate must be between 4 and 60",
            }),

        temperature: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 85 && Number(v) <= 110), {
                message: "Temperature must be between 85°F and 110°F",
            }),

        oxygenSaturation: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 50 && Number(v) <= 100), {
                message: "O2 saturation must be between 50% and 100%",
            }),

        // weight: z
        //     .string()
        //     .trim()
        //     .optional()
        //     .refine((v) => !v || Number(v) > 0, {
        //         message: "Weight must be a positive number",
        //     }),

        bloodGlucose: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 20 && Number(v) <= 800), {
                message: "Blood glucose must be between 20 and 800 mg/dL",
            }),

        painLevel: z
            .string()
            .trim()
            .optional()
            .refine((v) => !v || (Number(v) >= 0 && Number(v) <= 10), {
                message: "Pain level must be between 0 and 10",
            }),

        recordedDate: z.string().min(1, "Date is required"),

        recordedTime: z.string().min(1, "Time is required"),

        notes: z.string().trim().optional(),
    })
    .refine(
        (data) =>
            // Pain level alone doesn't count as a recorded vital sign — at
            // least one actual measurement is required.
            [
                data.bloodPressureSystolic,
                data.bloodPressureDiastolic,
                data.heartRate,
                data.respiratoryRate,
                data.temperature,
                data.oxygenSaturation,
                data.bloodGlucose,
            ].some((v) => v && v.length > 0),
        {
            message: "Record at least one vital sign",
        },
    )
    .refine(
        (data) =>
            (!data.bloodPressureSystolic && !data.bloodPressureDiastolic) ||
            (data.bloodPressureSystolic && data.bloodPressureDiastolic),
        {
            message: "Enter both systolic and diastolic values",
            path: ["bloodPressureDiastolic"],
        },
    );

export type VitalFormData = z.infer<typeof vitalSchema>;
export interface Vital extends VitalFormData {
    id: string;
    category: string;
}

export const dosageUnitOptions = [
    { label: "Tablet(s)", value: "tablet" },
    { label: "Capsule(s)", value: "capsule" },
    { label: "Patch(es)", value: "patch" },
    { label: "mL", value: "ml" },
    { label: "mg", value: "mg" },
    { label: "Drop(s)", value: "drop" },
    { label: "Puff(s)", value: "puff" },
    { label: "Application(s)", value: "application" },
];

export const routeOptions = [
    { label: "Oral", value: "oral" },
    { label: "Topical", value: "topical" },
    { label: "Transdermal (Patch)", value: "transdermal" },
    { label: "Subcutaneous", value: "subcutaneous" },
    { label: "Intramuscular", value: "intramuscular" },
    { label: "Intravenous", value: "intravenous" },
    { label: "Sublingual", value: "sublingual" },
    { label: "Rectal", value: "rectal" },
    { label: "Inhalation", value: "inhalation" },
    { label: "Ophthalmic (Eye)", value: "ophthalmic" },
    { label: "Otic (Ear)", value: "otic" },
    { label: "Nasal", value: "nasal" },
];

export const frequencyOptions = [
    { label: "Everyday", value: "everyday" },
    { label: "Every 2 Days", value: "every_2_days" },
    { label: "Every 3 Days", value: "every_3_days" },
    { label: "Every Week", value: "every_week" },
];

export function emptyForm(): MedicationForm {
    return {
        name: "",
        strength: "",
        dosageAmount: "",
        dosageUnit: "",
        route: "",
        instructions: "",
        takenFor: "",
        duration: "30",
        frequency: "everyday",
        startDate: new Date().toISOString().slice(0, 10),
        kind: "Scheduled",
        times: ["08:00"],
    };
}