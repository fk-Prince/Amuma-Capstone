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
                <BookingSteps
                    active="step5"
                    :completed="completedSteps"
                    @go="goEditStep"
                />
            </div>
        </aside>

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
                        class="rounded-2xl border border-gray-100 bg-white shadow-sm"
                    >
                        <ReviewSection
                            :category="bookingStore.category"
                            :homecare="bookingStore.homecare"
                            :facility="bookingStore.facility"
                            :patient="bookingStore.patient"
                            :guardian="bookingStore.guardian"
                            :assessment="bookingStore.assessment"
                            :services="bookingStore.services"
                            :branchHomecare="
                                bookingStore.branchHomecare ?? undefined
                            "
                            :branchFacility="
                                bookingStore.branchFacility ?? undefined
                            "
                            @edit-step="goEditStep"
                        />
                    </div>

                    <!-- v-if="!showPayment" -->
                    <div
                        class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm p-6"
                    >
                        <BaseButton
                            variant="primary"
                            class="w-full rounded-xl py-3"
                            :disabled="submitting"
                            @click="handleSubmit"
                        >
                            {{
                                submitting
                                    ? "Submitting..."
                                    : "Confirm & Submit Booking"
                            }}
                        </BaseButton>
                    </div>
                </div>

                <!-- <div v-if="showPayment" class="xl:sticky xl:top-8">
                    <div
                        class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6"
                    >
                        <PaymentForm
                            :card="card"
                            :processing="processingPayment"
                            title="Complete Your Booking"
                            description="Choose your payment method to confirm your facility reservation."
                            submit-label="Confirm & Pay"
                            processing-label="Confirming payment..."
                            gcash-label="Pay with GCash"
                            gcash-processing-label="Redirecting to GCash..."
                            @card-pay="handleCardPay"
                            @g-cash-pay="handleGCashPay"
                        />
                    </div>
                </div> -->
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import ReviewSection from "~/components/sections/booking/provider/ReviewSection.vue";
import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import { useBookingStore } from "~/stores/booking";
import { bookingService } from "~/api/booking/BookingService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import type { CardDetails } from "~/types/payment";

useHead({ title: "Review Booking" });
definePageMeta({
    navVariant: 1,
    // middleware: ["auth-client", "booking-review-guard"],
    middleware: ["auth-client"],
});

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();
const toast = useToast();
const submitting = ref(false);
const processingPayment = ref(false);
const closeModal = ref<(() => void) | null>(null);
const uuid = route.params.branch_uuid as string;

onMounted(() => {
    if (!bookingStore.category) {
        router.replace(
            `/booking/provider/${uuid}?category=${route.query.category ?? "homecare"}`,
        );
    }
});

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
const completedSteps = computed(() => ["step1", "step2", "step3", "step4"]);
const progress = computed(() => 100);

function goEditStep(step: string) {
    router.push({
        path: `/booking/provider/${uuid}/details`,
        query: { category: bookingStore.category, step },
    });
}
const bookingData = computed(() => ({
    service:
        bookingStore.category === "facility"
            ? bookingStore.facility
            : bookingStore.homecare,
    patient: bookingStore.patient,
    guardian: bookingStore.guardian,
    assessment: bookingStore.assessment,
}));

// async function handleCardPay() {
//     if (processingPayment.value) return;
//     processingPayment.value = true;
//     try {
//         await cardPayment({
//             card,
//             amount: 5000, // TODO: to be change
//             onClose: () => {
//                 processingPayment.value = false;
//             },

//             createPayment: ({ token_id, authentication_id }) =>
//                 bookingService.facilityBooking({
//                     branch_uuid: uuid,
//                     token_id,
//                     authentication_id,
//                     booking_data: bookingData.value,
//                     payment_method: "CREDIT-CARD",
//                     category: bookingStore.category,
//                 }),

//             onSuccess: async (result) => {
//                 // await navigateTo({
//                 //     path: `/booking/provider/${uuid}/success`,
//                 //     query: {
//                 //         status: result.status,
//                 //     },
//                 // });
//             },
//         });
//     } catch (err: any) {
//         toast.error(err?.message ?? "Payment failed.");
//     } finally {
//         processingPayment.value = false;
//     }
// }

// async function handleGCashPay() {
//     if (processingPayment.value) return;
//     processingPayment.value = true;
//     try {
//         await gcashPayment({
//             closeModal,

//             createPayment: () =>
//                 bookingService.facilityBooking({
//                     branch_uuid: uuid,
//                     booking_data: bookingData.value,
//                     payment_method: "GCASH",
//                     category: bookingStore.category,
//                     payment_type: "BOOKING_FACILITY",
//                 }),

//             onSuccess: async (result) => {
//                 await navigateTo({
//                     path: "/subscription/success",
//                     query: {
//                         status: result.status,
//                     },
//                 });
//             },

//             onClose: () => {
//                 processingPayment.value = false;
//             },
//         });
//     } catch (err: any) {
//         console.error(err);
//     } finally {
//         processingPayment.value = false;
//     }
// }

async function handleSubmit() {
    if (submitting.value) return;
    submitting.value = true;

    const booking_data = {
        service:
            bookingStore.category === "facility"
                ? bookingStore.facility
                : bookingStore.homecare,
        patient: bookingStore.patient,
        guardian: bookingStore.guardian,
        assessment: bookingStore.assessment,
    };

    try {
        const res = await bookingService.create({
            branch_uuid: uuid,
            category: bookingStore.category,
            booking_data,
        });
        toast.success(res.message);
        router.push({
            path: `/booking/provider/${uuid}/success`,
            // query: {
            //     message: res.message,
            // },
        });
    } catch (err: any) {
        toast.error(
            err.message ??
                "Something went wrong while submitting your request.",
        );
    } finally {
        submitting.value = false;
    }
}
</script>
