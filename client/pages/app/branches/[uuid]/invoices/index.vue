<template>
    <div class="flex-1 flex flex-col gap-6 min-h-0 bg-slate-100">
        <InvoiceOverview
            :overview="overview"
            @month-change="onMonthChange"
            :loading="overviewLoading"
        />

        <div class="flex-1 flex flex-col gap-6 min-h-0 px-6">
            <div
                class="flex bg-white border rounded-2xl mt-3 border-slate-200 flex-col overflow-hidden"
            >
                <div
                    class="flex flex-col gap-4 border-b border-slate-200 p-4 shrink-0 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="inline-flex rounded-xl bg-slate-100 p-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-white text-primary shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700'
                            "
                            @click="switchTab(tab.key)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2 lg:justify-end">
                        <div class="flex-1 lg:w-80">
                            <BaseInput
                                v-model="query"
                                :placeholder="searchPlaceholder"
                                is-search
                                @keyup.enter="onSearch"
                            />
                        </div>

                        <button
                            type="button"
                            class="h-9 shrink-0 rounded-lg bg-primary text-white px-5 text-sm font-medium hover:bg-primary-600 transition-colors"
                            @click="onSearch"
                        >
                            Search
                        </button>

                        <button
                            v-if="query"
                            type="button"
                            class="h-9 shrink-0 rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-50"
                            @click="clearSearch"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <div v-if="activeTab === 'patients'" class="flex-1 min-h-0">
                    <DataTable
                        class="h-full"
                        :columns="patientColumns"
                        :rows="invoices"
                        :pagination="pagination"
                        :loading="loading"
                        :searchable="false"
                        empty-title="No invoices found"
                        empty-description="Try a different search term or filter."
                        :on-row-click="onRowClick"
                        @page-change="fetchInvoices"
                    >
                        <template #cell-patient_name="{ row }">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-800">
                                    {{ row.patient?.full_name ?? "—" }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    Latest
                                    {{
                                        row.latest_invoice?.invoice_code ??
                                        "No invoices yet"
                                    }}
                                </span>
                            </div>
                        </template>

                        <template #cell-total_amount="{ value }">
                            <span class="font-medium"
                                >₱{{ formatMoney(value) }}</span
                            >
                        </template>

                        <template #cell-total_paid="{ value }">
                            <span class="font-medium text-green-600"
                                >₱{{ formatMoney(value) }}</span
                            >
                        </template>

                        <template #cell-total_refunded="{ row }">
                            <span
                                class="font-medium"
                                :class="
                                    totalRefunded(row) > 0
                                        ? 'text-amber-600'
                                        : 'text-slate-400'
                                "
                                >₱{{ formatMoney(totalRefunded(row)) }}</span
                            >
                        </template>

                        <template #cell-total_balance="{ value }">
                            <span class="font-medium text-red-500"
                                >₱{{ formatMoney(value) }}</span
                            >
                        </template>
                    </DataTable>
                </div>

                <div v-else class="flex-1 min-h-0">
                    <DataTable
                        class="h-full"
                        :columns="receiptColumns"
                        :rows="receipts"
                        :pagination="receiptPagination"
                        :loading="receiptsLoading"
                        :searchable="false"
                        empty-title="No receipts found"
                        empty-description="Search by receipt number, patient, payor, invoice code or gateway reference."
                        :on-row-click="openReceipt"
                        @page-change="fetchReceipts"
                    >
                        <template #cell-receipt_no="{ row }">
                            <span class="font-mono font-semibold text-slate-800">
                                {{ row.receipt_no }}
                            </span>
                        </template>

                        <template #cell-issued_at="{ row }">
                            <span class="text-slate-600">
                                {{ formatDateTime(row.issued_at) }}
                            </span>
                        </template>

                        <template #cell-patient="{ row }">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-800">
                                    {{ row.patient?.full_name ?? "—" }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    from {{ row.payor?.name ?? "—" }}
                                </span>
                            </div>
                        </template>

                        <template #cell-channel="{ row }">
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="
                                    row.channel === 'portal'
                                        ? 'bg-blue-50 text-blue-600'
                                        : 'bg-emerald-50 text-emerald-600'
                                "
                            >
                                {{
                                    row.channel === "portal"
                                        ? "Online"
                                        : "Counter"
                                }}
                            </span>
                        </template>

                        <template #cell-amount="{ row }">
                            <span class="font-medium text-green-600">
                                ₱{{ formatMoney(row.payment?.amount_applied) }}
                            </span>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <PaymentReceipt
            v-if="activeReceipt"
            :receipt="activeReceipt"
            @close="activeReceipt = null"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import InvoiceOverview from "~/components/sections/app/Billing/InvoiceOverview.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { usePagination } from "~/composables/usePagination";
import { invoiceService } from "~/api/invoice/InvoiceService";
import type { PatientSummaryRow } from "~/types/invoice";
import type { PaymentReceipt as PaymentReceiptData } from "~/types/receipt";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Invoices",
});

const route = useRoute();
const router = useRouter();

type TabKey = "patients" | "receipts";

const tabs: { key: TabKey; label: string }[] = [
    { key: "patients", label: "Patients" },
    { key: "receipts", label: "Receipts" },
];

const activeTab = ref<TabKey>("patients");

const patientColumns: DataTableColumn[] = [
    { key: "patient_name", label: "Patient", sortable: false },
    { key: "total_amount", label: "Total", align: "right", sortable: false },
    { key: "total_paid", label: "Paid", align: "right", sortable: false },
    {
        key: "total_refunded",
        label: "Refunded",
        align: "right",
        sortable: false,
    },
    { key: "total_balance", label: "Balance", align: "right", sortable: false },
];

const receiptColumns: DataTableColumn[] = [
    { key: "receipt_no", label: "Receipt No.", sortable: false },
    { key: "issued_at", label: "Issued", sortable: false },
    { key: "patient", label: "Patient", sortable: false },
    { key: "channel", label: "Channel", sortable: false },
    { key: "amount", label: "Amount", align: "right", sortable: false },
];

const invoices = ref<PatientSummaryRow[]>([]);
const loading = ref(true);
const uuid = computed(() => route.params.uuid);
const query = ref("");
const pagination = usePagination({
    pageSize: 10,
});

const receipts = ref<PaymentReceiptData[]>([]);
const receiptsLoading = ref(false);
const activeReceipt = ref<PaymentReceiptData | null>(null);
const receiptPagination = usePagination({
    pageSize: 10,
});

const searchPlaceholder = computed(() =>
    activeTab.value === "patients"
        ? "Enter patient name..."
        : "Receipt no., patient, payor or invoice code...",
);

function switchTab(tab: TabKey) {
    if (activeTab.value === tab) return;

    activeTab.value = tab;
    query.value = "";

    if (tab === "receipts" && !receipts.value.length) {
        fetchReceipts(1);
    }
}

function onSearch() {
    if (activeTab.value === "patients") {
        pagination.reset();
        fetchInvoices(1);
        return;
    }

    receiptPagination.reset();
    fetchReceipts(1);
}

function clearSearch() {
    query.value = "";
    onSearch();
}

async function fetchInvoices(page = pagination.currentPage.value) {
    loading.value = true;

    pagination.currentPage.value = page;

    try {
        const res = await invoiceService.list({
            branch_uuid: route.params.uuid,
            search: query.value,
            search_type: "patient",
            page,
            per_page: pagination.pageSize.value,
        });

        invoices.value = res.data ?? [];

        pagination.setTotal(res.total ?? invoices.value.length);
    } catch (error) {
        console.error(error);
        invoices.value = [];
        pagination.setTotal(0);
    } finally {
        loading.value = false;
    }
}

async function fetchReceipts(page = receiptPagination.currentPage.value) {
    receiptsLoading.value = true;

    receiptPagination.currentPage.value = page;

    try {
        const res = await invoiceService.receipts({
            branch_uuid: route.params.uuid,
            search: query.value,
            page,
            per_page: receiptPagination.pageSize.value,
        });

        receipts.value = res.data ?? [];

        receiptPagination.setTotal(
            res.meta?.total ?? res.total ?? receipts.value.length,
        );
    } catch (error) {
        console.error(error);
        receipts.value = [];
        receiptPagination.setTotal(0);
    } finally {
        receiptsLoading.value = false;
    }
}

function openReceipt(row: PaymentReceiptData) {
    activeReceipt.value = row;
}

function onRowClick(row: PatientSummaryRow) {
    viewPatient(row);
}

function viewPatient(row: PatientSummaryRow) {
    if (!row.patient?.patient_uuid) return;

    router.push({
        path: `/app/branches/${uuid.value}/invoices/patient/${row.patient.patient_uuid}`,
        query: {
            mode: "patient",
        },
    });
}

function totalRefunded(row: PatientSummaryRow) {
    return (
        Number(row.total_refunded ?? 0) +
        Number(row.total_refund_processing ?? 0)
    );
}

function formatMoney(amount: number | string | null | undefined) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
    });
}

function formatDateTime(value: string | null | undefined) {
    if (!value) return "—";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

const overview = ref(null);
const now = new Date();
const currentMonthIndex = ref(now.getMonth());
const currentYear = ref(now.getFullYear());
const overviewLoading = ref(true);

async function fetchOverview() {
    overviewLoading.value = true;
    try {
        const overviewData = await invoiceService.overview({
            branch_uuid: uuid.value,
            month: currentMonthIndex.value + 1,
            year: currentYear.value,
        });

        overview.value = overviewData.data ?? overviewData;
    } catch (err) {
        console.error("Failed loading billing overview:", err);
    } finally {
        overviewLoading.value = false;
    }
}

function onMonthChange({ month, year }: { month: number; year: number }) {
    currentMonthIndex.value = month - 1;
    currentYear.value = year;

    fetchOverview();
}

onMounted(async () => {
    await Promise.all([fetchInvoices(1), fetchOverview()]);
});
</script>
