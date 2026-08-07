import type { Patient } from "./patient";

export interface PatientAdmission {
    patient_admission_id: number;
    bed_id: number;
    patient_id: number;
    status: string;
    note: string | null;
    admitted_at: string;
    end_date: string | null;
    patient: Patient | null;
    booking_reference_id?: string
}