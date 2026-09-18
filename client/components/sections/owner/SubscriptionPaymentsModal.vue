<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary"
                            >
                                Payment history
                            </p>

                            <h3
                                class="mt-1 truncate text-base font-semibold text-secondary dark:text-white"
                            >
                                {{ agencyName || "Subscription payments" }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            <AppIcon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto">
                        <table class="w-full min-w-[780px]">
                            <thead class="sticky top-0 bg-white dark:bg-secondary">
                                <tr class="bg-slate-50/60 dark:bg-white/5">
                                    <th
                                        v-for="head in [
                                            'Reference',
                                            'Plan',
                                            'Type',
                                            'Cycle',
                                            'Method',
                                            'Card',
                                            'Amount',
                                            'Date',
                                            'Status',
                                        ]"
                                        :key="head"
                                        class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                    >
                                        {{ head }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-slate-100 dark:divide-white/10"
                            >
                                <tr
                                    v-for="payment in sortedPayments"
                                    :key="payment.subscription_payment_id"
                                    class="transition hover:bg-slate-50/60 dark:hover:bg-white/5"
                                >
                                    <td
                                        class="px-4 py-2.5 text-xs font-medium text-slate-700 dark:text-gray-300"
                                    >
                                        {{ payment.xendit_invoice_id ?? "—" }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                    >
                                        {{ payment.plan_name ?? "—" }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                            :class="
                                                payment.type === 'renewal'
                                                    ? 'bg-primary-50 text-primary dark:bg-primary-500/10 dark:text-primary-300'
                                                    : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300'
                                            "
                                        >
                                            {{ payment.type ?? "—" }}
                                        </span>
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-4 py-2.5 text-xs capitalize text-slate-500 dark:text-gray-400"
                                    >
                                        {{
                                            payment.billing_interval
                                                ? payment.billing_interval.toLowerCase()
                                                : "—"
                                        }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                    >
                                        {{ payment.payment_method ?? "—" }}
                                    </td>

                                    <td
                                        class="px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                    >
                                        {{ payment.masked_card_number ?? "—" }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold text-slate-800 dark:text-white"
                                    >
                                        {{ formatCurrency(payment.price) }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                    >
                                        {{ formatDate(payment.created_at) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                                            :class="
                                                payment.status === 'paid'
                                                    ? 'bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-300'
                                                    : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300'
                                            "
                                        >
                                            {{ payment.status }}
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="!sortedPayments.length">
                                    <td
                                        colspan="9"
                                        class="px-4 py-8 text-center text-xs text-muted dark:text-gray-500"
                                    >
                                        No payments recorded yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import { formatCurrency } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import type { SubscriptionPaymentRecord } from "~/types/subscription";

const props = defineProps<{
    open: boolean;
    agencyName?: string | null;
    payments: SubscriptionPaymentRecord[];
}>();

const emit = defineEmits<{
    (event: "close"): void;
}>();

const sortedPayments = computed(() =>
    [...props.payments].sort((a, b) =>
        (b.created_at ?? "").localeCompare(a.created_at ?? ""),
    ),
);
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(12px) scale(0.98);
}
</style>
