<template>
    <div
        class="flex min-h-screen-header flex-1 flex-col gap-2 bg-slate-100 dark:bg-surface"
    >
        <InvoiceOverview
            :overview="overview"
            @filter-change="onOverviewFilter"
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
                        <ViewModeToggle
                            :model-value="viewMode"
                            @update:model-value="setViewMode"
                        />

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
                    class="flex-1 min-h-0 rounded-t-none rounded-b-xl border-none"
                    :columns="patientColumns"
                    :view="viewMode"
                    :rows="invoices"
                    :pagination="pagination"
                    :loading="loading"
                    :searchable="false"
                    empty-title="No patients found"
                    empty-description="Try a different patient code or name."
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
                    class="flex-1 min-h-0 rounded-t-none rounded-b-xl border-none"
                    :columns="invoiceColumns"
                    :view="viewMode"
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
                </DataTable>

                <DataTable
                    v-else-if="activeTab === 'bookings'"
                    class="flex-1 min-h-0 rounded-t-none rounded-b-xl border-none"
                    :columns="bookingColumns"
                    :view="viewMode"
                    :rows="bookingRows"
                    :pagination="bookingPagination"
                    :loading="bookingRowsLoading"
                    :searchable="false"
                    :row-key="(row: BookingBillingRow) => row.reference_id"
                    empty-title="No booking payments found"
                    empty-description="Bookings that carry a payment show up here."
                    :on-row-click="openBookingReceipt"
                    @page-change="fetchBookingRows"
                >
                    <template #cell-reference_id="{ value }">
                        <span
                            class="font-mono font-semibold text-slate-800 dark:text-white"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-patient_name="{ value }">
                        <span class="font-medium text-slate-800 dark:text-white">
                            {{ value ?? "—" }}
                        </span>
                    </template>

                    <template #cell-type="{ row }">
                        <span class="text-slate-600 dark:text-gray-400">
                            {{ bookingTypeLabel(row) }}
                        </span>
                    </template>

                    <template #cell-status="{ row }">
                        <span
                            class="rounded-full px-3 py-1 text-xs font-medium capitalize"
                            :class="statusClasses(row.status)"
                        >
                            {{ formatStatus(row.status) }}
                        </span>
                    </template>

                    <template #cell-booking_amount="{ row }">
                        <span
                            v-if="row.booking_amount > 0"
                            class="font-medium text-green-600"
                        >
                            ₱{{ formatMoney(row.booking_amount) }}
                        </span>
                        <span v-else class="text-slate-400 dark:text-gray-500">
                            —
                        </span>
                    </template>

                    <template #cell-balance_amount="{ row }">
                        <span
                            v-if="row.payment_status !== 'refunded'"
                            class="font-medium text-red-500"
                        >
                            ₱{{ formatMoney(row.balance_amount) }}
                        </span>
                        <span v-else class="text-slate-400 dark:text-gray-500">
                            —
                        </span>
                    </template>

                    <template #cell-payment_status="{ value }">
                        <span
                            class="rounded-full border px-2.5 py-1 text-[11px] font-semibold capitalize"
                            :class="paymentStatusClass(String(value))"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-created_at="{ value }">
                        <span class="text-slate-600 dark:text-gray-400">
                            {{ formatDateTime(value) }}
                        </span>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                v-if="row.can_pay && canCreate(Modules.BillingAndInvoices)"
                                type="button"
                                class="rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-600"
                                @click.stop="openPayModal(row)"
                            >
                                Pay
                            </button>

                            <button
                                v-if="row.can_refund && canUpdate(Modules.BillingAndInvoices)"
                                type="button"
                                class="rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                @click.stop="refundTarget = row"
                            >
                                Refund
                            </button>

                            <button
                                v-if="row.receipt"
                                type="button"
                                class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                                @click.stop="openBookingReceipt(row)"
                            >
                                Receipt
                            </button>
                        </div>
                    </template>
                </DataTable>

                <DataTable
                    v-else
                    class="flex-1 min-h-0 rounded-t-none rounded-b-xl border-none"
                    :columns="receiptColumns"
                    :view="viewMode"
                    :rows="receipts"
                    :pagination="receiptPagination"
                    :loading="receiptsLoading"
                    :searchable="false"
                    empty-title="No receipts found"
                    empty-description="Search by transaction number, patient, payor or invoice code."
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
                v-if="payTarget"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4"
                @click.self="!paying && closePayModal()"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                >
                    <h3
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        Collect payment for {{ payTarget.reference_id }}
                    </h3>

                    <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ payTarget.patient_name ?? "This booking" }} pays the
                        downpayment in cash at the branch.
                    </p>

                    <dl class="mt-4 text-sm">
                        <div class="flex justify-between py-1">
                            <dt class="text-gray-500 dark:text-gray-400">
                                Booking total
                            </dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                ₱{{ formatMoney(payTarget.total_amount) }}
                            </dd>
                        </div>

                        <div
                            class="flex justify-between border-t border-gray-100 py-1.5 dark:border-white/10"
                        >
                            <dt class="font-semibold text-gray-900 dark:text-white">
                                Amount due now
                            </dt>
                            <dd class="font-semibold text-primary">
                                ₱{{ formatMoney(payTarget.amount_due) }}
                            </dd>
                        </div>
                    </dl>

                    <label
                        class="mt-3 block text-xs font-medium text-gray-600 dark:text-gray-400"
                        for="booking-cash"
                    >
                        Cash received
                    </label>
                    <input
                        id="booking-cash"
                        v-model="payCash"
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        :disabled="paying"
                        class="mt-1.5 w-full rounded-lg border border-gray-200 px-3 py-2 text-right text-sm font-semibold text-gray-700 focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        @keyup.enter="confirmPay"
                    />

                    <p
                        v-if="payChange > 0"
                        class="mt-1.5 text-right text-xs text-gray-500 dark:text-gray-400"
                    >
                        Change ₱{{ formatMoney(payChange) }}
                    </p>

                    <div class="mt-5 flex justify-end gap-2.5">
                        <button
                            type="button"
                            :disabled="paying"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                            @click="closePayModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="paying || !canConfirmPay"
                            class="flex min-w-[110px] items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-60"
                            @click="confirmPay"
                        >
                            {{ paying ? "Saving..." : "Confirm payment" }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <ConfirmDialog
            :open="!!refundTarget"
            variant="danger"
            title="Refund this booking?"
            :message="`${refundTarget?.reference_id ?? ''} was rejected. ₱${formatMoney(refundTarget?.booking_amount)} paid for it will be marked as refunded.`"
            confirm-label="Refund"
            :loading="refunding"
            @confirm="confirmRefund"
            @cancel="refundTarget = null"
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
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import ViewModeToggle from "~/components/ui/ViewModeToggle.vue";
import { useListView } from "~/composables/useListView";
import { Modules } from "~/types/module";
import { formatStatus, statusClasses } from "~/types/booking";
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

type TabKey = "patients" | "invoices" | "bookings" | "receipts";

const tabs: { key: TabKey; label: string }[] = [
    { key: "patients", label: "Patients" },
    { key: "invoices", label: "Invoices" },
    { key: "bookings", label: "Bookings" },
    { key: "receipts", label: "Receipts" },
];

const { view: viewMode, setView: setViewMode } = useListView();
const { canCreate, canUpdate } = usePermissions();

interface BookingBillingRow {
    booking_id: number;
    reference_id: string;
    category: string;
    type: string | null;
    status: string;
    patient_name: string | null;
    total_amount: number;
    booking_amount: number;
    balance_amount: number;
    payment_status: string | null;
    payment_method: string | null;
    created_at: string | null;
    receipt: PaymentReceiptData | null;
    can_pay: boolean;
    can_refund: boolean;
    amount_due: number | null;
}

const activeTab = ref<TabKey>("patients");

const patientColumns: DataTableColumn[] = [
    { key: "patient_name", label: "Patient", sortable: false },
    { key: "total_amount", label: "Total", align: "right", sortable: false },
    { key: "total_paid", label: "Paid", align: "right", sortable: false },
    {
        key: "total_refundable",
        label: "Credit",
        align: "right",
        sortable: false,
    },
    { key: "total_balance", label: "Balance", align: "right", sortable: false },
];

const invoiceColumns: DataTableColumn[] = [
    { key: "invoice_code", label: "Invoice Reference", sortable: false },
    { key: "patient", label: "Patient", sortable: false },
    { key: "status", label: "Status", sortable: false },
    { key: "total", label: "Total", align: "right", sortable: false },
    { key: "paid", label: "Paid", align: "right", sortable: false },
    { key: "amount", label: "Balance", align: "right", sortable: false },
    { key: "created_at", label: "Date", sortable: false },
];

const bookingColumns: DataTableColumn[] = [
    {
        key: "reference_id",
        label: "Booking Ref.",
        sortable: false,
        primary: true,
    },
    { key: "patient_name", label: "Patient", sortable: false },
    { key: "type", label: "Type", sortable: false },
    { key: "status", label: "Status", sortable: false },
    { key: "booking_amount", label: "Paid", align: "right", sortable: false },
    { key: "balance_amount", label: "Balance", align: "right", sortable: false },
    { key: "payment_status", label: "Payment", sortable: false },
    { key: "created_at", label: "Date", sortable: false },
    { key: "actions", label: "", align: "right", sortable: false },
];

const receiptColumns: DataTableColumn[] = [
    { key: "payment_code", label: "Transaction No.", sortable: false },
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

const bookingRows = ref<BookingBillingRow[]>([]);
const bookingRowsLoading = ref(false);
const bookingPagination = usePagination({
    pageSize: 10,
});

const payTarget = ref<BookingBillingRow | null>(null);
const payCash = ref<number | string>("");
const paying = ref(false);
const refundTarget = ref<BookingBillingRow | null>(null);
const refunding = ref(false);

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
    if (activeTab.value === "invoices")
        return "Search by invoice code or patient name...";
    if (activeTab.value === "bookings")
        return "Search by booking reference or patient name...";

    return "Transaction no., patient code, name, payor or invoice code...";
});

function switchTab(tab: TabKey) {
    if (activeTab.value === tab) return;

    activeTab.value = tab;
    query.value = "";

    if (tab === "receipts" && !receipts.value.length) {
        fetchReceipts(1);
    } else if (tab === "invoices" && !invoiceRows.value.length) {
        fetchInvoiceRows(1);
    } else if (tab === "bookings" && !bookingRows.value.length) {
        fetchBookingRows(1);
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

    if (activeTab.value === "bookings") {
        bookingPagination.reset();
        fetchBookingRows(1);
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

async function fetchBookingRows(page = bookingPagination.currentPage.value) {
    bookingRowsLoading.value = true;

    bookingPagination.currentPage.value = page;

    try {
        const res = await invoiceService.bookings({
            branch_uuid: route.params.uuid,
            search: query.value,
            page,
            per_page: bookingPagination.pageSize.value,
        });

        bookingRows.value = res.data ?? [];

        bookingPagination.setTotal(res.meta?.total ?? bookingRows.value.length);
    } catch (err) {
        console.error(err);
        bookingRows.value = [];
        bookingPagination.setTotal(0);
    } finally {
        bookingRowsLoading.value = false;
    }
}

function bookingTypeLabel(row: BookingBillingRow) {
    if (row.type === "Complete") return "Complete Admission";

    return row.category === "homecare" && row.type
        ? `Homecare · ${row.type}`
        : (row.type ?? "—");
}

const PAYMENT_STATUS_CLASSES: Record<string, string> = {
    paid: "bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20",
    partial:
        "bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/20",
    pending:
        "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/20",
    refunded:
        "bg-slate-100 text-slate-600 border-slate-200 dark:bg-white/10 dark:text-gray-400 dark:border-white/10",
};

function paymentStatusClass(status: string) {
    return (
        PAYMENT_STATUS_CLASSES[status] ??
        "bg-slate-50 text-slate-600 border-slate-200"
    );
}

function openBookingReceipt(row: BookingBillingRow) {
    if (row.receipt) activeReceipt.value = row.receipt;
}

function replaceBookingRow(next: BookingBillingRow) {
    bookingRows.value = bookingRows.value.map((row) =>
        row.reference_id === next.reference_id ? next : row,
    );
}

const payChange = computed(() =>
    Math.max(
        0,
        Math.round(
            (Number(payCash.value || 0) - Number(payTarget.value?.amount_due ?? 0)) *
                100,
        ) / 100,
    ),
);

const canConfirmPay = computed(
    () =>
        Number(payCash.value || 0) > 0 &&
        Number(payCash.value || 0) >= Number(payTarget.value?.amount_due ?? 0),
);

function openPayModal(row: BookingBillingRow) {
    payTarget.value = row;
    payCash.value = row.amount_due ?? "";
}

function closePayModal() {
    if (paying.value) return;

    payTarget.value = null;
    payCash.value = "";
}

async function confirmPay() {
    if (!payTarget.value || paying.value || !canConfirmPay.value) return;

    paying.value = true;

    try {
        const res = await invoiceService.action({
            type: "booking_pay",
            branch_uuid: route.params.uuid,
            reference_id: payTarget.value.reference_id,
            cash: Number(payCash.value),
        });

        success(res.message ?? "Payment received.");

        replaceBookingRow(res.data);

        payTarget.value = null;
        payCash.value = "";

        openBookingReceipt(res.data);
    } catch (err: any) {
        error(err?.message ?? "Failed to record the payment. Please try again.");
    } finally {
        paying.value = false;
    }
}

async function confirmRefund() {
    if (!refundTarget.value || refunding.value) return;

    refunding.value = true;

    try {
        const res = await invoiceService.action({
            type: "booking_refund",
            branch_uuid: route.params.uuid,
            reference_id: refundTarget.value.reference_id,
        });

        success(res.message ?? "Booking refunded.");

        replaceBookingRow(res.data);

        refundTarget.value = null;
    } catch (err: any) {
        error(err?.message ?? "Failed to refund the booking. Please try again.");
    } finally {
        refunding.value = false;
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

interface OverviewFilter {
    period: "month" | "date" | "all";
    month?: number;
    year?: number;
    from?: string;
    to?: string;
}

const overview = ref(null);
const now = new Date();
const overviewFilter = ref<OverviewFilter>({
    period: "month",
    month: now.getMonth() + 1,
    year: now.getFullYear(),
});
const overviewLoading = ref(true);
let overviewRequest = 0;

async function fetchOverview() {
    const request = ++overviewRequest;

    overviewLoading.value = true;

    try {
        const overviewData = await invoiceService.overview({
            branch_uuid: uuid.value,
            ...overviewFilter.value,
        });

        if (request === overviewRequest) {
            overview.value = overviewData.data ?? overviewData;
        }
    } catch (err) {
        console.error("Failed loading billing overview:", err);
    } finally {
        if (request === overviewRequest) {
            overviewLoading.value = false;
        }
    }
}

function onOverviewFilter(filter: OverviewFilter) {
    overviewFilter.value = filter;

    fetchOverview();
}

onMounted(async () => {
    await Promise.all([fetchInvoices(1), fetchOverview()]);
});
</script>
