<template>
    <div
        class="relative min-h-screen overflow-hidden bg-slate-950 flex items-center justify-center px-5 pt-28 pb-12"
    >
        <img
            :src="backdrop"
            class="pointer-events-none absolute inset-[-12px] h-[calc(100%+24px)] w-[calc(100%+24px)] scale-105 select-none object-cover blur-[2px]"
            alt=""
        />

        <div
            class="pointer-events-none absolute inset-0 bg-blue-950/40 mix-blend-multiply"
        />

        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-blue-950/65 via-slate-950/50 to-slate-950/90"
        />

        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-56 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-transparent"
        />

        <div
            class="pointer-events-none absolute inset-0 bg-white/[0.02] backdrop-blur-[1px]"
        />

        <div class="relative z-10 max-w-lg w-full">
            <div
                class="rounded-[20px] border border-white/10 bg-white/95 px-6 py-8 shadow-[0_30px_70px_-15px_rgba(0,0,0,0.65)] backdrop-blur-xl text-center md:px-10 dark:bg-secondary/95"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"
                >
                    <CheckCircle2 class="h-9 w-9 text-primary" />
                </div>

                <h1
                    class="text-2xl font-extrabold tracking-tight text-slate-900 mt-6 dark:text-white"
                >
                    Booking Request Submitted
                </h1>

                <p
                    class="text-[15px] text-slate-500 mt-2 leading-relaxed dark:text-gray-400"
                >
                    Thank you. Your
                    {{
                        category === "facility"
                            ? "facility admission"
                            : "homecare service"
                    }}
                    request has been sent to the care team for review.
                </p>

                <div
                    v-if="referenceId"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-gray-50 border border-gray-100 px-4 py-2 dark:bg-white/5 dark:border-white/10"
                >
                    <span class="text-xs text-gray-400 dark:text-gray-500"
                        >Reference No.</span
                    >
                    <span
                        class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >
                        {{ referenceId }}
                    </span>
                </div>

                <div class="mt-8 flex flex-col gap-3">
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <BellRing
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            You'll be notified in the app and by email once your
                            booking request has been reviewed and accepted.
                        </span>
                    </div>
                    <div
                        v-if="isLoadingAcknowledgement"
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <LoaderCircle
                            class="h-4 w-4 shrink-0 mt-0.5 animate-spin text-primary"
                        />
                        <span>Generating your receipt...</span>
                    </div>
                    <div
                        v-else-if="acknowledgement?.receipt_code"
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <ReceiptText
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            Your receipt is ready. You can also print it later
                            from your booking.
                        </span>
                    </div>
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <ShieldCheck
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            All patient information is kept confidential and
                            used only to provide the best care possible.
                        </span>
                    </div>
                </div>

                <div
                    v-if="isLoadingAcknowledgement || acknowledgement"
                    class="mt-8 flex flex-col gap-3 sm:flex-row"
                >
                    <BaseButton
                        variant="secondary"
                        class="w-full rounded-xl py-3"
                        :loading="!acknowledgement"
                        :disabled="!acknowledgement"
                        @click="showAcknowledgement = true"
                    >
                        <Printer v-if="acknowledgement" class="h-4 w-4" />
                        {{ acknowledgement ? "Print booking form" : "Preparing..." }}
                    </BaseButton>

                    <BaseButton
                        v-if="isLoadingAcknowledgement || acknowledgement?.receipt_code"
                        variant="secondary"
                        class="w-full rounded-xl py-3"
                        :loading="isLoadingAcknowledgement || openingReceipt"
                        :disabled="isLoadingAcknowledgement || openingReceipt"
                        @click="printReceipt"
                    >
                        <ReceiptText
                            v-if="!isLoadingAcknowledgement && !openingReceipt"
                            class="h-4 w-4"
                        />
                        {{
                            isLoadingAcknowledgement
                                ? "Generating receipt..."
                                : "Print receipt"
                        }}
                    </BaseButton>
                </div>

                <div class="mt-3 flex flex-col sm:flex-row gap-3">
                    <BaseButton
                        variant="secondary"
                        class="w-full rounded-xl py-3"
                        :loading="isOpeningBookings"
                        :disabled="isOpeningBookings"
                        @click="viewBookings"
                    >
                        {{
                            isOpeningBookings
                                ? "Loading..."
                                : "View My Bookings"
                        }}
                    </BaseButton>
                    <BaseButton
                        variant="primary"
                        class="w-full rounded-xl py-3"
                        @click="goHome"
                    >
                        Back to Home
                    </BaseButton>
                </div>

                <p
                    v-if="showCountdown"
                    class="mt-3 text-xs text-gray-400 dark:text-gray-500"
                >
                    Redirecting to your bookings in {{ secondsLeft }}s...
                </p>
            </div>
        </div>

        <BookingAcknowledgement
            v-if="showAcknowledgement && acknowledgement"
            :booking="acknowledgement"
            :branch-name="acknowledgement.branch_name"
            @close="showAcknowledgement = false"
        />

        <PaymentReceipt
            v-if="receipt"
            :receipt="receipt"
            @close="receipt = null"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    CheckCircle2,
    BellRing,
    LoaderCircle,
    Printer,
    ReceiptText,
    ShieldCheck,
} from "lucide-vue-next";
import BaseButton from "~/components/ui/BaseButton.vue";
import backdrop from "~/assets/logo/signinLogo2.png";
import BookingAcknowledgement from "~/components/booking/BookingAcknowledgement.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { useBookingStore } from "~/stores/booking";
import { useAuthUser } from "~/composables/useAuthUser";
import { useSubmittedBooking } from "~/composables/useSubmittedBooking";
import { useToast } from "~/composables/useToast";

definePageMeta({ layout: false });
useHead({ title: "Booking Submitted" });

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();
const authUser = useAuthUser();

const category = computed<"homecare" | "facility">(() => {
    const fromQuery = route.query.category;

    if (fromQuery === "facility" || fromQuery === "homecare") {
        return fromQuery;
    }

    return bookingStore.category ?? "homecare";
});

const referenceId = ref(bookingStore.lastSubmittedId ?? "");

const {
    booking: acknowledgement,
    waiting: isLoadingAcknowledgement,
    receipt,
    openingReceipt,
    waitForReceipt,
    openReceipt,
} = useSubmittedBooking();
const { error: showError } = useToast();

const showAcknowledgement = ref(false);
const isOpeningBookings = ref(false);

const showCountdown = ref(false);
const secondsLeft = ref(5);
let countdownTimer: ReturnType<typeof setInterval> | null = null;

function stopCountdown() {
    showCountdown.value = false;

    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

watch(showAcknowledgement, (open) => {
    if (open) stopCountdown();
});

async function printReceipt() {
    stopCountdown();

    try {
        await openReceipt();
    } catch (err: any) {
        showError(err?.message || "Unable to load your receipt.");
    }
}

function startCountdown() {
    if (countdownTimer || isOpeningBookings.value) return;

    showCountdown.value = true;

    countdownTimer = setInterval(() => {
        secondsLeft.value -= 1;

        if (secondsLeft.value <= 0) {
            stopCountdown();
            viewBookings();
        }
    }, 1000);
}

onMounted(async () => {
    const reference = referenceId.value;

    bookingStore.$reset?.();

    if (authUser.value) {
        authUser.value = { ...authUser.value, hasBooking: true };
    }

    if (reference) await waitForReceipt(reference);

    if (acknowledgement.value?.receipt_code) {
        try {
            await openReceipt();
        } catch {}
    }

    if (!showAcknowledgement.value && !receipt.value) startCountdown();
});

watch(receipt, (current, previous) => {
    if (!current && previous && !showAcknowledgement.value) startCountdown();
});

onBeforeUnmount(stopCountdown);

function goHome() {
    stopCountdown();
    router.push("/");
}

async function viewBookings() {
    stopCountdown();
    isOpeningBookings.value = true;

    try {
        await router.push({
            path: "/portal/bookings",
            query: referenceId.value
                ? { reference_id: referenceId.value }
                : {},
        });
    } finally {
        isOpeningBookings.value = false;
    }
}
</script>
