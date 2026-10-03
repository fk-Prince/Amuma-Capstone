<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#EEF3FB] px-5 py-12 dark:bg-surface"
    >
        <div
            v-if="receipt"
            class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div class="px-6 pb-6 pt-8 text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full"
                    :class="
                        isPaid
                            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                            : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'
                    "
                >
                    <AppIcon
                        :name="isPaid ? 'check' : 'clock'"
                        class="h-6 w-6"
                    />
                </div>

                <p
                    class="mt-4 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-gray-500"
                >
                    {{
                        receipt.method === "GCASH"
                            ? "Xendit GCash payment"
                            : "Xendit card payment"
                    }}
                </p>

                <p
                    class="mt-1 text-3xl font-bold text-secondary dark:text-white"
                >
                    {{ formatCurrency(receipt.amount) }}
                </p>

                <span
                    class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    :class="
                        isPaid
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                            : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
                    "
                >
                    {{ statusLabel }}
                </span>
            </div>

            <dl
                class="divide-y divide-slate-100 border-t border-slate-100 px-6 dark:divide-white/10 dark:border-white/10"
            >
                <div
                    v-for="row in rows"
                    :key="row.label"
                    class="flex items-start justify-between gap-4 py-3"
                >
                    <dt class="text-xs text-slate-500 dark:text-gray-400">
                        {{ row.label }}
                    </dt>

                    <dd
                        class="break-all text-right text-xs font-medium text-secondary dark:text-white"
                    >
                        {{ row.value }}
                    </dd>
                </div>
            </dl>

            <div
                class="flex justify-end border-t border-slate-100 bg-slate-50/60 px-6 py-4 print:hidden dark:border-white/10 dark:bg-white/5"
            >
                <button
                    type="button"
                    class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600"
                    @click="print"
                >
                    Print
                </button>
            </div>
        </div>

        <div
            v-else
            class="w-full max-w-md rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <template v-if="notFound">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-white/10"
                >
                    <AppIcon
                        name="file-x"
                        class="h-6 w-6 text-slate-400 dark:text-gray-400"
                    />
                </div>

                <h1
                    class="mt-4 text-lg font-semibold text-secondary dark:text-white"
                >
                    Invoice not found
                </h1>

                <p class="mt-1 text-sm text-muted dark:text-gray-400">
                    {{ message }}
                </p>

                <p
                    class="mt-4 break-all text-[11px] text-slate-400 dark:text-gray-500"
                >
                    {{ reference }}
                </p>
            </template>

            <template v-else>
                <AppIcon
                    name="loader-circle"
                    class="mx-auto h-7 w-7 animate-spin text-primary"
                />

                <p class="mt-4 text-sm text-muted dark:text-gray-400">
                    Opening the Xendit invoice…
                </p>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import AppIcon from "~/components/ui/AppIcon.vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { formatCurrency } from "~/utils/currency";
import { stringToDateTime } from "~/utils/time";
import { planTypeLabel } from "~/utils/planType";
import { paymentTypeLabel } from "~/utils/subscriptionInvoice";

interface PaymentReceipt {
    method: "GCASH" | "CREDIT-CARD";
    xendit_id: string;
    status: string | null;
    amount: number;
    currency: string;
    card_brand?: string | null;
    card_type?: string | null;
    masked_card_number?: string | null;
    reference_id: string;
    paid_at: string | null;
    plan: string | null;
    type: string | null;
    plan_type: string | null;
}

definePageMeta({ layout: false });
useHead({ title: "Payment receipt" });

const route = useRoute();
const reference = String(route.params.reference ?? "");
const notFound = ref(false);
const message = ref("");
const receipt = ref<PaymentReceipt | null>(null);

const isPaid = computed(() =>
    ["CAPTURED", "SETTLED", "PAID"].includes(receipt.value?.status ?? ""),
);

const statusLabel = computed(() => {
    const status = receipt.value?.status ?? "";

    if (isPaid.value) return "Paid";

    return status
        ? status.charAt(0) + status.slice(1).toLowerCase()
        : "Unknown";
});

const capitalize = (value?: string | null) =>
    value ? value.charAt(0).toUpperCase() + value.slice(1).toLowerCase() : null;

const rows = computed(() => {
    const data = receipt.value;

    if (!data) return [];

    const paidWith =
        data.method === "GCASH"
            ? "GCash"
            : [data.card_brand, capitalize(data.card_type), data.masked_card_number]
                  .filter(Boolean)
                  .join(" · ");

    return [
        { label: "Paid on", value: stringToDateTime(data.paid_at) },
        { label: "Paid with", value: paidWith },
        {
            label: "Plan",
            value: [data.plan, planTypeLabel(data.plan_type)]
                .filter(Boolean)
                .join(" · "),
        },
        { label: "Payment for", value: paymentTypeLabel(data.type) },
        { label: "Xendit ID", value: data.xendit_id },
        { label: "Reference", value: data.reference_id },
    ].filter((row) => !!row.value);
});

function print() {
    window.print();
}

onMounted(async () => {
    try {
        const branch = route.query.branch;
        const res = await subscriptionService.paymentInvoice(
            reference,
            typeof branch === "string" ? branch : null,
        );

        if (res?.receipt) {
            receipt.value = res.receipt;
            return;
        }

        throw new Error("Xendit has no invoice for this payment.");
    } catch (err: any) {
        message.value =
            err?.data?.message ??
            err?.message ??
            "Xendit has no invoice for this payment.";
        notFound.value = true;
    }
});
</script>

<style>
@media print {
    html,
    body {
        background: #ffffff !important;
    }
}
</style>
