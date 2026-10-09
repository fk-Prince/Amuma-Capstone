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
                    class="mt-6 rounded-2xl border border-gray-100 bg-gray-50 px-5 py-4 text-left dark:bg-white/5 dark:border-white/10"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500 dark:text-gray-400">
                            Payment Status
                        </span>

                        <span
                            class="text-xs font-semibold px-3 py-1 rounded-full"
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
                        :disabled="checking"
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
import { useBranchStore } from "~/stores/branch";
import { fetchAuthUser } from "~/composables/useAuthUser";
import { paymentService } from "~/api/payment/PaymentService";
import { PAYMENT_RETURN_KEY } from "~/utils/paymentReturn";

definePageMeta({ layout: false });
useHead({ title: "Branch Payment" });

const route = useRoute();
const router = useRouter();
const branchStore = useBranchStore();

const paid = computed(() => route.query.status === "success");
const reference = computed(() => route.query.ref as string | undefined);

type ConfirmState = "checking" | "submitted" | "failed";
const confirmState = ref<ConfirmState>(paid.value ? "checking" : "failed");

const checking = computed(() => confirmState.value === "checking");
const failed = computed(() => confirmState.value === "failed");

const title = computed(() => {
    if (checking.value) return "Confirming Your Payment";

    return failed.value ? "Payment Failed" : "Payment Successful";
});

const description = computed(() => {
    if (checking.value) {
        return "We're verifying your payment. This will only take a moment.";
    }

    if (failed.value) {
        return paid.value
            ? "We couldn't confirm your payment. If you were charged, it will be automatically refunded."
            : "We couldn't complete your payment. Nothing was charged. You can go back and try again.";
    }

    return "Your additional branch has been submitted. We'll notify you once it has been reviewed.";
});

async function confirmPayment() {
    if (!reference.value) {
        confirmState.value = "submitted";
        return;
    }

    try {
        const res = await paymentService.checkStatus(reference.value);
        confirmState.value = res.status === "submitted" ? "submitted" : "failed";
    } catch {
        confirmState.value = "failed";
    }
}

function returnPath() {
    try {
        const stored = sessionStorage.getItem(PAYMENT_RETURN_KEY);

        if (stored && stored.startsWith("/")) return stored;
    } catch {}

    const uuid =
        branchStore.activeBranch?.uuid ??
        branchStore.lastSelectedBranch?.uuid ??
        branchStore.branches?.[0]?.uuid;

    return uuid ? `/app/branches/${uuid}/manage-subscription` : "/";
}

function goBack() {
    const path = returnPath();

    try {
        sessionStorage.removeItem(PAYMENT_RETURN_KEY);
    } catch {}

    router.push(path);
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

    await fetchAuthUser();
    await confirmPayment();
});
</script>
