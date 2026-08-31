<script setup lang="ts">
import { ref, computed, watch, onMounted, defineComponent, h } from "vue";
import * as LucideIcons from "lucide-vue-next";
import { patientAccessService } from "~/api/patient-access/PatientAccessService.js";
import { refundService } from "~/api/refund/RefundService";
import { paymentService } from "~/api/payment/PaymentService";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import { formatCurrency } from "~/utils/currency";
import { useToast } from "~/composables/useToast";

import type { PaymentReceipt as PaymentReceiptData } from "~/types/receipt";

useHead({ title: "Settings" });

definePageMeta({
    layout: "portal",
});

type RefundMethod = "GCash" | "Bank Transfer" | "Cash Pickup";

type PaymentMethod = "GCash" | "Bank Transfer";

interface RefundRequest {
    id: number;
    amount: number;
    reason: string;
    created_at: string;
    status: "Pending" | "Approved" | "Released" | "Rejected";
}

interface LovedOne {
    patient_id: number;
    full_name: string;
    status: "Active" | "Discharged" | "On Leave";
    room_label: string;
    room_type: string | null;
    refundable_amount: number;
    refundable_reason: string | null;
    location_type: "facility" | "homecare" | "none";
    full_address: string | null;
}

interface BillingInfo {
    roomLabel?: string;
    accommodationType?: string;
    billingCycle?: string;
    contractPrice?: number;
    totalBilled?: number;
    adjustedTotal?: number;
    amountPaid?: number;
    balanceDue?: number;
    dueDate?: string;
    billingPeriod?: string;
    invoiceCount?: number;
}

interface Transaction {
    id: string;
    invoiceId: number;
    invoiceCode: string;
    type: "invoice" | "payment" | "refund" | "adjustment";
    label: string;
    reference?: string;
    date: string;
    amount: number;
    method?: string;
    status?: string;
    reason?: string;
    maskedCardNumber?: string | null;
    receiptNo?: string | null;
}

interface InvoiceService {
    type: string | null;
    schedule_services_id: number;
    price: number;
    hours_booked: number | null;
    service: {
        service_id: number;
        service_name: string;
        type: string;
    } | null;
    schedule: {
        schedule_id: number;
        schedule_code: string;
        scheduled_at: string | null;
    } | null;
}

interface InvoiceSummary {
    invoice_id: number;
    invoice_code: string;
    status: string;
    total: number;
    adjusted_total: number;
    amount_paid: number;
    balance_due: number;
    refund_status: string;
    created_at: string;
    accommodation_type: string | null;
    billing_cycle: string | null;
    start_date: string | null;
    end_date: string | null;
    source_type: string | null;
    services: InvoiceService[];
    adjustments: Array<{
        invoice_adjustment_id?: number;
        type?: string;
        reason: string;
        amount: number;
        created_at?: string;
    }>;
}

const AppIcon = defineComponent({
    name: "AppIcon",
    inheritAttrs: false,
    props: {
        name: {
            type: String,
            required: true,
        },
    },
    setup(props, { attrs }) {
        return () => {
            const pascalName = props.name
                .split("-")
                .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                .join("");

            const IconComponent =
                (LucideIcons as Record<string, any>)[pascalName] ||
                LucideIcons.CircleHelp;

            return h(IconComponent, attrs);
        };
    },
});

const { success, error } = useToast();

const isLoading = ref(true);
const loadError = ref<string | null>(null);
const noPatients = ref(false);
const rawRecords = ref<any[]>([]);
const lovedOnes = ref<LovedOne[]>([]);
const selectedIndex = ref(0);

const billing = ref<BillingInfo>({
    roomLabel: "",
    accommodationType: "",
    billingCycle: "",
    contractPrice: 0,
    totalBilled: 0,
    adjustedTotal: 0,
    amountPaid: 0,
    balanceDue: 0,
    dueDate: "",
    billingPeriod: "",
    invoiceCount: 0,
});

const transactions = ref<Transaction[]>([]);
const invoices = ref<InvoiceSummary[]>([]);
const residentName = ref("");
const residentStatus = ref<"Active" | "Discharged" | "On Leave">("Active");
const advanceBalance = ref(0);
const refundRequests = ref<RefundRequest[]>([]);

const invoiceTransactions = computed(() =>
    transactions.value.filter(
        (transaction) => transaction.type !== "adjustment",
    ),
);

const totalInvoiceAmount = computed(() =>
    invoices.value.reduce(
        (total, invoice) => total + Number(invoice.total || 0),
        0,
    ),
);

const totalAdjustedAmount = computed(() =>
    invoices.value.reduce(
        (total, invoice) => total + Number(invoice.adjusted_total || 0),
        0,
    ),
);

const totalPaidAmount = computed(() =>
    invoices.value.reduce(
        (total, invoice) => total + Number(invoice.amount_paid || 0),
        0,
    ),
);

const totalRefundedAmount = computed(() =>
    transactions.value
        .filter((transaction) => transaction.type === "refund")
        .reduce((total, transaction) => total + transaction.amount, 0),
);

const totalAdjustmentAmount = computed(() =>
    transactions.value
        .filter((transaction) => transaction.type === "adjustment")
        .reduce((total, transaction) => total + transaction.amount, 0),
);

const netPaidAmount = computed(() =>
    Math.max(0, totalPaidAmount.value - totalRefundedAmount.value),
);

const currentBalance = computed(() =>
    Math.max(0, totalAdjustedAmount.value - netPaidAmount.value),
);

const hasBalanceDue = computed(() => currentBalance.value > 0);
const isDischarged = computed(() => residentStatus.value === "Discharged");

const refundReason = computed(() => {
    const explicit = lovedOnes.value[selectedIndex.value]?.refundable_reason;

    if (explicit) return explicit;

    if (residentStatus.value === "Discharged") {
        return "Resident discharged with an unused advance balance.";
    }

    if (residentStatus.value === "On Leave") {
        return "Resident on leave with an unused advance balance.";
    }

    if (totalPaidAmount.value > totalAdjustedAmount.value) {
        return "Overpayment on billed invoices.";
    }

    return "Unused advance balance.";
});

const paymentProgress = computed(() => {
    if (totalAdjustedAmount.value <= 0) return 0;

    return Math.min(
        100,
        Math.round((netPaidAmount.value / totalAdjustedAmount.value) * 100),
    );
});

const selectedLovedOne = computed(
    () => lovedOnes.value[selectedIndex.value] ?? null,
);

function mapResidentStatus(
    status?: string,
): "Active" | "Discharged" | "On Leave" {
    switch ((status || "").toLowerCase()) {
        case "discharged":
            return "Discharged";
        case "on leave":
        case "on_leave":
            return "On Leave";
        default:
            return "Active";
    }
}

function lovedOneStatusClasses(status: string) {
    const map: Record<string, string> = {
        active: "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-100",
        discharged: "bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200",
        "on leave":
            "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-100",
    };

    return map[status?.toLowerCase()] ?? "bg-gray-100 text-gray-600";
}

function mapRefundRequestStatus(status?: string): RefundRequest["status"] {
    switch ((status || "").toLowerCase()) {
        case "approved":
            return "Approved";
        case "released":
        case "completed":
            return "Released";
        case "rejected":
        case "denied":
            return "Rejected";
        default:
            return "Pending";
    }
}

function formatDateLabel(dateStr?: string): string {
    if (!dateStr) return "";

    const parsed = new Date(dateStr);

    if (Number.isNaN(parsed.getTime())) return dateStr;

    return parsed.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
}

function formatDateTime(dateStr?: string): string {
    if (!dateStr) return "";

    const parsed = new Date(dateStr);

    if (Number.isNaN(parsed.getTime())) return dateStr;

    return parsed.toLocaleString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
}

function formatStatus(status?: string) {
    return (status || "Unknown")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function transactionStatusClasses(status?: string) {
    switch ((status || "").toLowerCase()) {
        case "paid":
        case "completed":
        case "released":
            return "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-100";
        case "partial":
        case "processing":
        case "pending":
            return "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-100";
        case "refunded":
        case "partially refunded":
            return "bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100";
        case "cancelled":
        case "rejected":
            return "bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-100";
        default:
            return "bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200";
    }
}

function transactionIcon(type: Transaction["type"]) {
    switch (type) {
        case "payment":
            return "banknote";
        case "refund":
            return "arrow-down-circle";
        case "adjustment":
            return "sliders-horizontal";
        default:
            return "receipt";
    }
}

function transactionSign(type: Transaction["type"]) {
    switch (type) {
        case "payment":
            return "-";
        case "refund":
            return "+";
        default:
            return "";
    }
}

function transactionAmountColor(type: Transaction["type"]) {
    switch (type) {
        case "payment":
            return "text-emerald-600";
        case "refund":
            return "text-blue-600";
        case "adjustment":
            return "text-amber-600";
        default:
            return "text-rose-500";
    }
}

function transactionIconClasses(type: Transaction["type"]) {
    switch (type) {
        case "payment":
            return "bg-emerald-50 text-emerald-600";
        case "refund":
            return "bg-blue-50 text-blue-600";
        case "adjustment":
            return "bg-amber-50 text-amber-600";
        default:
            return "bg-rose-50 text-rose-500";
    }
}

function getSourceTypeLabel(sourceType?: string | null): string {
    if (!sourceType) return "Unknown";

    if (sourceType.toLowerCase() === "adl") {
        return "Activities of Daily Living (ADL)";
    }

    if (sourceType.toLowerCase() === "medical") {
        return "Medical Services";
    }

    return sourceType;
}

function invoiceStatusClasses(status?: string) {
    switch ((status || "").toLowerCase()) {
        case "paid":
        case "completed":
            return "bg-emerald-50 text-emerald-700";
        case "partial":
        case "partially_paid":
            return "bg-amber-50 text-amber-700";
        case "cancelled":
        case "rejected":
            return "bg-rose-50 text-rose-700";
        default:
            return "bg-gray-100 text-gray-600";
    }
}

function mapInvoices(items: any[]): InvoiceSummary[] {
    const result: InvoiceSummary[] = [];

    for (const invoice of items || []) {
        const source = invoice.source ?? {};
        const contract = source.contract ?? {};

        const services: InvoiceService[] = (source.services || []).map(
            (service: any) => ({
                type: service.type ?? null,
                schedule_services_id: service.schedule_services_id,
                price: Number(service.price ?? 0),
                hours_booked:
                    service.hours_booked !== null &&
                    service.hours_booked !== undefined
                        ? Number(service.hours_booked)
                        : null,
                service: service.service
                    ? {
                          service_id: service.service.service_id,
                          service_name: service.service.service_name,
                          type: service.service.type,
                      }
                    : null,
                schedule: service.schedule
                    ? {
                          schedule_id: service.schedule.schedule_id,
                          schedule_code: service.schedule.schedule_code,
                          scheduled_at: service.schedule.scheduled_at ?? null,
                      }
                    : null,
            }),
        );

        const adjustmentsList = (invoice.adjustments || []).map((adj: any) => ({
            invoice_adjustment_id: adj.invoice_adjustment_id,
            type: adj.type,
            reason: adj.reason || "Adjustment",
            amount: Number(adj.amount ?? 0),
            created_at: adj.created_at,
        }));

        result.push({
            invoice_id: invoice.invoice_id,
            invoice_code: invoice.invoice_code,
            status: invoice.status,
            total: Number(invoice.total ?? 0),
            adjusted_total: Number(
                invoice.adjusted_total ?? invoice.total ?? 0,
            ),
            amount_paid: Number(invoice.amount_paid ?? 0),
            balance_due: Number(invoice.balance_due ?? 0),
            refund_status: invoice.refund_status ?? "none",
            created_at: invoice.created_at,
            accommodation_type: contract.accommodation_type ?? null,
            billing_cycle: contract.billing_cycle ?? null,
            start_date: source.start_date ?? null,
            end_date: source.end_date ?? null,
            source_type:
                source.type ?? (services.length ? services[0]?.type : null),
            services,
            adjustments: adjustmentsList,
        });
    }

    return result;
}

function buildTransactionsFromInvoices(invoiceList: any[]): Transaction[] {
    const entries: Transaction[] = [];

    for (const invoice of invoiceList || []) {
        const invoiceId = invoice.invoice_id;
        const invoiceCode = invoice.invoice_code || `Invoice #${invoiceId}`;

        if (invoice.total !== undefined && invoice.total !== null) {
            entries.push({
                id: `invoice-${invoiceId}`,
                invoiceId,
                invoiceCode,
                type: "invoice",
                label: `Invoice ${invoiceCode}`,
                reference: invoiceCode,
                date: formatDateTime(invoice.created_at),
                amount: Number(invoice.total ?? 0),
                status: invoice.status,
            });
        }

        for (const payment of invoice.payments || []) {
            entries.push({
                id: `payment-${payment.payment_id}`,
                invoiceId,
                invoiceCode,
                type: "payment",
                label: `Payment · ${payment.payment_method || "Unknown"}`,
                reference:
                    payment.reference_id || `Payment #${payment.payment_id}`,
                date: formatDateTime(payment.created_at),
                amount: Number(payment.amount ?? 0),
                method: payment.payment_method,
                status: "completed",
                maskedCardNumber: payment.masked_card_number ?? null,
                receiptNo: payment.receipt_no ?? null,
            });

            for (const refund of payment.refunds || []) {
                entries.push({
                    id: `refund-${refund.refund_id}`,
                    invoiceId,
                    invoiceCode,
                    type: "refund",
                    label: `Refund · ${refund.refund_method || "Unknown"}`,
                    reference:
                        refund.reference_id || `Refund #${refund.refund_id}`,
                    date: formatDateTime(refund.created_at),
                    amount: Number(refund.amount ?? 0),
                    method: refund.refund_method,
                    status: refund.status,
                    reason: refund.reason,
                    maskedCardNumber: refund.masked_card_number ?? null,
                });
            }
        }
    }

    return entries.sort(
        (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime(),
    );
}

function buildRefundRequestsFromInvoices(invoiceList: any[]): RefundRequest[] {
    const requests: RefundRequest[] = [];

    for (const invoice of invoiceList || []) {
        for (const payment of invoice.payments || []) {
            for (const refund of payment.refunds || []) {
                requests.push({
                    id: refund.refund_id,
                    amount: Number(refund.amount ?? 0),
                    reason: refund.reason || "",
                    created_at: formatDateLabel(refund.created_at),
                    status: mapRefundRequestStatus(refund.status),
                });
            }
        }
    }

    return requests.sort(
        (a, b) =>
            new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
    );
}

function mapPatientRecord(item: any): LovedOne {
    const patient = item.patient;
    const ctx = item.location_context ?? {};

    return {
        patient_id: patient.patient_id,
        full_name: patient.full_name,
        status: mapResidentStatus(ctx.status),
        room_label: ctx.room?.room_no ? `Room ${ctx.room.room_no}` : "",
        room_type: ctx.room?.room_type ?? null,
        refundable_amount: Number(item.patient_refundable ?? 0),
        refundable_reason:
            item.patient_refundable_reason ?? item.refund_reason ?? null,
        location_type: (ctx.type === "homecare"
            ? "homecare"
            : ctx.type === "facility" || ctx.room
              ? "facility"
              : "none") as "facility" | "homecare" | "none",
        full_address: patient.full_address ?? null,
    };
}

function updateBillingFromRecord(item: any) {
    const invoiceList = Array.isArray(item.invoices)
        ? item.invoices
        : item.latest_invoice
          ? [item.latest_invoice]
          : [];

    const mappedInvoices = mapInvoices(invoiceList);

    invoices.value = mappedInvoices;

    const latestInvoice =
        mappedInvoices[0] ??
        (item.latest_invoice ? mapInvoices([item.latest_invoice])[0] : null);

    const room = item.location_context?.room;

    const contract = latestInvoice?.invoice_id
        ? invoiceList.find(
              (invoice: any) => invoice.invoice_id === latestInvoice.invoice_id,
          )?.source?.contract
        : null;

    residentName.value = item.patient?.full_name ?? residentName.value;
    residentStatus.value = mapResidentStatus(item.location_context?.status);
    advanceBalance.value = Number(item.patient_refundable ?? 0);

    billing.value = {
        roomLabel: room?.room_no ? `Room ${room.room_no}` : "",
        accommodationType:
            contract?.accommodation_type ?? room?.room_type ?? null,
        billingCycle: contract?.billing_cycle ?? null,
        contractPrice: Number(contract?.price ?? 0),
        totalBilled: mappedInvoices.reduce(
            (sum, invoice) => sum + invoice.total,
            0,
        ),
        adjustedTotal: mappedInvoices.reduce(
            (sum, invoice) => sum + invoice.adjusted_total,
            0,
        ),
        amountPaid: mappedInvoices.reduce(
            (sum, invoice) => sum + invoice.amount_paid,
            0,
        ),
        balanceDue: currentBalance.value,
        dueDate: latestInvoice?.end_date
            ? formatDateLabel(latestInvoice.end_date)
            : "",
        billingPeriod:
            latestInvoice?.start_date && latestInvoice?.end_date
                ? `${formatDateLabel(latestInvoice.start_date)} - ${formatDateLabel(latestInvoice.end_date)}`
                : "",
        invoiceCount: mappedInvoices.length,
    };

    transactions.value = buildTransactionsFromInvoices(invoiceList);
    refundRequests.value = buildRefundRequestsFromInvoices(invoiceList);
}

watch(selectedIndex, (idx) => {
    const record = rawRecords.value[idx];

    if (record) {
        updateBillingFromRecord(record);
    }
});

function nextLovedOne() {
    if (!lovedOnes.value.length) return;

    selectedIndex.value = (selectedIndex.value + 1) % lovedOnes.value.length;
}

function prevLovedOne() {
    if (!lovedOnes.value.length) return;

    selectedIndex.value =
        (selectedIndex.value - 1 + lovedOnes.value.length) %
        lovedOnes.value.length;
}

async function loadPatientData() {
    isLoading.value = true;
    loadError.value = null;
    noPatients.value = false;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "profile,financials",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (records.length) {
            lovedOnes.value = records.map(mapPatientRecord);
            rawRecords.value = records;
            updateBillingFromRecord(records[0]);
        } else {
            noPatients.value = true;
        }
    } catch (err: any) {
        console.error("Error loading patient data:", err);
        loadError.value = err?.message || "Failed to load patient data.";
    } finally {
        isLoading.value = false;
    }
}

onMounted(loadPatientData);

function statusClasses(status: RefundRequest["status"]) {
    if (status === "Pending") return "bg-amber-50 text-amber-600";
    if (status === "Approved") return "bg-primary-50 text-primary-600";
    if (status === "Released") return "bg-emerald-50 text-emerald-600";
    return "bg-rose-50 text-rose-500";
}

const showModal = ref(false);

const form = ref({
    method: "GCash" as RefundMethod,
    accountDetails: "",
});

const formError = ref("");
const isRefunding = ref(false);

function openModal() {
    form.value = {
        method: "GCash",
        accountDetails: "",
    };

    formError.value = "";
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submit() {
    if (!form.value.accountDetails.trim()) {
        formError.value =
            form.value.method === "Cash Pickup"
                ? "Enter the name of who will pick up the refund."
                : "Enter the account details to receive the refund.";

        return;
    }

    const patientId = lovedOnes.value[selectedIndex.value]?.patient_id;

    if (!patientId) {
        formError.value =
            "Unable to determine which loved one this refund is for.";
        return;
    }

    isRefunding.value = true;
    formError.value = "";

    try {
        const res = await refundService.claim({
            patient_id: patientId,
            method: form.value.method,
            account_details: form.value.accountDetails,
        });

        success(res?.message || "Your refund has been claimed.");
        showModal.value = false;

        await loadPatientData();
    } catch (err: any) {
        formError.value = err?.message || "Failed to submit refund request.";
    } finally {
        isRefunding.value = false;
    }
}

const showPaymentModal = ref(false);
const payAmount = ref(0);
const paymentError = ref("");
const isPaying = ref(false);
const activeReceipt = ref<PaymentReceiptData | null>(null);

const paymentForm = ref({
    method: "GCash" as PaymentMethod,
    accountDetails: "",
});

function openPaymentModal() {
    if (!hasBalanceDue.value) return;

    payAmount.value = currentBalance.value;

    paymentForm.value = {
        method: "GCash",
        accountDetails: "",
    };

    paymentError.value = "";
    showPaymentModal.value = true;
}

function closePaymentModal() {
    showPaymentModal.value = false;
}

async function payBalance() {
    const patientId = lovedOnes.value[selectedIndex.value]?.patient_id;

    if (!patientId) {
        error("Unable to determine which loved one this payment is for.");
        return;
    }

    if (!payAmount.value || payAmount.value <= 0) {
        paymentError.value = "Enter an amount greater than ₱0.";
        return;
    }

    if (payAmount.value > currentBalance.value) {
        paymentError.value = `Amount can't exceed your balance of ${peso(
            currentBalance.value,
        )}.`;
        return;
    }

    if (!paymentForm.value.accountDetails.trim()) {
        paymentError.value = "Enter the account details you're paying from.";

        return;
    }

    isPaying.value = true;
    paymentError.value = "";

    try {
        const res = await paymentService.pay({
            patient_id: patientId,
            amount: payAmount.value,
            method: paymentForm.value.method,
            account_details: paymentForm.value.accountDetails,
        });

        const receipt: PaymentReceiptData | null = res?.receipt ?? null;

        showPaymentModal.value = false;

        if (!receipt) {
            success(res?.message || "Payment recorded successfully.");

            await loadPatientData();

            return;
        }

        applyReceiptLocally(receipt);

        activeReceipt.value = receipt;

        success(`Payment recorded. Receipt ${receipt.receipt_no}.`);
    } catch (err: any) {
        paymentError.value = err?.message || "Failed to process payment.";
    } finally {
        isPaying.value = false;
    }
}

function applyReceiptLocally(receipt: PaymentReceiptData) {
    const issuedAt = formatDateTime(receipt.issued_at ?? undefined);

    const entries: Transaction[] = [];

    for (const line of receipt.lines) {
        const invoice = invoices.value.find(
            (item) => item.invoice_code === line.invoice_code,
        );

        if (invoice) {
            invoice.amount_paid =
                Number(invoice.amount_paid) + line.amount_applied;
            invoice.balance_due = line.new_balance;
            invoice.status = line.new_balance <= 0 ? "paid" : "partial";
        }

        entries.push({
            id: `payment-${line.payment_id ?? `${receipt.receipt_no}-${line.line_no}`}`,
            invoiceId: line.invoice_id,
            invoiceCode: line.invoice_code,
            type: "payment",
            label: `Payment · ${receipt.payment.method || "Unknown"}`,
            reference: line.payment_reference ?? receipt.receipt_no,
            date: issuedAt,
            amount: line.amount_applied,
            method: receipt.payment.method ?? undefined,
            status: "completed",
            maskedCardNumber: receipt.payment.masked_account,
            receiptNo: receipt.receipt_no,
        });
    }

    transactions.value = [...entries, ...transactions.value];

    billing.value = {
        ...billing.value,
        amountPaid: totalPaidAmount.value,
        balanceDue: currentBalance.value,
    };
}

async function openReceipt(receiptNo?: string | null) {
    if (!receiptNo) return;

    try {
        const res = await paymentService.receipt({ receipt_no: receiptNo });

        activeReceipt.value = res?.data ?? res ?? null;
    } catch (err: any) {
        error(err?.message || "Unable to load that receipt.");
    }
}
</script>

<template>
    <div class="min-h-full bg-slate-50/60 p-5 dark:bg-[#0b0f1a]">
        <div v-if="isLoading" class="space-y-5">
            <div
                class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]"
            >
                <section
                    class="overflow-hidden rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary sm:p-6"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-8 w-8 animate-pulse rounded-xl bg-gray-100 dark:bg-white/10"
                        />
                        <div
                            class="h-4 w-32 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                        />
                    </div>

                    <div
                        class="mt-5 rounded-2xl border border-gray-100 bg-gradient-to-br from-gray-50 to-white p-5 dark:border-white/10 dark:from-transparent dark:to-transparent"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="h-14 w-14 shrink-0 animate-pulse rounded-2xl bg-gray-100 dark:bg-white/10"
                            />
                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-4 w-2/3 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                                />
                                <div
                                    class="h-3 w-1/3 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div class="flex items-center justify-center p-14">
                        <div
                            class="h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500 dark:border-white/10"
                        />
                    </div>
                </section>
            </div>

            <div
                class="grid grid-cols-1 gap-px overflow-hidden rounded-3xl border border-gray-100 bg-gray-100 shadow-sm dark:border-white/10 dark:bg-white/10 sm:grid-cols-2 xl:grid-cols-4"
            >
                <div
                    v-for="n in 4"
                    :key="n"
                    class="space-y-3 bg-white p-5 dark:bg-secondary sm:p-6"
                >
                    <div
                        class="h-10 w-10 animate-pulse rounded-xl bg-gray-100 dark:bg-white/10"
                    />
                    <div
                        class="h-3 w-16 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                    />
                    <div
                        class="h-6 w-20 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                    />
                </div>
            </div>

            <section
                class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary sm:p-6"
            >
                <div
                    class="h-4 w-24 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                />
                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div
                        v-for="n in 2"
                        :key="n"
                        class="h-40 animate-pulse rounded-2xl border border-gray-100 bg-gray-50 dark:border-white/10 dark:bg-white/5"
                    />
                </div>
            </section>

            <section
                class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary sm:p-6"
            >
                <div
                    class="h-4 w-32 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                />
                <div class="mt-5 space-y-2">
                    <div
                        v-for="n in 3"
                        :key="n"
                        class="h-14 animate-pulse rounded-2xl bg-gray-50 dark:bg-white/5"
                    />
                </div>
            </section>
        </div>

        <EmptyState
            v-else-if="noPatients"
            title="You currently have no patients"
            cta-label="Book a Service"
            cta-to="/booking/search"
        />

        <div v-else class="space-y-5">
            <!-- <div
                v-if="selectedLovedOne"
                class="inline-flex items-center gap-3 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-600 dark:bg-primary-500/10"
                >
                    {{
                        selectedLovedOne.full_name
                            .split(" ")
                            .map((n) => n[0])
                            .slice(0, 2)
                            .join("")
                    }}
                </div>

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                        {{ selectedLovedOne.full_name }}
                    </p>

                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        {{
                            selectedLovedOne.room_label ||
                            selectedLovedOne.location_type === "homecare"
                                ? selectedLovedOne.room_label || "Homecare"
                                : "Resident"
                        }}
                    </p>
                </div>
            </div> -->

            <div
                class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]"
            >
                <section
                    class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10"
                >
                    <div
                        class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-white/10"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10"
                                    >
                                        <AppIcon name="users" class="h-4 w-4" />
                                    </div>

                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-white"
                                    >
                                        My Loved Ones
                                    </p>
                                </div>

                                <p
                                    class="mt-1 pl-10 text-xs text-gray-400 dark:text-gray-500"
                                >
                                    {{ lovedOnes.length }} resident{{
                                        lovedOnes.length === 1 ? "" : "s"
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="lovedOnes.length > 1"
                                class="flex items-center gap-1 rounded-full border border-gray-100 bg-gray-50 p-1 dark:bg-white/5 dark:border-white/10"
                            >
                                <button
                                    @click="prevLovedOne"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white hover:text-gray-700 hover:shadow-sm dark:text-gray-500"
                                >
                                    <AppIcon
                                        name="chevron-left"
                                        class="h-4 w-4"
                                    />
                                </button>

                                <span
                                    class="min-w-8 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400"
                                >
                                    {{ selectedIndex + 1 }}/{{
                                        lovedOnes.length
                                    }}
                                </span>

                                <button
                                    @click="nextLovedOne"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white hover:text-gray-700 hover:shadow-sm dark:text-gray-500"
                                >
                                    <AppIcon
                                        name="chevron-right"
                                        class="h-4 w-4"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div v-if="lovedOnes.length" class="overflow-hidden">
                            <div
                                class="flex transition-transform duration-300 ease-out"
                                :style="{
                                    transform: `translateX(-${selectedIndex * 100}%)`,
                                }"
                            >
                                <div
                                    v-for="lo in lovedOnes"
                                    :key="lo.patient_id"
                                    class="w-full shrink-0"
                                >
                                    <div
                                        class="rounded-2xl border border-gray-100 bg-gradient-to-br from-gray-50 to-white p-5 dark:border-white/10 dark:from-white/5 dark:to-white/5"
                                    >
                                        <div
                                            class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div
                                                class="flex min-w-0 items-center gap-4"
                                            >
                                                <div
                                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary-100 text-lg font-bold text-primary-700 dark:bg-primary-500/15"
                                                >
                                                    {{
                                                        lo.full_name
                                                            .split(" ")
                                                            .map((n) => n[0])
                                                            .slice(0, 2)
                                                            .join("")
                                                    }}
                                                </div>

                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-lg font-bold text-gray-900 dark:text-white"
                                                    >
                                                        {{ lo.full_name }}
                                                    </p>

                                                    <div
                                                        class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1"
                                                    >
                                                        <span
                                                            v-if="lo.room_label"
                                                            class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400"
                                                        >
                                                            <AppIcon
                                                                name="home"
                                                                class="h-3.5 w-3.5"
                                                            />
                                                            {{ lo.room_label }}
                                                        </span>

                                                        <span
                                                            v-else-if="
                                                                lo.full_address
                                                            "
                                                            class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400"
                                                        >
                                                            <AppIcon
                                                                name="map-pin"
                                                                class="h-3.5 w-3.5"
                                                            />
                                                            {{
                                                                lo.full_address
                                                            }}
                                                        </span>

                                                        <span
                                                            v-if="lo.room_type"
                                                            class="text-xs text-primary-600"
                                                        >
                                                            {{ lo.room_type }}
                                                        </span>
                                                    </div>

                                                    <span
                                                        class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                                        :class="
                                                            lovedOneStatusClasses(
                                                                lo.status,
                                                            )
                                                        "
                                                    >
                                                        {{ lo.status }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                class="hidden h-16 w-px bg-gray-100 sm:block dark:bg-white/10"
                                            />

                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div
                                                    class="flex h-10 w-10 items-center justify-center rounded-xl"
                                                    :class="
                                                        lo.location_type ===
                                                        'homecare'
                                                            ? 'bg-blue-50 text-blue-600'
                                                            : 'bg-primary-50 text-primary-600'
                                                    "
                                                >
                                                    <AppIcon
                                                        :name="
                                                            lo.location_type ===
                                                            'homecare'
                                                                ? 'map-pin'
                                                                : 'building'
                                                        "
                                                        class="h-5 w-5"
                                                    />
                                                </div>

                                                <div>
                                                    <p
                                                        class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                                                    >
                                                        Care Type
                                                    </p>

                                                    <p
                                                        class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-white"
                                                    >
                                                        {{
                                                            lo.location_type ===
                                                            "homecare"
                                                                ? "Homecare"
                                                                : lo.location_type ===
                                                                    "facility"
                                                                  ? "Facility"
                                                                  : "Not Assigned"
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="isLoading"
                            class="flex min-h-40 items-center justify-center"
                        >
                            <div class="text-center">
                                <div
                                    class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500 dark:border-white/10"
                                />
                                <p
                                    class="mt-3 text-sm text-gray-400 dark:text-gray-500"
                                >
                                    Loading patient data...
                                </p>
                            </div>
                        </div>

                        <div
                            v-else-if="loadError"
                            class="rounded-2xl bg-rose-50 p-6 text-center dark:bg-rose-500/10"
                        >
                            <div
                                class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-500"
                            >
                                <AppIcon name="alert-circle" class="h-5 w-5" />
                            </div>

                            <p class="mt-3 text-sm font-medium text-rose-700">
                                {{ loadError }}
                            </p>

                            <button
                                @click="loadPatientData"
                                class="mt-3 rounded-full bg-white px-4 py-2 text-xs font-semibold text-primary-600 shadow-sm ring-1 ring-gray-100 transition hover:bg-gray-50 dark:bg-secondary"
                            >
                                Try Again
                            </button>
                        </div>

                        <div v-else>
                            <EmptyState
                                title="You currently have no patients"
                                cta-label="Book a Service"
                                cta-to="/booking/search"
                            />
                        </div>

                        <div
                            v-if="lovedOnes.length > 1"
                            class="mt-5 flex justify-center gap-1.5"
                        >
                            <button
                                v-for="(lo, idx) in lovedOnes"
                                :key="lo.patient_id"
                                @click="selectedIndex = idx"
                                class="h-1.5 rounded-full transition-all"
                                :class="
                                    selectedIndex === idx
                                        ? 'w-6 bg-primary-500'
                                        : 'w-1.5 bg-gray-200 hover:bg-gray-300'
                                "
                            />
                        </div>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10"
                >
                    <div
                        class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-white/10"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300"
                                    >
                                        <AppIcon
                                            name="wallet"
                                            class="h-4 w-4"
                                        />
                                    </div>

                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-white"
                                    >
                                        Financial Summary
                                    </p>
                                </div>

                                <p
                                    class="mt-1 pl-10 text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Your current billing and payment overview
                                </p>
                            </div>

                            <span
                                v-if="billing.invoiceCount"
                                class="inline-flex w-fit items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1.5 text-[11px] font-semibold text-gray-500 dark:bg-white/5 dark:text-gray-400"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-primary-500"
                                />
                                {{ billing.invoiceCount }} invoice{{
                                    billing.invoiceCount === 1 ? "" : "s"
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-px bg-gray-100 sm:grid-cols-2 xl:grid-cols-4 dark:bg-white/10"
                    >
                        <div class="bg-white p-5 sm:p-6 dark:bg-secondary">
                            <div class="flex items-start justify-between">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300"
                                >
                                    <AppIcon name="file-text" class="h-5 w-5" />
                                </div>
                            </div>

                            <p
                                class="mt-5 text-xs font-medium text-gray-400 dark:text-gray-500"
                            >
                                Total Invoices
                            </p>

                            <p
                                class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ billing.invoiceCount || 0 }}
                            </p>

                            <p
                                class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                Billing records
                            </p>
                        </div>

                        <div class="bg-white p-5 sm:p-6 dark:bg-secondary">
                            <div class="flex items-start justify-between">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-primary-500/10"
                                >
                                    <AppIcon name="receipt" class="h-5 w-5" />
                                </div>

                                <span
                                    v-if="
                                        totalAdjustedAmount !==
                                        totalInvoiceAmount
                                    "
                                    class="rounded-full bg-amber-50 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-amber-600"
                                >
                                    Adjusted
                                </span>
                            </div>

                            <p
                                class="mt-5 text-xs font-medium text-gray-400 dark:text-gray-500"
                            >
                                Total Billed
                            </p>

                            <div
                                v-if="
                                    totalAdjustedAmount !== totalInvoiceAmount
                                "
                            >
                                <p
                                    class="mt-1 text-xs text-gray-400 line-through dark:text-gray-500"
                                >
                                    {{ peso(totalInvoiceAmount) }}
                                </p>

                                <p
                                    class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white"
                                >
                                    {{ peso(totalAdjustedAmount) }}
                                </p>
                            </div>

                            <p
                                v-else
                                class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ peso(totalInvoiceAmount) }}
                            </p>

                            <p
                                class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                Adjusted billing total
                            </p>
                        </div>

                        <div
                            class="relative overflow-hidden bg-white p-5 sm:p-6 dark:bg-secondary"
                            :class="hasBalanceDue ? 'bg-rose-50/30' : ''"
                        >
                            <div class="flex items-start justify-between">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl"
                                    :class="
                                        hasBalanceDue
                                            ? 'bg-rose-50 text-rose-500'
                                            : 'bg-emerald-50 text-emerald-600'
                                    "
                                >
                                    <AppIcon
                                        name="credit-card"
                                        class="h-5 w-5"
                                    />
                                </div>

                                <span
                                    class="rounded-full px-2 py-1 text-[9px] font-bold uppercase tracking-wide"
                                    :class="
                                        hasBalanceDue
                                            ? 'bg-rose-50 text-rose-600'
                                            : 'bg-emerald-50 text-emerald-600'
                                    "
                                >
                                    {{ hasBalanceDue ? "Balance Due" : "Paid" }}
                                </span>
                            </div>

                            <p
                                class="mt-5 text-xs font-medium text-gray-400 dark:text-gray-500"
                            >
                                Total Balance
                            </p>

                            <p
                                class="mt-1 text-2xl font-bold"
                                :class="
                                    hasBalanceDue
                                        ? 'text-rose-600'
                                        : 'text-emerald-600'
                                "
                            >
                                {{ peso(currentBalance) }}
                            </p>

                            <button
                                v-if="hasBalanceDue"
                                @click="openPaymentModal"
                                class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700"
                            >
                                Pay Now
                                <AppIcon
                                    name="arrow-right"
                                    class="h-3.5 w-3.5"
                                />
                            </button>

                            <p v-else class="mt-1 text-[11px] text-emerald-600">
                                No outstanding balance
                            </p>
                        </div>

                        <div class="bg-white p-5 sm:p-6 dark:bg-secondary">
                            <div class="flex items-start justify-between">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                                >
                                    <AppIcon
                                        name="arrow-down-circle"
                                        class="h-5 w-5"
                                    />
                                </div>

                                <span
                                    v-if="advanceBalance > 0"
                                    class="rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-emerald-600"
                                >
                                    Available
                                </span>
                            </div>

                            <p
                                class="mt-5 text-xs font-medium text-gray-400 dark:text-gray-500"
                            >
                                Available Refund
                            </p>

                            <p class="mt-1 text-2xl font-bold text-emerald-600">
                                {{ peso(advanceBalance) }}
                            </p>

                            <button
                                v-if="isDischarged && advanceBalance > 0"
                                @click="openModal"
                                class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Request Refund
                                <AppIcon
                                    name="arrow-right"
                                    class="h-3.5 w-3.5"
                                />
                            </button>

                            <p
                                v-else-if="advanceBalance > 0"
                                class="mt-1 line-clamp-2 text-[11px] leading-4 text-gray-400 dark:text-gray-500"
                            >
                                {{ refundReason }}
                            </p>

                            <p
                                v-else
                                class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                No refundable balance
                            </p>
                        </div>
                    </div>

                    <div
                        class="border-t border-gray-100 p-5 sm:p-6 dark:border-white/10"
                    >
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            <div>
                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-xs font-medium text-gray-400 dark:text-gray-500"
                                    >
                                        Amount Paid
                                    </p>

                                    <AppIcon
                                        name="check-circle"
                                        class="h-4 w-4 text-emerald-500"
                                    />
                                </div>

                                <p
                                    class="mt-1 text-lg font-bold text-emerald-600"
                                >
                                    {{ peso(totalPaidAmount) }}
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-xs font-medium text-gray-400 dark:text-gray-500"
                                    >
                                        Refunded
                                    </p>

                                    <AppIcon
                                        name="arrow-down-circle"
                                        class="h-4 w-4 text-blue-500"
                                    />
                                </div>

                                <p class="mt-1 text-lg font-bold text-blue-600">
                                    {{ peso(totalRefundedAmount) }}
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-xs font-medium text-gray-400 dark:text-gray-500"
                                    >
                                        Payment Progress
                                    </p>

                                    <span
                                        class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                                    >
                                        {{ paymentProgress }}%
                                    </span>
                                </div>

                                <div
                                    class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                                >
                                    <div
                                        class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                        :style="{
                                            width: `${paymentProgress}%`,
                                        }"
                                    />
                                </div>

                                <p
                                    class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    {{ peso(netPaidAmount) }} of
                                    {{ peso(totalAdjustedAmount) }} paid
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <section
                v-if="invoices.length"
                class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6 dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex flex-col gap-2 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-primary-500/10"
                            >
                                <AppIcon name="file-text" class="h-4 w-4" />
                            </div>

                            <p
                                class="text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Invoices
                            </p>
                        </div>

                        <p
                            class="mt-1 pl-10 text-xs text-gray-400 dark:text-gray-500"
                        >
                            Detailed billing records for
                            {{ selectedLovedOne?.full_name || "this resident" }}
                        </p>
                    </div>

                    <span
                        class="w-fit rounded-full bg-gray-50 px-3 py-1.5 text-[11px] font-semibold text-gray-500 dark:bg-white/5 dark:text-gray-400"
                    >
                        {{ invoices.length }} invoice{{
                            invoices.length === 1 ? "" : "s"
                        }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article
                        v-for="invoice in invoices"
                        :key="invoice.invoice_id"
                        class="group overflow-hidden rounded-2xl border border-gray-100 bg-white transition hover:border-gray-200 hover:shadow-md dark:bg-secondary dark:border-white/10"
                    >
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="text-sm font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ invoice.invoice_code }}
                                        </p>

                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                                            :class="
                                                invoiceStatusClasses(
                                                    invoice.status,
                                                )
                                            "
                                        >
                                            {{ formatStatus(invoice.status) }}
                                        </span>

                                        <span
                                            v-if="
                                                invoice.adjusted_total !==
                                                invoice.total
                                            "
                                            class="rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-bold text-amber-600"
                                        >
                                            ADJUSTED
                                        </span>
                                    </div>

                                    <div
                                        v-if="invoice.source_type"
                                        class="mt-2 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-medium text-blue-600 dark:bg-primary-500/10"
                                    >
                                        {{
                                            getSourceTypeLabel(
                                                invoice.source_type,
                                            )
                                        }}
                                    </div>

                                    <p
                                        class="mt-2 text-[11px] text-gray-400 dark:text-gray-500"
                                    >
                                        {{ formatDateTime(invoice.created_at) }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p
                                        v-if="
                                            invoice.adjusted_total !==
                                            invoice.total
                                        "
                                        class="text-[11px] text-gray-400 line-through dark:text-gray-500"
                                    >
                                        {{ peso(invoice.total) }}
                                    </p>

                                    <p
                                        class="text-base font-bold"
                                        :class="
                                            invoice.adjusted_total !==
                                            invoice.total
                                                ? 'text-emerald-600'
                                                : 'text-gray-900'
                                        "
                                    >
                                        {{ peso(invoice.adjusted_total) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-xl bg-gray-50 p-3 dark:bg-white/5"
                                >
                                    <p
                                        class="text-[10px] font-medium text-gray-400 dark:text-gray-500"
                                    >
                                        Paid
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-emerald-600"
                                    >
                                        {{ peso(invoice.amount_paid) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl p-3"
                                    :class="
                                        invoice.balance_due > 0
                                            ? 'bg-rose-50'
                                            : 'bg-gray-50'
                                    "
                                >
                                    <p
                                        class="text-[10px] font-medium"
                                        :class="
                                            invoice.balance_due > 0
                                                ? 'text-rose-400'
                                                : 'text-gray-400'
                                        "
                                    >
                                        Balance
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold"
                                        :class="
                                            invoice.balance_due > 0
                                                ? 'text-rose-600'
                                                : 'text-gray-800'
                                        "
                                    >
                                        {{ peso(invoice.balance_due) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="
                                    invoice.start_date ||
                                    invoice.end_date ||
                                    invoice.billing_cycle
                                "
                                class="mt-3 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-white/10"
                            >
                                <div>
                                    <p
                                        class="text-[10px] text-gray-400 dark:text-gray-500"
                                    >
                                        Billing Period
                                    </p>

                                    <p
                                        class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {{
                                            invoice.start_date &&
                                            invoice.end_date
                                                ? `${formatDateLabel(invoice.start_date)} - ${formatDateLabel(invoice.end_date)}`
                                                : "N/A"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="text-[10px] text-gray-400 dark:text-gray-500"
                                    >
                                        Cycle
                                    </p>

                                    <p
                                        class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {{ invoice.billing_cycle || "N/A" }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="invoice.adjustments.length"
                                class="mt-4 rounded-xl border border-amber-100 bg-amber-50/50 p-3"
                            >
                                <div class="mb-2 flex items-center gap-1.5">
                                    <AppIcon
                                        name="sliders-horizontal"
                                        class="h-3.5 w-3.5 text-amber-600"
                                    />

                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wide text-amber-700"
                                    >
                                        Adjustments
                                    </p>
                                </div>

                                <div
                                    v-for="(adj, idx) in invoice.adjustments"
                                    :key="idx"
                                    class="flex items-start justify-between gap-3 border-t border-amber-100 py-2 first:border-t-0 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[10px] leading-4 text-gray-600 dark:text-gray-300"
                                    >
                                        {{ adj.reason }}
                                    </p>

                                    <span
                                        class="shrink-0 text-[10px] font-bold text-amber-600"
                                    >
                                        -{{ peso(adj.amount) }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="invoice.services.length"
                                class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10"
                            >
                                <p
                                    class="mb-2 text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                                >
                                    Services
                                </p>

                                <div class="space-y-2">
                                    <div
                                        v-for="service in invoice.services.slice(
                                            0,
                                            3,
                                        )"
                                        :key="service.schedule_services_id"
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-xs font-medium text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    service.service
                                                        ?.service_name ||
                                                    "Service"
                                                }}
                                            </p>

                                            <p
                                                v-if="
                                                    service.schedule
                                                        ?.scheduled_at
                                                "
                                                class="mt-0.5 text-[10px] text-gray-400 dark:text-gray-500"
                                            >
                                                {{
                                                    formatDateTime(
                                                        service.schedule
                                                            .scheduled_at,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <p
                                            class="shrink-0 text-xs font-semibold text-gray-700 dark:text-gray-300"
                                        >
                                            {{ peso(service.price) }}
                                        </p>
                                    </div>

                                    <p
                                        v-if="invoice.services.length > 3"
                                        class="pt-1 text-[10px] font-medium text-primary-600"
                                    >
                                        +{{ invoice.services.length - 3 }} more
                                        service{{
                                            invoice.services.length - 3 === 1
                                                ? ""
                                                : "s"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section
                class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6 dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex flex-col gap-2 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-purple-600"
                            >
                                <AppIcon name="activity" class="h-4 w-4" />
                            </div>

                            <p
                                class="text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Transaction History
                            </p>
                        </div>

                        <p
                            class="mt-1 pl-10 text-xs text-gray-400 dark:text-gray-500"
                        >
                            All billing transactions and payment activity
                        </p>
                    </div>

                    <span
                        v-if="invoiceTransactions.length"
                        class="w-fit rounded-full bg-gray-50 px-3 py-1.5 text-[11px] font-semibold text-gray-500 dark:bg-white/5 dark:text-gray-400"
                    >
                        {{ invoiceTransactions.length }} transaction{{
                            invoiceTransactions.length === 1 ? "" : "s"
                        }}
                    </span>
                </div>

                <div
                    v-if="
                        !invoiceTransactions || invoiceTransactions.length === 0
                    "
                    class="py-14 text-center"
                >
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-50 dark:bg-white/5"
                    >
                        <AppIcon name="receipt" class="h-7 w-7 text-gray-300" />
                    </div>

                    <p
                        class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-300"
                    >
                        No transactions yet
                    </p>

                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        Your billing activity will appear here.
                    </p>
                </div>

                <div
                    v-else
                    class="mt-5 divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-100 dark:border-white/10 dark:divide-white/10"
                >
                    <div
                        v-for="transaction in invoiceTransactions"
                        :key="transaction.id"
                        class="group flex items-start gap-3 p-4 transition hover:bg-gray-50/70 sm:gap-4 sm:p-5"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            :class="transactionIconClasses(transaction.type)"
                        >
                            <AppIcon
                                :name="transactionIcon(transaction.type)"
                                class="h-5 w-5"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="text-sm font-semibold text-gray-800 dark:text-white"
                                        >
                                            {{ transaction.label }}
                                        </p>

                                        <span
                                            v-if="transaction.status"
                                            class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                                            :class="
                                                transactionStatusClasses(
                                                    transaction.status,
                                                )
                                            "
                                        >
                                            {{
                                                formatStatus(transaction.status)
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-400 dark:text-gray-500"
                                    >
                                        <span
                                            v-if="
                                                transaction.reference &&
                                                transaction.type !== 'invoice'
                                            "
                                            class="truncate"
                                        >
                                            Ref:
                                            {{ transaction.reference }}
                                        </span>

                                        <span
                                            v-if="transaction.maskedCardNumber"
                                        >
                                            Account:
                                            {{ transaction.maskedCardNumber }}
                                        </span>

                                        <span>
                                            {{ transaction.date }}
                                        </span>

                                        <button
                                            v-if="transaction.receiptNo"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-2.5 py-1 text-[10px] font-semibold text-primary-600 transition hover:bg-primary-50 dark:bg-white/5"
                                            @click="
                                                openReceipt(
                                                    transaction.receiptNo,
                                                )
                                            "
                                        >
                                            <AppIcon
                                                name="receipt"
                                                class="h-3 w-3"
                                            />
                                            {{ transaction.receiptNo }}
                                        </button>
                                    </div>

                                    <p
                                        v-if="transaction.reason"
                                        class="mt-2 rounded-lg bg-gray-50 px-2.5 py-1.5 text-[11px] text-gray-500 dark:bg-white/5 dark:text-gray-400"
                                    >
                                        {{ transaction.reason }}
                                    </p>
                                </div>

                                <div
                                    class="flex shrink-0 items-center justify-between gap-4 sm:block sm:text-right"
                                >
                                    <p
                                        class="text-sm font-bold"
                                        :class="
                                            transactionAmountColor(
                                                transaction.type,
                                            )
                                        "
                                    >
                                        {{ transactionSign(transaction.type)
                                        }}{{ peso(transaction.amount) }}
                                    </p>

                                    <p
                                        class="mt-1 text-[10px] text-gray-400 dark:text-gray-500"
                                    >
                                        {{
                                            transaction.type === "invoice"
                                                ? "Billed"
                                                : transaction.type === "payment"
                                                  ? "Paid"
                                                  : "Returned"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <Transition name="modal">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="closeModal"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="bg-gradient-to-br from-emerald-500 to-emerald-600 px-6 py-5 text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"
                            >
                                <AppIcon
                                    name="arrow-down-circle"
                                    class="h-5 w-5"
                                />
                            </div>

                            <button
                                @click="closeModal"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white"
                            >
                                <AppIcon name="x" class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="mt-4 text-lg font-bold">Request Refund</p>

                        <p class="mt-1 text-xs leading-5 text-white/75">
                            Your available refundable balance will be sent using
                            your selected method.
                        </p>
                    </div>

                    <div class="space-y-5 p-6">
                        <div
                            class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-wide text-emerald-600"
                            >
                                Refund Amount
                            </p>

                            <p class="mt-1 text-3xl font-bold text-emerald-700">
                                {{ peso(advanceBalance) }}
                            </p>

                            <div
                                class="mt-3 flex items-start gap-2 border-t border-emerald-100 pt-3"
                            >
                                <AppIcon
                                    name="info"
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500"
                                />

                                <p
                                    class="text-[11px] leading-4 text-emerald-700/80"
                                >
                                    {{ refundReason }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                            >
                                Refund Method
                            </label>

                            <div class="relative mt-1.5">
                                <select
                                    v-model="form.method"
                                    class="w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-3 pr-10 text-sm text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:bg-secondary dark:border-white/10 dark:text-white"
                                >
                                    <option>GCash</option>
                                    <option>Bank Transfer</option>
                                    <option>Cash Pickup</option>
                                </select>

                                <AppIcon
                                    name="chevron-down"
                                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                                />
                            </div>
                        </div>

                        <BaseInput
                            v-model="form.accountDetails"
                            :label="
                                form.method === 'Cash Pickup'
                                    ? 'Authorized Pickup Name'
                                    : form.method === 'GCash'
                                      ? 'GCash Number'
                                      : 'Bank Account Details'
                            "
                            :placeholder="
                                form.method === 'Cash Pickup'
                                    ? 'e.g. Bunny Wawa'
                                    : form.method === 'GCash'
                                      ? 'e.g. 0917 123 4567'
                                      : 'e.g. BDO – 1234 5678 9012'
                            "
                        />

                        <div
                            v-if="formError"
                            class="flex items-start gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-600 dark:bg-rose-500/10"
                        >
                            <AppIcon
                                name="alert-circle"
                                class="mt-0.5 h-4 w-4 shrink-0"
                            />
                            <span>{{ formError }}</span>
                        </div>

                        <div class="flex gap-2.5 pt-1">
                            <button
                                @click="closeModal"
                                class="flex-1 rounded-full border border-gray-200 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300"
                            >
                                Cancel
                            </button>

                            <button
                                @click="submit"
                                :disabled="isRefunding"
                                class="flex flex-1 items-center justify-center gap-2 rounded-full bg-emerald-500 py-3 text-sm font-semibold text-white shadow-sm shadow-emerald-500/20 transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span
                                    v-if="isRefunding"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                                />
                                {{
                                    isRefunding
                                        ? "Submitting..."
                                        : "Confirm Refund"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div
                v-if="showPaymentModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="closePaymentModal"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="bg-gradient-to-br from-primary-600 to-primary-700 px-6 py-5 text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"
                            >
                                <AppIcon name="credit-card" class="h-5 w-5" />
                            </div>

                            <button
                                @click="closePaymentModal"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white"
                            >
                                <AppIcon name="x" class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="mt-4 text-lg font-bold">Pay Balance</p>

                        <p class="mt-1 text-xs leading-5 text-white/75">
                            Make a payment toward the outstanding balance across
                            all invoices.
                        </p>
                    </div>

                    <div class="space-y-5 p-6">
                        <div
                            class="rounded-2xl border border-rose-100 bg-rose-50 p-4 dark:bg-rose-500/10"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-wide text-rose-500"
                                >
                                    Balance Due
                                </p>

                                <AppIcon
                                    name="alert-circle"
                                    class="h-4 w-4 text-rose-400"
                                />
                            </div>

                            <p class="mt-1 text-3xl font-bold text-rose-600">
                                {{ peso(currentBalance) }}
                            </p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label
                                    class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                >
                                    Payment Amount
                                </label>

                                <button
                                    @click="payAmount = currentBalance"
                                    class="text-[11px] font-semibold text-primary-600 transition hover:text-primary-700"
                                >
                                    Pay full balance
                                </button>
                            </div>

                            <BaseInput
                                v-model.number="payAmount"
                                mode="number"
                                min="0"
                                :max="String(currentBalance)"
                                class-name="mt-1.5"
                            >
                                <template #prefix>₱</template>
                            </BaseInput>

                            <div
                                class="mt-2 flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500"
                            >
                                <span>Maximum payment</span>
                                <span
                                    class="font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ peso(currentBalance) }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label
                                class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                            >
                                Payment Method
                            </label>

                            <div class="relative mt-1.5">
                                <select
                                    v-model="paymentForm.method"
                                    class="w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-3 pr-10 text-sm text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:bg-secondary dark:border-white/10 dark:text-white"
                                >
                                    <option>GCash</option>
                                    <option>Bank Transfer</option>
                                </select>

                                <AppIcon
                                    name="chevron-down"
                                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                                />
                            </div>
                        </div>

                        <BaseInput
                            v-model="paymentForm.accountDetails"
                            :label="
                                paymentForm.method === 'GCash'
                                    ? 'GCash Number'
                                    : 'Bank Account Details'
                            "
                            :placeholder="
                                paymentForm.method === 'GCash'
                                    ? 'e.g. 0917 123 4567'
                                    : 'e.g. 1234 5678 9012'
                            "
                        />

                        <div
                            v-if="paymentError"
                            class="flex items-start gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-600 dark:bg-rose-500/10"
                        >
                            <AppIcon
                                name="alert-circle"
                                class="mt-0.5 h-4 w-4 shrink-0"
                            />
                            <span>{{ paymentError }}</span>
                        </div>

                        <div class="flex gap-2.5 pt-1">
                            <button
                                @click="closePaymentModal"
                                class="flex-1 rounded-full border border-gray-200 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300"
                            >
                                Cancel
                            </button>

                            <button
                                @click="payBalance"
                                :disabled="isPaying"
                                class="flex flex-1 items-center justify-center gap-2 rounded-full bg-primary-600 py-3 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span
                                    v-if="isPaying"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                                />

                                {{
                                    isPaying
                                        ? "Processing..."
                                        : "Confirm Payment"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <PaymentReceipt
            v-if="activeReceipt"
            :receipt="activeReceipt"
            @close="activeReceipt = null"
        />
    </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(12px) scale(0.98);
}

button,
input,
select {
    -webkit-tap-highlight-color: transparent;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    margin: 0;
    -webkit-appearance: none;
}

input[type="number"] {
    appearance: textfield;
}
</style>
