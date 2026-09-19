<template>
    <div
        class="relative min-h-screen overflow-hidden bg-slate-950 flex items-center justify-center px-5 py-12"
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
                            This reservation fee will be credited toward your
                            total bill, or automatically refunded if your
                            booking is rejected or expires.
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

                    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
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
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    CheckCircle2,
    XCircle,
    BellRing,
    Wallet,
    ShieldCheck,
    LoaderCircle,
} from "lucide-vue-next";
import BaseButton from "~/components/ui/BaseButton.vue";
import backdrop from "~/assets/logo/signinLogo2.png";
import { paymentService } from "~/api/payment/PaymentService";

definePageMeta({ layout: false });
useHead({ title: "Payment" });

const route = useRoute();
const router = useRouter();
const uuid = route.params.branch_uuid as string;

const isSuccess = computed(() => route.query.status === "success");
const reference = computed(() => route.query.ref as string | undefined);

// Xendit redirects here the moment the GCash payment clears on its side,
// which happens independently of our server. The booking itself is only
// created afterwards, asynchronously, when Xendit's webhook reaches us —
// so "success" here only means the money moved, not that we've recorded
// it yet. This state tracks that separate confirmation with a single
// check: anything other than a confirmed "submitted" is treated as
// failed rather than left ambiguous.
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
            "Thank you. Your reservation fee has been received and your booking has been sent to the care team for review.",
        failed: "We couldn't confirm your payment. If you were charged, it will be automatically refunded.",
    }[confirmState.value];
});

const showRedirectUi = ref(false);
const secondsLeft = ref(5);
let countdownTimer: ReturnType<typeof setInterval> | null = null;

function stopCountdown() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

function goHome() {
    stopCountdown();
    router.push("/");
}

function viewBookings() {
    stopCountdown();
    router.push("/portal/bookings");
}

function tryAgain() {
    stopCountdown();
    router.push(`/booking/provider/${uuid}/review`);
}

async function confirmPayment() {
    if (!reference.value) {
        confirmState.value = "submitted";
        return;
    }

    confirmState.value = "checking";

    try {
        const res = await paymentService.checkStatus(reference.value);
        confirmState.value =
            res.status === "submitted" ? "submitted" : "failed";
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

    countdownTimer = setInterval(() => {
        secondsLeft.value -= 1;

        if (secondsLeft.value <= 0) {
            viewBookings();
        }
    }, 1000);
});

onBeforeUnmount(stopCountdown);
</script>
