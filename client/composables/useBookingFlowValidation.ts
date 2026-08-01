// import { computed, type ComputedRef, type Ref } from "vue";
// import { useSchemaValidation } from "~/composables/useSchemaValidation";
// import type { HomecareBooking, FacilityBooking } from "~/types/booking";
// import type { Patient, Guardian, Assessment } from "~/types/patient";
// import type { ZodTypeAny } from "zod";
// import type { Room } from "~/types/room";
// import type { Bed } from "~/types/bed";
// import { reservedSchema, type Reserved } from "~/types/contract";


// export function useBookingFlowValidation(opts: {
//     category: ComputedRef<"homecare" | "facility">;
//     homecareSchema: ComputedRef<ZodTypeAny>;
//     facilityBookingSchema: ZodTypeAny;
//     patientSchema: ComputedRef<ZodTypeAny>;
//     guardianSchema: ZodTypeAny;
//     assessmentSchema: ZodTypeAny;

//     homecareData: HomecareBooking;
//     facilityData: FacilityBooking;
//     patientData: Patient;
//     guardianData: Guardian;
//     assessmentData: Assessment;

//     reserved?: Ref<Reserved>;
// }) {
//     const homecare = useSchemaValidation(
//         opts.homecareSchema,
//         opts.homecareData
//     );

//     const facility = useSchemaValidation(
//         opts.facilityBookingSchema,
//         opts.facilityData
//     );

//     const patient = useSchemaValidation(
//         opts.patientSchema,
//         opts.patientData
//     );

//     const guardian = useSchemaValidation(
//         opts.guardianSchema,
//         opts.guardianData
//     );

//     const assessment = useSchemaValidation(
//         opts.assessmentSchema,
//         opts.assessmentData
//     );


//     const step1Valid = computed(() => {
//         const accommodationValid =
//             opts.category.value === "facility"
//                 ? facility.isValid.value
//                 : homecare.isValid.value;

//         const bedValid =
//             opts.reserved && opts.category.value === "facility"
//                 ? !!opts.reserved.value.bed?.bed_id &&
//                 !!opts.reserved.value.admitted_at
//                 : true;

//         return accommodationValid && bedValid;
//     });


//     const step2Valid = patient.isValid;
//     const step3Valid = guardian.isValid;
//     const step4Valid = assessment.isValid;


//     const canSubmit = computed(
//         () =>
//             step1Valid.value &&
//             step2Valid.value &&
//             step3Valid.value &&
//             step4Valid.value
//     );


//     const progress = computed(() => {
//         const flags = [
//             step1Valid.value,
//             step2Valid.value,
//             step3Valid.value,
//             step4Valid.value,
//         ];

//         return (
//             (flags.filter(Boolean).length / flags.length) *
//             100
//         );
//     });


//     const completedSteps = computed(() =>
//         [
//             step1Valid.value ? "step1" : null,
//             step2Valid.value ? "step2" : null,
//             step3Valid.value ? "step3" : null,
//             step4Valid.value ? "step4" : null,
//         ].filter((key): key is string => key !== null)
//     );


//     function validateStep1(): boolean {
//         return opts.category.value === "facility"
//             ? facility.validate()
//             : homecare.validate();
//     }


//     function validateAll():
//         | "step1"
//         | "step2"
//         | "step3"
//         | "step4"
//         | null {

//         const step1Ok = validateStep1();

//         if (!step1Ok) {
//             return "step1";
//         }

//         if (
//             opts.reserved &&
//             opts.category.value === "facility" &&
//             (!opts.reserved.value.bed?.bed_id ||
//                 !opts.reserved.value.admitted_at)
//         ) {
//             return "step1";
//         }


//         const step2Ok = patient.validate();
//         const step3Ok = guardian.validate();
//         const step4Ok = assessment.validate();


//         if (!step2Ok) return "step2";
//         if (!step3Ok) return "step3";
//         if (!step4Ok) return "step4";


//         return null;
//     }


//     return {
//         homecareErrors: homecare.errors,
//         facilityErrors: facility.errors,
//         patientErrors: patient.errors,
//         guardianErrors: guardian.errors,
//         assessmentErrors: assessment.errors,

//         step1Valid,
//         step2Valid,
//         step3Valid,
//         step4Valid,

//         canSubmit,
//         progress,
//         completedSteps,

//         validateStep1,
//         validatePatient: patient.validate,
//         validateGuardian: guardian.validate,
//         validateAssessment: assessment.validate,
//         validateAll,
//     };
// }
import { computed, ref, type ComputedRef, type Ref } from "vue";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import type { HomecareBooking, FacilityBooking } from "~/types/booking";
import type { Patient, Guardian, Assessment } from "~/types/patient";
import type { ZodTypeAny } from "zod";
import { reservedSchema, type Reserved } from "~/types/contract";


export function useBookingFlowValidation(opts: {
    category: ComputedRef<"homecare" | "facility">;
    homecareSchema: ComputedRef<ZodTypeAny>;
    facilityBookingSchema: ZodTypeAny;
    patientSchema: ComputedRef<ZodTypeAny>;
    guardianSchema: ZodTypeAny;
    assessmentSchema: ZodTypeAny;

    homecareData: HomecareBooking;
    facilityData: FacilityBooking;
    patientData: Patient;
    guardianData: Guardian;
    assessmentData: Assessment;

    // Only relevant when category === "facility". Homecare bookings have
    // no room/bed to reserve, so this stays optional and every check below
    // guards on both its presence AND the active category.
    reserved?: Ref<Reserved>;
}) {
    const homecare = useSchemaValidation(
        opts.homecareSchema,
        opts.homecareData
    );

    const facility = useSchemaValidation(
        opts.facilityBookingSchema,
        opts.facilityData
    );

    const patient = useSchemaValidation(
        opts.patientSchema,
        opts.patientData
    );

    const guardian = useSchemaValidation(
        opts.guardianSchema,
        opts.guardianData
    );

    const assessment = useSchemaValidation(
        opts.assessmentSchema,
        opts.assessmentData
    );

    // ---- reserved (room/bed/date) validation — facility only ----

    const reservedErrors = ref<Record<string, string>>({});

    const appliesToReserved = computed(
        () => opts.category.value === "facility" && !!opts.reserved
    );

    const reservedIsValid = computed(() => {
        if (!appliesToReserved.value || !opts.reserved) return true;
        return reservedSchema.safeParse(opts.reserved.value).success;
    });

    function validateReserved(): boolean {
        if (!appliesToReserved.value || !opts.reserved) {
            reservedErrors.value = {};
            return true;
        }

        const result = reservedSchema.safeParse(opts.reserved.value);

        if (result.success) {
            reservedErrors.value = {};
            return true;
        }

        const formatted = result.error.format() as any;
        const next: Record<string, string> = {};

        for (const key of [
            "room",
            "bed",
            "billing_cycle",
            "contract_id",
            "price",
            "accommodation_type",
            "admitted_at",
        ] as const) {
            const fieldErrors = formatted[key]?._errors;
            if (fieldErrors?.length) {
                next[key] = fieldErrors[0];
            }
        }

        reservedErrors.value = next;
        return false;
    }


    const step1Valid = computed(() => {
        const accommodationValid =
            opts.category.value === "facility"
                ? facility.isValid.value
                : homecare.isValid.value;

        return accommodationValid && reservedIsValid.value;
    });


    const step2Valid = patient.isValid;
    const step3Valid = guardian.isValid;
    const step4Valid = assessment.isValid;


    const canSubmit = computed(
        () =>
            step1Valid.value &&
            step2Valid.value &&
            step3Valid.value &&
            step4Valid.value
    );


    const progress = computed(() => {
        const flags = [
            step1Valid.value,
            step2Valid.value,
            step3Valid.value,
            step4Valid.value,
        ];

        return (
            (flags.filter(Boolean).length / flags.length) *
            100
        );
    });


    const completedSteps = computed(() =>
        [
            step1Valid.value ? "step1" : null,
            step2Valid.value ? "step2" : null,
            step3Valid.value ? "step3" : null,
            step4Valid.value ? "step4" : null,
        ].filter((key): key is string => key !== null)
    );


    function validateStep1(): boolean {
        const accommodationOk =
            opts.category.value === "facility"
                ? facility.validate()
                : homecare.validate();

        const reservedOk = validateReserved();

        return accommodationOk && reservedOk;
    }


    function validateAll():
        | "step1"
        | "step2"
        | "step3"
        | "step4"
        | null {

        const step1Ok = validateStep1();

        if (!step1Ok) {
            return "step1";
        }

        const step2Ok = patient.validate();
        const step3Ok = guardian.validate();
        const step4Ok = assessment.validate();


        if (!step2Ok) return "step2";
        if (!step3Ok) return "step3";
        if (!step4Ok) return "step4";


        return null;
    }


    return {
        homecareErrors: homecare.errors,
        facilityErrors: facility.errors,
        reservedErrors,
        patientErrors: patient.errors,
        guardianErrors: guardian.errors,
        assessmentErrors: assessment.errors,

        step1Valid,
        step2Valid,
        step3Valid,
        step4Valid,

        canSubmit,
        progress,
        completedSteps,

        validateStep1,
        validateReserved,
        validatePatient: patient.validate,
        validateGuardian: guardian.validate,
        validateAssessment: assessment.validate,
        validateAll,
    };
}