<template>
    <div
        class="min-h-screen grid grid-cols-1 lg:grid-cols-[1fr_320px] bg-slate-50"
    >
        <main class="px-5 sm:px-10 py-8 w-full mx-auto lg:mx-0">
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

            <div
                :class="
                    showPayment
                        ? 'grid grid-cols-1 xl:grid-cols-[1fr_450px] gap-6 items-start'
                        : ''
                "
            >
                <div>
                    <div
                        class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm"
                    >
                        <AdmissionReview
                            :reserved="bookingStore.reserved"
                            :roomContract="bookingStore.contract"
                            :patient="bookingStore.patient"
                            :guardian="bookingStore.guardian"
                            :assessment="bookingStore.assessment"
                            :payment="bookingStore.payment"
                            @edit-step="goEditStep"
                        />
                    </div>

                    <div
                        v-if="requiresReservation && !hasReservation"
                        class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3"
                    >
                        <span
                            class="h-8 w-8 shrink-0 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 text-sm font-semibold"
                        >
                            !
                        </span>
                        <div>
                            <p class="text-sm font-medium text-amber-800">
                                No room or bed reserved
                            </p>
                            <p class="text-xs text-amber-700 mt-0.5">
                                You need to select an accommodation and bed
                                before you can proceed to payment.
                            </p>
                            <button
                                type="button"
                                class="mt-2 text-xs font-medium text-amber-800 underline hover:no-underline"
                                @click="goEditStep('step2')"
                            >
                                Go back and select a room
                            </button>
                        </div>
                    </div>

                    <div
                        class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm p-6"
                    >
                        <BaseButton
                            variant="primary"
                            class="w-full rounded-xl py-3"
                            :disabled="
                                submitting ||
                                (requiresReservation && !hasReservation)
                            "
                            @click="handleSubmit"
                        >
                            {{ submitting ? "Submitting..." : actionLabel }}
                        </BaseButton>
                    </div>
                </div>
            </div>
        </main>

        <aside
            class="hidden lg:flex flex-col bg-white border-r sticky top-0 h-screen"
        >
            <div class="px-6 py-6 border-b">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                >
                    Admission Progress
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
                <BookingSteps
                    active="step5"
                    :completed="completedSteps"
                    @go="goEditStep"
                />
            </div>
        </aside>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import { useBookingStore } from "~/stores/booking";
import type { CardDetails } from "~/types/payment";
import AdmissionReview from "~/components/sections/app/Admission/AdmissionReview.vue";
import { admissionService } from "~/api/admission/AdmissionService";
useHead({ title: "Review Admission" });

definePageMeta({
    navVariant: 1,
    layout: "dashboard",
    // middleware: ["auth-client", "booking-review-guard"],
    middleware: ["auth-client"],
});

const actionLabel = computed(() => {
    // if (
    //     route.query.reference_id &&
    //     bookingStore.payment?.payment_status === "paid"
    // ) {
    //     return "Admit Patient";
    // }
    return "Confirm & Submit Admission";
});

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();
const toast = useToast();
const submitting = ref(false);
const uuid = route.params.uuid as string;

const card = reactive<CardDetails>({
    number: "4000000000002503",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const showPayment = computed(
    () =>
        bookingStore.category === "facility" &&
        bookingStore.facility?.type === "Complete",
);

const requiresReservation = computed(
    () =>
        bookingStore.category === "facility" &&
        bookingStore.facility?.type === "Complete",
);

const hasReservation = computed(
    () => !!bookingStore.reserved?.room && !!bookingStore.reserved?.bed,
);

const completedSteps = computed(() => ["step1", "step2", "step3", "step4"]);
const progress = computed(() => 100);

function goEditStep(step: string) {
    router.push({
        path: `/app/branches/${uuid}/admissions`,
        query: {
            ...route.query,
            step,
        },
    });
}

const bookingData = computed(() => ({
    homecare: bookingStore.homecare,
    facility: bookingStore.facility,
    patient: bookingStore.patient,
    guardian: bookingStore.guardian,
    assessment: bookingStore.assessment,
    reserved: bookingStore.reserved,
    payment: bookingStore.payment,
}));

async function handleSubmit() {
    if (submitting.value) return;

    if (requiresReservation.value && !hasReservation.value) {
        toast.error(
            "Please select a room and bed before submitting your admission.",
        );
        return;
    }

    submitting.value = true;

    try {
        const res = await admissionService.create({
            reference_id: route.query.reference_id ?? "",
            branch_uuid: uuid,
            ...bookingData.value,
        });
        toast.success(
            res.message ?? "Your admission request was submitted successfully!",
        );
        bookingStore.$reset();
        router.push(`/app/branches/${uuid}/admissions/`);
    } catch (err: any) {
        console.error(err);
        toast.error(
            err.message ??
                "Something went wrong while submitting your admission request.",
        );
    } finally {
        submitting.value = false;
    }
}
</script>
