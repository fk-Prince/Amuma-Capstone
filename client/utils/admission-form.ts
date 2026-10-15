import {
    patientData,
    guardianData,
    diagnosisData,
    assessmentData,
} from "~/schema/patient-schema";
import { facilityData } from "~/schema/booking-schema";
import { reserved } from "~/types/contract";

function replaceFields<T extends object>(target: T, fresh: T) {
    Object.keys(target).forEach((key) => {
        delete (target as any)[key];
    });
    Object.assign(target, fresh);
}

export function resetAdmissionForm() {
    replaceFields(patientData, {
        first_name: "",
        middle_name: "",
        last_name: "",
        suffix: "",
        gender: "",
        citizenship: "",
        occupation: "",
        date_of_birth: "",
        phone_number: "",
        marital_status: "",
        height: "",
        weight: "",
        blood_type: "",
        address: "",
    });

    replaceFields(guardianData, {
        first_name: "",
        middle_name: "",
        last_name: "",
        phone_number: "",
        email: "",
        relationship: "",
        occupation: "",
        address: "",
    });

    diagnosisData.splice(0, diagnosisData.length, {
        diagnosis: "",
        diagnosis_date: "",
        diagnosis_notes: "",
        diagnosis_file: undefined,
        diagnosis_file_name: "",
    });

    assessmentData.splice(0, assessmentData.length, {
        condition: "ambulatory",
        communication: "Coherent & Logical",
        speech: "clear",
        mental_state: "alert",
        affect: "cheerful",
        behavior: "cooperative",
        life_system_profile: {
            bathing: 5,
            transferring: 5,
            toileting: 5,
            grooming: 5,
            eating: 5,
            locomotion: 5,
            dressing: 5,
        },
    });

    replaceFields(facilityData, {
        type: "Pre-Admission",
        plan: "",
        billing_cycle: "",
        admission_date: "",
    });

    reserved.value = {
        room: null,
        bed: null,
        contract_id: null,
        billing_cycle: "monthly",
        price: 0,
        accommodation_type: "Common",
        admitted_at: "",
    };
}
