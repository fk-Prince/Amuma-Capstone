<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-primary-100 px-6 py-4 dark:border-white/10"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-600 dark:text-amber-300"
                        >
                            Withdrawal requests
                        </p>

                        <h3
                            class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                        >
                            {{ requests.length }} awaiting review
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="emit('close')"
                    >
                        <AppIcon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div
                    class="flex items-center justify-between gap-3 border-b border-primary-100 bg-slate-50 px-6 py-3 dark:border-white/10 dark:bg-white/5"
                >
                    <span class="text-xs text-muted dark:text-gray-400">
                        Credit on account
                    </span>

                    <span
                        class="text-sm font-bold text-secondary dark:text-white"
                    >
                        ₱{{ formatMoney(credit) }}
                    </span>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <article
                        v-for="request in requests"
                        :key="request.refund_id"
                        class="overflow-hidden rounded-md border border-primary-500 dark:border-primary-500/50"
                    >
                        <div
                            class="flex items-center justify-between gap-3 bg-primary px-3 py-1.5 text-white"
                        >
                            <span class="font-mono text-[11px] font-semibold">
                                {{
                                    request.refund_code ??
                                    `#${request.refund_id}`
                                }}
                            </span>

                            <span class="text-[11px] font-semibold">
                                Withdraw credits
                            </span>
                        </div>

                        <dl class="text-[11px]">
                            <div
                                v-for="(row, index) in rowsFor(request)"
                                :key="row.label"
                                class="flex items-start justify-between gap-3 px-3 py-1.5"
                                :class="
                                    index % 2
                                        ? 'bg-white dark:bg-secondary'
                                        : 'bg-primary-50/60 dark:bg-white/5'
                                "
                            >
                                <dt
                                    class="shrink-0 text-muted dark:text-gray-400"
                                >
                                    {{ row.label }}
                                </dt>

                                <dd
                                    class="min-w-0 truncate text-right font-medium text-secondary dark:text-gray-100"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>

                            <div
                                class="flex items-start justify-between gap-3 px-3 py-1.5"
                                :class="
                                    rowsFor(request).length % 2
                                        ? 'bg-white dark:bg-secondary'
                                        : 'bg-primary-50/60 dark:bg-white/5'
                                "
                            >
                                <dt
                                    class="shrink-0 text-muted dark:text-gray-400"
                                >
                                    Status
                                </dt>

                                <dd
                                    class="min-w-0 text-right font-medium text-amber-600 dark:text-amber-400"
                                >
                                    Requested
                                </dd>
                            </div>
                        </dl>

                        <p
                            v-if="exceedsCredit(request)"
                            class="border-t border-primary-100 bg-danger/5 px-4 py-2.5 text-[11px] leading-4 text-danger dark:border-white/10"
                        >
                            This request is larger than the ₱{{
                                formatMoney(credit)
                            }}
                            credit on the account. Check the payments before
                            approving it.
                        </p>

                        <div
                            class="flex items-center justify-end gap-2 border-t border-primary-100 px-4 py-2.5 dark:border-white/10"
                        >
                            <button
                                type="button"
                                :disabled="processing"
                                class="rounded-lg border border-danger/30 px-3 py-1.5 text-[12px] font-semibold text-danger transition hover:bg-danger/10 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="emit('decline', request)"
                            >
                                Decline
                            </button>

                            <button
                                type="button"
                                :disabled="processing"
                                class="rounded-lg bg-primary px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="emit('approve', request)"
                            >
                                {{ processing ? "Approving…" : "Approve" }}
                            </button>
                        </div>
                    </article>

                    <p
                        v-if="!requests.length"
                        class="py-6 text-center text-xs text-muted dark:text-gray-400"
                    >
                        No withdrawal requests are waiting for review.
                    </p>

                    <p
                        v-if="errorMessage"
                        class="rounded-xl bg-danger/10 px-4 py-2.5 text-[11px] leading-4 text-danger"
                    >
                        {{ errorMessage }}
                    </p>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import { formatAmount } from "~/utils/currency";
import { formatDate } from "~/utils/time";

const props = defineProps<{
    open: boolean;
    requests: any[];
    credit: number;
    processing: boolean;
    errorMessage?: string;
}>();

const emit = defineEmits<{
    (event: "approve", request: any): void;
    (event: "decline", request: any): void;
    (event: "close"): void;
}>();

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

function exceedsCredit(request: any) {
    return Number(request.amount ?? 0) > Number(props.credit ?? 0) + 0.001;
}

// Same rows the family sees in the portal: the credit is claimed against the
// account, so the invoice it is drawn from is left out.
function rowsFor(request: any) {
    return [
        { label: "Date of request", value: formatDate(request.created_at) },
        { label: "Requested by", value: request.requested_by?.name },
        {
            label: "Electronic method",
            value: methodLabel(request.refund_method, ""),
        },
        { label: "Amount", value: `₱${formatMoney(request.amount)}` },
        { label: "Account", value: request.masked_account_detail },
    ].filter((row) => !!row.value);
}
</script>
