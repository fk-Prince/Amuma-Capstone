<template>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full mx-auto px-4 lg:px-8 py-8">
            <div class="grid lg:grid-cols-[1fr_320px] gap-8">
                <main class="border border-slate-200 bg-white rounded-2xl">
                    <div class="px-[3rem] md:px-[3.5rem] py-[1.5rem]">
                        <p
                            class="text-sm font-semibold text-primary uppercase tracking-wider"
                        >
                            Facility Admission
                        </p>

                        <h1 class="mt-2 text-3xl font-bold text-slate-900">
                            Patient Admission Request
                        </h1>

                        <p
                            class="mt-3 text-slate-500 leading-relaxed max-w-3xl"
                        >
                            Complete the information below to register a patient
                            for admission. Required fields are marked with
                            <span class="text-red-500">*</span>.
                        </p>
                    </div>

                    <div class="px-[3rem] md:px-[3.5rem]">
                        <div class="flex flex-col md:flex-row gap-4">
                            <BaseInput
                                class="flex-1"
                                label="Reference Number"
                                placeholder="BKN-000001"
                                :model-value="referenceInput"
                                @update:model-value="referenceInput = $event"
                                :error="referenceError"
                            />

                            <BaseButton
                                class="md:self-end"
                                variant="secondary"
                                :disabled="loadingReference || !referenceInput"
                                @click="loadByReference"
                            >
                                {{
                                    loadingReference
                                        ? "Loading..."
                                        : "Load Admission"
                                }}
                            </BaseButton>
                        </div>
                    </div>

                    <section class="px-6" ref="step1">
                        <AdmissionDetail
                            variant="page"
                            :loading="loadingContract"
                            :roomContract="roomContract"
                            :errors="reservedErrors"
                            @update:model="reserved = $event"
                            :requireAdmissionDate="true"
                        />
                    </section>

                    <section class="px-6" ref="step2">
                        <PatientForm
                            category="facility"
                            :model="patientData"
                            :errors="patientErrors"
                            @update:model="Object.assign(patientData, $event)"
                            @update:errors="patientErrors = $event"
                        />
                    </section>

                    <section class="px-6" ref="step3">
                        <GuardianForm
                            :isAdmission="true"
                            :model="guardianData"
                            :errors="guardianErrors"
                            @update:model="Object.assign(guardianData, $event)"
                            @update:errors="guardianErrors = $event"
                        />
                    </section>

                    <section class="px-6" ref="step4">
                        <AssessmentForm
                            :model="assessmentData"
                            :errors="assessmentErrors"
                            @update:model="
                                Object.assign(assessmentData, $event)
                            "
                            @update:errors="assessmentErrors = $event"
                        />
                    </section>
                </main>

                <aside class="hidden lg:block">
                    <div class="sticky top-8 space-y-5">
                        <div class="px-6 py-6 border-b">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                            >
                                Completion
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <div
                                    class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary transition-all duration-300"
                                        :style="{ width: `${progress}%` }"
                                    ></div>
                                </div>
                                <span
                                    class="text-xs font-medium text-gray-400 shrink-0"
                                >
                                    {{ Math.round(progress) }}%
                                </span>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto px-3 py-4">
                            <BookingSteps
                                :active="activeStep"
                                :completed="completedSteps"
                                @go="scrollTo"
                            />
                        </div>

                        <BaseButton class="w-full py-3" @click="submit">
                            Submit Admission
                        </BaseButton>
                    </div>
                </aside>
            </div>

            <div class="lg:hidden sticky bottom-0 left-0 right-0 border-t p-4">
                <BaseButton class="w-full py-3" @click="submit">
                    Submit Admission
                </BaseButton>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { computed, ref, onMounted, toRaw } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useBookingFlowValidation } from "~/composables/useBookingFlowValidation";

import AdmissionDetail from "~/components/sections/app/Admission/AdmissionDetail.vue";
import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";

import GuardianForm from "~/components/forms/GuardianForm.vue";
import PatientForm from "~/components/forms/PatientForm.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";

import {
    patientData,
    createPatientSchema,
    assessmentData,
    assessmentSchema,
    guardianData,
    guardianSchema,
} from "~/types/patient";

import {
    homecareData,
    createHomecareBookingSchema,
    facilityData,
    facilityBookingSchema,
} from "~/types/booking";

import { bookingService } from "~/api/booking/BookingService";
import { useBookingStore, mapBookingResponse } from "~/stores/booking";
import { useBranch } from "~/composables/useBranchProvider";
import { branchContractService } from "~/api/branch-contract/BranchContractService";
import type { RoomContract, reservedSchema } from "~/types/contract";
import { reserved } from "~/types/contract";

useHead({ title: "Admission" });

definePageMeta({
    layout: "dashboard",
    middleware: ["auth-client"],
});

const bookingStore = useBookingStore();
const route = useRoute();
const router = useRouter();

const uuid = computed(() => route.params.uuid as string);
const { branch } = useBranch();
const loading = ref(true);
const category = computed<"homecare" | "facility">(() => "facility");
const patientSchema = computed(() => createPatientSchema(category.value));
const homecareSchema = computed(() =>
    createHomecareBookingSchema(branch.value?.homecare.adl_min_hour ?? 0),
);

const {
    facilityErrors,
    reservedErrors,
    patientErrors,
    guardianErrors,
    assessmentErrors,
    progress,
    validateAll,
    completedSteps,
} = useBookingFlowValidation({
    category,
    homecareSchema,
    facilityBookingSchema,
    patientSchema,
    guardianSchema,
    assessmentSchema,
    homecareData,
    facilityData,
    patientData,
    guardianData,
    assessmentData,
    reserved,
});

const referenceInput = ref((route.query.reference_id as string) ?? "");
const roomContract = ref<RoomContract[]>([]);
const loadingContract = ref(true);
const loadingReference = ref(false);
const referenceError = ref("");

async function loadByReference() {
    if (!referenceInput.value) return;

    loadingReference.value = true;
    referenceError.value = "";

    try {
        const [bookingResponse] = await Promise.all([
            bookingService.show(referenceInput.value, {
                branch_uuid: uuid.value,
                reference_id: referenceInput.value,
            }),
            loadRoomContracts(),
        ]);

        const mapped = mapBookingResponse(
            bookingResponse.data ?? bookingResponse,
        );

        Object.assign(patientData, mapped.patient);
        Object.assign(guardianData, mapped.guardian);
        Object.assign(assessmentData, mapped.assessment);

        if (mapped.facility) {
            Object.assign(facilityData, mapped.facility);
        }
        bookingStore.lastSubmittedId = mapped.referenceId;
    } catch (err) {
        console.error(err);

        referenceError.value =
            "Couldn't find an admission with that reference ID.";
    } finally {
        loadingReference.value = false;
    }
}
async function loadRoomContracts() {
    loadingContract.value = true;

    try {
        const response = await branchContractService.list({
            type: "room_contract",
            branch_uuid: uuid.value,
        });

        roomContract.value = response;
    } catch (err) {
        console.error("Failed loading room contracts", err);
        roomContract.value = [];
    } finally {
        loadingContract.value = false;
    }
}
onMounted(async () => {
    loading.value = true;

    try {
        if (referenceInput.value) {
            await loadByReference();
        } else {
            await loadRoomContracts();
        }
    } finally {
        loading.value = false;
    }
});

async function submit() {
    const firstInvalid = validateAll();
    if (firstInvalid) {
        scrollTo(firstInvalid);
        return;
    }
    bookingStore.contract = roomContract.value;
    bookingStore.reserved = deepToRaw(reserved.value);
    bookingStore.category = "facility";
    if (facilityData.type.toLowerCase() != "complete") {
        facilityData.type = "Walk-in Admission";
    }
    bookingStore.payment.total_amount = reserved.value.price;
    facilityData.plan = reserved.value.accommodation_type;
    facilityData.admission_date = new Date().toISOString();
    bookingStore.facility = deepToRaw(facilityData);
    bookingStore.patient = deepToRaw(patientData);
    bookingStore.guardian = deepToRaw(guardianData);
    bookingStore.assessment = deepToRaw(assessmentData);
    bookingStore.branchFacility = branch.value?.facility ?? [];

    router.push({
        path: `/app/branches/${uuid.value}/admissions/review`,
        query: {
            reference_id: referenceInput.value,
        },
    });
}

function deepToRaw<T>(val: T): T {
    if (Array.isArray(val)) {
        return val.map(deepToRaw) as any;
    }
    if (val && typeof val === "object") {
        const raw = toRaw(val);
        return Object.fromEntries(
            Object.entries(raw).map(([k, v]) => [k, deepToRaw(v)]),
        ) as any;
    }
    return val;
}

const step1 = ref<HTMLElement | null>(null);
const step2 = ref<HTMLElement | null>(null);
const step3 = ref<HTMLElement | null>(null);
const step4 = ref<HTMLElement | null>(null);

const activeStep = ref("step1");

const scrollTo = (step: string) => {
    if (step === "step5") {
        submit();
        return;
    }

    activeStep.value = step;

    const map: Record<string, any> = {
        step1,
        step2,
        step3,
        step4,
    };

    map[step]?.value?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
};
</script>
