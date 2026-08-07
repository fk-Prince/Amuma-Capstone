<!-- <template>
    <div
        class="min-h-[calc(100vh-90px)] w-full bg-gray-50 flex items-center justify-center px-4"
    >
        <div
            class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 text-center"
        >
            <template v-if="isSuccess">
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-green-100"
                >
                    <svg
                        class="h-8 w-8 text-green-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900">
                    Payment Successful!
                </h1>

                <p class="mt-2 text-gray-600">
                    Your subscription has been activated successfully.
                </p>
            </template>

            <template v-else>
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-red-100"
                >
                    <svg
                        class="h-8 w-8 text-red-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900">Payment Failed</h1>

                <p class="mt-2 text-gray-600">
                    We couldn't complete your payment. Please try again.
                </p>
            </template>

            <div class="mt-6 rounded-lg bg-gray-50 px-4 py-3">
                <p class="text-sm text-gray-500">Payment status</p>
                <p
                    class="mt-1 font-semibold"
                    :class="isSuccess ? 'text-green-600' : 'text-red-600'"
                >
                    {{
                        isSuccess
                            ? "Payment completed successfully"
                            : "Payment failed"
                    }}
                </p>
            </div>

            <NuxtLink
                to="/"
                class="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-green-700"
            >
                Continue
            </NuxtLink>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useBranchStore } from "~/stores/branch";

const useBranch = useBranchStore();

onMounted(async () => {
    await useBranch.refreshBranch();
});

useHead({
    title: "Subscription Status",
});

const route = useRoute();
const isSuccess = computed(() => route.query.status === "success");
</script> -->

<template>
    <div
        class="min-h-[calc(100vh-90px)] w-full bg-slate-50 flex items-center justify-center px-4"
    >
        <div
            class="w-full max-w-md bg-white rounded-3xl shadow-sm p-8 text-center"
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

                <h1 class="text-2xl font-bold text-slate-900">
                    Payment Successful
                </h1>

                <p class="mt-3 text-sm text-slate-500">
                    Your subscription has been activated successfully.
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

                <h1 class="text-2xl font-bold text-slate-900">
                    Payment Failed
                </h1>

                <p class="mt-3 text-sm text-slate-500">
                    We couldn't complete your payment. Please try again.
                </p>
            </template>

            <div class="mt-8 rounded-2xl bg-slate-50 px-5 py-4 text-left">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500"> Payment Status </span>

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

            <NuxtLink
                v-if="isSuccess && dashboardUrl"
                :to="dashboardUrl"
                class="mt-8 inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
            >
                View Dashboard
            </NuxtLink>

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
import { computed, onMounted } from "vue";
import { useBranchStore } from "~/stores/branch";
import { fetchAuthUser } from "~/composables/useAuthUser";

const branchStore = useBranchStore();
const route = useRoute();

useHead({
    title: "Subscription Status",
});

const isSuccess = computed(() => {
    return route.query.status === "true";
});

onMounted(async () => {
    await fetchAuthUser();
    await branchStore.refreshBranch();

    if (!branchStore.branches.length) {
        await branchStore.fetchBranches();
    }
});

const dashboardUrl = computed(() => {
    const uuid =
        branchStore.activeBranch?.uuid ?? branchStore.branches?.[0]?.uuid;

    if (!uuid) {
        return null;
    }

    return `/app/branches/${uuid}/dashboard`;
});
</script>
