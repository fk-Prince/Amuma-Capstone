<template>
    <div class="min-h-screen-header flex flex-col gap-3 bg-[#EEF3FB] dark:bg-surface">
        <section class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <OverviewStatCard
                label="Gross Sales"
                :value="money(summary?.gross_sales)"
                :icon="Receipt"
                tone="primary"
                :chip="`Receivable ${money(summary?.accounts_receivable)}`"
                :note="periodLabel"
            />
            <OverviewStatCard
                label="Vatable Sales"
                :value="money(summary?.vatable_sales)"
                :icon="TrendingUp"
                tone="accent"
                :note="periodLabel"
            />
            <OverviewStatCard
                label="VAT-Exempt Sales"
                :value="money(summary?.vat_exempt_sales)"
                :icon="ShieldCheck"
                tone="amber"
                :note="periodLabel"
            />
            <OverviewStatCard
                label="Output VAT (12%)"
                :value="money(summary?.output_vat)"
                :icon="Landmark"
                tone="rose"
                :note="periodLabel"
            />
        </section>

        <div
            class="flex min-h-0 flex-1 flex-col overflow-visible rounded-lg border border-[#E4EFED] bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div
                class="flex shrink-0 flex-col gap-3 border-b border-[#E4EFED] px-6 py-4 dark:border-white/10"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2
                            class="font-semibold text-slate-800 dark:text-white"
                        >
                            Sales &amp; VAT
                        </h2>
                        <p
                            class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                        >
                            One row per invoice, VAT included in the gross
                            amount.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[#E4EFED] text-slate-500 transition hover:bg-slate-50 hover:text-primary disabled:opacity-60 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                            aria-label="Refresh report"
                            :disabled="loading"
                            @click="refresh"
                        >
                            <RefreshCw
                                class="h-4 w-4"
                                :class="{ 'animate-spin': loading }"
                            />
                        </button>

                        <button
                            v-if="canExport(Modules.Reports)"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="exporting || !totalItems"
                            @click="exportExcel"
                        >
                            <Download class="h-4 w-4" />
                            {{ exporting ? "Exporting..." : "Export Excel" }}
                        </button>
                    </div>
                </div>

                <SalesVatFilter
                    v-model:mode="mode"
                    v-model:anchor="anchor"
                    v-model:date-from="dateFrom"
                    v-model:date-to="dateTo"
                    v-model:status="status"
                    v-model:search="search"
                />
            </div>

            <SalesVatTable
                :rows="rows"
                :loading="loading"
                :current-page="currentPage"
                :total-items="totalItems"
                :page-size="PAGE_SIZE"
                @page-change="onPageChange"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useDebounceFn } from "@vueuse/core";
import {
    Download,
    Landmark,
    Receipt,
    RefreshCw,
    ShieldCheck,
    TrendingUp,
} from "lucide-vue-next";
import OverviewStatCard from "~/components/sections/app/Dashboard/overview/OverviewStatCard.vue";
import SalesVatFilter from "~/components/sections/app/Report/SalesVatFilter.vue";
import SalesVatTable from "~/components/sections/app/Report/SalesVatTable.vue";
import { reportService } from "~/api/report/ReportService";
import { usePermissions } from "~/composables/usePermission";
import { useToast } from "~/composables/useToast";
import { Modules } from "~/types/module";
import type {
    ReportPeriodMode,
    SalesStatus,
    SalesVatRow,
    SalesVatSummary,
} from "~/types/report";
import { formatCurrency } from "~/utils/currency";
import { downloadExcel, fileSafe, moneyCell } from "~/utils/excel";
import { formatDate, toLocalDateString } from "~/utils/time";
import { useBranchStore } from "~/stores/branch";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Financial Reports" });

const PAGE_SIZE = 15;

const route = useRoute();
const uuid = route.params.uuid as string;
const { error } = useToast();
const { canExport } = usePermissions();
const branchStore = useBranchStore();

const mode = ref<ReportPeriodMode>("quarter");
const anchor = ref(new Date());
const dateFrom = ref(toLocalDateString(new Date()));
const dateTo = ref(toLocalDateString(new Date()));
const status = ref<SalesStatus>("");
const search = ref("");

const rows = ref<SalesVatRow[]>([]);
const summary = ref<SalesVatSummary | null>(null);
const loading = ref(false);
const exporting = ref(false);
const currentPage = ref(1);
const totalItems = ref(0);

const range = computed(() => {
    if (mode.value === "range") {
        return { from: dateFrom.value, to: dateTo.value || dateFrom.value };
    }

    const year = anchor.value.getFullYear();
    const startMonth =
        mode.value === "quarter"
            ? Math.floor(anchor.value.getMonth() / 3) * 3
            : anchor.value.getMonth();
    const months = mode.value === "quarter" ? 3 : 1;

    return {
        from: toLocalDateString(new Date(year, startMonth, 1)),
        to: toLocalDateString(new Date(year, startMonth + months, 0)),
    };
});

const periodLabel = computed(() => {
    const year = anchor.value.getFullYear();

    if (mode.value === "quarter") {
        return `Q${Math.floor(anchor.value.getMonth() / 3) + 1} ${year}`;
    }

    if (mode.value === "month") {
        return anchor.value.toLocaleDateString("en-US", {
            month: "long",
            year: "numeric",
        });
    }

    return `${formatDate(range.value.from)} – ${formatDate(range.value.to)}`;
});

const money = (value?: number | null) =>
    formatCurrency(value, { treatMissingAsZero: true });

const periodParams = () => ({
    branch_uuid: uuid,
    date_from: range.value.from,
    date_to: range.value.to,
});

const filterParams = () => ({
    ...periodParams(),
    status: status.value || undefined,
    search: search.value.trim() || undefined,
});

async function fetchRows() {
    if (!range.value.from) return;

    loading.value = true;

    try {
        const res: any = await reportService.salesVat({
            ...filterParams(),
            page: currentPage.value,
            per_page: PAGE_SIZE,
        });

        rows.value = res.data ?? [];
        totalItems.value = res.meta?.total ?? 0;
    } catch (err: any) {
        rows.value = [];
        error(err?.data?.message || err?.message || "Unable to load the report.");
    } finally {
        loading.value = false;
    }
}

async function fetchSummary() {
    if (!range.value.from) return;

    try {
        const res: any = await reportService.salesVatSummary(periodParams());
        summary.value = res.data ?? res;
    } catch (err: any) {
        summary.value = null;
        error(err?.data?.message || err?.message || "Unable to load the totals.");
    }
}

function refresh() {
    fetchSummary();
    fetchRows();
}

function onPageChange(page: number) {
    currentPage.value = page;
    fetchRows();
}

function reloadFromFirstPage() {
    currentPage.value = 1;
    fetchRows();
}

const debouncedSearch = useDebounceFn(reloadFromFirstPage, 400);

watch(search, debouncedSearch);
watch(status, reloadFromFirstPage);
watch(
    () => [range.value.from, range.value.to],
    () => {
        currentPage.value = 1;
        refresh();
    },
);

async function exportExcel() {
    exporting.value = true;

    try {
        const res: any = await reportService.salesVatExport(filterParams());
        const data: SalesVatRow[] = res.data ?? [];
        const total = (key: keyof SalesVatRow) =>
            moneyCell(data.reduce((sum, row) => sum + Number(row[key] ?? 0), 0));
        const branchName = branchStore.activeBranch?.name ?? "Branch";

        await downloadExcel(fileSafe(`Sales_VAT_${branchName}_${periodLabel.value}`), [
            {
                name: "Sales & VAT",
                rows: [
                    [`${branchName} – Sales & VAT Report`],
                    [`Period: ${periodLabel.value}`],
                    [],
                    [
                        "Date",
                        "Invoice No.",
                        "Patient",
                        "Description",
                        "Gross Amount",
                        "Vatable Sales",
                        "VAT-Exempt Sales",
                        "VAT (12%)",
                        "Status",
                    ],
                    ...data.map((row) => [
                        formatDate(row.created_at),
                        row.invoice_code,
                        row.patient_name ?? "",
                        row.description ?? "",
                        moneyCell(row.gross_amount),
                        moneyCell(row.vatable_sales),
                        moneyCell(row.vat_exempt_sales),
                        moneyCell(row.vat_amount),
                        row.status.replace(/_/g, " "),
                    ]),
                    [
                        "Total",
                        "",
                        "",
                        "",
                        total("gross_amount"),
                        total("vatable_sales"),
                        total("vat_exempt_sales"),
                        total("vat_amount"),
                        "",
                    ],
                ],
            },
        ]);
    } catch (err: any) {
        error(err?.data?.message || err?.message || "Unable to export the report.");
    } finally {
        exporting.value = false;
    }
}

onMounted(refresh);
</script>
