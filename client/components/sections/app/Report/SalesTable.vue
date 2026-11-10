<script setup lang="ts">
import { computed, watch } from "vue";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import { usePagination } from "~/composables/usePagination";
import {
    SALES_STATUS_CLASS,
    SALES_STATUS_LABEL,
    type SalesVatRow,
} from "~/types/report";
import { formatCurrency } from "~/utils/currency";
import { formatDate } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        rows: SalesVatRow[];
        loading: boolean;
        currentPage?: number;
        totalItems?: number;
        pageSize?: number;
        view?: "table" | "card";
    }>(),
    {
        currentPage: 1,
        totalItems: 0,
        pageSize: 15,
    },
);

const emit = defineEmits<{
    "page-change": [page: number];
}>();

const pagination = usePagination({ pageSize: props.pageSize });

watch(
    () => props.totalItems,
    (value) => {
        pagination.totalItems.value = value;
    },
    { immediate: true },
);

watch(
    () => props.currentPage,
    (value) => {
        pagination.currentPage.value = value;
    },
    { immediate: true },
);

const columns: DataTableColumn[] = [
    { key: "date", label: "Date" },
    { key: "invoice_code", label: "Invoice No.", primary: true },
    { key: "patient", label: "Patient" },
    { key: "description", label: "Description" },
    { key: "gross", label: "Gross Amount", align: "right" },
    { key: "vatable", label: "Vatable Sales", align: "right" },
    { key: "exempt", label: "VAT-Exempt", align: "right" },
    { key: "vat", label: "VAT (12%)", align: "right" },
    { key: "status", label: "Status", badge: true },
];

const money = (value: number) =>
    formatCurrency(value, { treatMissingAsZero: true, withSymbol: false });

const tableRows = computed(() =>
    props.rows.map((row) => ({
        id: row.invoice_code,
        date: formatDate(row.created_at),
        invoice_code: row.invoice_code,
        patient: row.patient_name ?? "—",
        description: row.description || "—",
        gross: money(row.gross_amount),
        vatable: money(row.vatable_sales),
        exempt: money(row.vat_exempt_sales),
        vat: money(row.vat_amount),
        status: row.status,
    })),
);
</script>

<template>
    <DataTable
        class="flex-1 h-full rounded-t-none rounded-b-xl border-none"
        :columns="columns"
        :rows="tableRows"
        :pagination="pagination"
        :loading="loading"
        :view="view"
        :searchable="false"
        empty-title="No sales in this period"
        empty-description="Invoices created in the selected period will be listed here."
        :row-key="(row) => row.id"
        @page-change="emit('page-change', $event)"
    >
        <template #cell-date="{ row }">
            <span class="whitespace-nowrap text-slate-600 dark:text-gray-300">
                {{ row.date }}
            </span>
        </template>

        <template #cell-invoice_code="{ row }">
            <span
                class="whitespace-nowrap font-medium text-slate-800 dark:text-white"
            >
                {{ row.invoice_code }}
            </span>
        </template>

        <template #cell-description="{ row }">
            <p
                class="max-w-xs truncate text-slate-600 dark:text-gray-300"
                :title="row.description"
            >
                {{ row.description }}
            </p>
        </template>

        <template
            v-for="key in ['gross', 'vatable', 'exempt', 'vat']"
            :key="key"
            #[`cell-${key}`]="{ row }"
        >
            <span
                class="whitespace-nowrap font-mono tabular-nums text-slate-700 dark:text-gray-200"
            >
                {{ row[key] }}
            </span>
        </template>

        <template #cell-status="{ row }">
            <span
                class="whitespace-nowrap rounded-full border px-3 py-1 text-xs font-medium"
                :class="SALES_STATUS_CLASS[row.status] ?? SALES_STATUS_CLASS.written_off"
            >
                {{ SALES_STATUS_LABEL[row.status] ?? row.status }}
            </span>
        </template>
    </DataTable>
</template>
