<template>
    <div v-if="isPending" class="flex h-full items-center justify-center px-6">
        <div class="w-full max-w-md text-center">
            <div
                class="relative mx-auto mb-6 flex h-20 w-20 items-center justify-center"
            >
                <span
                    class="absolute inset-0 animate-ping rounded-full bg-primary-200 opacity-40"
                    style="animation-duration: 2.5s"
                />
                <span
                    class="absolute inset-0 rounded-full bg-gradient-to-br from-primary-50 to-primary-100"
                />
                <svg
                    class="relative h-9 w-9 text-primary-600"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 2" />
                </svg>
            </div>

            <span
                class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary-700"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-primary-500" />
                Review in progress
            </span>

            <h2 class="text-xl font-bold text-secondary">
                Your Subscription Is Under Review
            </h2>

            <p class="mt-2 text-sm leading-6 text-muted-DEFAULT">
                We're verifying your account and branch details. This usually
                takes
                <span class="font-medium text-secondary">1–2 business days</span
                >.
            </p>

            <div
                v-if="agencyName || branchName"
                class="mt-5 divide-y divide-muted-light rounded-xl border border-muted-light bg-muted-light/60 text-left"
            >
                <div v-if="agencyName" class="px-4 py-3">
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-muted-DEFAULT"
                    >
                        Agency
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-secondary">
                        {{ agencyName }}
                    </p>
                </div>
                <div v-if="branchName" class="px-4 py-3">
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-muted-DEFAULT"
                    >
                        Branch
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-secondary">
                        {{ branchName }}
                    </p>
                </div>
            </div>

            <div
                class="mt-4 flex items-center justify-center gap-2 rounded-xl border border-accent-100 bg-accent-50 px-4 py-3 text-sm text-accent-700"
            >
                <svg
                    class="h-4 w-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M22 6l-10 7L2 6" />
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                </svg>
                We'll email you once the review is complete
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useBranchStore } from "@/stores/branch";

const branchStore = useBranchStore();

const branch = computed(() => branchStore.activeBranch);

const isPending = computed(() => {
    return !branch.value?.agency?.is_verified || !branch.value?.is_verified;
});

const agencyName = computed(() => branch.value?.agency?.name ?? null);
const branchName = computed(() => branch.value?.name ?? null);
</script>
