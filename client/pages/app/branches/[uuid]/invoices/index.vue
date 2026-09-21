<template>
    <div
        class="flex min-h-screen-header flex-1 flex-col gap-2 bg-slate-100 dark:bg-surface"
    >
        <InvoiceOverview
            :overview="overview"
            @month-change="onMonthChange"
            :loading="overviewLoading"
        />

        <div class="flex-1 flex flex-col gap-3 min-h-0">
            <div
                class="flex flex-1 min-h-0 bg-white border rounded-lg border-slate-200 flex-col overflow-hidden dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex flex-col gap-4 border-b border-slate-200 p-4 shrink-0 lg:flex-row lg:items-center lg:justify-between dark:border-white/10"
                >
                    <div
                        class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-white/10"
                    >
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-white text-primary shadow-sm dark:bg-secondary'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
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
                            class="h-9 shrink-0 rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                            @click="clearSearch"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <DataTable
                    v-if="activeTab === 'patients'"
                    class="flex-1 min-h-0"
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
                            <span
                                class="font-medium text-slate-800 dark:text-white"
                            >
                                {{ row.patient?.full_name ?? "—" }}
                            </span>
                            <span
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                <span
                                    v-if="row.patient?.patient_code"
                                    class="font-mono"
                                >
                                    {{ row.patient.patient_code }} ·
                                </span>
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
                                    ? 'text-amber-600 dark:text-amber-300'
                                    : 'text-slate-400 dark:text-gray-500'
                            "
                            >₱{{ formatMoney(totalRefunded(row)) }}</span
                        >
                    </template>

                    <template #cell-total_refundable="{ row }">
                        <div class="leading-tight">
                            <span
                                class="font-medium"
                                :class="
                                    creditOnAccount(row) > 0
                                        ? 'text-emerald-600 dark:text-emerald-300'
                                        : 'text-slate-400 dark:text-gray-500'
                                "
                                >₱{{ formatMoney(creditOnAccount(row)) }}</span
                            >

                            <p
                                v-if="refundProcessing(row) > 0"
                                class="text-[11px] text-amber-600 dark:text-amber-300"
                            >
                                ₱{{ formatMoney(refundProcessing(row)) }} being
                                withdrawn
                            </p>
                        </div>
                    </template>

                    <template #cell-total_balance="{ value }">
                        <span class="font-medium text-red-500"
                            >₱{{ formatMoney(value) }}</span
                        >
                    </template>
                </DataTable>

                <DataTable
                    v-else-if="activeTab === 'invoices'"
                    class="flex-1 min-h-0"
                    :columns="invoiceColumns"
                    :rows="invoiceRows"
                    :pagination="invoicePagination"
                    :loading="invoiceRowsLoading"
                    :searchable="false"
                    :row-key="
                        (row: { invoice_code: string }) => row.invoice_code
                    "
                    empty-title="No invoices found"
                    empty-description="Try a different search term."
                    :on-row-click="viewInvoice"
                    @page-change="fetchInvoiceRows"
                >
                    <template #cell-invoice_code="{ value }">
                        <span
                            class="font-mono font-semibold text-slate-800 dark:text-white"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-status="{ value }">
                        <span
                            class="rounded-full border px-2.5 py-1 text-[11px] font-semibold capitalize"
                            :class="
                                invoiceStatusClass(String(value).toLowerCase())
                            "
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-total="{ value }">
                        <span class="font-medium"
                            >₱{{ formatMoney(value) }}</span
                        >
                    </template>

                    <template #cell-paid="{ value }">
                        <span class="font-medium text-green-600"
                            >₱{{ formatMoney(value) }}</span
                        >
                    </template>

                    <template #cell-amount="{ value }">
                        <span class="font-medium text-red-500"
                            >₱{{ formatMoney(value) }}</span
                        >
                    </template>

                    <template #cell-created_at="{ value }">
                        <span class="text-slate-600 dark:text-gray-400">
                            {{ formatDateTime(value) }}
                        </span>
                    </template>

                    <template #cell-actions="{ row }">
                        <button
                            v-if="
                                ![
                                    'void',
                                    'paid',
                                    'written off',
                                    'written_off',
                                ].includes(row.status?.toLowerCase())
                            "
                            type="button"
                            class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 dark:border-red-500/20 dark:text-red-300 dark:hover:bg-red-500/10"
                            @click.stop="openVoidModal(row)"
                        >
                            Void
                        </button>
                    </template>
                </DataTable>

                <DataTable
                    v-else
                    class="flex-1 min-h-0"
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
                    <template #cell-payment_code="{ row }">
                        <span
                            class="font-mono font-semibold text-slate-800 dark:text-white"
                        >
                            {{ row.payment_code }}
                        </span>
                    </template>

                    <template #cell-issued_at="{ row }">
                        <span class="text-slate-600 dark:text-gray-400">
                            {{ formatDateTime(row.issued_at) }}
                        </span>
                    </template>

                    <template #cell-patient="{ row }">
                        <div class="flex flex-col">
                            <span
                                class="font-medium text-slate-800 dark:text-white"
                            >
                                {{ row.patient?.full_name ?? "—" }}
                            </span>
                            <span
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                from {{ row.payor?.name ?? "—" }}
                            </span>
                        </div>
                    </template>

                    <template #cell-channel="{ row }">
                        <span
                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                            :class="
                                row.channel === 'portal'
                                    ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300'
                                    : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                            "
                        >
                            {{
                                row.channel === "portal" ? "Online" : "Counter"
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

        <PaymentReceipt
            v-if="activeReceipt"
            :receipt="activeReceipt"
            @close="activeReceipt = null"
        />

        <Teleport to="body">
            <div
                v-if="voidTarget"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4"
                @click.self="!voidingInvoice && closeVoidModal()"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                >
                    <h3
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        Void invoice {{ voidTarget.invoice_code }}?
                    </h3>

                    <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                        This refunds any amount already paid on this invoice and
                        marks it void. This cannot be undone.
                    </p>

                    <label
                        class="mt-4 block text-xs font-medium text-gray-600 dark:text-gray-400"
                    >
                        Reason
                        <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="voidReason"
                        rows="2"
                        class="mt-1.5 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />

                    <div class="mt-5 flex justify-end gap-2.5">
                        <button
                            type="button"
                            :disabled="voidingInvoice"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                            @click="closeVoidModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="voidingInvoice || !voidReason.trim()"
                            class="flex min-w-[110px] items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-600 disabled:cursor-not-allowed disabled:opacity-70"
                            @click="confirmVoid"
                        >
                            {{ voidingInvoice ? "Voiding..." : "Void invoice" }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import { formatAmount } from "~/utils/currency";
import BaseInput from "~/components/ui/BaseInput.vue";
import InvoiceOverview from "~/components/sections/app/Billing/InvoiceOverview.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { usePagination } from "~/composables/usePagination";
import { invoiceService } from "~/api/invoice/InvoiceService";
import {
    INVOICE_STATUS,
    type InvoiceRow,
    type PatientSummaryRow,
} from "~/types/invoice";
import type { PaymentReceipt as PaymentReceiptData } from "~/types/receipt";
import { useToast } from "~/composables/useToast";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Invoices",
});

const route = useRoute();
const router = useRouter();

type TabKey = "patients" | "invoices" | "receipts";

const tabs: { key: TabKey; label: string }[] = [
    { key: "patients", label: "Patients" },
    { key: "invoices", label: "Invoices" },
    { key: "receipts", label: "Receipts" },
];

const activeTab = ref<TabKey>("patients");

const patientColumns: DataTableColumn[] = [
    { key: "patient_name", label: "Patient", sortable: false },
    { key: "total_amount", label: "Total", align: "right", sortable: false },
    { key: "total_paid", label: "Paid", align: "right", sortable: false },
    {
        key: "total_refunded",
        label: "Refunds",
        align: "right",
        sortable: false,
    },
    {
        key: "total_refundable",
        label: "Credit",
        align: "right",
        sortable: false,
    },
    { key: "total_balance", label: "Balance", align: "right", sortable: false },
];

const invoiceColumns: DataTableColumn[] = [
    { key: "invoice_code", label: "Invoice Code", sortable: false },
    { key: "patient", label: "Patient", sortable: false },
    { key: "status", label: "Status", sortable: false },
    { key: "total", label: "Total", align: "right", sortable: false },
    { key: "paid", label: "Paid", align: "right", sortable: false },
    { key: "amount", label: "Balance", align: "right", sortable: false },
    { key: "created_at", label: "Date", sortable: false },
    { key: "actions", label: "", align: "right", sortable: false },
];

const receiptColumns: DataTableColumn[] = [
    { key: "payment_code", label: "Receipt No.", sortable: false },
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

const invoiceRows = ref<InvoiceRow[]>([]);
const invoiceRowsLoading = ref(false);
const invoicePagination = usePagination({
    pageSize: 10,
});

const receipts = ref<PaymentReceiptData[]>([]);
const receiptsLoading = ref(false);
const activeReceipt = ref<PaymentReceiptData | null>(null);
const receiptPagination = usePagination({
    pageSize: 10,
});

const { success, error } = useToast();

const voidTarget = ref<InvoiceRow | null>(null);
const voidReason = ref("");
const voidingInvoice = ref(false);

const searchPlaceholder = computed(() => {
    if (activeTab.value === "patients") return "Patient code or name...";
    if (activeTab.value === "invoices") return "Search by invoice code...";

    return "Receipt no., patient code, name, payor or invoice code...";
});

function switchTab(tab: TabKey) {
    if (activeTab.value === tab) return;

    activeTab.value = tab;
    query.value = "";

    if (tab === "receipts" && !receipts.value.length) {
        fetchReceipts(1);
    } else if (tab === "invoices" && !invoiceRows.value.length) {
        fetchInvoiceRows(1);
    }
}

function onSearch() {
    if (activeTab.value === "patients") {
        pagination.reset();
        fetchInvoices(1);
        return;
    }

    if (activeTab.value === "invoices") {
        invoicePagination.reset();
        fetchInvoiceRows(1);
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

async function fetchInvoiceRows(page = invoicePagination.currentPage.value) {
    invoiceRowsLoading.value = true;

    invoicePagination.currentPage.value = page;

    try {
        const res = await invoiceService.list({
            branch_uuid: route.params.uuid,
            search: query.value,
            search_type: "invoice",
            page,
            per_page: invoicePagination.pageSize.value,
        });

        invoiceRows.value = res.data ?? [];

        invoicePagination.setTotal(res.total ?? invoiceRows.value.length);
    } catch (err) {
        console.error(err);
        invoiceRows.value = [];
        invoicePagination.setTotal(0);
    } finally {
        invoiceRowsLoading.value = false;
    }
}

function invoiceStatusClass(status: string) {
    return (
        INVOICE_STATUS[status] ?? "bg-slate-50 text-slate-600 border-slate-200"
    );
}

function viewInvoice(row: InvoiceRow) {
    router.push({
        path: `/app/branches/${uuid.value}/invoices/${row.invoice_code}`,
        query: { mode: "invoice" },
    });
}

function openVoidModal(row: InvoiceRow) {
    voidTarget.value = row;
    voidReason.value = "";
}

function closeVoidModal() {
    if (voidingInvoice.value) return;

    voidTarget.value = null;
    voidReason.value = "";
}

async function confirmVoid() {
    if (!voidTarget.value || voidingInvoice.value || !voidReason.value.trim())
        return;

    voidingInvoice.value = true;

    try {
        const res = await invoiceService.action({
            type: "void",
            branch_uuid: route.params.uuid,
            invoice_code: voidTarget.value.invoice_code,
            reason: voidReason.value.trim() || undefined,
        });

        success(res.message ?? "Invoice voided successfully.");

        invoiceRows.value = invoiceRows.value.filter(
            (row) => row.invoice_code !== voidTarget.value?.invoice_code,
        );
        invoicePagination.setTotal(
            Math.max(0, invoicePagination.totalItems.value - 1),
        );

        voidTarget.value = null;
        voidReason.value = "";
    } catch (err: any) {
        error(err?.message ?? "Failed to void invoice. Please try again.");
    } finally {
        voidingInvoice.value = false;
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

function totalRefundable(row: PatientSummaryRow) {
    return Number(row.total_refundable ?? 0);
}

// Credit claimed by a withdrawal awaiting a decision is still the family's
// money, so it belongs in the figure even though it cannot be spent yet.
function creditOnAccount(row: PatientSummaryRow) {
    return totalRefundable(row) + refundProcessing(row);
}

// Only money that has actually gone back.
function totalRefunded(row: PatientSummaryRow) {
    return Number(row.total_refunded ?? 0);
}

function refundProcessing(row: PatientSummaryRow) {
    return Number(row.total_refund_requested ?? 0);
}

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
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
