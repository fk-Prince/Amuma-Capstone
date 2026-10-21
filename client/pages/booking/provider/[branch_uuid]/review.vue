<template>
    <div
        class="min-h-screen lg:h-[calc(100dvh-126px)] lg:min-h-0 lg:overflow-hidden"
    >
    <div
        class="mx-auto grid w-[94%] grid-cols-1 gap-5 pb-6 pt-2 lg:h-full lg:min-h-0 lg:w-[88%] lg:max-w-[1600px] lg:grid-cols-[300px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)] lg:pb-4 lg:pt-1"
    >
        <aside
            class="hidden flex-col overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-br from-primary-50/70 via-white to-accent-50/50 shadow-sm lg:flex lg:h-full lg:min-h-0 dark:border-white/10 dark:bg-none dark:bg-secondary"
        >
            <BookingProgressHeader
                title="Booking Progress"
                :progress="progress"
            />

            <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3">
                <BookingSteps
                    compact
                    active="step6"
                    :completed="completedSteps"
                    @go="goEditStep"
                />
            </div>
        </aside>

        <main
            class="flex min-w-0 flex-col pt-2 lg:h-full lg:min-h-0 lg:overflow-hidden lg:rounded-2xl lg:border lg:border-gray-100 lg:bg-white lg:pt-0 lg:shadow-sm dark:lg:border-white/10 dark:lg:bg-secondary"
        >
            <div
                class="lg:shrink-0 lg:border-b lg:border-gray-100 lg:px-6 lg:py-4 dark:lg:border-white/10"
            >
            <Breadcrumb
                class="mb-5 lg:mb-0"
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
            </div>

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

            <div class="lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:px-8 lg:py-6">
            <div
                :class="
                    showPayment
                        ? 'grid grid-cols-1 xl:grid-cols-[1fr_450px] gap-6 items-start'
                        : ''
                "
            >
                <div>
                    <div
                        class="rounded-2xl border border-gray-100 bg-white shadow-sm lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none dark:bg-secondary dark:border-white/10 dark:lg:bg-transparent"
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
                            :diagnosis-charges="branch?.diagnosis_case_prices"
                            :diagnosis-notice="
                                bookingStore.category === 'facility'
                            "
                            :bookingPercent="bookingPercent"
                            @edit-step="goEditStep"
                        />
                    </div>

                    <div
                        v-if="!showPayment"
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
                                    We'll let you know in the app and by email
                                    as soon as the branch has reviewed your
                                    booking request.
                                </span>
                            </div>

                            <div
                                class="flex items-start gap-3 text-[13px] text-gray-500 dark:text-gray-400"
                            >
                                <CircleAlert
                                    class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                                />

                                <span>
                                    Please double-check your details before
                                    you submit. A booking can't be cancelled
                                    once it's made.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="showPayment" class="xl:sticky xl:top-8">
                    <div class="rounded-xl shadow-sm">
                        <PaymentForm
                            compact
                            :card="card"
                            :total-amount="bookingAmount"
                            :processing="
                                processingPayment ||
                                loadingTotal ||
                                !branch?.settings
                            "
                            title="Complete Your Booking"
                            :description="
                                isHomecare
                                    ? 'Choose your payment method to pay the downpayment and confirm your homecare booking.'
                                    : 'Choose your payment method to confirm your facility reservation.'
                            "
                            submit-label="Confirm & Pay"
                            processing-label="Confirming payment..."
                            gcash-label="Pay with GCash"
                            gcash-processing-label="Redirecting to GCash..."
                            terms-context="booking"
                            @update:card="(value) => Object.assign(card, value)"
                            @card-pay="handleCardPay"
                            @g-cash-pay="handleGCashPay"
                        />
                    </div>
                </div>
            </div>
            </div>

            <div
                v-if="!showPayment"
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
            </div>
        </main>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from "vue";
import { BellRing, CircleAlert, ShieldCheck } from "lucide-vue-next";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import ReviewSection from "~/components/sections/booking/provider/ReviewSection.vue";
import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import BookingProgressHeader from "~/components/sections/booking/provider/BookingProgressHeader.vue";
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
    navVariant: 6,
    navTheme: "light",
    // The page fills the screen on desktop; no footer to scroll down to.
    footer: false,
    middleware: [
        "auth-client",
        "prevent-staff-booking",
        "branch-available-guard",
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
    number: "4000000000001000",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const isHomecare = computed(() => bookingStore.category === "homecare");

const showPayment = computed(
    () =>
        isHomecare.value ||
        (bookingStore.category === "facility" &&
            bookingStore.facility?.type === "Complete"),
);

const paymentAction = computed(() =>
    isHomecare.value ? "homecare" : "complete-admission",
);

const bookingPercent = computed(() => {
    if (!showPayment.value) return 100;

    const settings = branch.value?.settings;
    if (!settings) return 100;

    return isHomecare.value
        ? (settings.homecare_downpayment_percent ?? 50)
        : (settings.complete_admission_booking_percent ?? 100);
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
                    action: paymentAction.value,
                    branch_uuid: uuid,
                    token_id,
                    authentication_id,
                    booking_data: booking_data,
                    payment_method: "CREDIT-CARD",
                    category: bookingStore.category,
                    total: bookingAmount.value,
                }),
            onSuccess: async () => {
                await navigateTo({
                    path: `/booking/provider/${uuid}/payment-complete`,
                    query: { status: "success" },
                });
            },
        });
    } catch (err: any) {
        toast.error(err?.message ?? "Payment failed.");
    } finally {
        processingPayment.value = false;
    }
}

async function handleGCashPay() {
    if (processingPayment.value) return;
    processingPayment.value = true;

    try {
        await gcashPayment({
            closeModal,
            createPayment: () =>
                bookingService.create({
                    action: paymentAction.value,
                    branch_uuid: uuid,
                    booking_data: booking_data,
                    payment_method: "GCASH",
                    category: bookingStore.category,
                    total: bookingAmount.value,
                }),
            onSuccess: async () => {
                await navigateTo({
                    path: `/booking/provider/${uuid}/payment-complete`,
                    query: { status: "success" },
                });
            },
            onClose: () => {
                processingPayment.value = false;
            },
        });
    } catch (err: any) {
        toast.error(err?.message ?? "GCash payment failed.");
    } finally {
        processingPayment.value = false;
    }
}

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
        window.location.href = `/booking/provider/${uuid}/success?category=${bookingStore.category}`;
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