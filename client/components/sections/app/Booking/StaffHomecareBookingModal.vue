<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
            />

            <div
                class="relative z-10 flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                        >
                            Homecare Booking
                        </p>

                        <h2
                            class="mt-1 text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            New booking
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-white/80"
                        @click="close"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <div v-if="loadingBranch" class="space-y-4 p-8">
                        <div
                            class="h-5 w-40 animate-pulse rounded bg-slate-200 dark:bg-white/10"
                        />
                        <div
                            class="h-28 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/5"
                        />
                    </div>

                    <div
                        v-else-if="loadError"
                        class="flex flex-col items-center gap-2 p-10 text-center"
                    >
                        <p
                            class="text-sm font-medium text-slate-700 dark:text-gray-300"
                        >
                            Couldn't load booking options
                        </p>
                        <p class="text-xs text-muted dark:text-gray-400">
                            {{ loadError }}
                        </p>
                    </div>

                    <div v-else class="flex flex-col divide-y divide-slate-200 dark:divide-white/10">
                        <HomecareBooking
                            :model="model"
                            :services="services"
                            :homecare="branch?.homecare"
                            :settings="branch?.settings"
                            :errors="errors"
                            @update:model="(value) => Object.assign(model, value)"
                            @update:errors="(value) => (errors = value)"
                        />

                        <PatientForm
                            category="homecare"
                            :model="patientData"
                            :errors="patientErrors"
                            @update:model="Object.assign(patientData, $event)"
                            @update:errors="patientErrors = $event"
                        />

                        <div ref="guardianFormRef">
                        <GuardianForm
                            :isAdmission="true"
                            email-check
                            :checking-email="checkingEmail"
                            :email-status="guardianEmailStatus"
                            :linked-fields="linkedGuardianFields"
                            :model="guardianData"
                            :errors="guardianErrors"
                            @update:model="Object.assign(guardianData, $event)"
                            @update:errors="guardianErrors = $event"
                            @check-email="checkGuardianEmail"
                            @reset-email="resetGuardianLink"
                        />
                        </div>

                        <DiagnosisForm
                            :model="diagnosisData"
                            :errors="diagnosisErrors"
                            @update:model="
                                diagnosisData.splice(
                                    0,
                                    diagnosisData.length,
                                    ...$event,
                                )
                            "
                            @update:errors="diagnosisErrors = $event"
                        />

                        <AssessmentForm
                            :model="assessmentData"
                            :errors="diagnosisErrors"
                            @update:model="
                                assessmentData.splice(
                                    0,
                                    assessmentData.length,
                                    ...$event,
                                )
                            "
                            @update:errors="diagnosisErrors = $event"
                        />
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting || loadingBranch || !!loadError"
                        @click="submit"
                    >
                        <Loader2
                            v-if="submitting"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ submitting ? "Creating..." : "Create Booking" }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <ConfirmDialog
        :open="showEmailExistsWarning"
        title="This email already exists"
        :message="`${guardianData.email ?? 'This email'} already has an account. If you continue, the guardian details saved on that account will be filled in and it will be able to access the patient's records.`"
        confirm-label="Continue"
        cancel-label="Cancel"
        :loading="submitting"
        @confirm="confirmEmailExists"
        @cancel="showEmailExistsWarning = false"
    />
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { Loader2, X } from "lucide-vue-next";
import PatientForm from "~/components/forms/PatientForm.vue";
import GuardianForm from "~/components/forms/GuardianForm.vue";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import { admissionService } from "~/api/admission/AdmissionService";
import DiagnosisForm from "~/components/forms/DiagnosisForm.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";
import HomecareBooking from "~/components/sections/booking/provider/HomecareBooking.vue";
import { useBranch } from "~/composables/useBranchProvider";
import { bookingService } from "~/api/booking/BookingService";
import { serviceService } from "~/api/service/ServiceService";
import { useToast } from "~/composables/useToast";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import { createHomecareBookingSchema } from "~/schema/booking-schema";
import {
    assessmentSchema,
    createPatientSchema,
    guardianSchema,
} from "~/schema/patient-schema";
import type { HomecareBooking as HomecareBookingModel } from "~/types/booking";
import type {
    Assessment,
    Diagnosis,
    Guardian,
    Patient,
} from "~/types/patient";
import { LIFE_SYSTEM_ACTIVITIES } from "~/utils/assessment";
import type { Service } from "~/types/service";

const props = defineProps<{
    open: boolean;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "booked", booking: any): void;
}>();

const { success, error: toastError } = useToast();
const guardianFormRef = ref<HTMLElement | null>(null);

const { branch, fetchBranch } = useBranch();
const services = ref<Service[]>([]);
const loadingBranch = ref(false);
const loadError = ref<string | null>(null);

function emptyModel(): HomecareBookingModel {
    return {
        type: "ADL",
        date: "",
        prefered_time: "",
        time_span: "",
        address: "",
        latitude: null,
        longitude: null,
        services: [],
    };
}

const emptyPatient = (): Patient => ({
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
    allergies: "",
});

const emptyGuardian = (): Guardian => ({
    first_name: "",
    middle_name: "",
    last_name: "",
    phone_number: "",
    email: "",
    address: "",
    relationship: "",
    occupation: "",
});

const emptyDiagnosis = (): Diagnosis => ({
    diagnosis: "",
    diagnosis_date: "",
    diagnosis_notes: "",
    diagnosis_file: undefined,
    diagnosis_file_name: "",
});

const emptyAssessment = (): Assessment => ({
    condition: "ambulatory",
    mental_state: "alert",
    affect: "cheerful",
    behavior: "cooperative",
    communication: "Coherent & Logical",
    speech: "clear",
    life_system_profile: Object.fromEntries(
        LIFE_SYSTEM_ACTIVITIES.map((activity) => [activity, 5]),
    ) as Assessment["life_system_profile"],
});

const model = reactive<HomecareBookingModel>(emptyModel());
const diagnosisData = reactive<Diagnosis[]>([emptyDiagnosis()]);
const assessmentData = reactive<Assessment[]>([emptyAssessment()]);
const diagnosisErrors = ref<Record<string, string>>({});
const patientData = reactive<Patient>(emptyPatient());
const guardianData = reactive<Guardian>(emptyGuardian());

const homecareSchema = computed(() =>
    createHomecareBookingSchema(branch.value?.homecare?.adl_min_hour ?? 0),
);

const { validate, errors } = useSchemaValidation(homecareSchema, model);

const { validate: validatePatient, errors: patientErrors } =
    useSchemaValidation(createPatientSchema("homecare"), patientData);

const { validate: validateGuardian, errors: guardianErrors } =
    useSchemaValidation(guardianSchema, guardianData);

const DIAGNOSIS_FIELDS = [
    "diagnosis",
    "diagnosis_date",
    "diagnosis_notes",
    "diagnosis_file",
] as const;

// A diagnosis is optional: an untouched entry is skipped, a started one must be complete.
function filledDiagnoses(): Diagnosis[] {
    return diagnosisData.filter((item) =>
        DIAGNOSIS_FIELDS.some((key) => {
            const value = (item as any)[key];
            return value !== "" && value !== undefined && value !== null;
        }),
    );
}

function validateDiagnoses(): boolean {
    const next: Record<string, string> = {};

    diagnosisData.forEach((item, index) => {
        if (!filledDiagnoses().includes(item)) return;

        const result = assessmentSchema.safeParse(item);

        if (result.success) return;

        const formatted = result.error.format() as any;

        for (const key of DIAGNOSIS_FIELDS) {
            const message = formatted[key]?._errors?.[0];

            if (message) next[`${key}.${index}`] = message;
        }
    });

    diagnosisErrors.value = next;

    return Object.keys(next).length === 0;
}

const showEmailExistsWarning = ref(false);
const emailExistsConfirmed = ref(false);
const checkingEmail = ref(false);
const checkedEmail = ref("");
const emailAvailable = ref(false);
const existingGuardian = ref<Partial<Guardian> | null>(null);
const emailPromptSource = ref<"check" | "submit">("submit");
const linkedGuardianFields = ref<(keyof Guardian)[]>([]);

const normalizedGuardianEmail = computed(() =>
    (guardianData.email ?? "").trim().toLowerCase(),
);

const guardianEmailStatus = computed(() => {
    if (!normalizedGuardianEmail.value) return null;
    if (checkedEmail.value !== normalizedGuardianEmail.value) return null;
    if (emailExistsConfirmed.value) return "linked";
    return emailAvailable.value ? "available" : null;
});

watch(normalizedGuardianEmail, (email) => {
    if (email !== checkedEmail.value) {
        emailExistsConfirmed.value = false;
        emailAvailable.value = false;
        existingGuardian.value = null;
    }
});

watch(guardianEmailStatus, (status) => {
    if (status !== "linked") linkedGuardianFields.value = [];
});

async function lookupGuardianEmail() {
    const email = normalizedGuardianEmail.value;
    const res = await admissionService.guardianEmailExists(email);

    checkedEmail.value = email;
    emailAvailable.value = !res?.exists;
    existingGuardian.value = res?.guardian ?? null;

    return Boolean(res?.exists);
}

// Shows the reason under the email field and brings the guardian form into view.
function rejectGuardianEmail(message: string) {
    guardianErrors.value = { ...guardianErrors.value, email: message };
    toastError(message);
    guardianFormRef.value?.scrollIntoView({ behavior: "smooth", block: "center" });
}

async function checkGuardianEmail() {
    if (!normalizedGuardianEmail.value || checkingEmail.value) return;

    checkingEmail.value = true;

    try {
        if (await lookupGuardianEmail()) {
            emailPromptSource.value = "check";
            showEmailExistsWarning.value = true;
        }
    } catch (err: any) {
        rejectGuardianEmail(err?.message ?? "Internal Server Error");
    } finally {
        checkingEmail.value = false;
    }
}

function applyExistingGuardian() {
    const saved = existingGuardian.value;
    if (!saved) return;

    const filled: Partial<Guardian> = {
        first_name: saved.first_name ?? "",
        middle_name: saved.middle_name ?? "",
        last_name: saved.last_name ?? "",
    };

    (["phone_number", "address", "occupation"] as const).forEach((key) => {
        if (saved[key]) filled[key] = saved[key];
    });

    Object.assign(guardianData, filled);
    linkedGuardianFields.value = Object.keys(filled) as (keyof Guardian)[];

    const cleared = { ...guardianErrors.value };
    Object.keys(filled).forEach((key) => delete cleared[key]);
    guardianErrors.value = cleared;
}

function resetGuardianLink() {
    const cleared: Partial<Guardian> = { email: "" };
    linkedGuardianFields.value.forEach((key) => {
        cleared[key] = "";
    });

    Object.assign(guardianData, cleared);

    checkedEmail.value = "";
    emailExistsConfirmed.value = false;
    emailAvailable.value = false;
    existingGuardian.value = null;
    linkedGuardianFields.value = [];
}

async function confirmEmailExists() {
    emailExistsConfirmed.value = true;
    applyExistingGuardian();
    showEmailExistsWarning.value = false;

    if (emailPromptSource.value === "submit") await submit();
}

async function loadOptions() {
    loadingBranch.value = true;
    loadError.value = null;

    try {
        await fetchBranch(props.branchUuid);

        const serviceRes: any = await serviceService.getBranchService(
            props.branchUuid,
        );

        services.value = serviceRes?.services ?? serviceRes?.data?.services ?? [];
    } catch (err: any) {
        loadError.value =
            err?.response?.data?.message ??
            err?.message ??
            "Something went wrong loading this branch's options.";
    } finally {
        loadingBranch.value = false;
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        Object.assign(model, emptyModel());
        Object.assign(patientData, emptyPatient());
        Object.assign(guardianData, emptyGuardian());
        resetGuardianLink();
        diagnosisData.splice(0, diagnosisData.length, emptyDiagnosis());
        assessmentData.splice(0, assessmentData.length, emptyAssessment());
        diagnosisErrors.value = {};
        errors.value = {};
        patientErrors.value = {};
        guardianErrors.value = {};

        loadOptions();
    },
);

const submitting = ref(false);

function priceFor(booking: HomecareBookingModel): number {
    if (booking.type === "Medical") {
        return (booking.services ?? []).reduce(
            (total, item) => total + Number(item.price || 0),
            0,
        );
    }

    const hours = Number(booking.time_span) || 0;
    const rate = Number(branch.value?.homecare?.adl_hourly_rate ?? 0);

    return hours * rate;
}

async function submit() {
    if (submitting.value) return;

    // Every form runs so every problem is shown at once.
    const results = [
        validate(),
        validatePatient(),
        validateGuardian(),
        validateDiagnoses(),
    ];

    if (results.includes(false)) return;

    submitting.value = true;

    const alreadyChecked =
        checkedEmail.value === normalizedGuardianEmail.value &&
        (emailExistsConfirmed.value || emailAvailable.value);

    if (!alreadyChecked && normalizedGuardianEmail.value) {
        try {
            if (await lookupGuardianEmail()) {
                emailPromptSource.value = "submit";
                showEmailExistsWarning.value = true;
                submitting.value = false;
                return;
            }
        } catch (err) {
            console.error(err);
        }
    }

    try {
        const res: any = await bookingService.actionBooking({
            action: "staff_homecare",
            branch_uuid: props.branchUuid,
            patient: {
                first_name: patientData.first_name,
                middle_name: patientData.middle_name || null,
                last_name: patientData.last_name,
                suffix: patientData.suffix || null,
                gender: patientData.gender,
                citizenship: patientData.citizenship,
                occupation: patientData.occupation,
                date_of_birth: patientData.date_of_birth,
                phone_number: patientData.phone_number || null,
                marital_status: patientData.marital_status,
                height: patientData.height || null,
                weight: patientData.weight || null,
                blood_type: patientData.blood_type || null,
                allergies: patientData.allergies || null,
                address: patientData.address,
            },
            guardian: {
                first_name: guardianData.first_name,
                middle_name: guardianData.middle_name || null,
                last_name: guardianData.last_name,
                phone_number: guardianData.phone_number,
                email: guardianData.email,
                address: guardianData.address,
                relationship: guardianData.relationship,
                occupation: guardianData.occupation || null,
            },
            diagnoses: filledDiagnoses().map((item) => ({ ...item })),
            assessment: assessmentData.map((item) => ({ ...item })),
            type: model.type,
            date: model.date,
            prefered_time: model.prefered_time,
            time_span: model.time_span,
            address: model.address,
            latitude: model.latitude,
            longitude: model.longitude,
            services: model.services,
            price: priceFor(model),
            rate:
                model.type === "ADL"
                    ? Number(branch.value?.homecare?.adl_hourly_rate ?? 0)
                    : undefined,
        });

        const booking = res?.data ?? null;
        const reference = booking?.reference_id ?? null;

        success(
            reference
                ? `Homecare booking #${reference} created.`
                : (res?.message ?? "Homecare booking created."),
        );

        emit("booked", booking);
        close();
    } catch (err: any) {
        toastError(
            err?.response?.data?.message ??
                err?.message ??
                "Failed to create the homecare booking.",
        );
    } finally {
        submitting.value = false;
    }
}

function close() {
    emit("close");
}
</script>
