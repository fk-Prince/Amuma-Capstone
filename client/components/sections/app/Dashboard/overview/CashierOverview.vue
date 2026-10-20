<template>
    <div class="space-y-3">
        <div v-if="loading" class="space-y-3">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-24 animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                />
            </div>
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div
                    v-for="n in 2"
                    :key="n"
                    class="h-[300px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                />
            </div>
        </div>

        <template v-else>
            <section
                class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4"
            >
                <OverviewStatCard
                    label="Total Billed"
                    :value="money(overview?.total_revenue?.value)"
                    :icon="Receipt"
                    tone="primary"
                    :chip="overview?.total_revenue?.secondary ?? undefined"
                    :note="periodLabel"
                />
                <OverviewStatCard
                    label="Payments Received"
                    :value="money(overview?.payments_received?.value)"
                    :icon="Wallet"
                    tone="accent"
                    :chip="overview?.payments_received?.secondary ?? undefined"
                    :note="periodLabel"
                />
                <OverviewStatCard
                    label="Outstanding Balance"
                    :value="money(overview?.outstanding_balance?.value)"
                    :icon="HandCoins"
                    :tone="outstanding > 0 ? 'amber' : 'primary'"
                    :chip="overview?.outstanding_balance?.secondary ?? undefined"
                    :to="invoicesLink"
                    :action-label="outstanding > 0 ? 'Collect payments' : undefined"
                />
                <OverviewStatCard
                    label="Refunds Issued"
                    :value="money(overview?.refunds_issued?.value)"
                    :icon="RotateCcw"
                    tone="rose"
                    :chip="overview?.refunds_issued?.secondary ?? undefined"
                    :note="periodLabel"
                />
            </section>

            <section class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <OverviewDonutCard
                    title="Collection"
                    subtitle="Paid vs outstanding this month"
                    :icon="Wallet"
                    tone="accent"
                    :segments="collectionSegments"
                    :center-value="`${collectedPct}%`"
                    center-label="Collected"
                    empty-text="No billing this month"
                    :format-value="money"
                />

                <div
                    class="rounded-xl border border-slate-200/70 bg-white p-3 shadow-sm ring-1 ring-slate-100/60 transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:ring-white/10"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary-100 to-primary-50 text-primary-600 dark:from-primary-500/20 dark:to-primary-500/10 dark:text-primary-300"
                        >
                            <TrendingUp class="h-4 w-4" />
                        </div>
                        <div>
                            <h2
                                class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                            >
                                {{ overview?.chart?.title ?? "Revenue" }}
                            </h2>
                            <p
                                class="text-[11px] text-muted dark:text-gray-400"
                            >
                                Billed per month · this month highlighted
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex h-[210px] items-end gap-1.5">
                        <div
                            v-for="bar in bars"
                            :key="bar.label"
                            class="flex h-full flex-1 flex-col items-center justify-end gap-1"
                            :title="`${bar.label}: ${money(bar.value)}`"
                        >
                            <div
                                class="w-full rounded-t-md transition-all"
                                :class="
                                    bar.highlight
                                        ? 'bg-primary'
                                        : 'bg-primary-200 dark:bg-primary-500/30'
                                "
                                :style="{ height: `${bar.height}%` }"
                            />
                            <span
                                class="text-[9px] font-semibold text-muted dark:text-gray-400"
                                >{{ bar.label }}</span
                            >
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm ring-1 ring-slate-100/60 dark:border-white/10 dark:bg-white/5 dark:ring-white/10"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5 dark:border-white/10"
                >
                    <div>
                        <h2
                            class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                        >
                            Recent payments
                        </h2>
                        <p class="text-[11px] text-muted dark:text-gray-400">
                            Latest receipts issued
                        </p>
                    </div>
                    <NuxtLink
                        :to="invoicesLink"
                        class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300"
                    >
                        View all →
                    </NuxtLink>
                </div>

                <p
                    v-if="!receipts.length"
                    class="px-3 py-8 text-center text-[11px] text-muted dark:text-gray-400"
                >
                    Payments you record will appear here.
                </p>

                <div
                    v-else
                    class="divide-y divide-slate-100 dark:divide-white/10"
                >
                    <div
                        v-for="receipt in receipts"
                        :key="receipt.payment_code"
                        class="flex items-center gap-3 px-3 py-2.5"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-xs font-semibold text-secondary dark:text-white"
                            >
                                {{ receipt.patient?.full_name ?? "Unknown patient" }}
                            </p>
                            <p
                                class="truncate text-[11px] text-muted dark:text-gray-400"
                            >
                                {{ receipt.payment_code }} ·
                                {{ receipt.payment?.method ?? receipt.channel }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p
                                class="text-xs font-bold tabular-nums text-secondary dark:text-white"
                            >
                                {{ money(receipt.payment?.amount_applied) }}
                            </p>
                            <p
                                class="mt-0.5 text-[10px] text-muted dark:text-gray-400"
                            >
                                {{ formatDate(receipt.issued_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
    HandCoins,
    Receipt,
    RotateCcw,
    TrendingUp,
    Wallet,
} from "lucide-vue-next";

import OverviewDonutCard from "~/components/sections/app/Dashboard/overview/OverviewDonutCard.vue";
import OverviewStatCard from "~/components/sections/app/Dashboard/overview/OverviewStatCard.vue";
import { invoiceService } from "~/api/invoice/InvoiceService";
import { formatCurrency } from "~/utils/currency";
import type { PaymentReceipt } from "~/types/receipt";

const props = defineProps<{ branchUuid: string }>();

const loading = ref(true);
const overview = ref<any>(null);
const receipts = ref<PaymentReceipt[]>([]);

const invoicesLink = computed(() => `/app/branches/${props.branchUuid}/invoices`);

const money = (value: unknown) => formatCurrency(Number(value) || 0);

const periodLabel = computed(() => overview.value?.period ?? "This month");

const paid = computed(() => Number(overview.value?.payments_received?.value) || 0);
const outstanding = computed(
    () => Number(overview.value?.outstanding_balance?.value) || 0,
);

const collectedPct = computed(() => {
    const total = paid.value + outstanding.value;

    return total ? Math.round((paid.value / total) * 100) : 0;
});

const collectionSegments = computed(() => [
    { key: "paid", label: "Paid", count: paid.value, color: "#0E7C7B" },
    { key: "outstanding", label: "Outstanding", count: outstanding.value, color: "#d97706" },
]);

const bars = computed(() => {
    const chart = overview.value?.chart;
    const labels: string[] = chart?.labels ?? [];
    const revenue: number[] = chart?.revenue ?? [];
    const highlight: number[] = chart?.highlight ?? [];
    const max = Math.max(...revenue, 0);

    return labels.map((label, index) => ({
        label,
        value: revenue[index] ?? 0,
        highlight: highlight.includes(index),
        height: max ? Math.max(((revenue[index] ?? 0) / max) * 100, 2) : 2,
    }));
});

const formatDate = (date: string | null) => {
    if (!date) return "—";

    const parsed = new Date(date);

    return Number.isNaN(parsed.getTime())
        ? date
        : parsed.toLocaleDateString("en-US", { month: "short", day: "numeric" });
};

onMounted(async () => {
    const now = new Date();

    const [overviewRes, receiptsRes] = await Promise.allSettled([
        invoiceService.overview({
            branch_uuid: props.branchUuid,
            period: "month",
            month: now.getMonth() + 1,
            year: now.getFullYear(),
        }),
        invoiceService.receipts({
            branch_uuid: props.branchUuid,
            page: 1,
            per_page: 6,
        }),
    ]);

    if (overviewRes.status === "fulfilled") {
        overview.value = overviewRes.value?.data ?? overviewRes.value;
    }

    if (receiptsRes.status === "fulfilled") {
        receipts.value = receiptsRes.value?.data ?? [];
    }

    loading.value = false;
});
</script>