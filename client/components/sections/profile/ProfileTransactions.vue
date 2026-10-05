<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { userService } from "~/api/user/UserService";
import { formatCurrency } from "~/utils/currency";
import { stringToDateTime } from "~/utils/time";

interface PaymentRow {
    source: "subscription" | "care";
    reference: string | null;
    description: string | null;
    method: string | null;
    amount: number;
    status: string;
    created_at: string | null;
}

const payments = ref<PaymentRow[]>([]);
const loading = ref(true);
const failed = ref(false);

const sourceLabels: Record<PaymentRow["source"], string> = {
    subscription: "Subscription",
    care: "Care",
};

const statusClasses: Record<string, string> = {
    paid: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    completed:
        "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    refunded:
        "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
};

const methodLabel = (method: string | null) =>
    method ? method.replace(/[-_]/g, " ").toUpperCase() : "—";

const statusLabel = (status: string) =>
    status.charAt(0).toUpperCase() + status.slice(1);

const hasPayments = computed(() => payments.value.length > 0);

const load = async () => {
    loading.value = true;
    failed.value = false;

    try {
        const res = await userService.payments();
        payments.value = res?.data ?? [];
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<template>
    <section
        class="bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <div class="mb-5">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                Payments
            </h2>
            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400">
                Every payment made with this account.
            </p>
        </div>

        <div v-if="loading" class="space-y-3">
            <div
                v-for="n in 4"
                :key="n"
                class="h-12 animate-pulse rounded bg-slate-100 dark:bg-white/10"
            />
        </div>

        <p
            v-else-if="failed"
            class="py-10 text-center text-sm text-slate-500 dark:text-gray-400"
        >
            We couldn't load your payments.
            <button
                type="button"
                class="font-medium text-primary hover:underline"
                @click="load"
            >
                Try again
            </button>
        </p>

        <p
            v-else-if="!hasPayments"
            class="py-10 text-center text-sm text-slate-500 dark:text-gray-400"
        >
            No payments yet.
        </p>

        <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr
                        class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-white/10 dark:text-gray-400"
                    >
                        <th class="py-3 pr-4 font-semibold">Date</th>
                        <th class="py-3 pr-4 font-semibold">Type</th>
                        <th class="py-3 pr-4 font-semibold">Details</th>
                        <th class="py-3 pr-4 font-semibold">Method</th>
                        <th class="py-3 pr-4 text-right font-semibold">
                            Amount
                        </th>
                        <th class="py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(payment, index) in payments"
                        :key="`${payment.reference}-${index}`"
                        class="border-b border-slate-100 last:border-0 dark:border-white/5"
                    >
                        <td
                            class="whitespace-nowrap py-3 pr-4 text-slate-600 dark:text-gray-300"
                        >
                            {{ stringToDateTime(payment.created_at) || "—" }}
                        </td>
                        <td class="py-3 pr-4 text-slate-600 dark:text-gray-300">
                            {{ sourceLabels[payment.source] }}
                        </td>
                        <td class="py-3 pr-4">
                            <p
                                v-if="payment.description"
                                class="text-slate-900 dark:text-white"
                            >
                                {{ payment.description }}
                            </p>
                            <p
                                v-if="payment.reference"
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                {{ payment.reference }}
                            </p>
                        </td>
                        <td class="py-3 pr-4 text-slate-600 dark:text-gray-300">
                            {{ methodLabel(payment.method) }}
                        </td>
                        <td
                            class="whitespace-nowrap py-3 pr-4 text-right font-semibold text-slate-900 dark:text-white"
                        >
                            {{ formatCurrency(payment.amount) }}
                        </td>
                        <td class="py-3">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="
                                    statusClasses[payment.status] ??
                                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300'
                                "
                            >
                                {{ statusLabel(payment.status) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
