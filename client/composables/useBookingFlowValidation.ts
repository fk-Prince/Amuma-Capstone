import { computed } from "vue";
import type { ComputedRef } from "vue";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import type { HomecareBooking, FacilityBooking } from "~/types/booking";
import type { Patient, Guardian, Assessment, assessmentSchema } from "~/types/patient";
import type { ZodTypeAny } from "zod";


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
}) {
    const homecare = useSchemaValidation(opts.homecareSchema, opts.homecareData);
    const facility = useSchemaValidation(opts.facilityBookingSchema, opts.facilityData);
    const patient = useSchemaValidation(opts.patientSchema, opts.patientData);
    const guardian = useSchemaValidation(opts.guardianSchema, opts.guardianData);
    const assessment = useSchemaValidation(opts.assessmentSchema, opts.assessmentData);

    const step1Valid = computed(() =>
        opts.category.value === "facility" ? facility.isValid.value : homecare.isValid.value,
    );
    const step2Valid = patient.isValid;
    const step3Valid = guardian.isValid;

    // Step 4 is optional: it's only "invalid" if the person has started
    // filling it in and left it in a bad state (e.g. diagnosis fields
    // filled but name missing). An untouched section should never block progress.
    const step4Valid = assessment.isValid;

    const canSubmit = computed(
        () => step1Valid.value && step2Valid.value && step3Valid.value && step4Valid.value,
    );

    const progress = computed(() => {
        const flags = [step1Valid.value, step2Valid.value, step3Valid.value, step4Valid.value];
        const done = flags.filter(Boolean).length;
        return (done / flags.length) * 100;
    });

    const completedSteps = computed(() =>
        [
            step1Valid.value ? "step1" : null,
            step2Valid.value ? "step2" : null,
            step3Valid.value ? "step3" : null,
            step4Valid.value ? "step4" : null,
        ].filter((key): key is string => key !== null),
    );

    function validateStep1(): boolean {
        return opts.category.value === "facility"
            ? facility.validate()
            : homecare.validate();
    }

    function validateAll(): "step1" | "step2" | "step3" | "step4" | null {
        const step1Ok = validateStep1();
        const step2Ok = patient.validate();
        const step3Ok = guardian.validate();
        const step4Ok = assessment.validate();

        if (!step1Ok) return "step1";
        if (!step2Ok) return "step2";
        if (!step3Ok) return "step3";
        if (!step4Ok) return "step4";
        return null;
    }

    return {
        homecareErrors: homecare.errors,
        facilityErrors: facility.errors,
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
        validatePatient: patient.validate,
        validateGuardian: guardian.validate,
        validateAssessment: assessment.validate,
        validateAll,
    };
}