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
                        isSuccess
                            ? 'bg-primary/10'
                            : 'bg-red-50 dark:bg-red-500/10'
                    "
                >
                    <CheckCircle2
                        v-if="isSuccess"
                        class="h-9 w-9 text-primary"
                    />
                    <XCircle v-else class="h-9 w-9 text-red-500" />
                </div>

                <h1
                    class="text-2xl font-extrabold tracking-tight text-slate-900 mt-6 dark:text-white"
                >
                    {{ isSuccess ? "Payment Successful" : "Payment Failed" }}
                </h1>

                <p
                    class="text-[15px] text-slate-500 mt-2 leading-relaxed dark:text-gray-400"
                >
                    {{
                        isSuccess
                            ? "We'll verify your subscription and notify you of your request's status within 1-2 business days."
                            : "We couldn't complete your payment. Please try again."
                    }}
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
                                isSuccess
                                    ? 'bg-emerald-100 text-emerald-600'
                                    : 'bg-red-100 text-red-600'
                            "
                        >
                            {{ isSuccess ? "Completed" : "Failed" }}
                        </span>
                    </div>

                    <p
                        class="mt-3 text-sm font-semibold"
                        :class="isSuccess ? 'text-emerald-600' : 'text-red-600'"
                    >
                        {{
                            isSuccess
                                ? "Payment completed successfully"
                                : "Payment failed"
                        }}
                    </p>
                </div>

                <div v-if="isSuccess" class="mt-6 flex flex-col gap-3">
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <BellRing
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            You'll be notified once your subscription has been
                            reviewed and approved.
                        </span>
                    </div>
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <ShieldCheck
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            If the subscription is rejected, this payment will
                            be refunded to your original payment method.
                        </span>
                    </div>
                </div>

                <div v-else class="mt-6 flex flex-col gap-3">
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left dark:text-gray-400"
                    >
                        <Wallet class="h-4 w-4 shrink-0 mt-0.5 text-primary" />
                        <span>
                            Your subscription has not been submitted. Nothing
                            was charged to your payment method.
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

                <div v-if="isSuccess" class="mt-8 flex flex-col gap-3">
                    <NuxtLink
                        v-if="dashboardUrl"
                        :to="dashboardUrl"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        View Dashboard
                    </NuxtLink>

                    <BaseButton
                        v-else
                        variant="primary"
                        class="w-full rounded-xl py-3"
                        :loading="isPreparing"
                        :disabled="isPreparing"
                        @click="resolveBranch"
                    >
                        {{
                            isPreparing
                                ? "Preparing your workspace..."
                                : "Check again"
                        }}
                    </BaseButton>

                    <BaseButton
                        variant="secondary"
                        class="w-full rounded-xl py-3"
                        @click="goHome"
                    >
                        Back to Home
                    </BaseButton>
                </div>

                <div v-else class="mt-8 flex flex-col gap-3">
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
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    CheckCircle2,
    XCircle,
    BellRing,
    Wallet,
    ShieldCheck,
} from "lucide-vue-next";
import BaseButton from "~/components/ui/BaseButton.vue";
import backdrop from "~/assets/logo/signinLogo2.png";
import { useBranchStore } from "~/stores/branch";
import { fetchAuthUser } from "~/composables/useAuthUser";

definePageMeta({ layout: false });
useHead({ title: "Payment" });

const route = useRoute();
const router = useRouter();
const branchStore = useBranchStore();

const isSuccess = computed(() => route.query.status === "success");

function goHome() {
    router.push("/");
}

function tryAgain() {
    router.push("/product/subscription-details/checkout");
}

const isPreparing = ref(true);

async function resolveBranch() {
    isPreparing.value = true;

    try {
        for (let attempt = 0; attempt < 8; attempt++) {
            await branchStore.refreshBranch();

            if (branchStore.branches.length) return;

            await new Promise((resolve) => setTimeout(resolve, 1500));
        }
    } finally {
        isPreparing.value = false;
    }
}

const dashboardUrl = computed(() => {
    const uuid =
        branchStore.activeBranch?.uuid ??
        branchStore.lastSelectedBranch?.uuid ??
        branchStore.branches?.[0]?.uuid;

    if (!uuid) {
        return null;
    }

    return `/app/branches/${uuid}/dashboard`;
});

onMounted(async () => {
    if (window.parent !== window) {
        window.parent.postMessage(
            { status: isSuccess.value ? "success" : "failed" },
            window.location.origin,
        );
        return;
    }

    if (!isSuccess.value) return;
    await fetchAuthUser();
    await resolveBranch();
});
</script>
