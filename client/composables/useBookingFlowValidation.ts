import { computed, ref, type ComputedRef, type Ref } from "vue";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import type { HomecareBooking, FacilityBooking } from "~/types/booking";
import type { Patient, Guardian, Diagnosis } from "~/types/patient";
import type { ZodTypeAny } from "zod";
import { reservedSchema, type Reserved } from "~/types/contract";

type ValidationMode = "facility" | "reserved";

export function useBookingFlowValidation(opts: {
    category: ComputedRef<"homecare" | "facility">;

    validationMode: Ref<ValidationMode>;

    homecareSchema: ComputedRef<ZodTypeAny>;
    facilityBookingSchema: ZodTypeAny;
    patientSchema: ComputedRef<ZodTypeAny>;
    guardianSchema: ZodTypeAny;
    assessmentSchema: ZodTypeAny;

    homecareData: HomecareBooking;
    facilityData: FacilityBooking;
    patientData: Patient;
    guardianData: Guardian;
    diagnosisData: Diagnosis[];

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

    const assessmentErrors = ref<Record<string, string>>({});

    // always carry a default, so there is nothing to fail on.
    const ASSESSMENT_FIELDS = [
        "diagnosis",
        "diagnosis_date",
        "diagnosis_notes",
        "diagnosis_file",
    ] as const;

    function isAssessmentTouched(item: Diagnosis): boolean {
        return ASSESSMENT_FIELDS.some((key) => {
            const value = (item as any)[key];
            return value !== "" && value !== undefined && value !== null;
        });
    }

    const assessmentIsValid = computed(() => {
        const items = opts.diagnosisData ?? [];

        return items.every((item) => {
            if (!isAssessmentTouched(item)) return true;
            return opts.assessmentSchema.safeParse(item).success;
        });
    });

    function validateAssessment(): boolean {
        const items = opts.diagnosisData ?? [];
        const next: Record<string, string> = {};
        let valid = true;

        items.forEach((item, index) => {
            if (!isAssessmentTouched(item)) return;

            const result = opts.assessmentSchema.safeParse(item);

            if (result.success) return;

            valid = false;

            const formatted = result.error.format() as any;

            for (const key of ASSESSMENT_FIELDS) {
                const errors = formatted[key]?._errors;

                if (errors?.length) {
                    next[`${key}.${index}`] = errors[0];
                }
            }
        });

        assessmentErrors.value = next;

        return valid;
    }

    const reservedErrors = ref<Record<string, string>>({});

    const reservedIsValid = computed(() => {
        if (!opts.reserved) return true;

        return reservedSchema.safeParse(
            opts.reserved.value
        ).success;
    });

    function validateReserved(): boolean {
        if (!opts.reserved) {
            reservedErrors.value = {};
            return true;
        }

        const result = reservedSchema.safeParse(
            opts.reserved.value
        );

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
            const errors = formatted[key]?._errors;

            if (errors?.length) {
                next[key] = errors[0];
            }
        }

        reservedErrors.value = next;

        return false;
    }

    const step1Valid = computed(() => {
        if (opts.category.value === "homecare") {
            return homecare.isValid.value;
        }

        if (opts.validationMode.value === "reserved") {
            return reservedIsValid.value;
        }

        return facility.isValid.value;
    });

    const step2Valid = patient.isValid;
    const step3Valid = guardian.isValid;
    const step4Valid = assessmentIsValid;

    const canSubmit = computed(() =>
        step1Valid.value &&
        step2Valid.value &&
        step3Valid.value &&
        step4Valid.value
    );

    const progress = computed(() => {
        const steps = [
            step1Valid.value,
            step2Valid.value,
            step3Valid.value,
            step4Valid.value,
        ];

        return (
            (steps.filter(Boolean).length / steps.length) * 100
        );
    });

    const completedSteps = computed(() =>
        [
            step1Valid.value ? "step1" : null,
            step2Valid.value ? "step2" : null,
            step3Valid.value ? "step3" : null,
            step4Valid.value ? "step4" : null,
            // The assessment is comboboxes with defaults, so it never blocks
            // and always reads as done.
            "step5",
        ].filter((x): x is string => x !== null)
    );

    function validateStep1(): boolean {
        if (opts.category.value === "homecare") {
            return homecare.validate();
        }

        if (opts.validationMode.value === "reserved") {
            return validateReserved();
        }

        return facility.validate();
    }

    function validateAll():
        | "step1"
        | "step2"
        | "step3"
        | "step4"
        | null {

        const step1Ok = validateStep1();
        const step2Ok = patient.validate();
        const step3Ok = guardian.validate();
        const step4Ok = validateAssessment();

        if (!step1Ok) return "step1";
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
        assessmentErrors,

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
        validateAssessment,

        validateAll,
    };
}