import { z } from "zod";

export const patientActivitySchema = z.object({
    title: z.string().trim().min(1, "Title is required"),
    subtitle: z.string().trim().optional(),
    description: z.string().trim().optional(),
    type: z.string().min(1, "Select an activity type"),
    occurredAt: z.string().min(1, "Date & time is required"),
});

export type PatientActivityForm = z.infer<typeof patientActivitySchema>;

export interface PatientActivity extends PatientActivityForm {
    id: string;
}

export const activityTypeOptions = [
    { label: "Appointment", value: "appointment" },
    { label: "Therapy", value: "therapy" },
    { label: "Meal", value: "meal" },
    { label: "Activity", value: "activity" },
];

export function emptyForm(): PatientActivityForm {
    return {
        title: "",
        subtitle: "",
        description: "",
        type: "appointment",
        occurredAt: "",
    };
}
