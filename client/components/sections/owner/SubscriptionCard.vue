<template>
    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-secondary"
    >
        <div
            class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-white/10"
        >
            <div class="flex min-w-0 items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent dark:bg-accent-500/10"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5"
                    >
                        <path d="M3 21h18" />
                        <path d="M5 21V5l7-3 7 3v16" />
                        <path d="M9 21v-4h6v4" />
                        <path d="M9 8h.01" />
                        <path d="M15 8h.01" />
                        <path d="M9 12h.01" />
                        <path d="M15 12h.01" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <p
                        class="text-[10px] font-semibold uppercase tracking-widest text-accent-600 dark:text-accent-300"
                    >
                        Agency
                    </p>

                    <h2
                        class="mt-0.5 truncate text-sm font-semibold text-secondary dark:text-white"
                    >
                        {{ agency.name }}
                    </h2>

                    <p class="truncate text-xs text-muted dark:text-gray-400">
                        {{ agency.email }}
                    </p>

                    <p
                        v-if="agency.address"
                        class="mt-1 flex items-start gap-1.5 text-[11px] text-muted dark:text-gray-400"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="mt-0.5 h-3 w-3 shrink-0 text-accent"
                        >
                            <path
                                d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                            />
                            <circle cx="12" cy="10" r="2.5" />
                        </svg>

                        <span>{{ agency.address }}</span>
                    </p>

                    <div
                        v-if="hasAgencyDocuments"
                        class="mt-2 flex flex-wrap gap-1.5"
                    >
                        <DocumentLink
                            v-if="agency.id_front"
                            :url="agency.id_front"
                            label="ID Front"
                        />

                        <DocumentLink
                            v-if="agency.id_back"
                            :url="agency.id_back"
                            label="ID Back"
                        />

                        <DocumentLink
                            v-if="agency.document"
                            :url="agency.document"
                            label="Agency Document"
                        />
                    </div>
                </div>
            </div>

            <span
                class="shrink-0 rounded-full px-2 py-1 text-[10px] font-medium"
                :class="
                    agency.is_verified
                        ? 'bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-300'
                        : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'
                "
            >
                {{ agency.is_verified ? "Verified" : "Unverified" }}
            </span>
        </div>

        <div
            class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-3 dark:border-white/10"
        >
            <div class="flex min-w-0 items-center gap-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-500/10"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                    >
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <path d="M16 2v4" />
                        <path d="M8 2v4" />
                        <path d="M3 10h18" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <p
                            class="truncate text-sm font-semibold text-secondary dark:text-white"
                        >
                            {{ subscription.plan.name }}
                        </p>

                        <span
                            class="rounded-md bg-primary-50 px-1.5 py-0.5 text-[10px] font-semibold text-primary dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            {{ subscription.plan.plan_code }}
                        </span>
                    </div>

                    <p class="mt-0.5 text-[11px] text-muted dark:text-gray-400">
                        {{ subscription.billing_interval }} ·
                        {{ formatDate(subscription.start_date) }} →
                        {{ formatDate(subscription.end_date) }}
                    </p>
                </div>
            </div>

            <span
                class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold capitalize"
                :class="statusClass(subscription.status)"
            >
                {{ subscription.status }}
            </span>
        </div>

        <div
            v-if="canSwitchBranch"
            class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-white/10 dark:bg-white/5"
        >
            <div class="flex items-center justify-between gap-3">
                <p
                    class="text-[10px] font-semibold uppercase tracking-widest text-muted dark:text-gray-500"
                >
                    Branches on this subscription
                </p>

                <span
                    class="shrink-0 text-[10px] font-semibold tabular-nums"
                    :class="
                        slotsLeft > 0
                            ? 'text-muted dark:text-gray-400'
                            : 'text-amber-600 dark:text-amber-300'
                    "
                >
                    {{ coveredBranches.length }} of {{ branchLimit }} used
                </span>
            </div>

            <div class="mt-2 flex items-center gap-1">
                <span
                    v-for="slot in branchLimit"
                    :key="slot"
                    class="h-1 flex-1 rounded-full"
                    :class="
                        slot <= coveredBranches.length
                            ? 'bg-primary'
                            : 'bg-slate-200 dark:bg-white/10'
                    "
                />
            </div>

            <div class="mt-2.5 flex flex-wrap gap-1.5">
                <button
                    v-for="covered in coveredBranches"
                    :key="covered.uuid"
                    type="button"
                    :disabled="!canSwitchBranch"
                    class="inline-flex max-w-full items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-medium transition disabled:cursor-default"
                    :class="[
                        covered.uuid === selectedBranch?.uuid
                            ? 'border-primary/30 bg-primary/10 text-primary dark:border-primary-500/30 dark:text-primary-300'
                            : 'border-slate-200 bg-white text-slate-600 dark:border-white/10 dark:bg-secondary dark:text-gray-300',
                        canSwitchBranch
                            ? 'hover:border-primary/30 hover:text-primary'
                            : '',
                    ]"
                    @click="canSwitchBranch && (selectedUuid = covered.uuid)"
                >
                    <span
                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                        :class="coveredDotClass(covered.status)"
                    />

                    <span class="truncate">{{ covered.name }}</span>
                </button>

                <span
                    v-for="n in slotsLeft"
                    :key="`open-${n}`"
                    class="inline-flex items-center rounded-full border border-dashed border-slate-200 px-2 py-0.5 text-[10px] text-slate-400 dark:border-white/10 dark:text-gray-500"
                >
                    Open slot
                </span>
            </div>
        </div>

        <section v-if="selectedBranch" class="px-5 py-4">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary dark:bg-primary-500/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-4 w-4"
                        >
                            <path d="M3 21h18" />
                            <path d="M5 21V5l7-3 7 3v16" />
                            <path d="M9 21v-4h6v4" />
                        </svg>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-widest text-primary dark:text-primary-300"
                        >
                            Branch
                        </p>

                        <p class="text-[11px] text-muted dark:text-gray-400">
                            {{ branchSubtitle }}
                        </p>
                    </div>
                </div>

                <span
                    class="shrink-0 rounded-full px-2 py-1 text-[10px] font-medium"
                    :class="
                        selectedBranch.is_verified
                            ? 'bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-300'
                            : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'
                    "
                >
                    {{ selectedBranch.is_verified ? "Verified" : "Unverified" }}
                </span>
            </div>

            <h3
                class="truncate text-sm font-semibold text-secondary dark:text-white"
            >
                {{ selectedBranch.name }}
            </h3>

            <p
                v-if="selectedBranch.email"
                class="mt-1 truncate text-xs text-muted dark:text-gray-400"
            >
                {{ selectedBranch.email }}
            </p>

            <p
                v-if="selectedBranch.tin"
                class="mt-1.5 flex items-center gap-1.5 text-[11px] text-muted dark:text-gray-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="h-3 w-3 shrink-0 text-primary"
                >
                    <rect x="3" y="4" width="18" height="16" rx="2" />
                    <path d="M7 9h6" />
                    <path d="M7 13h10" />
                </svg>

                <span>TIN {{ selectedBranch.tin }}</span>
            </p>

            <p
                v-if="selectedBranch.address"
                class="mt-2 flex items-start gap-1.5 text-[11px] text-muted dark:text-gray-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="mt-0.5 h-3 w-3 shrink-0 text-primary"
                >
                    <path
                        d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                    />
                    <circle cx="12" cy="10" r="2.5" />
                </svg>

                <span>{{ selectedBranch.address }}</span>
            </p>

            <div class="mt-3">
                <p
                    class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500"
                >
                    Documents
                </p>

                <DocumentLink
                    v-if="selectedBranch.document"
                    :url="selectedBranch.document"
                    label="Branch Document"
                />

                <span v-else class="text-[10px] text-muted dark:text-gray-500">
                    No document
                </span>
            </div>
        </section>

        <div
            v-if="latestPayment && isFirstBranch"
            class="flex items-center justify-between gap-4 border-t border-slate-100 px-5 py-3 dark:border-white/10"
        >
            <div
                class="flex items-center gap-2 text-[11px] text-muted dark:text-gray-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="h-3.5 w-3.5 shrink-0 text-primary"
                >
                    <rect x="2" y="5" width="20" height="14" rx="2" />
                    <path d="M2 10h20" />
                </svg>

                <span v-if="latestPayment.masked_card_number">
                    {{ latestPayment.masked_card_number }}
                </span>

                <span v-else>No card on file</span>
            </div>

            <div class="flex items-center gap-2 text-[11px]">
                <span class="font-semibold text-secondary dark:text-white">
                    {{ formatCurrency(latestPayment.price) }}
                </span>

                <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                    :class="
                        latestPayment.status === 'paid'
                            ? 'bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-300'
                            : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300'
                    "
                >
                    {{ latestPayment.status }}
                </span>
            </div>
        </div>

        <div
            v-if="agency.registered_by"
            class="flex items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-2.5 text-[11px] text-muted dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                class="h-3.5 w-3.5 shrink-0"
            >
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21c0-4 3.6-6 8-6s8 2 8 6" />
            </svg>

            Agency Registered by

            <span
                class="truncate font-medium text-secondary dark:text-gray-200"
            >
                {{ agency.registered_by }}
            </span>
        </div>

        <div
            v-if="canShowActions"
            class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3 dark:border-white/10 dark:bg-white/5"
        >
            <button
                type="button"
                :disabled="!!actionLoading"
                class="inline-flex items-center gap-1.5 rounded-lg border border-danger/30 bg-white px-4 py-2 text-[11px] font-semibold text-danger transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-secondary dark:hover:bg-red-500/10"
                @click="emit('reject', subscription)"
            >
                <svg
                    v-if="actionLoading === 'reject'"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-3 w-3 animate-spin"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                {{
                    actionLoading === "reject"
                        ? "Rejecting…"
                        : isFirstBranch
                          ? "Reject & Refund"
                          : "Reject"
                }}
            </button>

            <button
                type="button"
                :disabled="!!actionLoading"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-5 py-2 text-[11px] font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-60"
                @click="emit('approve', subscription)"
            >
                <svg
                    v-if="actionLoading === 'approve'"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-3 w-3 animate-spin"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                {{ actionLoading === "approve" ? "Approving…" : "Approve" }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import DocumentLink from "~/components/ui/DocumentLink.vue";
import { formatCurrency } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import type { SubscriptionPaymentRecord } from "~/types/subscription";

interface CoveredBranch {
    uuid: string;
    name: string;
    email?: string | null;
    address?: string | null;
    document?: string | null;
    tin?: string | null;
    is_verified: boolean;
    status: "pending" | "approved" | "rejected";
}

interface SubscriptionCardData {
    uuid: string;
    billing_interval: "YEARLY" | "MONTHLY";
    status: "pending" | "active" | "inactive" | "expired";
    start_date: string;
    end_date: string;
    is_first_branch?: boolean;
    payments?: SubscriptionPaymentRecord[];

    branch: {
        branch_id: number;
        uuid: string;
        name: string;
        email: string;
        address: string | null;
        status: string;
        is_verified: boolean;
        document: string | null;
        tin?: string | null;

        agency: {
            agency_id: number;
            uuid: string;
            name: string;
            email: string;
            address: string | null;
            is_verified: boolean;
            id_front: string | null;
            id_back: string | null;
            document: string | null;
            registered_by?: string | null;
        };
    };

    plan: {
        plan_id: number;
        name: string;
        plan_code: string;
    };

    subscription?: {
        branch_limit?: number;
        covered_branches: CoveredBranch[];
    };
}

const props = withDefaults(
    defineProps<{
        subscription: SubscriptionCardData;
        showActions?: boolean;
        actionLoading?: "approve" | "reject" | null;
    }>(),
    {
        showActions: true,
        actionLoading: null,
    },
);

const emit = defineEmits<{
    approve: [subscription: SubscriptionCardData];
    reject: [subscription: SubscriptionCardData];
}>();

const agency = computed(() => props.subscription.branch.agency);

const latestPayment = computed(() => {
    const payments = props.subscription.payments;

    if (!payments?.length) {
        return null;
    }

    return [...payments].sort((a, b) =>
        (b.created_at ?? "").localeCompare(a.created_at ?? ""),
    )[0];
});

const hasAgencyDocuments = computed(
    () =>
        !!(
            agency.value.id_front ||
            agency.value.id_back ||
            agency.value.document
        ),
);

const isPending = computed(() => props.subscription.status === "pending");
const canShowActions = computed(() => isPending.value && props.showActions);

const isFirstBranch = computed(
    () => props.subscription.is_first_branch !== false,
);

const coveredBranches = computed<CoveredBranch[]>(
    () => props.subscription.subscription?.covered_branches ?? [],
);

const branchLimit = computed(
    () => props.subscription.subscription?.branch_limit ?? 5,
);

const slotsLeft = computed(() =>
    Math.max(0, branchLimit.value - coveredBranches.value.length),
);

const selectedUuid = ref(props.subscription.branch.uuid);

watch(
    () => props.subscription.branch.uuid,
    (uuid) => {
        selectedUuid.value = uuid;
    },
);

// A pending card is a verification request: it stays on the branch under
// review. Once approved, the card is a window on the whole subscription and
// the slots switch between its branches.
const canSwitchBranch = computed(() => !isPending.value);

const selectedBranch = computed<CoveredBranch | null>(() => {
    const covered = coveredBranches.value;
    const own = props.subscription.branch as unknown as CoveredBranch;

    if (!canSwitchBranch.value) {
        return (
            covered.find((branch) => branch.uuid === own.uuid) ?? own ?? null
        );
    }

    return (
        covered.find((branch) => branch.uuid === selectedUuid.value) ??
        covered.find((branch) => branch.uuid === own.uuid) ??
        own ??
        null
    );
});

const branchSubtitle = computed(() => {
    if (isPending.value) return "Needs verification";

    return selectedBranch.value?.uuid === props.subscription.branch.uuid
        ? "Covered branch"
        : "Also on this subscription";
});

const coveredDotClass = (status: string) => {
    switch (status) {
        case "approved":
            return "bg-accent-500";
        case "rejected":
            return "bg-rose-400";
        default:
            return "bg-amber-400";
    }
};

const statusClass = (status: SubscriptionCardData["status"]) => {
    switch (status) {
        case "pending":
            return "bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-300";
        case "active":
            return "bg-accent-50 dark:bg-accent-500/10 text-accent-600 dark:text-accent-300";
        case "inactive":
            return "bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-gray-300";
        case "expired":
            return "bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-300";
        default:
            return "bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-gray-300";
    }
};
</script>
