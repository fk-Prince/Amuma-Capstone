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
                        class="flex items-center gap-2 flex-1 lg:justify-end"
                    >
                        <div class="flex-1 lg:max-w-xs">
                            <BaseInput
                                v-model="query"
                                placeholder="Enter patient name..."
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
import type { PatientSummaryRow } from "~/types/invoice";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Invoices",
});

const route = useRoute();
const router = useRouter();

const columns: DataTableColumn[] = [
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

const invoices = ref<PatientSummaryRow[]>([]);
const loading = ref(true);
const uuid = computed(() => route.params.uuid);
const query = ref("");
const pagination = usePagination({
    pageSize: 10,
});

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

function onSearch() {
    pagination.reset();

    fetchInvoices(1);
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

function formatMoney(amount: number | string) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
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
