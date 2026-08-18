<template>
    <div
        class="min-h-screen grid grid-cols-1 lg:grid-cols-[280px_1fr] bg-gray-50"
    >
        <aside
            data-booking-sidebar
            class="sticky hidden w-[300px] shrink-0 flex-col overflow-hidden border-r border-gray-100 bg-gradient-to-br from-primary-50/70 via-white to-accent-50/50 lg:flex"
            :style="{
                top: `${sidebarTop}px`,
                height: `calc(100vh - ${sidebarTop}px)`,
            }"
        >
            <div class="relative z-10 flex h-full min-h-0 flex-col">
                <div class="shrink-0 border-b border-gray-100/80 px-6 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.12em] text-primary-600"
                            >
                                Booking Progress
                            </p>

                            <p class="mt-1 text-xs text-muted">
                                Complete each step to continue
                            </p>
                        </div>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/80 text-primary-500 shadow-sm ring-1 ring-primary-100"
                        >
                            <ClipboardCheck class="h-4.5 w-4.5" />
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-medium text-muted">
                                Overall progress
                            </span>

                            <span class="text-xs font-bold text-primary-600">
                                {{ Math.round(progress) }}%
                            </span>
                        </div>

                        <div
                            class="h-2 overflow-hidden rounded-full bg-white/80 shadow-inner ring-1 ring-gray-100"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary-500 to-accent-500 shadow-sm transition-all duration-500 ease-out"
                                :style="{ width: `${progress}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5">
                    <BookingSteps
                        :active="activeStep"
                        :completed="completedSteps"
                        @go="scrollTo"
                    />
                </div>

                <div class="shrink-0 border-t border-gray-100/80 px-6 py-4">
                    <div
                        class="flex items-start gap-3 rounded-xl bg-white/60 p-3 ring-1 ring-gray-100"
                    >
                        <div
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent-50 text-accent-600"
                        >
                            <Info class="h-3.5 w-3.5" />
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-secondary">
                                Need help?
                            </p>

                            <p class="mt-0.5 text-[11px] leading-4 text-muted">
                                Complete the required steps before continuing.
                            </p>
                        </div>
                    </div>
                </div>
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
                    class="scroll-mt-8 border-t rounded-t-2xl border-x border-gray-100 bg-white shadow-sm"
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
                        :homecare="branch?.homecare"
                    />
                    <FacilityBooking
                        v-else
                        :model="facilityData"
                        :errors="facilityErrors"
                        :branch="branch"
                        :loading="loading"
                        @update:model="Object.assign(facilityData, $event)"
                        @update:errors="facilityErrors = $event"
                    />

                    <div class="h-px bg-slate-200" />
                </section>

                <section
                    id="step2"
                    ref="step2"
                    class="scroll-mt-8 border-x border-gray-100 bg-white shadow-sm"
                >
                    <PatientForm
                        :category="category"
                        :model="patientData"
                        :errors="patientErrors"
                        @update:model="Object.assign(patientData, $event)"
                        @update:errors="patientErrors = $event"
                    />
                    <div class="h-px bg-slate-200" />
                </section>

                <section
                    id="step3"
                    ref="step3"
                    class="scroll-mt-8 border border-gray-100 bg-white shadow-sm"
                >
                    <GuardianForm
                        :model="guardianData"
                        :current-user="user"
                        :errors="guardianErrors"
                        @update:model="Object.assign(guardianData, $event)"
                        @update:errors="guardianErrors = $event"
                    />
                    <div class="h-px bg-slate-200" />
                </section>

                <section
                    id="step4"
                    ref="step4"
                    class="scroll-mt-8 border-x rounded-b-2xl border-b border-gray-100 bg-white shadow-sm"
                >
                    <!-- <AssessmentForm
                        :model="assessmentData"
                        @update:model="Object.assign(assessmentData, $event)"
                    /> -->
                    <AssessmentForm
                        :model="assessmentData"
                        :errors="assessmentErrors"
                        @update:model="Object.assign(assessmentData, $event)"
                        @update:errors="assessmentErrors = $event"
                    />
                </section>
            </div>

            <div
                class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm p-6"
            >
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
<script lang="ts" setup>
import { computed, ref, onMounted, toRaw } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ShieldCheck, BellRing } from "lucide-vue-next";
import { useToast } from "~/composables/useToast";
import { ClipboardCheck, Info } from "lucide-vue-next";
import { useBookingFlowValidation } from "~/composables/useBookingFlowValidation";

import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import HomecareBooking from "~/components/sections/booking/provider/HomecareBooking.vue";
import FacilityBooking from "~/components/sections/booking/provider/FacilityBooking.vue";
import GuardianForm from "~/components/forms/GuardianForm.vue";
import PatientForm from "~/components/forms/PatientForm.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";

import {
    patientData,
    createPatientSchema,
    assessmentData,
    assessmentSchema,
} from "~/schema/patient-schema";
import { guardianData, guardianSchema } from "~/schema/patient-schema";
import {
    homecareData,
    createHomecareBookingSchema,
    facilityData,
    facilityBookingSchema,
} from "~/schema/booking-schema";

import { useAuthUser } from "~/composables/useAuthUser";
import { type Service } from "~/types/service";
import { serviceService } from "~/api/service/ServiceService";
import { useBookingStore } from "~/stores/booking";
import { useBranch } from "~/composables/useBranchProvider";

useHead({ title: "Patient Details" });
definePageMeta({
    navVariant: 4,
    navTheme: "dark",
    middleware: ["auth-client", "provider-guard"],
});

const user = useAuthUser();
const bookingStore = useBookingStore();
const route = useRoute();
const router = useRouter();
const uuid = computed(() => route.params.branch_uuid as string);

const { branch, fetchBranch } = useBranch();
const loading = ref(true);

const category = computed<"homecare" | "facility">(() =>
    route.query.category === "facility" ? "facility" : "homecare",
);

const serviceData = ref<Service[]>([]);

const homecareSchema = computed(() =>
    createHomecareBookingSchema(branch.value?.homecare.adl_min_hour ?? 0),
);
const validationMode = ref<"facility" | "reserved">("facility");
const patientSchema = computed(() => createPatientSchema(category.value));

const {
    homecareErrors,
    facilityErrors,
    patientErrors,
    guardianErrors,
    assessmentErrors,
    progress,
    completedSteps,
    validateAll,
} = useBookingFlowValidation({
    category,
    validationMode,
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
});

onMounted(async () => {
    loading.value = true;
    bookingStore.clear();
    try {
        if (
            !branch.value?.homecare ||
            !branch.value.facility ||
            !branch.value
        ) {
            await fetchBranch(uuid.value);
        }
        await Promise.all([loadServices()]);
        if (route.query.step) {
            scrollTo(route.query.step as string);
        }
        bookingStore.clear();
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
});

const loadServices = async () => {
    const res: any = await serviceService.getBranchService(uuid.value);
    serviceData.value = res?.services ?? res?.data?.services ?? [];
};

async function submit() {
    const firstInvalid = validateAll();

    if (firstInvalid) {
        scrollTo(firstInvalid);
        return;
    }

    bookingStore.category = category.value;
    bookingStore.homecare = structuredClone(toRaw(homecareData));
    bookingStore.facility = structuredClone(toRaw(facilityData));
    bookingStore.patient = structuredClone(toRaw(patientData));
    bookingStore.guardian = structuredClone(toRaw(guardianData));
    bookingStore.assessment = structuredClone(toRaw(assessmentData));
    bookingStore.services = serviceData.value;
    bookingStore.branchHomecare = branch.value?.homecare ?? {};
    bookingStore.branchFacility = branch.value?.facility ?? [];

    router.push(
        `/booking/provider/${uuid.value}/review?category=${category.value}`,
    );
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
    const map: Record<string, any> = { step1, step2, step3, step4 };
    map[step]?.value?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const sidebarTop = ref(90);

const handleScroll = () => {
    const sidebar = document.querySelector(
        "[data-booking-sidebar]",
    ) as HTMLElement | null;

    if (!sidebar) return;

    const top = sidebar.getBoundingClientRect().top;

    if (top <= 90) {
        sidebarTop.value = 0;
    } else {
        sidebarTop.value = 90;
    }
};

onMounted(() => {
    window.addEventListener("scroll", handleScroll, {
        passive: true,
    });

    handleScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener("scroll", handleScroll);
});
</script>
