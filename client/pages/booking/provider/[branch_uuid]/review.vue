<template>
    <div
        class="min-h-screen grid grid-cols-1 lg:grid-cols-[280px_1fr] bg-gray-50 dark:bg-surface"
    >
        <aside
            class="hidden lg:flex flex-col bg-white border-r sticky top-0 h-screen dark:bg-secondary dark:border-white/10"
        >
            <div class="px-6 py-6 border-b dark:border-white/10">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                >
                    Booking Progress
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <div
                        class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden dark:bg-white/10"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                    <span
                        class="text-xs font-medium text-gray-400 shrink-0 dark:text-gray-500"
                    >
                        {{ Math.round(progress) }}%
                    </span>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
                <BookingSteps
                    active="step6"
                    :completed="completedSteps"
                    @go="goEditStep"
                />
            </div>
        </aside>

        <main class="px-5 sm:px-10 py-8 w-full mx-auto lg:mx-0">
            <Breadcrumb
                class="mb-5"
                :items="[
                    { label: 'Find a Provider', to: '/booking/search' },
                    {
                        label: branch?.name ?? 'Provider',
                        to: `/booking/provider/${uuid}`,
                    },
                    {
                        label: 'Booking Details',
                        to: `/booking/provider/${uuid}/details`,
                    },
                    { label: 'Review & Submit' },
                ]"
            />

            <div class="lg:hidden mb-6">
                <div class="flex items-center gap-2">
                    <div
                        class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden dark:bg-white/10"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                    <span
                        class="text-xs font-medium text-gray-400 shrink-0 dark:text-gray-500"
                    >
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
                        class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10"
                    >
                        <ReviewSection
                            :category="bookingStore.category"
                            :homecare="bookingStore.homecare"
                            :facility="bookingStore.facility"
                            :patient="bookingStore.patient"
                            :guardian="bookingStore.guardian"
                            :assessments="bookingStore.assessment"
                            :diagnoses="bookingStore.diagnoses"
                            :services="bookingStore.services"
                            :branchHomecare="
                                bookingStore.branchHomecare ?? undefined
                            "
                            :branchFacility="
                                bookingStore.branchFacility ?? undefined
                            "
                            :showPayment="showPayment"
                            :bookingPercent="bookingPercent"
                            @edit-step="goEditStep"
                        />
                    </div>

                    <div
                        v-if="!showPayment"
                        class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm p-6 dark:bg-secondary dark:border-white/10"
                    >
                        <BaseButton
                            variant="primary"
                            class="w-full rounded-xl py-3"
                            :disabled="submitting || loadingTotal"
                            @click="handleSubmit"
                        >
                            {{
                                loadingTotal
                                    ? "Loading"
                                    : submitting
                                      ? "Submitting..."
                                      : "Confirm & Submit Booking"
                            }}
                        </BaseButton>

                        <div class="mt-4 flex flex-col gap-3">
                            <div
                                class="flex items-start gap-3 text-[13px] text-gray-500 dark:text-gray-400"
                            >
                                <ShieldCheck
                                    class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                                />

                                <span>
                                    All patient information is kept confidential
                                    and used only to provide the best care
                                    possible.
                                </span>
                            </div>

                            <div
                                class="flex items-start gap-3 text-[13px] text-gray-500 dark:text-gray-400"
                            >
                                <BellRing
                                    class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                                />

                                <span>
                                    You'll be notified in the app and by email
                                    once your booking request has been reviewed
                                    and accepted.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="showPayment" class="xl:sticky xl:top-8">
                    <div
                        class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6 dark:bg-secondary dark:border-white/10"
                    >
                        <PaymentForm
                            :card="card"
                            :total-amount="bookingAmount"
                            :processing="processingPayment || loadingTotal"
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
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from "vue";
import { BellRing, ShieldCheck } from "lucide-vue-next";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import ReviewSection from "~/components/sections/booking/provider/ReviewSection.vue";
import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import { useBookingStore } from "~/stores/booking";
import { bookingService } from "~/api/booking/BookingService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import type { CardDetails } from "~/types/payment";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import Breadcrumb from "~/components/ui/Breadcrumb.vue";
import { useBranch } from "~/composables/useBranchProvider";

useHead({ title: "Review Booking" });
definePageMeta({
    navVariant: 4,
    navTheme: "dark",
    middleware: [
        "auth-client",
        "prevent-staff-booking",
        "booking-review-guard",
    ],
});

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();
const toast = useToast();
const submitting = ref(false);
const processingPayment = ref(false);
const closeModal = ref<(() => void) | null>(null);
const uuid = route.params.branch_uuid as string;
const loadingTotal = ref(false);

const { branch, fetchBranch } = useBranch();

onMounted(async () => {
    if (!branch.value || branch.value.uuid !== uuid) {
        fetchBranch(uuid).catch(() => {});
    }
});

// Mirrors ReviewSection's own facilityTotal/ADL-rate math, so the amount
// shown here and the amount actually charged to Xendit can never diverge.
const total = computed<number>(() => {
    if (bookingStore.category === "facility") {
        const plan = bookingStore.facility?.plan?.toUpperCase();
        const billing = bookingStore.facility?.billing_cycle?.toUpperCase();

        const facility = bookingStore.branchFacility?.find(
            (item) =>
                item.accommodation_type?.toUpperCase() === plan &&
                item.billing_cycle?.toUpperCase() === billing,
        );

        return Number(facility?.price ?? 0);
    }

    if (bookingStore.homecare?.type === "ADL") {
        const hours = Number(bookingStore.homecare.time_span) || 0;
        const rate = Number(bookingStore.branchHomecare?.adl_hourly_rate ?? 0);

        return hours * rate;
    }

    return (bookingStore.homecare?.services ?? []).reduce(
        (sum, item) => sum + Number(item.price || 0),
        0,
    );
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

const bookingPercent = computed(() => {
    if (!showPayment.value) return 100;

    const settings = branch.value?.settings;
    if (!settings) return 100;

    return settings.complete_admission_booking_percent ?? 100;
});

const bookingAmount = computed(() => {
    const amount =
        Math.round(
            Number(total.value ?? 0) * (bookingPercent.value / 100) * 100,
        ) / 100;

    return amount;
});
const completedSteps = computed(() => [
    "step1",
    "step2",
    "step3",
    "step4",
    "step5",
]);
const progress = computed(() => 100);

function goEditStep(step: string) {
    router.push({
        path: `/booking/provider/${uuid}/details`,
        query: { category: bookingStore.category, step },
    });
}
const booking_data = {
    facility:
        bookingStore.category === "facility" ? bookingStore.facility : null,
    homecare:
        bookingStore.category === "homecare" ? bookingStore.homecare : null,
    patient: bookingStore.patient,
    guardian: bookingStore.guardian,

    assessment: bookingStore.assessment,
    diagnoses: bookingStore.diagnoses,
};

if (booking_data.homecare?.type === "ADL") {
    booking_data.homecare.price = Number(
        bookingStore.branchHomecare?.adl_hourly_rate ?? 0,
    );
}

async function handleCardPay() {
    if (processingPayment.value) return;
    processingPayment.value = true;
    try {
        await cardPayment({
            card,
            amount: bookingAmount.value,
            onClose: () => {
                processingPayment.value = false;
            },
            createPayment: ({ token_id, authentication_id }) =>
                bookingService.create({
                    action: "complete-admission",
                    branch_uuid: uuid,
                    token_id,
                    authentication_id,
                    booking_data: booking_data,
                    payment_method: "CREDIT-CARD",
                    category: bookingStore.category,
                    total: bookingAmount.value,
                }),
            onSuccess: async (result) => {
                await navigateTo({
                    path: `/booking/provider/${uuid}/success`,
                    query: {
                        status: result.status,
                    },
                });
            },
        });
    } catch (err: any) {
        toast.error(err?.message ?? "Payment failed.");
    } finally {
        processingPayment.value = false;
    }
}

async function handleGCashPay() {}
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

    try {
        const res = await bookingService.create({
            branch_uuid: uuid,
            category: bookingStore.category,
            booking_data,
            action: "regular",
        });
        toast.success(res.message);
        window.location.href = `/booking/provider/${uuid}/success`;
        // router.push({
        //     path: `/booking/provider/${uuid}/success`,
        //     // query: {
        //     //     message: res.message,
        //     // },
        // });
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
