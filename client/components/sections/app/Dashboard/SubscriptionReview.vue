<template>
    <div v-if="isRejected" class="flex h-full items-center justify-center px-6">
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
                    Review complete · Not approved
                </span>

                <h2 class="text-xl font-bold text-secondary dark:text-white">
                    {{
                        agencyRejected
                            ? "Your Application Needs Changes"
                            : "This Branch Needs Changes"
                    }}
                </h2>

                <p class="mt-2 text-sm leading-6 text-muted-DEFAULT">
                    {{
                        agencyRejected
                            ? "We reviewed your agency and branch details and couldn't approve them yet. Fix what's listed below, then reapply."
                            : "We reviewed this branch's details and couldn't approve it yet. Fix what's listed below, then resubmit it."
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
                            Not approved
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
                            Not approved
                        </span>
                    </div>
                    <div
                        v-if="agencyRejected && rejectedPlanLabel"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-muted-DEFAULT"
                            >
                                Plan
                            </p>
                            <p class="mt-0.5 text-sm font-semibold text-secondary dark:text-white">
                                {{ rejectedPlanLabel }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            Kept
                        </span>
                    </div>
                </div>

                <div
                    class="mt-4 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-left dark:border-rose-500/20 dark:bg-rose-500/10"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-300"
                    >
                        What needs fixing
                    </p>
                    <p class="mt-1 text-sm leading-6 text-rose-800 dark:text-rose-200">
                        {{
                            rejectionReason ||
                            "No reason was given. Contact Customer Support to find out what to change."
                        }}
                    </p>
                </div>

                <p
                    v-if="demoLocked && switchLink"
                    class="mt-4 rounded-xl border border-muted-light px-4 py-3 text-left text-xs leading-5 text-muted-DEFAULT dark:border-white/10"
                >
                    The demo is locked until you reapply. It opens again while
                    we review your new details.
                </p>

                <template v-if="agencyRejected">
                    <ActionButton
                        variant="primary"
                        class="mt-5 w-full"
                        extra-class="w-full !rounded-xl !py-3 font-semibold"
                        :disabled="!canReapply"
                        tooltip="You need permission to update Manage Subscription to reapply."
                        @click="showReapply = true"
                    >
                        <RotateCw class="h-4 w-4" />
                        Fix details & reapply
                    </ActionButton>

                    <p class="mt-3 text-xs leading-5 text-muted-DEFAULT">
                        We'll review it again within 1–2 business days and
                        email you the result.
                        <template v-if="rejectedPlanIsTrial">
                            Your free trial restarts when you reapply.
                        </template>
                    </p>
                </template>

                <p v-else class="mt-4 text-xs leading-5 text-muted-DEFAULT">
                    To resubmit this branch, open Manage Subscription and click
                    Resubmit Subscription.
                </p>

                <a
                    v-if="demoLink"
                    :href="demoLink"
                    title="Open a copy of this branch filled with demo patients. Nothing you do there touches your real branch."
                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-muted-light px-4 py-3 text-sm font-semibold text-secondary transition hover:bg-muted-light/60 dark:border-white/15 dark:text-white dark:hover:bg-white/5"
                >
                    <PlayCircle class="h-4 w-4" />
                    Try with demo data
                </a>
                <p
                    v-if="demoLink"
                    class="mt-2 text-xs leading-5 text-muted-DEFAULT"
                >
                    You can keep using the demo while you fix your details.
                </p>
            </template>

            <button
                v-if="canContactSupport"
                type="button"
                class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary transition hover:underline"
                @click="openSupport()"
            >
                <LifeBuoy class="h-4 w-4" />
                Need help? Contact Customer Support
            </button>
        </div>

        <ResubmitBranchModal
            v-if="showReapply && branch && agencyRejected"
            :branch="branch"
            :acting-branch-uuid="branch.uuid"
            :rejection-reason="rejectionReason"
            :requires-purchase="false"
            :agency="branch.agency"
            show-plan
            @close="showReapply = false"
            @resubmitted="onReapplied"
        />
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { LifeBuoy, PlayCircle, RotateCw } from "lucide-vue-next";
import { useSandbox } from "~/composables/useSandbox";
import { isBranchRejected } from "~/utils/branchAccess";
import { useBranchStore } from "@/stores/branch";
import { useSupportCenter } from "~/composables/useSupportCenter";
import ResubmitBranchModal from "~/components/sections/app/branches/ResubmitBranchModal.vue";
import ActionButton from "~/components/ui/ActionButton.vue";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";

const branchStore = useBranchStore();

const { canUpdate, hasModule } = usePermissions();
const canReapply = computed(() => canUpdate(Modules.ManageSubscription));
const canContactSupport = computed(() => hasModule(Modules.ManageSubscription));
const { openSupport } = useSupportCenter();

const branch = computed(() => branchStore.activeBranch);

const { isSandbox, switchLink } = useSandbox();
const demoLocked = computed(
    () =>
        !isSandbox.value &&
        branch.value?.resubmit_subscription_status === "rejected",
);
const demoLink = computed(() =>
    isSandbox.value || demoLocked.value ? "" : switchLink.value,
);

const showReapply = ref(false);

const agencyRejected = computed(() => branch.value?.agency?.status === "rejected");

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
            subscription_awaiting_payment:
                result.subscription?.status === "pending" ||
                next.subscription_awaiting_payment,
            rejection_reason: null,
            resubmit_requires_payment: false,
            resubmit_subscription_status: null,
            resubmit_plan: null,
            location: {
                ...updated.location,
                address: updated.location?.full_address,
            },
        };
    });
};

const isRejected = computed(() => isBranchRejected(branch.value));

const agencyName = computed(() => branch.value?.agency?.name ?? null);
const branchName = computed(() => branch.value?.name ?? null);
const planLabel = computed(() => branch.value?.plan?.[0]?.name ?? null);
const rejectionReason = computed(() => branch.value?.rejection_reason ?? null);

const rejectedPlanIsTrial = computed(
    () => branch.value?.subscription_mode !== "live",
);

const rejectedPlanLabel = computed(() => {
    const name = branch.value?.resubmit_plan?.name ?? planLabel.value;

    if (!name) return null;

    return rejectedPlanIsTrial.value ? `${name} · 2-week free trial` : name;
});
</script>
