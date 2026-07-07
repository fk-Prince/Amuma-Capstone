<template>
    <div
        class="min-h-screen grid grid-cols-1 lg:grid-cols-[280px_1fr] bg-gray-50"
    >
        <aside
            class="hidden lg:block bg-white border-r px-4 py-6 sticky top-0 h-screen"
        >
            <BookingSteps @go="scrollTo" />
        </aside>

        <main class="px-10 py-8 max-w-7xl">
            <header class="mb-10">
                <h1 class="font-serif text-3xl text-secondary">
                    Patient Booking Request Form
                </h1>
                <p class="text-[15px] text-muted mt-2">
                    Please complete the fillup form below. Fields marked with
                    <span class="text-danger">*</span> are required.
                </p>
            </header>

            <div class="space-y-6">
                <div ref="step1">
                    <HomecareBooking
                        v-if="category === 'homecare'"
                        :model="homecareData"
                        :services="serviceData"
                        :loading="loading"
                        @update:model="Object.assign(homecareData, $event)"
                    />
                    <FacilityBooking
                        v-else
                        :model="facilityData"
                        @update:model="Object.assign(facilityData, $event)"
                    />
                </div>
                <div ref="step2">
                    <PatientForm
                        :model="patientData"
                        @update:model="Object.assign(patientData, $event)"
                    />
                </div>

                <div ref="step3">
                    <GuardianForm
                        :model="guardianData"
                        :current-user="user"
                        @update:model="Object.assign(guardianData, $event)"
                    />
                </div>

                <div ref="step4">
                    <AssessmentForm
                        :model="assessmentData"
                        @update:model="Object.assign(assessmentData, $event)"
                    />
                </div>
            </div>

            <div class="flex flex-col justify-end mt-8">
                <BaseButton
                    variant="primary"
                    class="px-6 rounded-md"
                    :disabled="!canSubmit"
                    @click="submit"
                >
                    Submit Booking Registration
                </BaseButton>

                <p class="mt-2 text-[12px] flex gap-2 items-center">
                    <span class="flex h-8 w-8 items-center justify-center">
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#3182ED"
                            stroke-width="2"
                        >
                            <path
                                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                            />
                        </svg>
                    </span>
                    All patient information is kept confidential and will only
                    be used to provide the best care possible.
                </p>

                <p class="mt-2 text-[12px] flex gap-2 items-center">
                    <span class="flex h-8 w-8 items-center justify-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#3182ED"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"
                            />
                            <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                        </svg>
                    </span>
                    You will be notified once your booking request has been
                    reviewed and accepted.
                </p>
            </div>
        </main>
    </div>
</template>
<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { useRoute } from "vue-router";

import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import HomecareBooking from "~/components/sections/booking/provider/HomecareBooking.vue";
import FacilityBooking from "~/components/sections/booking/provider/FacilityBooking.vue";
import GuardianForm from "~/components/forms/GuardianForm.vue";
import PatientForm from "~/components/forms/PatientForm.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";

import { patientData, patientSchema, assessmentData } from "~/types/patient";
import { guardianData, guardianSchema } from "~/types/auth";
import {
    homecareData,
    homecareBookingSchema,
    facilityData,
    facilityBookingSchema,
} from "~/types/booking";

import { useAuthUser } from "~/composables/useAuthUser";
import { bookingService } from "~/api/booking/BookingService";
import { type Service } from "~/types/service";
import { serviceService } from "~/api/service/ServiceService";
useHead({ title: "Patient Details" });
definePageMeta({ navVariant: 1 });
const user = useAuthUser();
const loading = ref(true);
const route = useRoute();

const category = computed<"homecare" | "facility">(() =>
    route.query.category === "facility" ? "facility" : "homecare",
);

const canSubmit = computed(() => {
    const serviceCheck =
        category.value === "facility"
            ? facilityBookingSchema.safeParse(facilityData)
            : homecareBookingSchema.safeParse(homecareData);

    return (
        serviceCheck.success &&
        patientSchema.safeParse(patientData).success &&
        guardianSchema.safeParse(guardianData).success
    );
});
const serviceData = ref<Service[]>([]);

onMounted(async () => {
    try {
        loading.value = true;
        const res: any = await serviceService.getBranchService(
            route.params.branch_uuid as string,
        );
        serviceData.value = res.services;
    } catch (err: any) {
        console.error(err);
    } finally {
        loading.value = false;
    }
});

async function submit() {
    if (!canSubmit.value) {
        alert("Please complete all required fields before submitting.");
        return;
    }

    const booking_data = {
        service: category.value === "facility" ? facilityData : homecareData,
        patient: patientData,
        guardian: guardianData,
        assessment: assessmentData,
    };

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

const step1 = ref<HTMLElement | null>(null);
const step2 = ref<HTMLElement | null>(null);
const step3 = ref<HTMLElement | null>(null);
const step4 = ref<HTMLElement | null>(null);

const scrollTo = (step: string) => {
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
