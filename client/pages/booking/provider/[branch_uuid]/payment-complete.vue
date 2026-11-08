<template>
    <div
        class="relative min-h-screen overflow-hidden bg-slate-50 flex items-center justify-center px-5 py-12 dark:bg-secondary"
    >
        <img
            :src="backdrop"
            class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover dark:opacity-20"
            alt=""
            aria-hidden="true"
        />

        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-b from-white/40 via-white/10 to-white/50 dark:from-secondary/80 dark:via-secondary/60 dark:to-secondary/90"
            aria-hidden="true"
        />

        <div class="relative z-10 max-w-lg w-full">
            <div
                class="rounded-3xl bg-white/90 px-6 py-8 text-center shadow-xl shadow-primary/10 ring-1 ring-white/60 backdrop-blur-md md:px-10 dark:bg-secondary/90 dark:ring-white/10"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                    :class="
                        !isSuccess || confirmState === 'failed'
                            ? 'bg-red-50 dark:bg-red-500/10'
                            : confirmState === 'checking'
                              ? 'bg-gray-100 dark:bg-white/10'
                              : 'bg-primary/10'
                    "
                >
                    <LoaderCircle
                        v-if="isSuccess && confirmState === 'checking'"
                        class="h-9 w-9 animate-spin text-gray-500 dark:text-gray-300"
                    />
                    <CheckCircle2
                        v-else-if="isSuccess && confirmState === 'submitted'"
                        class="h-9 w-9 text-primary"
                    />
                    <XCircle v-else class="h-9 w-9 text-red-500" />
                </div>

                <h1
                    class="text-2xl font-extrabold tracking-tight text-slate-900 mt-6 dark:text-white"
                >
                    {{ title }}
                </h1>

                <p
                    class="text-[15px] text-slate-500 mt-2 leading-relaxed dark:text-gray-400"
                >
                    {{ description }}
                </p>

                <div
                    v-if="confirmState === 'submitted' && bookingReference"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-gray-50 border border-gray-100 px-4 py-2 dark:bg-white/5 dark:border-white/10"
                >
                    <span class="text-xs text-gray-400 dark:text-gray-500"
                        >Reference No.</span
                    >
                    <span
                        class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >
                        {{ bookingReference }}
                    </span>
                </div>

                <div v-if="!isSuccess" class="mt-8 flex flex-col gap-3">
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <Wallet class="h-4 w-4 shrink-0 mt-0.5 text-primary" />
                        <span>
                            Your booking has not been submitted. Nothing was
                            charged to your payment method.
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
                    v-else-if="confirmState === 'submitted'"
                    class="mt-8 flex flex-col gap-3"
                >
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <BellRing
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            You'll be notified in the app and by email once your
                            booking has been reviewed and approved.
                        </span>
                    </div>
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <Wallet class="h-4 w-4 shrink-0 mt-0.5 text-primary" />
                        <span>
                            This payment will be credited toward your total
                            bill, or automatically refunded if your booking is
                            rejected or expires.
                        </span>
                    </div>
                    <div
                        v-if="waitingReceipt"
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <LoaderCircle
                            class="h-4 w-4 shrink-0 mt-0.5 animate-spin text-primary"
                        />
                        <span>Generating your receipt...</span>
                    </div>
                    <div
                        v-else-if="booking?.receipt_code"
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

                <div v-if="!isSuccess" class="mt-8">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <BaseButton
                            variant="secondary"
                            class="w-full rounded-xl py-3"
                            @click="tryAgain"
                        >
                            Try Again
                        </BaseButton>

                        <BaseButton
                            variant="primary"
                            class="w-full rounded-xl py-3"
                            @click="goHome"
                        >
                            Back to Home
                        </BaseButton>
                    </div>

                    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                        Redirecting back to try again in {{ secondsLeft }}s...
                    </p>
                </div>

                <div v-else-if="confirmState === 'submitted'" class="mt-8">
                    <div
                        v-if="waitingReceipt || booking"
                        class="mb-3 flex flex-col gap-3 sm:flex-row"
                    >
                        <BaseButton
                            variant="secondary"
                            class="w-full rounded-xl py-3"
                            :loading="!booking"
                            :disabled="!booking"
                            @click="showAcknowledgement = true"
                        >
                            <Printer v-if="booking" class="h-4 w-4" />
                            {{
                                booking ? "Print booking form" : "Preparing..."
                            }}
                        </BaseButton>

                        <BaseButton
                            v-if="waitingReceipt || booking?.receipt_code"
                            variant="secondary"
                            class="w-full rounded-xl py-3"
                            :loading="waitingReceipt || openingReceipt"
                            :disabled="waitingReceipt || openingReceipt"
                            @click="printReceipt"
                        >
                            <ReceiptText
                                v-if="!waitingReceipt && !openingReceipt"
                                class="h-4 w-4"
                            />
                            {{
                                waitingReceipt
                                    ? "Generating receipt..."
                                    : "Print receipt"
                            }}
                        </BaseButton>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <BaseButton
                            variant="secondary"
                            class="w-full rounded-xl py-3"
                            @click="viewBookings"
                        >
                            View My Booking
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
                        v-if="countingDown"
                        class="mt-3 text-xs text-gray-400 dark:text-gray-500"
                    >
                        Redirecting to your bookings in {{ secondsLeft }}s...
                    </p>
                </div>

                <div
                    v-else-if="confirmState === 'failed'"
                    class="mt-8 flex flex-col gap-3"
                >
                    <BaseButton
                        variant="primary"
                        class="w-full rounded-xl py-3"
                        @click="tryAgain"
                    >
                        Try Again
                    </BaseButton>

                    <BaseButton
                        variant="secondary"
                        class="w-full rounded-xl py-3"
                        @click="goHome"
                    >
                        Back to Home
                    </BaseButton>
                </div>
            </div>
        </div>

        <BookingAcknowledgement
            v-if="showAcknowledgement && booking"
            :booking="booking"
            :branch-name="booking.branch_name"
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
    XCircle,
    BellRing,
    Wallet,
    ShieldCheck,
    LoaderCircle,
    ReceiptText,
    Printer,
} from "lucide-vue-next";
import BaseButton from "~/components/ui/BaseButton.vue";
import backdrop from "~/assets/images/portalselection-bg.png";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import BookingAcknowledgement from "~/components/booking/BookingAcknowledgement.vue";
import { paymentService } from "~/api/payment/PaymentService";
import { useSubmittedBooking } from "~/composables/useSubmittedBooking";
import { useToast } from "~/composables/useToast";

definePageMeta({
    layout: false,
    middleware: (to) => {
        const status = to.query.status;
        const hasReference = Boolean(to.query.ref || to.query.booking);

        if (status !== "success" && status !== "failed") {
            return navigateTo("/", { replace: true });
        }

        if (status === "success" && !hasReference) {
            return navigateTo("/", { replace: true });
        }
    },
});
useHead({ title: "Payment" });

const route = useRoute();
const router = useRouter();
const uuid = route.params.branch_uuid as string;

const isSuccess = computed(() => route.query.status === "success");
const reference = computed(() => route.query.ref as string | undefined);

type ConfirmState = "checking" | "submitted" | "failed";
const confirmState = ref<ConfirmState>("checking");

const title = computed(() => {
    if (!isSuccess.value) return "Payment Not Completed";

    return {
        checking: "Confirming Your Payment",
        submitted: "Booking Request Submitted",
        failed: "Something Went Wrong",
    }[confirmState.value];
});

const description = computed(() => {
    if (!isSuccess.value) {
        return "No money was taken. You can return to your booking and try again.";
    }

    return {
        checking: "We're verifying your payment. This will only take a moment.",
        submitted:
            "Thank you. Your payment has been received and your booking has been sent to the care team for review.",
        failed: "We couldn't confirm your payment. If you were charged, it will be automatically refunded.",
    }[confirmState.value];
});

const showRedirectUi = ref(false);
const secondsLeft = ref(5);
const bookingReference = ref<string | null>(null);
const countingDown = ref(false);
let countdownTimer: ReturnType<typeof setInterval> | null = null;

const {
    booking,
    waiting: waitingReceipt,
    receipt,
    openingReceipt,
    waitForReceipt,
    openReceipt,
} = useSubmittedBooking();
const { error: showError } = useToast();

const showAcknowledgement = ref(false);

watch(showAcknowledgement, (open) => {
    if (open) stopCountdown();
});

function stopCountdown() {
    countingDown.value = false;

    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

async function printReceipt() {
    stopCountdown();

    try {
        await openReceipt();
    } catch (err: any) {
        showError(err?.message || "Unable to load your receipt.");
    }
}

function goHome() {
    stopCountdown();
    router.push("/");
}

function viewBookings() {
    stopCountdown();
    router.push({
        path: "/portal/bookings",
        query: bookingReference.value
            ? { reference_id: bookingReference.value }
            : {},
    });
}

function tryAgain() {
    stopCountdown();
    router.push(`/booking/provider/${uuid}/review`);
}

async function confirmPayment() {
    if (!reference.value) {
        confirmState.value = "submitted";
        bookingReference.value = (route.query.booking as string) ?? null;
        return;
    }

    confirmState.value = "checking";

    try {
        let res = await paymentService.checkStatus(reference.value);

        for (
            let attempt = 0;
            res.status === "pending" && attempt < 5;
            attempt++
        ) {
            await new Promise((resolve) => setTimeout(resolve, 2000));
            res = await paymentService.checkStatus(reference.value);
        }

        confirmState.value =
            res.status === "submitted" ? "submitted" : "failed";
        bookingReference.value = res.reference_id ?? null;
    } catch {
        confirmState.value = "failed";
    }
}

onMounted(async () => {
    if (window.parent !== window) {
        window.parent.postMessage(
            { status: isSuccess.value ? "success" : "failed" },
            window.location.origin,
        );
        return;
    }

    if (!isSuccess.value) {
        showRedirectUi.value = true;

        countdownTimer = setInterval(() => {
            secondsLeft.value -= 1;

            if (secondsLeft.value <= 0) {
                tryAgain();
            }
        }, 1000);

        return;
    }

    await confirmPayment();

    if (confirmState.value !== "submitted") return;

    showRedirectUi.value = true;

    if (bookingReference.value) await waitForReceipt(bookingReference.value);

    if (booking.value?.receipt_code) {
        try {
            await openReceipt();
        } catch {}
    }

    if (!receipt.value) startCountdown();
});

function startCountdown() {
    if (countdownTimer) return;

    countingDown.value = true;

    countdownTimer = setInterval(() => {
        secondsLeft.value -= 1;

        if (secondsLeft.value <= 0) {
            viewBookings();
        }
    }, 1000);
}

watch(receipt, (current, previous) => {
    if (!current && previous) startCountdown();
});

onBeforeUnmount(stopCountdown);
</script>
