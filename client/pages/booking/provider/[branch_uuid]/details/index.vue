<template>
    <div
        class="min-h-screen lg:h-[calc(100dvh-126px)] lg:min-h-0 lg:overflow-hidden"
    >
    <div
        class="mx-auto grid w-[94%] grid-cols-1 gap-5 pb-6 pt-2 lg:h-full lg:min-h-0 lg:w-[88%] lg:max-w-[1600px] lg:grid-cols-[300px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)] lg:pb-4 lg:pt-1"
    >
        <aside
            data-booking-sidebar
            class="hidden flex-col overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-br from-primary-50/70 via-white to-accent-50/50 shadow-sm lg:flex lg:h-full lg:min-h-0 dark:border-white/10 dark:bg-none dark:bg-secondary"
        >
            <div class="relative z-50 flex h-full min-h-0 flex-col">
                <div
                    class="shrink-0 border-b border-gray-100/80 px-5 py-4 dark:border-white/10"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.12em] text-primary-600 dark:text-primary-300"
                            >
                                Booking Progress
                            </p>

                            <p
                                class="mt-1 text-xs text-muted dark:text-gray-400"
                            >
                                Complete each step to continue
                            </p>
                        </div>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/80 text-primary-500 shadow-sm ring-1 ring-primary-100 dark:bg-white/10 dark:ring-primary-500/20 dark:text-primary-300"
                        >
                            <ClipboardCheck class="h-4.5 w-4.5" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-xs font-medium text-muted dark:text-gray-400"
                            >
                                Overall progress
                            </span>

                            <span
                                class="text-xs font-bold text-primary-600 dark:text-primary-300"
                            >
                                {{ Math.round(progress) }}%
                            </span>
                        </div>

                        <div
                            class="h-2 overflow-hidden rounded-full bg-white/80 shadow-inner ring-1 ring-gray-100 dark:bg-white/10 dark:ring-white/10"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary-500 to-accent-500 shadow-sm transition-all duration-500 ease-out"
                                :style="{ width: `${progress}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3">
                    <BookingSteps
                        compact
                        :active="activeStep"
                        :completed="completedSteps"
                        @go="scrollTo"
                    />
                </div>

                <div
                    class="shrink-0 border-t border-gray-100/80 px-5 py-3 dark:border-white/10"
                >
                    <div
                        class="flex items-start gap-3 rounded-xl bg-white/60 p-3 ring-1 ring-gray-100 dark:bg-white/5 dark:ring-white/10"
                    >
                        <div
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent-50 text-accent-600 dark:bg-accent-500/10"
                        >
                            <Info class="h-3.5 w-3.5" />
                        </div>

                        <div>
                            <p
                                class="text-xs font-semibold text-secondary dark:text-white"
                            >
                                Need help?
                            </p>

                            <p
                                class="mt-0.5 text-[11px] leading-4 text-muted dark:text-gray-400"
                            >
                                Complete the required steps before continuing.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main
            class="flex min-w-0 flex-col pt-2 lg:h-full lg:min-h-0 lg:overflow-hidden lg:rounded-2xl lg:border lg:border-gray-100 lg:bg-white lg:pt-0 lg:shadow-sm dark:lg:border-white/10 dark:lg:bg-secondary"
        >
            <div
                class="lg:shrink-0 lg:border-b lg:border-gray-100 lg:px-6 lg:py-4 dark:lg:border-white/10"
            >
            <Breadcrumb
                class="mb-5 lg:mb-2"
                :items="[
                    { label: 'Find a Provider', to: '/booking/search' },
                    {
                        label: branch?.name ?? 'Provider',
                        to: `/booking/provider/${uuid}`,
                    },
                    { label: 'Booking Details' },
                ]"
            />

            <header class="mb-6 lg:mb-0">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-primary"
                >
                    {{
                        category === "facility"
                            ? "Facility Admission"
                            : "Homecare Services"
                    }}
                </p>
                <h1
                    class="font-serif text-3xl text-gray-900 mt-1 lg:text-2xl dark:text-white"
                >
                    Patient Booking Request
                </h1>
                <p
                    class="text-[15px] text-gray-500 mt-2 leading-relaxed lg:mt-1 lg:text-sm dark:text-gray-400"
                >
                    Complete the form below to submit your request. Fields
                    marked with
                    <span class="text-red-500">*</span> are required.
                </p>
            </header>
            </div>

            <div
                class="sticky top-0 z-[50] mb-3 bg-gray-50/95 py-3 backdrop-blur-sm lg:hidden dark:bg-secondary/95"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl border border-gray-100 bg-white px-4 py-3 text-left shadow-sm dark:bg-secondary dark:border-white/10"
                    @click="stepsSheetOpen = true"
                >
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                    >
                        <ClipboardCheck class="h-4 w-4" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="text-xs font-semibold text-gray-900 dark:text-white"
                            >
                                Booking Progress
                            </span>
                            <span
                                class="text-xs font-bold text-primary-600 dark:text-primary-300"
                            >
                                {{ Math.round(progress) }}%
                            </span>
                        </div>

                        <div
                            class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                        >
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-300"
                                :style="{ width: `${progress}%` }"
                            />
                        </div>
                    </div>

                    <ChevronRight
                        class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500"
                    />
                </button>
            </div>

            <Teleport to="body">
                <Transition name="sheet-fade">
                    <div
                        v-if="stepsSheetOpen"
                        class="fixed inset-0 z-[999] bg-gray-900/50 lg:hidden"
                        @click.self="stepsSheetOpen = false"
                    >
                        <Transition name="sheet-slide" appear>
                            <div
                                class="fixed inset-x-0 bottom-0 max-h-[80vh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] dark:bg-secondary"
                            >
                                <div
                                    class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-gray-200 dark:bg-white/20"
                                />

                                <div
                                    class="mb-4 flex items-center justify-between"
                                >
                                    <p
                                        class="text-base font-semibold text-gray-900 dark:text-white"
                                    >
                                        Booking Progress
                                    </p>

                                    <button
                                        type="button"
                                        class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400"
                                        aria-label="Close"
                                        @click="stepsSheetOpen = false"
                                    >
                                        <X class="h-5 w-5" />
                                    </button>
                                </div>

                                <BookingSteps
                                    :active="activeStep"
                                    :completed="completedSteps"
                                    @go="goToStepMobile"
                                />
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>

            <div
                ref="scrollEl"
                class="lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:px-4 lg:py-4"
            >
            <div class="flex flex-col [&>section>section]:!p-4 sm:[&>section>section]:!p-6">
                <section
                    id="step1"
                    ref="step1"
                    class="scroll-mt-28 lg:scroll-mt-8 border-t rounded-t-2xl border-x border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10 lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none dark:lg:bg-transparent"
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
                        show-downpayment
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

                    <div class="h-px bg-slate-200 dark:bg-white/10" />
                </section>

                <section
                    id="step2"
                    ref="step2"
                    class="scroll-mt-28 lg:scroll-mt-8 border-x border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10 lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none dark:lg:bg-transparent"
                >
                    <PatientForm
                        :category="category"
                        :model="patientData"
                        :errors="patientErrors"
                        @update:model="Object.assign(patientData, $event)"
                        @update:errors="patientErrors = $event"
                    />
                    <div class="h-px bg-slate-200 dark:bg-white/10" />
                </section>

                <section
                    id="step3"
                    ref="step3"
                    class="scroll-mt-28 lg:scroll-mt-8 border border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10 lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none dark:lg:bg-transparent"
                >
                    <GuardianForm
                        :model="guardianData"
                        :current-user="user"
                        :errors="guardianErrors"
                        @update:model="Object.assign(guardianData, $event)"
                        @update:errors="guardianErrors = $event"
                    />
                    <div class="h-px bg-slate-200 dark:bg-white/10" />
                </section>

                <section
                    id="step4"
                    ref="step4"
                    class="scroll-mt-28 lg:scroll-mt-8 border-x border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10 lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none dark:lg:bg-transparent"
                >
                    <DiagnosisForm
                        :model="diagnosisData"
                        :charges="branch?.diagnosis_case_prices"
                        :charge-notice="
                            category === 'facility' &&
                            !!branch?.facility?.length
                        "
                        :errors="assessmentErrors"
                        @update:model="
                            diagnosisData.splice(
                                0,
                                diagnosisData.length,
                                ...$event,
                            )
                        "
                        @update:errors="assessmentErrors = $event"
                    />
                    <div class="h-px bg-slate-200 dark:bg-white/10" />
                </section>

                <section
                    id="step5"
                    ref="step5"
                    class="scroll-mt-28 lg:scroll-mt-8 border-x rounded-b-2xl border-b border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10 lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none dark:lg:bg-transparent"
                >
                    <AssessmentForm
                        :model="assessmentData"
                        :errors="assessmentErrors"
                        @update:model="
                            assessmentData.splice(
                                0,
                                assessmentData.length,
                                ...$event,
                            )
                        "
                        @update:errors="assessmentErrors = $event"
                    />
                </section>
            </div>

            <div
                class="mt-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:border-dashed lg:bg-transparent lg:shadow-none dark:bg-secondary dark:border-white/10 dark:lg:bg-transparent"
            >
                <div class="flex flex-col gap-3">
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 dark:text-gray-400"
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
                        class="flex items-start gap-3 text-[13px] text-gray-500 dark:text-gray-400"
                    >
                        <BellRing
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            We'll let you know in the app and by email as soon
                            as the branch has reviewed your booking request.
                        </span>
                    </div>
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 dark:text-gray-400"
                    >
                        <CircleAlert
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            Please double-check your details before you submit.
                            A booking can't be cancelled once it's made.
                        </span>
                    </div>
                </div>
            </div>
            </div>

            <div
                class="sticky bottom-3 z-20 mt-4 flex shrink-0 items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur lg:static lg:mt-0 lg:rounded-none lg:border-0 lg:border-t lg:bg-white lg:px-6 lg:py-3.5 lg:shadow-none lg:backdrop-blur-none dark:border-white/10 dark:bg-secondary/95 dark:lg:bg-secondary"
            >
                <p
                    class="hidden text-xs text-gray-500 sm:block dark:text-gray-400"
                >
                    Please double-check your details before you submit.
                </p>

                <BaseButton
                    variant="primary"
                    class="w-full rounded-xl px-6 py-2.5 sm:ml-auto sm:w-auto"
                    @click="submit"
                >
                    Review Booking Request
                </BaseButton>
            </div>
        </main>

        <ProviderInquiry :branch="branch" />
    </div>
    </div>
</template>
<script lang="ts" setup>
import { computed, ref, onMounted, nextTick, toRaw } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ShieldCheck, BellRing, CircleAlert } from "lucide-vue-next";
import { ClipboardCheck, Info, ChevronRight, X } from "lucide-vue-next";
import { useBookingFlowValidation } from "~/composables/useBookingFlowValidation";

import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import Breadcrumb from "~/components/ui/Breadcrumb.vue";
import HomecareBooking from "~/components/sections/booking/provider/HomecareBooking.vue";
import FacilityBooking from "~/components/sections/booking/provider/FacilityBooking.vue";
import GuardianForm from "~/components/forms/GuardianForm.vue";
import PatientForm from "~/components/forms/PatientForm.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import AssessmentForm from "~/components/forms/AssessmentForm.vue";
import DiagnosisForm from "~/components/forms/DiagnosisForm.vue";
import ProviderInquiry from "~/components/sections/booking/provider/ProviderInquiry.vue";

import {
    patientData,
    createPatientSchema,
    assessmentData,
    diagnosisData,
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
    navVariant: 6,
    navTheme: "light",
    // The page fills the screen on desktop; no footer to scroll down to.
    footer: false,
    middleware: [
        "auth-client",
        "prevent-staff-booking",
        "branch-available-guard",
        "provider-guard",
    ],
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
    diagnosisData,
});

onMounted(async () => {
    if (!route.query.step) window.scrollTo({ top: 0, behavior: "instant" });

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

    if (!route.query.step) {
        await nextTick();
        window.scrollTo({ top: 0, behavior: "instant" });
    }

    if (
        category.value === "homecare" &&
        route.query.type === "ADL" &&
        Number(branch.value?.homecare?.adl_hourly_rate ?? 0) > 0
    ) {
        await nextTick();
        homecareData.type = "ADL";
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
    bookingStore.diagnoses = structuredClone(toRaw(diagnosisData));
    bookingStore.services = serviceData.value;
    bookingStore.branchHomecare = branch.value?.homecare ?? {};
    bookingStore.branchFacility = branch.value?.facility ?? [];

    router.push(
        `/booking/provider/${uuid.value}/review?category=${category.value}`,
    );
}

const scrollEl = ref<HTMLElement | null>(null);

const step1 = ref<HTMLElement | null>(null);
const step2 = ref<HTMLElement | null>(null);
const step3 = ref<HTMLElement | null>(null);
const step4 = ref<HTMLElement | null>(null);
const step5 = ref<HTMLElement | null>(null);
const activeStep = ref("step1");

const scrollTo = async (step: string) => {
    if (step === "step6") {
        submit();
        return;
    }
    activeStep.value = step;

    await nextTick();

    setTimeout(() => {
        const map: Record<string, any> = {
            step1,
            step2,
            step3,
            step4,
        };
        const el = map[step]?.value as HTMLElement | undefined;
        if (!el) return;

        const container = scrollEl.value;
        const isDesktop = window.matchMedia("(min-width: 1024px)").matches;

        // Desktop: only the form area scrolls.
        if (isDesktop && container) {
            const top =
                el.getBoundingClientRect().top -
                container.getBoundingClientRect().top +
                container.scrollTop -
                8;

            container.scrollTo({ top, behavior: "smooth" });
            return;
        }

        // Mobile: the page scrolls; clear the progress bar.
        const top = el.getBoundingClientRect().top + window.scrollY - 112;

        window.scrollTo({ top, behavior: "smooth" });
    }, 50);
};

const stepsSheetOpen = ref(false);

function goToStepMobile(step: string) {
    scrollTo(step);
    stepsSheetOpen.value = false;
}

const stepRefs = {
    step1,
    step2,
    step3,
    step4,
    step5,
};

const stepOrder = ["step1", "step2", "step3", "step4", "step5"] as const;

const updateActiveStepFromScroll = () => {
    const container = scrollEl.value;
    const isDesktop = window.matchMedia("(min-width: 1024px)").matches;
    const base = isDesktop && container ? container.getBoundingClientRect().top : 0;
    const offset = (isDesktop ? 32 : 112) + 24;

    let current: (typeof stepOrder)[number] = "step1";

    for (const key of stepOrder) {
        const el = stepRefs[key].value;

        if (!el) continue;

        if (el.getBoundingClientRect().top - base - offset <= 0) {
            current = key;
        }
    }

    activeStep.value = current;
};

const handleScroll = () => {
    updateActiveStepFromScroll();
};

onMounted(() => {
    window.addEventListener("scroll", handleScroll, {
        passive: true,
    });
    scrollEl.value?.addEventListener("scroll", handleScroll, {
        passive: true,
    });

    handleScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener("scroll", handleScroll);
    scrollEl.value?.removeEventListener("scroll", handleScroll);
});
</script>

<style scoped>
.sheet-fade-enter-active,
.sheet-fade-leave-active {
    transition: opacity 0.2s ease;
}

.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

.sheet-slide-enter-active,
.sheet-slide-leave-active {
    transition: transform 0.25s ease-out;
}

.sheet-slide-enter-from,
.sheet-slide-leave-to {
    transform: translateY(100%);
}
</style>