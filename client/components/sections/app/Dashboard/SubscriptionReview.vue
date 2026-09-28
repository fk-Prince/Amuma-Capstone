<template>
    <div v-if="isPending" class="flex h-full items-center justify-center px-6">
        <div class="w-full max-w-md text-center">
            <template v-if="isRejected">
                <div
                    class="relative mx-auto mb-6 flex h-20 w-20 items-center justify-center"
                >
                    <span
                        class="absolute inset-0 rounded-full bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-500/15 dark:to-rose-500/10"
                    />
                    <svg
                        class="relative h-9 w-9 text-rose-600 dark:text-rose-300"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M9 9l6 6M15 9l-6 6" />
                    </svg>
                </div>

                <span
                    class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                    {{ agencyRejected ? "Application rejected" : "Request rejected" }}
                </span>

                <h2 class="text-xl font-bold text-secondary dark:text-white">
                    {{
                        agencyRejected
                            ? "Your Agency Application Was Rejected"
                            : "This Branch Request Was Rejected"
                    }}
                </h2>

                <p class="mt-2 text-sm leading-6 text-muted-DEFAULT">
                    {{
                        agencyRejected
                            ? "Your agency and this branch could not be approved onto the platform."
                            : "This branch could not be approved onto the platform."
                    }}
                </p>

                <div
                    v-if="agencyName || branchName"
                    class="mt-5 divide-y divide-muted-light rounded-xl border border-muted-light bg-muted-light/60 text-left dark:border-white/10 dark:bg-white/5"
                >
                    <div
                        v-if="agencyName"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-muted-DEFAULT"
                            >
                                Agency
                            </p>
                            <p class="mt-0.5 text-sm font-semibold text-secondary dark:text-white">
                                {{ agencyName }}
                            </p>
                        </div>
                        <span
                            v-if="agencyRejected"
                            class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            Rejected
                        </span>
                    </div>
                    <div
                        v-if="branchName"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-muted-DEFAULT"
                            >
                                Branch
                            </p>
                            <p class="mt-0.5 text-sm font-semibold text-secondary dark:text-white">
                                {{ branchName }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            Rejected
                        </span>
                    </div>
                </div>

                <div
                    class="mt-4 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-left dark:border-rose-500/20 dark:bg-rose-500/10"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-300"
                    >
                        Reason
                    </p>
                    <p class="mt-1 text-sm text-rose-800 dark:text-rose-200">
                        {{ rejectionReason || "No reason was provided." }}
                    </p>
                </div>

                <template v-if="agencyRejected">
                    <button
                        type="button"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600"
                        @click="showReapply = true"
                    >
                        <RotateCw class="h-4 w-4" />
                        Fix details & reapply
                    </button>

                    <p
                        v-if="branch?.resubmit_requires_payment"
                        class="mt-2 text-xs text-muted-DEFAULT"
                    >
                        {{ purchaseReason }} You'll pay again after updating the
                        details.
                    </p>
                </template>

                <p v-else class="mt-4 text-xs leading-5 text-muted-DEFAULT">
                    You can resubmit this branch from Manage Branches in any of
                    your verified branches.
                </p>
            </template>

            <template v-else>
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
                        class="relative h-9 w-9 text-primary-600 dark:text-primary-300"
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
                    class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-primary-500" />
                    Review in progress
                </span>

                <h2 class="text-xl font-bold text-secondary dark:text-white">
                    Your Subscription Is Under Review
                </h2>

                <p class="mt-2 text-sm leading-6 text-muted-DEFAULT">
                    We're verifying your account and branch details. This usually
                    takes
                    <span class="font-medium text-secondary dark:text-white">1–2 business days</span
                    >.
                </p>

                <div
                    v-if="agencyName || branchName"
                    class="mt-5 divide-y divide-muted-light rounded-xl border border-muted-light bg-muted-light/60 text-left dark:border-white/10 dark:bg-white/5"
                >
                    <div v-if="agencyName" class="px-4 py-3">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-muted-DEFAULT"
                        >
                            Agency
                        </p>
                        <p class="mt-0.5 text-sm font-semibold text-secondary dark:text-white">
                            {{ agencyName }}
                        </p>
                    </div>
                    <div v-if="branchName" class="px-4 py-3">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-muted-DEFAULT"
                        >
                            Branch
                        </p>
                        <p class="mt-0.5 text-sm font-semibold text-secondary dark:text-white">
                            {{ branchName }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 flex items-center justify-center gap-2 rounded-xl border border-accent-100 bg-accent-50 px-4 py-3 text-sm text-accent-700 dark:border-accent-500/20 dark:bg-accent-500/15 dark:text-accent-300"
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
            </template>
        </div>

        <ResubmitBranchModal
            v-if="showReapply && branch && agencyRejected"
            :branch="branch"
            :acting-branch-uuid="branch.uuid"
            :rejection-reason="rejectionReason"
            :requires-purchase="!!branch.resubmit_requires_payment"
            :purchase-reason="branch.resubmit_requires_payment ? purchaseReason : null"
            :agency="branch.agency"
            @close="showReapply = false"
            @resubmitted="onReapplied"
        />
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { RotateCw } from "lucide-vue-next";
import { useBranchStore } from "@/stores/branch";
import ResubmitBranchModal from "~/components/sections/app/branches/ResubmitBranchModal.vue";

const branchStore = useBranchStore();

const branch = computed(() => branchStore.activeBranch);

const showReapply = ref(false);

const agencyRejected = computed(() => branch.value?.agency?.status === "rejected");

const purchaseReason = computed(() => {
    switch (branch.value?.resubmit_subscription_status) {
        case "rejected":
            return "The payment was refunded when this request was rejected.";
        case "expired":
            return "This branch's subscription has expired.";
        default:
            return "This branch's subscription has no free slot left.";
    }
});

const onReapplied = (result) => {
    showReapply.value = false;

    const updated = result?.branch;
    if (!updated) return;

    const agency = result.agency;

    branchStore.branches = branchStore.branches.map((item) => {
        const sameAgency =
            agency && item.agency?.agency_id === agency.agency_id;

        const next = sameAgency
            ? {
                  ...item,
                  agency: {
                      ...item.agency,
                      ...agency,
                      location: agency.location ?? item.agency.location,
                  },
              }
            : item;

        if (item.uuid !== updated.uuid) return next;

        return {
            ...next,
            name: updated.name,
            email: updated.email,
            contact_number: updated.contact_number,
            description: updated.description,
            image: updated.image,
            document: updated.document,
            settings: updated.settings,
            status: updated.status,
            subscription_status: "pending",
            rejection_reason: null,
            resubmit_requires_payment: false,
            resubmit_subscription_status: null,
            location: {
                ...updated.location,
                address: updated.location?.full_address,
            },
        };
    });
};

const isRejected = computed(
    () => branch.value?.subscription_status === "rejected",
);

const isPending = computed(() => {
    return (
        branch.value?.agency?.status !== "verified" ||
        branch.value?.status !== "verified"
    );
});

const agencyName = computed(() => branch.value?.agency?.name ?? null);
const branchName = computed(() => branch.value?.name ?? null);
const rejectionReason = computed(() => branch.value?.rejection_reason ?? null);
</script>
