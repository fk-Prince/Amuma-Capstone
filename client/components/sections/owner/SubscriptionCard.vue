<template>
    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
    >
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-5 w-5"
                        >
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4" />
                            <path d="M8 2v4" />
                            <path d="M3 10h18" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2
                                class="truncate text-sm font-semibold text-secondary"
                            >
                                {{ subscription.plan.name }}
                            </h2>

                            <span
                                class="rounded-md bg-primary-50 px-1.5 py-0.5 text-[9px] font-semibold text-primary"
                            >
                                {{ subscription.plan.plan_code }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-[11px] text-muted">
                            {{ subscription.billing_interval }} subscription
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold capitalize"
                        :class="statusClass(subscription.status)"
                    >
                        {{ subscription.status }}
                    </span>

                    <p class="mt-1 text-[10px] text-muted">
                        {{ subscription.start_date }} →
                        {{ subscription.end_date }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="grid grid-cols-1 divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0"
        >
            <section class="p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent-50 text-accent"
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
                                <path d="M9 8h.01" />
                                <path d="M15 8h.01" />
                                <path d="M9 12h.01" />
                                <path d="M15 12h.01" />
                            </svg>
                        </div>

                        <div>
                            <p
                                class="text-[9px] font-semibold uppercase tracking-widest text-accent-600"
                            >
                                Agency
                            </p>

                            <p class="text-[11px] text-muted">
                                Registered agency
                            </p>
                        </div>
                    </div>

                    <span
                        class="rounded-full px-2 py-1 text-[9px] font-medium"
                        :class="
                            subscription.branch.agency.is_verified
                                ? 'bg-accent-50 text-accent-600'
                                : 'bg-amber-50 text-amber-600'
                        "
                    >
                        {{
                            subscription.branch.agency.is_verified
                                ? "Verified"
                                : "Unverified"
                        }}
                    </span>
                </div>

                <div>
                    <h3 class="truncate text-sm font-semibold text-secondary">
                        {{ subscription.branch.agency.name }}
                    </h3>

                    <p class="mt-1 truncate text-xs text-muted">
                        {{ subscription.branch.agency.email }}
                    </p>

                    <div
                        class="mt-2 flex items-start gap-1.5 text-[11px] text-muted"
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

                        <span>
                            {{
                                subscription.branch.agency.address ||
                                "No address provided"
                            }}
                        </span>
                    </div>
                </div>

                <div class="mt-4">
                    <p
                        class="mb-2 text-[9px] font-semibold uppercase tracking-wider text-muted"
                    >
                        Documents
                    </p>

                    <div class="flex flex-wrap gap-1.5">
                        <DocumentLink
                            v-if="subscription.branch.agency.id_front"
                            :url="subscription.branch.agency.id_front"
                            label="ID Front"
                        />

                        <DocumentLink
                            v-if="subscription.branch.agency.id_back"
                            :url="subscription.branch.agency.id_back"
                            label="ID Back"
                        />

                        <DocumentLink
                            v-if="subscription.branch.agency.document"
                            :url="subscription.branch.agency.document"
                            label="Agency Document"
                        />

                        <span
                            v-if="!hasAgencyDocuments"
                            class="text-[10px] text-muted"
                        >
                            No documents
                        </span>
                    </div>
                </div>
            </section>

            <section class="p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary"
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
                                <path d="M9 8h.01" />
                                <path d="M15 8h.01" />
                                <path d="M9 12h.01" />
                                <path d="M15 12h.01" />
                            </svg>
                        </div>

                        <div>
                            <p
                                class="text-[9px] font-semibold uppercase tracking-widest text-primary-600"
                            >
                                Branch
                            </p>

                            <p class="text-[11px] text-muted">
                                Subscription branch
                            </p>
                        </div>
                    </div>

                    <span
                        class="rounded-full px-2 py-1 text-[9px] font-medium"
                        :class="
                            subscription.branch.is_verified
                                ? 'bg-accent-50 text-accent-600'
                                : 'bg-amber-50 text-amber-600'
                        "
                    >
                        {{
                            subscription.branch.is_verified
                                ? "Verified"
                                : "Unverified"
                        }}
                    </span>
                </div>

                <div>
                    <h3 class="truncate text-sm font-semibold text-secondary">
                        {{ subscription.branch.name }}
                    </h3>

                    <p class="mt-1 truncate text-xs text-muted">
                        {{ subscription.branch.email }}
                    </p>

                    <div
                        class="mt-2 flex items-start gap-1.5 text-[11px] text-muted"
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

                        <span>
                            {{
                                subscription.branch.address ||
                                "No address provided"
                            }}
                        </span>
                    </div>
                </div>

                <div class="mt-4">
                    <p
                        class="mb-2 text-[9px] font-semibold uppercase tracking-wider text-muted"
                    >
                        Documents
                    </p>

                    <DocumentLink
                        v-if="subscription.branch.document"
                        :url="subscription.branch.document"
                        label="Branch Document"
                    />

                    <span v-else class="text-[10px] text-muted">
                        No document
                    </span>
                </div>
            </section>
        </div>

        <div
            v-if="latestPayment"
            class="flex items-center justify-between gap-4 border-t border-slate-100 px-5 py-3"
        >
            <div class="flex items-center gap-2 text-[11px] text-muted">
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
                <span class="font-semibold text-secondary">
                    {{ formatCurrency(latestPayment.price) }}
                </span>

                <span
                    class="rounded-full px-2 py-0.5 text-[9px] font-medium capitalize"
                    :class="
                        latestPayment.status === 'paid'
                            ? 'bg-accent-50 text-accent-600'
                            : 'bg-slate-100 text-slate-500'
                    "
                >
                    {{ latestPayment.status }}
                </span>
            </div>
        </div>

        <div
            v-if="canShowActions"
            class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3"
        >
            <button
                type="button"
                :disabled="!!actionLoading"
                class="inline-flex items-center gap-1.5 rounded-lg border border-danger/30 bg-white px-4 py-2 text-[11px] font-semibold text-danger transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
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
                        : "Reject & Refund"
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
import DocumentLink from "~/components/ui/DocumentLink.vue";
import { formatCurrency } from "~/utils/currency";
import type { SubscriptionPaymentRecord } from "~/types/subscription";

interface SubscriptionCardData {
    uuid: string;
    billing_interval: "YEARLY" | "MONTHLY";
    status: "pending" | "active" | "inactive" | "expired";
    start_date: string;
    end_date: string;
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
        };
    };

    plan: {
        plan_id: number;
        name: string;
        plan_code: string;
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

const latestPayment = computed(() => {
    const payments = props.subscription.payments;

    if (!payments?.length) {
        return null;
    }

    return [...payments].sort((a, b) =>
        (b.created_at ?? "").localeCompare(a.created_at ?? ""),
    )[0];
});

const hasAgencyDocuments = computed(() => {
    const agency = props.subscription.branch.agency;

    return !!(agency.id_front || agency.id_back || agency.document);
});

const isPending = computed(() => props.subscription.status === "pending");
const canShowActions = computed(() => isPending.value && props.showActions);

const statusClass = (status: SubscriptionCardData["status"]) => {
    switch (status) {
        case "pending":
            return "bg-amber-50 text-amber-600";
        case "active":
            return "bg-accent-50 text-accent-600";
        case "inactive":
            return "bg-slate-100 text-slate-500";
        case "expired":
            return "bg-red-50 text-red-500";
        default:
            return "bg-slate-100 text-slate-500";
    }
};
</script>
