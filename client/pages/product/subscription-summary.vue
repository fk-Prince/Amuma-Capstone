<template>
    <div
        class="relative min-h-screen w-full overflow-hidden bg-slate-50 flex items-center justify-center px-4 pt-32 pb-12 dark:bg-secondary"
    >
        <img
            :src="wallpaper"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover dark:opacity-20"
        />
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-b from-white/40 via-white/10 to-white/50 dark:from-secondary/80 dark:via-secondary/60 dark:to-secondary/90"
            aria-hidden="true"
        />

        <div
            class="relative z-10 w-full max-w-md rounded-3xl bg-white/90 p-8 text-center shadow-xl shadow-primary/10 ring-1 ring-white/60 backdrop-blur-md dark:bg-secondary/90 dark:ring-white/10"
        >
            <template v-if="isSuccess">
                <div
                    class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50"
                >
                    <svg
                        class="h-10 w-10 text-emerald-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    {{
                        withTrial
                            ? "Subscription Complete"
                            : "Payment Successful"
                    }}
                </h1>

                <p class="mt-3 text-sm text-slate-500 dark:text-gray-400">
                    We'll verify your subscription and notify you of your
                    request's status within 1-2 business days.
                    <template v-if="withTrial">
                        Your 2-week free trial has started, so you can set up
                        your branch while we verify your documents. Nothing is
                        charged today. Once your branch is approved, your card
                        is charged when the trial ends, unless you cancel before
                        then.
                    </template>
                    <template v-else>
                        Your paid year starts once your branch is approved.
                    </template>
                </p>
            </template>

            <template v-else>
                <div
                    class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-50"
                >
                    <svg
                        class="h-10 w-10 text-red-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    Payment Failed
                </h1>

                <p class="mt-3 text-sm text-slate-500 dark:text-gray-400">
                    We couldn't complete your payment. Please try again.
                </p>
            </template>

            <div
                v-if="!(withTrial && isSuccess)"
                class="mt-8 rounded-2xl bg-slate-50 px-5 py-4 text-left dark:bg-secondary"
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

            <template v-if="isSuccess">
                <button
                    v-if="dashboardUrl"
                    type="button"
                    class="mt-8 inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                    @click="showSplash = true"
                >
                    View Dashboard
                </button>

                <button
                    v-else
                    type="button"
                    :disabled="isPreparing"
                    class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                    @click="prepareWorkspace"
                >
                    <span
                        v-if="isPreparing"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                    />
                    {{
                        isPreparing
                            ? "Preparing your workspace..."
                            : "Check again"
                    }}
                </button>

                <NuxtLink
                    v-if="paymentUrl"
                    :to="paymentUrl"
                    target="_blank"
                    class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-300 dark:hover:bg-white/5"
                >
                    View Payment
                </NuxtLink>
            </template>

            <NuxtLink
                v-else
                to="/"
                class="mt-8 inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
            >
                Try Again
            </NuxtLink>
        </div>
        <WelcomeSplash
            v-if="showSplash"
            :name="authUser?.first_name"
            @continue="enterDashboard"
        />
    </div>
</template>

<script setup lang="ts">
import WelcomeSplash from "~/components/ui/WelcomeSplash.vue";
import wallpaper from "~/assets/images/portalselection-bg.png";
import { computed, onMounted, ref } from "vue";
import { useBranchStore } from "~/stores/branch";
import { fetchAuthUser, useAuthUser } from "~/composables/useAuthUser";
import { subscriptionInvoiceLink } from "~/utils/subscriptionInvoice";

const branchStore = useBranchStore();
const authUser = useAuthUser();
const showSplash = ref(false);

const enterDashboard = () => {
    if (dashboardUrl.value) navigateTo(dashboardUrl.value);
};
const route = useRoute();

definePageMeta({
    navVariant: 3,
});

useHead({
    title: "Subscription Status",
});

const withTrial = computed(() => route.query.trial !== "0");

const isSuccess = computed(() => {
    return route.query.status === "true";
});

const branchUuid = computed(
    () =>
        branchStore.activeBranch?.uuid ??
        branchStore.lastSelectedBranch?.uuid ??
        branchStore.branches?.[0]?.uuid,
);

const paymentUrl = computed(() =>
    typeof route.query.ref === "string" && route.query.ref
        ? subscriptionInvoiceLink(route.query.ref, branchUuid.value)
        : null,
);

const isPreparing = ref(true);

async function prepareWorkspace() {
    isPreparing.value = true;

    try {
        for (let attempt = 0; attempt < 8; attempt++) {
            await branchStore.refreshBranch();

            if (branchStore.branches?.length) break;

            await new Promise((resolve) => setTimeout(resolve, 1500));
        }
    } finally {
        isPreparing.value = false;
    }
}

onMounted(async () => {
    await fetchAuthUser();
    await prepareWorkspace();
});

const dashboardUrl = computed(() =>
    branchUuid.value ? `/app/branches/${branchUuid.value}/dashboard` : null,
);
</script>
