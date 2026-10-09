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

        <div class="relative z-10 max-w-lg w-full">
            <div
                class="rounded-[20px] border border-white/10 bg-white/95 px-6 py-8 shadow-[0_30px_70px_-15px_rgba(0,0,0,0.65)] backdrop-blur-xl text-center md:px-10 dark:bg-secondary/95"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                    :class="
                        failed
                            ? 'bg-red-50 dark:bg-red-500/10'
                            : checking
                              ? 'bg-gray-100 dark:bg-white/10'
                              : 'bg-primary/10'
                    "
                >
                    <LoaderCircle
                        v-if="checking"
                        class="h-9 w-9 animate-spin text-gray-500 dark:text-gray-300"
                    />
                    <XCircle v-else-if="failed" class="h-9 w-9 text-red-500" />
                    <CheckCircle2 v-else class="h-9 w-9 text-primary" />
                </div>

                <h1
                    class="mt-6 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                >
                    {{ title }}
                </h1>

                <p
                    class="mt-2 text-[15px] leading-relaxed text-slate-500 dark:text-gray-400"
                >
                    {{ description }}
                </p>

                <div
                    class="mt-6 rounded-2xl border border-gray-100 bg-gray-50 px-5 py-4 text-left dark:border-white/10 dark:bg-white/5"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500 dark:text-gray-400">
                            Payment Status
                        </span>

                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="
                                failed
                                    ? 'bg-red-100 text-red-600'
                                    : checking
                                      ? 'bg-gray-100 text-gray-600'
                                      : 'bg-emerald-100 text-emerald-600'
                            "
                        >
                            {{
                                failed
                                    ? "Failed"
                                    : checking
                                      ? "Processing"
                                      : "Completed"
                            }}
                        </span>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3">
                    <BaseButton
                        variant="primary"
                        class="w-full rounded-xl py-3"
                        @click="goBack"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Go back
                    </BaseButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    ArrowLeft,
    CheckCircle2,
    LoaderCircle,
    XCircle,
} from "lucide-vue-next";
import BaseButton from "~/components/ui/BaseButton.vue";
import backdrop from "~/assets/logo/signinLogo2.png";
import { paymentService } from "~/api/payment/PaymentService";

definePageMeta({ layout: false });
useHead({ title: "Payment" });

const route = useRoute();
const router = useRouter();

const paid = computed(() => route.query.status === "success");
const reference = computed(() => route.query.ref as string | undefined);

type ConfirmState = "checking" | "submitted" | "failed" | "pending";
const confirmState = ref<ConfirmState>(paid.value ? "checking" : "failed");

const checking = computed(() => confirmState.value === "checking");
const failed = computed(() => confirmState.value === "failed");

const CHECK_ATTEMPTS = 8;
const CHECK_DELAY_MS = 1500;

const title = computed(() => {
    if (checking.value) return "Confirming Your Payment";
    if (failed.value) return "Payment Failed";
    if (confirmState.value === "pending") return "Payment Received";

    return "Payment Successful";
});

const description = computed(() => {
    if (checking.value) {
        return "We're verifying your GCash payment. This will only take a moment.";
    }

    if (failed.value) {
        return paid.value
            ? "We couldn't apply your payment. If you were charged, it will be automatically refunded."
            : "We couldn't complete your payment. Nothing was charged. You can go back and try again.";
    }

    if (confirmState.value === "pending") {
        return "Your payment went through and is still being recorded. Your balance will update shortly.";
    }

    return "Your payment was received and applied to the balance. Your receipt is on the balance page.";
});

async function confirmPayment() {
    if (!reference.value) {
        confirmState.value = "submitted";
        return;
    }

    for (let attempt = 0; attempt < CHECK_ATTEMPTS; attempt++) {
        try {
            const res = await paymentService.checkStatus(reference.value);

            if (res.status === "submitted") {
                confirmState.value = "submitted";
                return;
            }

            if (res.status === "failed" || res.status === "unknown") {
                confirmState.value = "failed";
                return;
            }
        } catch {
            confirmState.value = "failed";
            return;
        }

        await new Promise((resolve) => setTimeout(resolve, CHECK_DELAY_MS));
    }

    confirmState.value = "pending";
}

function goBack() {
    router.push("/portal/balance");
}

onMounted(async () => {
    if (window.parent !== window) {
        window.parent.postMessage(
            { status: paid.value ? "success" : "failed" },
            window.location.origin,
        );
        return;
    }

    if (!paid.value) return;

    await confirmPayment();
});
</script>
