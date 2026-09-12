<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { patientAccessService } from "~/api/patient-access/PatientAccessService.js";
import { refundService } from "~/api/refund/RefundService";
import { paymentService } from "~/api/payment/PaymentService";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { cardPayment } from "~/composables/usePayment";
import { useSubscriptionCheckout } from "~/stores/subscription";
import type { CardDetails } from "~/types/payment";
import EmptyState from "~/components/ui/EmptyState.vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import BalanceInvoiceList from "~/components/sections/portal/BalanceInvoiceList.vue";
import InvoiceAdjustmentModal from "~/components/sections/portal/InvoiceAdjustmentModal.vue";
import BalanceTransactionList from "~/components/sections/portal/BalanceTransactionList.vue";
import BalanceHistoryModal from "~/components/sections/portal/BalanceHistoryModal.vue";
import WithdrawCreditsModal from "~/components/sections/portal/WithdrawCreditsModal.vue";
import PayBalanceModal from "~/components/sections/portal/PayBalanceModal.vue";
import { formatCurrency } from "~/utils/currency";
import {
    formatBillingDateTime as formatDateTime,
    formatBillingStatus as formatStatus,
} from "~/utils/portal-billing";
import { useToast } from "~/composables/useToast";

import type {
    PortalInvoice as InvoiceSummary,
    PortalInvoiceService as InvoiceService,
    PortalTransaction as Transaction,
} from "~/types/portal-billing";

import type { PaymentReceipt as PaymentReceiptData } from "~/types/receipt";

useHead({ title: "Settings" });

definePageMeta({
    layout: "portal",
    middleware: "portal",
});

type RefundMethod = "GCash" | "CREDIT-CARD";

interface RefundRequest {
    id: number;
    reference: string | null;
    amount: number;
    method: string | null;
    reason: string;
    requestedAt: string;
    created_at: string;
    settledAt: string;
    requestedBy: string | null;
    status: "Requested" | "Approved" | "Rejected";
    invoiceCodes: string[];
}

interface LovedOne {
    patient_id: number;
    uuid: string | null;
    full_name: string;
    photo: string | null;
    branch_name: string | null;
    branch_address: string | null;
    status: "Active" | "Discharged" | "On Leave";
    room_label: string;
    room_type: string | null;
    refundable_amount: number;
    pending_withdrawal: number;
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
    invoiceCount?: number;
}

const { success, error } = useToast();
const checkout = useSubscriptionCheckout();

const isLoading = ref(true);
const loadError = ref<string | null>(null);
const noPatients = ref(false);
const rawRecords = ref<any[]>([]);
const lovedOnes = ref<LovedOne[]>([]);
const selectedIndex = ref(0);

const { resolveIndex, syncQuery } = usePatientQuerySelection();

watch(selectedIndex, () =>
    syncQuery(lovedOnes.value[selectedIndex.value]?.uuid),
);

const billing = ref<BillingInfo>({
    roomLabel: "",
    accommodationType: "",
    billingCycle: "",
    contractPrice: 0,
    totalBilled: 0,
    adjustedTotal: 0,
    amountPaid: 0,
    balanceDue: 0,
    invoiceCount: 0,
});

const transactions = ref<Transaction[]>([]);
const rawTransactions = ref<any[]>([]);
const invoices = ref<InvoiceSummary[]>([]);
const voidedInvoices = ref<InvoiceSummary[]>([]);
const residentName = ref("");
const residentStatus = ref<"Active" | "Discharged" | "On Leave">("Active");
const advanceBalance = ref(0);
const pendingWithdrawal = ref(0);

// Credit a withdrawal has claimed is still the family's money until accounting
// releases it, so the card states it rather than reading as nothing left.
const creditOnAccount = computed(
    () => advanceBalance.value + pendingWithdrawal.value,
);
const refundRequests = ref<RefundRequest[]>([]);
const loadingRequests = ref(false);
const openReasonId = ref<number | null>(null);

// Credit that has not been withdrawn is not money on the move, so it is left to
// the refunds dialog rather than listed alongside cash that changed hands.
const invoiceTransactions = computed(() =>
    transactions.value.filter(
        (transaction) =>
            transaction.type === "payment" ||
            (transaction.type === "refund" &&
                (transaction.status ?? "").toLowerCase() !== "credited"),
    ),
);

const RECENT_LIMIT = 5;

// The dialogs page through the full history; the cards only ever preview it.
const PAGE_SIZE = 2;

type TransactionFilter = "all" | "payment" | "refund";

const transactionFilter = ref<TransactionFilter>("all");

const transactionFilters: { value: TransactionFilter; label: string }[] = [
    { value: "all", label: "All" },
    { value: "payment", label: "Payments" },
    { value: "refund", label: "Withdrawals" },
];

const showRefunds = ref(false);

/*
  The refunds dialog looks at the credit itself rather than at the withdrawal
  that may have claimed it, so every row keeps the bill it came off and the
  credit note that released it. Its amount is always the credit, which is why
  the status is stated as credited whatever the withdrawal is doing.
*/
const refundTransactions = computed(() =>
    rawTransactions.value
        .filter((entry: any) => entry.type === "refund")
        .map((entry: any) => ({
            id: entry.id,
            invoiceCode: entry.invoice_codes?.[0] ?? "",
            invoiceCodes: entry.invoice_codes ?? [],
            type: "refund" as const,
            label: "Credit issued",
            reference: entry.refund_code ?? undefined,
            date: formatDateTime(entry.created_at),
            amount: Number(entry.amount ?? 0),
            method: entry.refund_method ?? undefined,
            status: "credited",
            reason: entry.reason ?? undefined,
            maskedCardNumber: null,
            receiptNo: null,
        })),
);

// Says where the overpayment came from in the same terms as the summary above:
// the bill was lowered after it had already been paid, and the difference is
// what each row below returned.
const refundExplanation = computed(() => {
    const billed = peso(totalInvoiceAmount.value);
    const adjusted = peso(totalAdjustedAmount.value);
    const paid = peso(totalPaidAmount.value);

    if (totalAdjustedAmount.value < totalInvoiceAmount.value) {
        return `Billed ${billed}, lowered to ${adjusted}, and ${paid} had been paid â€” the difference came back as credit.`;
    }

    return `${paid} was paid against ${adjusted} billed â€” the difference came back as credit.`;
});

const refundBreakdown = computed(() =>
    [
        { label: "Billed", value: peso(totalInvoiceAmount.value) },
        totalAdjustedAmount.value !== totalInvoiceAmount.value
            ? {
                  label: "Lowered to",
                  value: peso(totalAdjustedAmount.value),
              }
            : null,
        { label: "Paid", value: peso(totalPaidAmount.value) },
        {
            label: "Returned as credit",
            value: peso(totalRefundedAmount.value),
            highlight: true,
        },
    ].filter((row): row is { label: string; value: string; highlight?: boolean } => !!row),
);

const filteredTransactions = computed(() =>
    transactionFilter.value === "all"
        ? invoiceTransactions.value
        : invoiceTransactions.value.filter(
              (transaction) => transaction.type === transactionFilter.value,
          ),
);

const listedInvoices = computed(() =>
    [...invoices.value, ...voidedInvoices.value].sort(
        (a, b) =>
            new Date(b.created_at || 0).getTime() -
            new Date(a.created_at || 0).getTime(),
    ),
);

const recentInvoices = computed(() =>
    listedInvoices.value.slice(0, RECENT_LIMIT),
);

const recentTransactions = computed(() =>
    filteredTransactions.value.slice(0, RECENT_LIMIT),
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

// Every credit counts from the moment it is raised, whatever has since become
// of the withdrawal: the money already came off the invoices it was drawn from,
// so leaving it out would report the bill as overpaid twice.
const totalRefundedAmount = computed(() =>
    transactions.value
        .filter((transaction) => transaction.type === "refund")
        .reduce((total, transaction) => total + transaction.amount, 0),
);

const withdrawnAmount = computed(() =>
    transactions.value
        .filter(
            (transaction) =>
                transaction.type === "refund" &&
                ["completed", "approved"].includes(
                    (transaction.status ?? "").toLowerCase(),
                ),
        )
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

function round2(value: number) {
    return Math.round((Number(value) || 0) * 100) / 100;
}

// Summed per invoice, never netted across them. Subtracting all payments from
// all charges let credit sitting on a settled invoice cancel out a debt on an
// unpaid one, so a family that still owed money was shown a zero balance.
const currentBalance = computed(() =>
    round2(
        invoices.value.reduce(
            (total, invoice) =>
                total + Math.max(0, Number(invoice.balance_due || 0)),
            0,
        ),
    ),
);

const hasBalanceDue = computed(() => currentBalance.value > 0);

const unpaidInvoiceCount = computed(
    () =>
        invoices.value.filter((invoice) => Number(invoice.balance_due || 0) > 0)
            .length,
);
const isDischarged = computed(() => residentStatus.value === "Discharged");

const refundableAmount = computed(
    () =>
        Number(lovedOnes.value[selectedIndex.value]?.refundable_amount ?? 0) ||
        0,
);

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

// Paying more than the bill is not 140% progress against it. Once an
// adjustment lowers the total below what was already paid, the excess stops
// counting here and sits on the Credit card instead.
const appliedToBills = computed(() =>
    round2(Math.min(netPaidAmount.value, totalAdjustedAmount.value)),
);

const overpaidAmount = computed(() =>
    round2(Math.max(0, netPaidAmount.value - totalAdjustedAmount.value)),
);

const paymentProgress = computed(() => {
    if (totalAdjustedAmount.value <= 0) return 0;

    if (currentBalance.value <= 0) return 100;

    return Math.min(
        99,
        Math.floor((appliedToBills.value / totalAdjustedAmount.value) * 100),
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
        active: "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20",
        discharged:
            "bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
        "on leave":
            "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20",
    };

    return (
        map[status?.toLowerCase()] ??
        "bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400"
    );
}

// A refund is only ever requested, completed or declined. The page used to
// invent Approved and Released stages that the record never had.
function mapRefundRequestStatus(status?: string): RefundRequest["status"] {
    switch ((status || "").toLowerCase()) {
        case "completed":
        case "approved":
            return "Approved";
        case "rejected":
            return "Rejected";
        default:
            return "Requested";
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

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
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
            description: invoice.description || "No line items recorded",
            status: invoice.status,
            total: Number(invoice.total ?? 0),
            adjusted_total: Number(
                invoice.adjusted_total ?? invoice.total ?? 0,
            ),
            amount_paid: Number(invoice.amount_paid ?? 0),
            balance_due: Number(invoice.balance_due ?? 0),
            refund_status: invoice.refund_status ?? "none",
            void_reason: invoice.void_reason ?? null,
            voided_at: invoice.voided_at ?? null,
            created_at: invoice.created_at,
            accommodation_type: contract.accommodation_type ?? null,
            billing_cycle: contract.billing_cycle ?? null,
            accommodation_status: source.accommodation_status ?? null,
            source_type:
                source.type ?? (services.length ? services[0]?.type : null),
            services,
            adjustments: adjustmentsList,
        });
    }

    return result;
}

// The API returns the statement already grouped: one entry per receipt and
// one per refund, however many invoices each of them touched.
function mapTransactions(list: any[]): Transaction[] {
    return (list || []).map((entry: any) => {
        const amount = Number(entry.amount ?? 0);
        const isRefund = entry.type === "refund";
        const isCredit = entry.payment_method === "CREDIT";

        // A withdrawal draws on the account, not on a bill. The invoice and the
        // credit note behind it describe the credit it claimed, so they belong
        // to that row rather than to this one.
        const isWithdrawal = isRefund && !!entry.refund_code;

        return {
            id: entry.id,
            invoiceCode: isWithdrawal ? "" : (entry.invoice_codes?.[0] ?? ""),
            invoiceCodes: isWithdrawal ? [] : (entry.invoice_codes ?? []),
            type: isRefund ? "refund" : "payment",
            label: isRefund
                ? entry.refund_code
                    ? `Withdrawal Â· ${methodLabel(entry.refund_method)}`
                    : "Credit issued"
                : isCredit
                  ? amount < 0
                      ? "Credit moved to another invoice"
                      : "Credit applied"
                  : `Payment Â· ${methodLabel(entry.payment_method)}`,
            reference: entry.reference_id ?? entry.refund_code ?? undefined,
            date: formatDateTime(entry.created_at),
            amount,
            method: entry.payment_method ?? entry.refund_method ?? undefined,
            status: entry.status,
            reason: isWithdrawal
                ? (entry.declined_reason ?? undefined)
                : (entry.declined_reason ?? entry.reason ?? undefined),
            maskedCardNumber: entry.masked_account_detail ?? null,
            receiptNo: entry.payment_code ?? null,
        };
    });
}

function refundRows(request: RefundRequest) {
    return [
        { label: "Date of request", value: request.created_at },
        { label: "Requested by", value: request.requestedBy },
        { label: "Electronic method", value: methodLabel(request.method, "") },
        { label: "Amount", value: peso(request.amount) },
    ].filter((row) => !!row.value);
}

function refundStatusColor(status: RefundRequest["status"]) {
    switch (status) {
        case "Approved":
            return "text-primary-700 dark:text-primary-300";

        case "Rejected":
            return "text-danger";

        default:
            return "text-amber-600 dark:text-amber-400";
    }
}

function toggleReason(id: number) {
    openReasonId.value = openReasonId.value === id ? null : id;
}

const collapsedRequests = ref<number[]>([]);

function isCollapsed(id: number) {
    return collapsedRequests.value.includes(id);
}

function toggleCollapsed(id: number) {
    collapsedRequests.value = isCollapsed(id)
        ? collapsedRequests.value.filter((item) => item !== id)
        : [...collapsedRequests.value, id];
}

function mapRefundRequest(refund: any): RefundRequest {
    return {
        id: Number(refund.refund_id),
        reference: refund.refund_code ?? null,
        amount: Number(refund.amount ?? 0),
        method: refund.refund_method ?? null,
        reason: refund.declined_reason || "",
        requestedAt: refund.requested_at ?? "",
        created_at: formatDateTime(refund.requested_at),
        settledAt: formatDateTime(refund.settled_at),
        requestedBy: refund.requested_by?.name || null,
        status: mapRefundRequestStatus(refund.status),
        invoiceCodes: refund.invoice_codes ?? [],
    };
}

function mapPatientRecord(item: any): LovedOne {
    const patient = item.patient;
    const ctx = item.location_context ?? {};
    const org = item.organization ?? {};

    return {
        patient_id: patient.patient_id,
        uuid: patient.uuid ?? null,
        full_name: patient.full_name,
        photo: patient.photo ?? null,
        branch_name: org.name ?? null,
        branch_address: org.full_address ?? null,
        status: mapResidentStatus(ctx.status),
        room_label: ctx.room?.room_no ? `Room ${ctx.room.room_no}` : "",
        room_type: ctx.room?.room_type ?? null,
        refundable_amount: Number(item.patient_refundable ?? 0),
        pending_withdrawal: Number(item.patient_pending_withdrawal ?? 0),
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

    const voidedList = Array.isArray(item.voided_invoices)
        ? item.voided_invoices
        : [];

    const mappedVoided = mapInvoices(voidedList);

    invoices.value = mappedInvoices;

    // Kept apart from the billing figures â€” a voided invoice asks for nothing â€”
    // but still listed, so a family can see it was cancelled and why.
    voidedInvoices.value = mappedVoided;

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
    pendingWithdrawal.value = Number(item.patient_pending_withdrawal ?? 0);

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
        // Counts every invoice: money paid on one that was later voided still
        // left the family's pocket.
        amountPaid: [...mappedInvoices, ...mappedVoided].reduce(
            (sum, invoice) => sum + invoice.amount_paid,
            0,
        ),
        balanceDue: currentBalance.value,
        invoiceCount: mappedInvoices.length,
    };

    rawTransactions.value = item.transactions ?? [];
    transactions.value = mapTransactions(item.transactions);

    fetchRefundRequests();
}

// Read on its own so the panel does not depend on the invoice list, and so
// "Refresh status" can pull just this without reloading the page.
async function fetchRefundRequests() {
    const patientId = selectedLovedOne.value?.patient_id;

    if (!patientId) {
        refundRequests.value = [];

        return;
    }

    loadingRequests.value = true;

    try {
        const res = await refundService.requests({ patient_id: patientId });
        const data = res?.data ?? res;

        // Only what the family is still waiting on. Once accounting settles a
        // request it belongs in the transaction list, not in this panel.
        refundRequests.value = (data?.requests ?? [])
            .map(mapRefundRequest)
            .filter((request: RefundRequest) => request.status === "Requested");
    } catch (err: any) {
        error(
            err?.data?.message ??
                err?.message ??
                "We couldn't load your withdrawal requests.",
        );
    } finally {
        loadingRequests.value = false;
    }
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

            selectedIndex.value = resolveIndex(lovedOnes.value);
            updateBillingFromRecord(records[selectedIndex.value]);
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

const showAllInvoices = ref(false);
const showAllTransactions = ref(false);

const adjustmentInvoice = ref<InvoiceSummary | null>(null);

function openAdjustments(invoice: InvoiceSummary) {
    adjustmentInvoice.value = invoice;
}
const isLoadingLedger = ref(false);

async function openAllInvoices() {
    showAllInvoices.value = true;

    await refreshLedger();
}

async function openAllTransactions() {
    showAllTransactions.value = true;

    await refreshLedger();
}

async function refreshLedger() {
    const patientId = lovedOnes.value[selectedIndex.value]?.patient_id;

    if (!patientId) return;

    isLoadingLedger.value = true;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "financials",
            patient_id: patientId,
        });

        const record = res?.data;

        if (!record) return;

        const merged = {
            ...(rawRecords.value[selectedIndex.value] ?? {}),
            ...record,
        };

        rawRecords.value[selectedIndex.value] = merged;

        updateBillingFromRecord(merged);
    } catch (err: any) {
        error(err?.message || "Unable to load the full billing history.");
    } finally {
        isLoadingLedger.value = false;
    }
}

// Only one refund can be in flight: a second request while accounting is
// still deciding would double-claim the same credit.
const openRefundRequest = computed(
    () =>
        refundRequests.value.find(
            (request) => request.status === "Requested",
        ) ?? null,
);

const showModal = ref(false);

const form = ref({
    method: "GCash" as RefundMethod,
    accountDetails: "",
    amount: null as number | null,
});

const formError = ref("");
const isRefunding = ref(false);

function openModal() {
    if (openRefundRequest.value) return;

    form.value = {
        method: "GCash",
        accountDetails: "",
        amount: refundableAmount.value,
    };

    formError.value = "";
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submit() {
    const requestedAmount = Number(form.value.amount ?? 0);

    if (requestedAmount < 1) {
        formError.value = "Enter an amount of at least â‚±1.";

        return;
    }

    if (requestedAmount > refundableAmount.value + 0.01) {
        formError.value = `Only ${peso(refundableAmount.value)} is available on this account.`;

        return;
    }

    if (!form.value.accountDetails.trim()) {
        formError.value =
            form.value.method === "GCash"
                ? "Enter the GCash number to receive the credit."
                : "Enter the card number to receive the credit.";

        return;
    }

    const patientId = lovedOnes.value[selectedIndex.value]?.patient_id;

    if (!patientId) {
        formError.value =
            "Unable to determine which loved one this withdrawal is for.";
        return;
    }

    isRefunding.value = true;
    formError.value = "";

    try {
        const requested = Number(form.value.amount ?? 0);

        const res = await refundService.claim({
            patient_id: patientId,
            method: form.value.method,
            account_details: form.value.accountDetails,
            ...(requested > 0 ? { amount: requested } : {}),
        });

        success(res?.message || "Your withdrawal request has been sent.");
        showModal.value = false;

        // The request comes back on the response, so the panel shows it without
        // waiting on another round trip.
        const created = res?.data?.request ?? res?.request;

        if (created) {
            refundRequests.value = [
                mapRefundRequest(created),
                ...refundRequests.value,
            ];
        } else {
            await fetchRefundRequests();
        }
    } catch (err: any) {
        formError.value = err?.message || "Failed to submit the withdrawal request.";
    } finally {
        isRefunding.value = false;
    }
}

const showPaymentModal = ref(false);
const payAmount = ref(0);
const isPaying = ref(false);
const activeReceipt = ref<PaymentReceiptData | null>(null);

const card = ref<CardDetails>({
    number: "4000000000002503",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

function openPaymentModal() {
    if (!hasBalanceDue.value) return;
    checkout.payment_method = "CREDIT-CARD";
    payAmount.value = currentBalance.value;
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

    const amount = round2(Number(payAmount.value) || 0);

    if (amount <= 0) {
        error("Enter an amount greater than â‚±0.");
        return;
    }

    if (amount > currentBalance.value) {
        error(
            `Amount can't exceed your balance of ${peso(currentBalance.value)}.`,
        );
        return;
    }

    payAmount.value = amount;

    isPaying.value = true;

    try {
        await cardPayment({
            card: card.value,
            amount,

            onClose: () => {
                isPaying.value = false;
            },

            createPayment: ({ token_id, authentication_id }) =>
                paymentService.pay({
                    patient_id: patientId,
                    amount,
                    token_id,
                    authentication_id,
                }),

            onSuccess: async (res: any) => {
                const receipt: PaymentReceiptData | null = res?.receipt ?? null;

                showPaymentModal.value = false;

                if (!receipt) {
                    success(res?.message || "Payment recorded successfully.");

                    await loadPatientData();

                    return;
                }

                applyReceiptLocally(receipt);

                activeReceipt.value = receipt;

                success(`Payment recorded. Receipt ${receipt.payment_code}.`);
            },
        });
    } catch (err: any) {
        error(err?.message || "Failed to process payment.");
    } finally {
        isPaying.value = false;
    }
}

function applyReceiptLocally(receipt: PaymentReceiptData) {
    const issuedAt = formatDateTime(receipt.issued_at ?? undefined);

    let applied = 0;

    const codes: string[] = [];

    for (const line of receipt.lines) {
        const invoice = invoices.value.find(
            (item) => item.invoice_code === line.invoice_code,
        );

        if (invoice) {
            const remaining = Math.max(
                0,
                Number(invoice.balance_due || 0) - line.amount_applied,
            );

            invoice.amount_paid =
                Number(invoice.amount_paid) + line.amount_applied;
            invoice.balance_due = remaining;
            invoice.status = remaining <= 0 ? "paid" : "partial";
        }

        applied += line.amount_applied;
        codes.push(line.invoice_code);
    }

    // One line for the receipt, matching how the ledger groups the payments it
    // loads from the API.
    const entry: Transaction = {
        id: `payment-${receipt.lines[0]?.payment_id ?? receipt.payment_code}-in`,
        invoiceId: receipt.lines[0]?.invoice_id ?? 0,
        invoiceCode: codes[0] ?? "",
        invoiceCodes: codes,
        type: "payment",
        label: `Payment Â· ${methodLabel(receipt.payment.method)}`,
        reference: receipt.lines[0]?.payment_reference ?? receipt.payment_code,
        date: issuedAt,
        amount: round2(applied),
        method: receipt.payment.method ?? undefined,
        status: "completed",
        maskedCardNumber: receipt.payment.masked_account,
        receiptNo: receipt.payment_code,
    };

    transactions.value = [entry, ...transactions.value];

    billing.value = {
        ...billing.value,
        amountPaid: totalPaidAmount.value,
        balanceDue: currentBalance.value,
    };
}

const loadingReceiptNo = ref<string | null>(null);

async function openReceipt(receiptNo?: string | null) {
    if (!receiptNo || loadingReceiptNo.value) return;

    loadingReceiptNo.value = receiptNo;

    try {
        const res = await paymentService.receipt({ payment_code: receiptNo });

        activeReceipt.value = res?.data ?? res ?? null;
    } catch (err: any) {
        error(err?.message || "Unable to load that receipt.");
    } finally {
        loadingReceiptNo.value = null;
    }
}
</script>

<template>
    <div class="min-h-full bg-slate-50/60 px-5 pb-16 pt-5 dark:bg-surface">
        <div class="h-full">
            <div v-if="isLoading" class="space-y-5">
                <div
                    class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.6fr)]"
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
                <section v-if="refundRequests.length" class="space-y-4">
                    <article
                        v-for="request in refundRequests"
                        :key="request.id"
                        class="overflow-hidden rounded-md border border-primary-500 bg-white dark:border-primary-500/50 dark:bg-secondary"
                    >
                        <div
                            class="flex items-center justify-between gap-3 bg-primary px-3 py-1.5 text-white"
                        >
                            <button
                                type="button"
                                :disabled="loadingRequests"
                                class="inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide transition hover:text-white/80 disabled:cursor-wait disabled:opacity-70"
                                @click="fetchRefundRequests"
                            >
                                <AppIcon
                                    name="refresh-cw"
                                    class="h-3 w-3"
                                    :class="
                                        loadingRequests ? 'animate-spin' : ''
                                    "
                                />
                                Refresh status
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold transition hover:text-white/80"
                                @click="toggleCollapsed(request.id)"
                            >
                                Withdrawal requests
                                <AppIcon
                                    :name="
                                        isCollapsed(request.id)
                                            ? 'chevron-down'
                                            : 'chevron-up'
                                    "
                                    class="h-3.5 w-3.5"
                                />
                            </button>
                        </div>

                        <dl
                            v-show="!isCollapsed(request.id)"
                            class="text-[11px]"
                        >
                            <div
                                v-for="(row, index) in refundRows(request)"
                                :key="row.label"
                                class="flex items-start justify-between gap-3 px-3 py-1.5"
                                :class="
                                    index % 2
                                        ? 'bg-white dark:bg-secondary'
                                        : 'bg-primary-50/60 dark:bg-white/5'
                                "
                            >
                                <dt
                                    class="shrink-0 text-muted dark:text-gray-400"
                                >
                                    {{ row.label }}
                                </dt>

                                <dd
                                    class="min-w-0 truncate text-right font-medium text-secondary dark:text-gray-100"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>

                            <div
                                class="flex items-start justify-between gap-3 px-3 py-1.5"
                                :class="
                                    refundRows(request).length % 2
                                        ? 'bg-white dark:bg-secondary'
                                        : 'bg-primary-50/60 dark:bg-white/5'
                                "
                            >
                                <dt
                                    class="shrink-0 text-muted dark:text-gray-400"
                                >
                                    Status
                                </dt>

                                <dd
                                    class="min-w-0 text-right font-medium"
                                    :class="refundStatusColor(request.status)"
                                >
                                    {{ request.status }}

                                    <button
                                        v-if="
                                            request.status === 'Rejected' &&
                                            request.reason
                                        "
                                        type="button"
                                        class="underline underline-offset-2 hover:no-underline"
                                        @click="toggleReason(request.id)"
                                    >
                                        ({{
                                            openReasonId === request.id
                                                ? "Hide reason"
                                                : "Find out reason for refusal"
                                        }})
                                    </button>
                                </dd>
                            </div>
                        </dl>

                        <p
                            v-if="
                                !isCollapsed(request.id) &&
                                openReasonId === request.id &&
                                request.reason
                            "
                            class="border-t border-danger/20 bg-danger/5 px-3 py-2 text-[11px] leading-4 text-danger"
                        >
                            {{ request.reason }}
                        </p>
                    </article>
                </section>

                <!-- <div
                v-if="selectedLovedOne"
                class="inline-flex items-center gap-3 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
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
                    class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.6fr)]"
                >
                    <section
                        class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:bg-secondary dark:border-white/10"
                    >
                        <div
                            class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-white/10"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                                        >
                                            <AppIcon
                                                name="users"
                                                class="h-4 w-4"
                                            />
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
                                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white hover:text-gray-700 hover:shadow-sm dark:text-gray-500 dark:hover:bg-secondary dark:hover:text-gray-400 dark:hover:bg-white/10"
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
                                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white hover:text-gray-700 hover:shadow-sm dark:text-gray-500 dark:hover:bg-secondary dark:hover:text-gray-400 dark:hover:bg-white/10"
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
                            <div
                                v-if="lovedOnes.length"
                                class="overflow-hidden"
                            >
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
                                            class="flex flex-col items-center rounded-2xl border border-gray-100 bg-gradient-to-br from-gray-50 to-white p-6 text-center dark:border-white/10 dark:from-white/5 dark:to-white/5"
                                        >
                                            <img
                                                v-if="lo.photo"
                                                :src="lo.photo"
                                                :alt="lo.full_name"
                                                class="h-20 w-20 rounded-full object-cover object-top"
                                            />

                                            <div
                                                v-else
                                                class="flex h-20 w-20 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                                            >
                                                {{
                                                    lo.full_name
                                                        .split(" ")
                                                        .map((n) => n[0])
                                                        .slice(0, 2)
                                                        .join("")
                                                }}
                                            </div>

                                            <p
                                                class="mt-3 text-lg font-bold text-gray-900 dark:text-white"
                                            >
                                                {{ lo.full_name }}
                                            </p>

                                            <p
                                                v-if="lo.branch_name"
                                                class="mt-1 text-xs font-medium text-primary-600 dark:text-primary-300"
                                            >
                                                {{ lo.branch_name }}
                                            </p>

                                            <p
                                                v-if="lo.branch_address"
                                                class="text-xs text-gray-400 dark:text-gray-500"
                                            >
                                                {{ lo.branch_address }}
                                            </p>

                                            <div
                                                class="mt-2 flex flex-wrap items-center justify-center gap-2"
                                            >
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                                    :class="
                                                        lovedOneStatusClasses(
                                                            lo.status,
                                                        )
                                                    "
                                                >
                                                    {{ lo.status }}
                                                </span>

                                                <span
                                                    v-if="lo.room_label"
                                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300"
                                                >
                                                    {{ lo.room_label
                                                    }}<template
                                                        v-if="lo.room_type"
                                                    >
                                                        Â·
                                                        {{
                                                            lo.room_type
                                                        }}</template
                                                    >
                                                </span>

                                                <span
                                                    v-else-if="
                                                        lo.location_type ===
                                                        'homecare'
                                                    "
                                                    class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-600 dark:bg-blue-500/10 dark:text-blue-300"
                                                >
                                                    Homecare
                                                </span>
                                            </div>

                                            <NuxtLink
                                                :to="`/portal/loved-ones?patient=${lo.uuid ?? ''}`"
                                                class="mt-5 w-full rounded-full border border-primary-600 py-2 text-center text-sm font-medium text-primary-600 transition hover:border-primary-500 hover:bg-primary-500 hover:text-white dark:text-primary-300"
                                            >
                                                View Full Profile
                                            </NuxtLink>
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
                                    class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-500 dark:bg-rose-500/15 dark:text-rose-300"
                                >
                                    <AppIcon
                                        name="alert-circle"
                                        class="h-5 w-5"
                                    />
                                </div>

                                <p
                                    class="mt-3 text-sm font-medium text-rose-700 dark:text-rose-300"
                                >
                                    {{ loadError }}
                                </p>

                                <button
                                    @click="loadPatientData"
                                    class="mt-3 rounded-full bg-white px-4 py-2 text-xs font-semibold text-primary-600 shadow-sm ring-1 ring-gray-100 transition hover:bg-gray-50 dark:bg-secondary dark:text-primary-300 dark:ring-white/10 dark:hover:bg-white/5"
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
                                            : 'w-1.5 bg-gray-200 hover:bg-gray-300 dark:bg-white/15 dark:hover:bg-white/25'
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
                                        Your current billing and payment
                                        overview
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
                                        <AppIcon
                                            name="file-text"
                                            class="h-5 w-5"
                                        />
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
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-primary-500/10 dark:text-blue-300"
                                    >
                                        <AppIcon
                                            name="receipt"
                                            class="h-5 w-5"
                                        />
                                    </div>

                                    <span
                                        v-if="
                                            totalAdjustedAmount !==
                                            totalInvoiceAmount
                                        "
                                        class="rounded-full bg-amber-50 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
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
                                        totalAdjustedAmount !==
                                        totalInvoiceAmount
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
                                :class="
                                    hasBalanceDue
                                        ? 'bg-rose-50/30 dark:bg-rose-500/10'
                                        : ''
                                "
                            >
                                <div class="flex items-start justify-between">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl"
                                        :class="
                                            hasBalanceDue
                                                ? 'bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-300'
                                                : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
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
                                                ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'
                                                : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                                        "
                                    >
                                        {{
                                            hasBalanceDue
                                                ? "Balance Due"
                                                : "Paid"
                                        }}
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
                                            ? 'text-rose-600 dark:text-rose-300'
                                            : 'text-emerald-600 dark:text-emerald-300'
                                    "
                                >
                                    {{ peso(currentBalance) }}
                                </p>

                                <!-- Says what the figure is made of, so a family
                                 holding credit on one invoice while owing on
                                 another can see why the two do not cancel. -->
                                <p
                                    v-if="unpaidInvoiceCount"
                                    class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    Across {{ unpaidInvoiceCount }} unpaid
                                    {{
                                        unpaidInvoiceCount === 1
                                            ? "invoice"
                                            : "invoices"
                                    }}
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

                                <p
                                    v-else
                                    class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-300"
                                >
                                    No outstanding balance
                                </p>
                            </div>

                            <div class="bg-white p-5 sm:p-6 dark:bg-secondary">
                                <div class="flex items-start justify-between">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                                    >
                                        <AppIcon
                                            name="arrow-down-circle"
                                            class="h-5 w-5"
                                        />
                                    </div>

                                    <span
                                        v-if="creditOnAccount > 0"
                                        class="rounded-full px-2 py-1 text-[9px] font-bold uppercase tracking-wide"
                                        :class="
                                            advanceBalance > 0
                                                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'
                                        "
                                    >
                                        {{
                                            advanceBalance > 0
                                                ? "Available"
                                                : "Being withdrawn"
                                        }}
                                    </span>
                                </div>

                                <p
                                    class="mt-5 text-xs font-medium text-gray-400 dark:text-gray-500"
                                >
                                    Credit
                                </p>

                                <p
                                    class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-300"
                                >
                                    {{ peso(creditOnAccount) }}
                                </p>

                                <p
                                    v-if="pendingWithdrawal > 0"
                                    class="mt-1 text-[11px] text-amber-600 dark:text-amber-300"
                                >
                                    {{ peso(pendingWithdrawal) }} being
                                    withdrawn
                                </p>

                                <p
                                    v-else-if="withdrawnAmount > 0"
                                    class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    {{ peso(withdrawnAmount) }} already
                                    withdrawn
                                </p>

                                <p
                                    v-if="openRefundRequest"
                                    class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-[11px] leading-4 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    A withdrawal request is already being reviewed.
                                    You can ask for another once it is settled.
                                </p>

                                <button
                                    v-else-if="advanceBalance > 0"
                                    @click="openModal"
                                    class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/15"
                                >
                                    Request Withdraw Credits
                                    <AppIcon
                                        name="arrow-right"
                                        class="h-3.5 w-3.5"
                                    />
                                </button>

                                <p
                                    v-else-if="creditOnAccount <= 0"
                                    class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    No credit on this account
                                </p>
                            </div>
                        </div>

                        <div
                            class="border-t border-gray-100 p-5 sm:p-6 dark:border-white/10"
                        >
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                <div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-xs font-medium text-gray-400 dark:text-gray-500"
                                        >
                                            Amount Paid
                                        </p>

                                        <AppIcon
                                            name="check-circle"
                                            class="h-4 w-4 text-emerald-500 dark:text-emerald-300"
                                        />
                                    </div>

                                    <p
                                        class="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-300"
                                    >
                                        {{ peso(totalPaidAmount) }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    :disabled="!refundTransactions.length"
                                    class="rounded-xl text-left transition disabled:cursor-default enabled:hover:opacity-80"
                                    @click="showRefunds = true"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-xs font-medium text-gray-400 dark:text-gray-500"
                                        >
                                            Refunded
                                        </p>

                                        <AppIcon
                                            name="arrow-down-circle"
                                            class="h-4 w-4 text-blue-500 dark:text-blue-300"
                                        />
                                    </div>

                                    <p
                                        class="mt-1 text-lg font-bold text-blue-600 dark:text-blue-300"
                                    >
                                        {{ peso(totalRefundedAmount) }}
                                    </p>

                                    <p
                                        v-if="refundTransactions.length"
                                        class="mt-0.5 text-[11px] font-medium text-primary-600 dark:text-primary-300"
                                    >
                                        View {{ refundTransactions.length }}
                                        refund{{
                                            refundTransactions.length === 1
                                                ? ""
                                                : "s"
                                        }}
                                    </p>
                                </button>

                                <div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
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
                                        {{ peso(appliedToBills) }} of
                                        {{ peso(totalAdjustedAmount) }} paid
                                    </p>

                                    <p
                                        v-if="overpaidAmount > 0"
                                        class="mt-0.5 text-[11px] text-emerald-600 dark:text-emerald-300"
                                    >
                                        {{ peso(overpaidAmount) }} paid above
                                        the adjusted bill is on your credit
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="grid gap-5 xl:grid-cols-2">
                    <section
                        class="flex flex-col rounded-3xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6 dark:border-white/10 dark:bg-secondary"
                    >
                        <div
                            class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-white/10"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-primary-500/10 dark:text-blue-300"
                                    >
                                        <AppIcon
                                            name="file-text"
                                            class="h-4 w-4"
                                        />
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
                                    Latest billing records for
                                    {{
                                        selectedLovedOne?.full_name ||
                                        "this resident"
                                    }}
                                </p>
                            </div>

                            <span
                                class="shrink-0 rounded-full bg-gray-50 px-3 py-1.5 text-[11px] font-semibold text-gray-500 dark:bg-white/5 dark:text-gray-400"
                            >
                                {{ listedInvoices.length }} invoice{{
                                    listedInvoices.length === 1 ? "" : "s"
                                }}
                            </span>
                        </div>

                        <BalanceInvoiceList
                            :invoices="recentInvoices"
                            class="mt-4 flex-1"
                            @adjustments="openAdjustments"
                        />

                        <button
                            v-if="listedInvoices.length > RECENT_LIMIT"
                            type="button"
                            class="mt-4 w-full rounded-xl border border-gray-200 py-2.5 text-xs font-semibold text-gray-600 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                            @click="openAllInvoices"
                        >
                            View all {{ listedInvoices.length }} invoices
                        </button>
                    </section>

                    <section
                        class="flex flex-col rounded-3xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6 dark:border-white/10 dark:bg-secondary"
                    >
                        <div
                            class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-white/10"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-300"
                                    >
                                        <AppIcon
                                            name="activity"
                                            class="h-4 w-4"
                                        />
                                    </div>

                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-white"
                                    >
                                        Withdrawals &amp; Payments
                                    </p>
                                </div>

                                <p
                                    class="mt-1 pl-10 text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Money paid and credit returned on this
                                    account
                                </p>
                            </div>

                            <div
                                v-if="invoiceTransactions.length"
                                class="flex shrink-0 items-center gap-1"
                            >
                                <button
                                    v-for="option in transactionFilters"
                                    :key="option.value"
                                    type="button"
                                    class="rounded-full px-2.5 py-1.5 text-[11px] font-semibold transition"
                                    :class="
                                        transactionFilter === option.value
                                            ? 'bg-primary text-white'
                                            : 'bg-gray-50 text-gray-500 hover:bg-gray-100 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10'
                                    "
                                    @click="transactionFilter = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>

                        <BalanceTransactionList
                            :transactions="recentTransactions"
                            :loading-receipt-no="loadingReceiptNo"
                            class="mt-4 flex-1"
                            @receipt="openReceipt"
                        />

                        <button
                            v-if="filteredTransactions.length > RECENT_LIMIT"
                            type="button"
                            class="mt-4 w-full rounded-xl border border-gray-200 py-2.5 text-xs font-semibold text-gray-600 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                            @click="openAllTransactions"
                        >
                            View all
                            {{ filteredTransactions.length }} transactions
                        </button>
                    </section>
                </div>
            </div>

            <BalanceHistoryModal
                :open="showAllInvoices"
                title="All invoices"
                :subtitle="`Every bill raised for ${selectedLovedOne?.full_name || 'this resident'}`"
                :items="listedInvoices"
                :page-size="PAGE_SIZE"
                :loading="isLoadingLedger"
                loading-label="Loading invoicesâ€¦"
                empty-label="No invoices yet"
                @close="showAllInvoices = false"
            >
                <template #default="{ items }">
                    <BalanceInvoiceList
                        :invoices="items"
                        @adjustments="openAdjustments"
                    />
                </template>
            </BalanceHistoryModal>

            <BalanceHistoryModal
                :open="showRefunds"
                title="Refunds"
                :subtitle="refundExplanation"
                :items="refundTransactions"
                :page-size="PAGE_SIZE"
                :loading="isLoadingLedger"
                loading-label="Loading refundsâ€¦"
                empty-label="No refunds yet"
                @close="showRefunds = false"
            >
                <template #default="{ items }">
                    <dl
                        class="mb-4 grid grid-cols-2 gap-x-4 gap-y-2 rounded-2xl bg-gray-50 p-4 text-[11px] dark:bg-white/5 sm:grid-cols-4"
                    >
                        <div
                            v-for="row in refundBreakdown"
                            :key="row.label"
                            class="min-w-0"
                        >
                            <dt class="text-gray-400 dark:text-gray-500">
                                {{ row.label }}
                            </dt>

                            <dd
                                class="mt-0.5 truncate text-sm font-bold"
                                :class="
                                    row.highlight
                                        ? 'text-emerald-600 dark:text-emerald-300'
                                        : 'text-gray-800 dark:text-white'
                                "
                            >
                                {{ row.value }}
                            </dd>
                        </div>
                    </dl>

                    <BalanceTransactionList
                        :transactions="items"
                        :loading-receipt-no="loadingReceiptNo"
                        @receipt="openReceipt"
                    />
                </template>
            </BalanceHistoryModal>

            <BalanceHistoryModal
                :open="showAllTransactions"
                title="All withdrawals &amp; payments"
                subtitle="Money paid and credit returned on this account"
                :items="filteredTransactions"
                :page-size="PAGE_SIZE"
                :loading="isLoadingLedger"
                loading-label="Loading transactionsâ€¦"
                empty-label="No transactions yet"
                :filters="transactionFilters"
                :filter="transactionFilter"
                @update:filter="transactionFilter = $event as TransactionFilter"
                @close="showAllTransactions = false"
            >
                <template #default="{ items }">
                    <BalanceTransactionList
                        :transactions="items"
                        :loading-receipt-no="loadingReceiptNo"
                        @receipt="openReceipt"
                    />
                </template>
            </BalanceHistoryModal>

            <WithdrawCreditsModal
                :open="showModal"
                :advance-balance="advanceBalance"
                :available-credit="refundableAmount"
                :credit-reason="refundReason"
                v-model:amount="form.amount"
                v-model:method="form.method"
                v-model:account-details="form.accountDetails"
                :submitting="isRefunding"
                :error-message="formError"
                @close="closeModal"
                @submit="submit"
            />

            <PayBalanceModal
                :open="showPaymentModal"
                :patient-name="selectedLovedOne?.full_name"
                :current-balance="currentBalance"
                :unpaid-invoice-count="unpaidInvoiceCount"
                v-model:amount="payAmount"
                v-model:card="card"
                :processing="isPaying"
                :on-card-pay="payBalance"
                @close="closePaymentModal"
            />

            <PaymentReceipt
                v-if="activeReceipt"
                :receipt="activeReceipt"
                @close="activeReceipt = null"
            />

            <InvoiceAdjustmentModal
                :invoice="adjustmentInvoice"
                @close="adjustmentInvoice = null"
            />
        </div>
    </div>
</template>

<style scoped>
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
