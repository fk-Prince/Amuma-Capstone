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
    recorded_by?: string | null;
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

const measurement = (
    required: string,
    min: number,
    max: number,
    range: string,
) =>
    z.preprocess(
        (value) => (value === null || value === undefined ? "" : String(value)),
        z
            .string()
            .trim()
            .min(1, required)
            .refine((v) => Number(v) >= min && Number(v) <= max, {
                message: range,
            }),
    );

export const vitalSchema = z.object({
    bloodPressureSystolic: measurement(
        "Systolic is required",
        40,
        260,
        "Systolic must be between 40 and 260",
    ),

    bloodPressureDiastolic: measurement(
        "Diastolic is required",
        20,
        200,
        "Diastolic must be between 20 and 200",
    ),

    heartRate: measurement(
        "Heart rate is required",
        20,
        250,
        "Heart rate must be between 20 and 250 bpm",
    ),

    respiratoryRate: measurement(
        "Respiratory rate is required",
        4,
        60,
        "Respiratory rate must be between 4 and 60",
    ),

    temperature: measurement(
        "Temperature is required",
        30,
        43,
        "Temperature must be between 30°C and 43°C",
    ),

    oxygenSaturation: measurement(
        "O2 saturation is required",
        50,
        100,
        "O2 saturation must be between 50% and 100%",
    ),

    bloodGlucose: measurement(
        "Blood glucose is required",
        20,
        800,
        "Blood glucose must be between 20 and 800 mg/dL",
    ),

    painLevel: measurement(
        "Choose a pain level",
        0,
        10,
        "Pain level must be between 0 and 10",
    ),

    recordedDate: z.string().min(1, "Date is required"),

    recordedTime: z.string().min(1, "Time is required"),

    notes: z.string().trim().optional(),
});

export type VitalFormData = z.infer<typeof vitalSchema>;
export interface Vital extends VitalFormData {
    id: string;
    category: string;
    recordedBy?: string | null;
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