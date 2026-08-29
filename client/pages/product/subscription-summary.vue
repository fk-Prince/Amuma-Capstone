<template>
    <div
        class="min-h-[calc(100vh-90px)] w-full bg-slate-50 flex items-center justify-center px-4 dark:bg-secondary"
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

            <NuxtLink
                v-if="isSuccess"
                @click="handleMenuClick"
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
import { handleMenuClick } from "~/config/profileMenu";

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
