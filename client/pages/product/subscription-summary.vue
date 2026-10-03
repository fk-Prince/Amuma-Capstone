<template>
    <div
        class="min-h-screen w-full bg-slate-50 flex items-center justify-center px-4 dark:bg-secondary"
    >
        <div
            class="w-full max-w-md bg-white rounded-3xl shadow-sm p-8 text-center dark:bg-secondary"
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
                    Payment Successful
                </h1>

                <p class="mt-3 text-sm text-slate-500 dark:text-gray-400">
                    We'll verify your subscription and notify you of your
                    request's status within 1-2 business days.
                    <template v-if="withTrial">
                        Your 1-month free testing starts once your branch is
                        approved, and your paid year begins right after it.
                        You can cancel for a full refund any time during the
                        test.
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
                <NuxtLink
                    v-if="dashboardUrl"
                    :to="dashboardUrl"
                    class="mt-8 inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    View Dashboard
                </NuxtLink>

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
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useBranchStore } from "~/stores/branch";
import { fetchAuthUser } from "~/composables/useAuthUser";
import { subscriptionInvoiceLink } from "~/utils/subscriptionInvoice";

const branchStore = useBranchStore();
const route = useRoute();

useHead({
    title: "Subscription Status",
});

const withTrial = computed(() => route.query.trial !== "0");

const isSuccess = computed(() => {
    return route.query.status === "true";
});


const paymentUrl = computed(() =>
    typeof route.query.ref === "string" && route.query.ref
        ? subscriptionInvoiceLink(route.query.ref)
        : null,
);

onMounted(async () => {
    await fetchAuthUser();

    for (let attempt = 0; attempt < 8; attempt++) {
        await branchStore.refreshBranch();

        if (branchStore.branches?.length) break;

        await new Promise((resolve) => setTimeout(resolve, 1500));
    }
});

const dashboardUrl = computed(() => {
    const uuid =
        branchStore.activeBranch?.uuid ??
        branchStore.lastSelectedBranch?.uuid ??
        branchStore.branches?.[0]?.uuid;

    return uuid ? `/app/branches/${uuid}/dashboard` : null;
});
</script>
