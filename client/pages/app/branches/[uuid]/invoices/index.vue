<template>
    <div class="flex-1 flex flex-col gap-6 min-h-0 bg-slate-100">
        <InvoiceOverview
            :overview="overview"
            @month-change="onMonthChange"
            :loading="overviewLoading"
        />
        <div class="flex-1 flex flex-col gap-6 min-h-0 px-6">
            <div
                class="flex bg-white border rounded-2xl mt-3 border-slate-200 flex-col"
            >
                <div class="p-4 flex flex-col gap-4 shrink-0">
                    <div
                        class="flex flex-col lg:flex-row lg:items-center gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs font-medium text-slate-400 uppercase tracking-wide shrink-0"
                            >
                                Search by
                            </span>

                            <div
                                class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1"
                            >
                                <button
                                    v-for="mode in searchModes"
                                    :key="mode.value"
                                    type="button"
                                    class="px-4 h-8 rounded-lg text-sm font-medium transition-all duration-200"
                                    :class="
                                        searchType === mode.value
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-slate-500 hover:text-slate-700'
                                    "
                                    @click="onSearchTypeChange(mode.value)"
                                >
                                    {{ mode.label }}
                                </button>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 flex-1 lg:justify-end"
                        >
                            <div class="flex-1 lg:max-w-xs">
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
                        </div>
                    </div>

                    <div
                        v-if="searchType === 'invoice'"
                        class="flex items-center gap-2 pt-3 border-t border-slate-100"
                    >
                        <span
                            class="text-xs font-medium text-slate-400 uppercase tracking-wide"
                        >
                            Date range
                        </span>

                        <BaseInput
                            v-model="dateFrom"
                            mode="date"
                            class="h-9 px-3 text-sm w-40"
                        />
                        <span class="text-sm text-slate-400">to</span>
                        <BaseInput
                            v-model="dateTo"
                            mode="date"
                            class="h-9 px-3 text-sm w-40"
                        />
                    </div>
                </div>

                <div class="flex-1 min-h-0">
                    <DataTable
                        class="h-full"
                        :columns="columns"
                        :rows="invoices"
                        :pagination="pagination"
                        :loading="loading"
                        :searchable="false"
                        empty-title="No invoices found"
                        empty-description="Try a different search term or filter."
                        :on-row-click="onRowClick"
                        @page-change="fetchInvoices"
                    >
                        <template #cell-invoice_code="{ row }">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-800">
                                    {{ asInvoiceRow(row).invoice_code }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ asInvoiceRow(row).patient }}
                                </span>
                            </div>
                        </template>

                        <template #cell-reference_id="{ row }">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-800">
                                    {{ asBookingRow(row).reference_id }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ asBookingRow(row).patient }}
                                </span>
                            </div>
                        </template>

                        <template #cell-patient_name="{ row }">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-800">
                                    {{
                                        asPatientRow(row).patient?.full_name ??
                                        "—"
                                    }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    Latest
                                    {{
                                        asPatientRow(row).latest_invoice
                                            ?.invoice_code ?? "No invoices yet"
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

                        <template #cell-total_balance="{ value }">
                            <span class="font-medium text-red-500"
                                >₱{{ formatMoney(value) }}</span
                            >
                        </template>

                        <template #cell-balance_due="{ value }">
                            <span class="font-medium text-red-500"
                                >₱{{ formatMoney(value) }}</span
                            >
                        </template>

                        <template #cell-category="{ value }">
                            <span
                                class="text-xs px-2 py-0.5 rounded-full font-medium border bg-blue-50 text-primary border-blue-200"
                            >
                                {{ value }}
                            </span>
                        </template>

                        <template #cell-status="{ value }">
                            <span
                                class="capitalize text-xs px-2 py-0.5 rounded-full font-medium border"
                                :class="statusClass(value)"
                            >
                                {{ value }}
                            </span>
                        </template>

                        <template #cell-total="{ value }">
                            <span class="font-medium"
                                >₱{{ formatMoney(value) }}</span
                            >
                        </template>

                        <template #cell-amount="{ value }">
                            <span class="font-medium text-red-500"
                                >₱{{ formatMoney(value) }}</span
                            >
                        </template>

                        <template #cell-created_at="{ value }">
                            {{ formatDate(value) }}
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import InvoiceOverview from "~/components/sections/app/Billing/InvoiceOverview.vue";
import { usePagination } from "~/composables/usePagination";
import { invoiceService } from "~/api/invoice/InvoiceService";
import type {
    InvoiceRow,
    PatientSummaryRow,
    BookingRow,
} from "~/types/invoice";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Invoices",
});

type SearchType = "invoice" | "patient";
type Row = InvoiceRow | PatientSummaryRow | BookingRow;

const route = useRoute();
const router = useRouter();
const today = new Date().toISOString().split("T")[0];

const dateFrom = ref(today);
const dateTo = ref(today);

const invoiceColumns: DataTableColumn[] = [
    { key: "invoice_code", label: "Invoice Code", sortable: false },
    { key: "category", label: "Category", sortable: false },
    { key: "status", label: "Status", sortable: false },
    { key: "total", label: "Total", align: "right", sortable: false },
    {
        key: "amount",
        label: "Amount to be paid",
        align: "right",
        sortable: false,
    },
    { key: "created_at", label: "Date", align: "right", sortable: true },
];

const patientColumns: DataTableColumn[] = [
    { key: "patient_name", label: "Patient", sortable: false },
    { key: "status", label: "Status", sortable: false },
    { key: "total_amount", label: "Total", align: "right", sortable: false },
    { key: "total_paid", label: "Paid", align: "right", sortable: false },
    { key: "total_balance", label: "Balance", align: "right", sortable: false },
];

const columns = computed<DataTableColumn[]>(() => {
    if (searchType.value === "patient") return patientColumns;
    return invoiceColumns;
});

const searchModes = [
    { label: "Invoice", value: "invoice" },
    { label: "Patient name", value: "patient" },
] as { label: string; value: SearchType }[];

const invoices = ref<Row[]>([]);
const loading = ref(true);
const uuid = computed(() => route.params.uuid);
const query = ref("");
const searchType = ref<SearchType>("invoice");
const pagination = usePagination({
    pageSize: 10,
});
const searchPlaceholder = computed(() => {
    const placeholders = {
        invoice: "Enter invoice code...",
        booking: "Enter booking reference...",
        patient: "Enter patient name...",
    };

    return placeholders[searchType.value];
});

async function fetchInvoices(page = pagination.currentPage.value) {
    loading.value = true;

    pagination.currentPage.value = page;

    try {
        const res = await invoiceService.list({
            branch_uuid: route.params.uuid,
            search: query.value,
            search_type: searchType.value,
            page,
            per_page: pagination.pageSize.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
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

function asInvoiceRow(row: Row): InvoiceRow {
    return row as InvoiceRow;
}

function asPatientRow(row: Row): PatientSummaryRow {
    return row as PatientSummaryRow;
}

function asBookingRow(row: Row): BookingRow {
    return row as BookingRow;
}

function onSearchTypeChange(mode: SearchType) {
    if (searchType.value === mode) return;
    searchType.value = mode;
    query.value = "";
    dateFrom.value = today;
    dateTo.value = today;
    pagination.reset();
    fetchInvoices(1);
}

function onSearch() {
    pagination.reset();

    fetchInvoices(1);
}

function onRowClick(row: Row) {
    if (searchType.value === "patient") {
        viewPatient(row as PatientSummaryRow);
    } else {
        viewInvoice(row as InvoiceRow);
    }
}

function viewInvoice(row: InvoiceRow) {
    router.push({
        path: `/app/branches/${uuid.value}/invoices/${row.invoice_code}`,
        query: {
            mode: searchType.value,
        },
    });
}

function viewPatient(row: PatientSummaryRow) {
    if (!row.patient?.patient_uuid) return;

    router.push({
        path: `/app/branches/${uuid.value}/invoices/patient/${row.patient.patient_uuid}`,
        query: {
            mode: searchType.value,
        },
    });
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        paid: "bg-green-50 text-green-700 border-green-200",
        partial: "bg-blue-50 text-blue-700 border-blue-200",
        pending: "bg-orange-50 text-orange-700 border-orange-200",
        overdue: "bg-red-50 text-red-600 border-red-200",
    };

    return (
        map[status?.toLowerCase()] ?? "bg-gray-50 text-gray-600 border-gray-200"
    );
}

function formatMoney(amount: number | string) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
    });
}

function formatDate(value: string) {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
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
