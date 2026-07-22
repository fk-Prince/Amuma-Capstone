<template>
    <div
        class="min-h-screen grid grid-cols-1 lg:grid-cols-[280px_1fr] bg-gray-50"
    >
        <aside
            class="hidden lg:flex flex-col bg-white border-r sticky top-0 h-screen"
        >
            <div class="px-6 py-6 border-b">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                >
                    Booking Progress
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
                    <span class="text-xs font-medium text-gray-400 shrink-0">
                        {{ Math.round(progress) }}%
                    </span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-4">
                <BookingSteps :active="activeStep" @go="scrollTo" />
            </div>
        </aside>

        <main class="px-5 sm:px-10 py-8 max-w-7xl w-full mx-auto lg:mx-0">
            <header class="mb-8">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-primary"
                >
                    {{
                        category === "facility"
                            ? "Facility Admission"
                            : "Homecare Services"
                    }}
                </p>
                <h1 class="font-serif text-3xl text-gray-900 mt-1">
                    Patient Booking Request
                </h1>
                <p class="text-[15px] text-gray-500 mt-2 leading-relaxed">
                    Complete the form below to submit your request. Fields
                    marked with
                    <span class="text-red-500">*</span> are required.
                </p>
            </header>

            <div class="lg:hidden mb-6">
                <div class="flex items-center gap-2">
                    <div
                        class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                    <span class="text-xs font-medium text-gray-400 shrink-0">
                        {{ Math.round(progress) }}%
                    </span>
                </div>
            </div>

            <div class="flex flex-col">
                <section
                    id="step1"
                    ref="step1"
                    class="scroll-mt-8 rounded-2xl bg-white p-6"
                >
                    <HomecareBooking
                        v-if="category === 'homecare'"
                        :model="homecareData"
                        :services="serviceData"
                        :loading="loading"
                        :errors="homecareErrors"
                        @update:model="Object.assign(homecareData, $event)"
                        @update:errors="homecareErrors = $event"
                        :settings="branch?.settings"
                    />
                    <FacilityBooking
                        v-else
                        :model="facilityData"
                        :errors="facilityErrors"
                        @update:model="Object.assign(facilityData, $event)"
                        @update:errors="facilityErrors = $event"
                    />
                </section>

                <section
                    id="step2"
                    ref="step2"
                    class="scroll-mt-8 rounded-2xl bg-white p-6"
                >
                    <PatientForm
                        :model="patientData"
                        :errors="patientErrors"
                        @update:model="Object.assign(patientData, $event)"
                        @update:errors="patientErrors = $event"
                    />
                </section>

                <section
                    id="step3"
                    ref="step3"
                    class="scroll-mt-8 rounded-2xl bg-white p-6"
                >
                    <GuardianForm
                        :model="guardianData"
                        :current-user="user"
                        :errors="guardianErrors"
                        @update:model="Object.assign(guardianData, $event)"
                        @update:errors="guardianErrors = $event"
                    />
                </section>

                <section
                    id="step4"
                    ref="step4"
                    class="scroll-mt-8 rounded-2xl bg-white p-6"
                >
                    <AssessmentForm
                        :model="assessmentData"
                        @update:model="Object.assign(assessmentData, $event)"
                    />
                </section>
            </div>

            <div class="rounded-2xl bg-white p-6">
                <BaseButton
                    variant="primary"
                    class="w-full rounded-xl py-3"
                    @click="submit"
                >
                    Submit Booking Registration
                </BaseButton>

                <div class="mt-4 flex flex-col gap-3">
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500"
                    >
                        <ShieldCheck
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            All patient information is kept confidential and
                            used only to provide the best care possible.
                        </span>
                    </div>
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500"
                    >
                        <BellRing
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            You'll be notified once your booking request has
                            been reviewed and accepted.
                        </span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { ShieldCheck, BellRing } from "lucide-vue-next";

import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import HomecareBooking from "~/components/sections/booking/provider/HomecareBooking.vue";
import FacilityBooking from "~/components/sections/booking/provider/FacilityBooking.vue";
import GuardianForm from "~/components/forms/GuardianForm.vue";
import PatientForm from "~/components/forms/PatientForm.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";

import { patientData, patientSchema, assessmentData } from "~/types/patient";
import { guardianData, guardianSchema } from "~/types/patient";
import {
    homecareData,
    createHomecareBookingSchema,
    facilityData,
    facilityBookingSchema,
} from "~/types/booking";

import { useAuthUser } from "~/composables/useAuthUser";
import { bookingService } from "~/api/booking/BookingService";
import { type Service } from "~/types/service";
import { serviceService } from "~/api/service/ServiceService";
import type { BranchRetrieve } from "~/types/branch";
import { branchService } from "~/api/branch/BranchService";

useHead({ title: "Patient Details" });
definePageMeta({ navVariant: 1 });

const user = useAuthUser();
const loading = ref(true);
const route = useRoute();
const uuid = computed(() => route.params.branch_uuid as string);

const category = computed<"homecare" | "facility">(() =>
    route.query.category === "facility" ? "facility" : "homecare",
);

const branch = ref<BranchRetrieve | null>(null);
const serviceData = ref<Service[]>([]);

onMounted(async () => {
    loading.value = true;

    try {
        const [branchRes, serviceRes]: any = await Promise.all([
            branchService.get(uuid.value),
            serviceService.getBranchService(uuid.value),
        ]);
        // const serviceRes: any = await serviceService.getBranchService(uuid.value);

        branch.value = branchRes;
        serviceData.value = serviceRes.services;
    } catch (err: any) {
        console.error(err);
    } finally {
        loading.value = false;
    }
});

const schema = computed(() =>
    createHomecareBookingSchema(branch.value?.settings?.adl_min_hour ?? 0),
);

async function submit() {
    const step1Ok =
        category.value === "facility" ? validateFacility() : validateHomecare();
    const patientOk = validatePatient();
    const guardianOk = validateGuardian();

    if (!step1Ok || !patientOk || !guardianOk) {
        const firstInvalid = !step1Ok
            ? "step1"
            : !patientOk
              ? "step2"
              : "step3";
        scrollTo(firstInvalid);
        return;
    }

    const booking_data = {
        service: category.value === "facility" ? facilityData : homecareData,
        patient: patientData,
        guardian: guardianData,
        assessment: assessmentData,
    };

    console.log(booking_data);
    try {
        const res = await bookingService.create({
            branch_uuid: route.params.branch_uuid,
            category: category.value,
            booking_data: booking_data,
        });
        console.log(res);
        alert("Request submitted successfully!");
    } catch (err: any) {
        console.error(err);
        alert("Something went wrong while submitting your request.");
    }
}

const step1Valid = computed(() =>
    category.value === "facility"
        ? facilityBookingSchema.safeParse(facilityData).success
        : schema.value.safeParse(homecareData).success,
);
const step2Valid = computed(() => patientSchema.safeParse(patientData).success);
const step3Valid = computed(
    () => guardianSchema.safeParse(guardianData).success,
);

const canSubmit = computed(
    () => step1Valid.value && step2Valid.value && step3Valid.value,
);

const progress = computed(() => {
    const flags = [step1Valid.value, step2Valid.value, step3Valid.value];
    const done = flags.filter(Boolean).length;
    return (done / flags.length) * 100;
});

const step1 = ref<HTMLElement | null>(null);
const step2 = ref<HTMLElement | null>(null);
const step3 = ref<HTMLElement | null>(null);
const step4 = ref<HTMLElement | null>(null);
const activeStep = ref("step1");

const scrollTo = (step: string) => {
    const map: Record<string, any> = { step1, step2, step3, step4 };
    map[step]?.value?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const homecareErrors = ref<Record<string, string>>({});
const facilityErrors = ref<Record<string, string>>({});
const patientErrors = ref<Record<string, string>>({});
const guardianErrors = ref<Record<string, string>>({});

function flattenErrors(fieldErrors: Record<string, string[] | undefined>) {
    return Object.fromEntries(
        Object.entries(fieldErrors).map(([key, messages]) => [
            key,
            messages?.[0] ?? "Invalid value",
        ]),
    );
}

function validateHomecare(): boolean {
    const result = schema.value.safeParse(homecareData);
    if (result.success) {
        homecareErrors.value = {};
        return true;
    }

    homecareErrors.value = flattenErrors(result.error.flatten().fieldErrors);
    return false;
}

function validateFacility(): boolean {
    const result = facilityBookingSchema.safeParse(facilityData);
    if (result.success) {
        facilityErrors.value = {};
        return true;
    }

    facilityErrors.value = flattenErrors(result.error.flatten().fieldErrors);
    return false;
}

function validatePatient(): boolean {
    const result = patientSchema.safeParse(patientData);
    if (result.success) {
        patientErrors.value = {};
        return true;
    }

    patientErrors.value = flattenErrors(result.error.flatten().fieldErrors);
    return false;
}

function validateGuardian(): boolean {
    const result = guardianSchema.safeParse(guardianData);
    if (result.success) {
        guardianErrors.value = {};
        return true;
    }

    guardianErrors.value = flattenErrors(result.error.flatten().fieldErrors);
    return false;
}
</script>
