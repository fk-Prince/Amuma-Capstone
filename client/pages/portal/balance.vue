<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { patientAccessService } from "~/api/patient-access/PatientAccessService.js";
import { refundService } from "~/api/refund/RefundService";
import { paymentService } from "~/api/payment/PaymentService";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { cardPayment } from "~/composables/usePayment";
import { useSubscriptionCheckout } from "~/stores/subscription";
import type { CardDetails } from "~/types/payment";
import EmptyState from "~/components/ui/EmptyState.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import BalanceInvoiceList from "~/components/sections/portal/BalanceInvoiceList.vue";
import BalanceTransactionList from "~/components/sections/portal/BalanceTransactionList.vue";
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
});

type RefundMethod = "GCash" | "Credit Card";

interface RefundRequest {
    id: number;
    reference: string | null;
    amount: number;
    method: string | null;
    reason: string;
    // Raw timestamp for sorting; created_at is the formatted label.
    requestedAt: string;
    created_at: string;
    status: "Requested" | "Completed" | "Declined";
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
const invoices = ref<InvoiceSummary[]>([]);
const voidedInvoices = ref<InvoiceSummary[]>([]);
const residentName = ref("");
const residentStatus = ref<"Active" | "Discharged" | "On Leave">("Active");
const advanceBalance = ref(0);
const refundRequests = ref<RefundRequest[]>([]);

const invoiceTransactions = computed(() =>
    transactions.value.filter(
        (transaction) =>
            transaction.type === "payment" || transaction.type === "refund",
    ),
);

const RECENT_LIMIT = 5;

// Voided invoices are shown alongside the live ones so a cancelled bill does
// not simply vanish from the family's records.
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
    invoiceTransactions.value.slice(0, RECENT_LIMIT),
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
        .filter(
            (transaction) =>
                transaction.type === "refund" &&
                (transaction.status ?? "").toLowerCase() === "completed",
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

const paymentProgress = computed(() => {
    if (totalAdjustedAmount.value <= 0) return 0;

    if (currentBalance.value <= 0) return 100;

    return Math.min(
        99,
        Math.floor((netPaidAmount.value / totalAdjustedAmount.value) * 100),
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
            return "Completed";
        case "declined":
            return "Declined";
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

        return {
            id: entry.id,
            invoiceCode: entry.invoice_codes?.[0] ?? "",
            invoiceCodes: entry.invoice_codes ?? [],
            type: isRefund ? "refund" : "payment",
            label: isRefund
                ? `Refund · ${entry.refund_method || "Unknown"}`
                : isCredit
                  ? amount < 0
                      ? "Credit moved to another invoice"
                      : "Credit applied"
                  : `Payment · ${entry.payment_method || "Unknown"}`,
            reference: entry.reference_id ?? entry.refund_code ?? undefined,
            date: formatDateTime(entry.created_at),
            amount,
            method: entry.payment_method ?? entry.refund_method ?? undefined,
            status: entry.status,
            reason: entry.declined_reason ?? undefined,
            maskedCardNumber: entry.masked_card_number ?? null,
            receiptNo: entry.receipt_no ?? null,
        };
    });
}

function refundStatusHeader(status: RefundRequest["status"]) {
    switch (status) {
        case "Completed":
            return "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300";

        case "Declined":
            return "bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300";

        default:
            return "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300";
    }
}

function refundRows(request: RefundRequest) {
    return [
        { label: "Date of request", value: request.created_at },
        { label: "Payment method", value: request.method },
        { label: "Amount", value: peso(request.amount) },
        {
            label: request.invoiceCodes.length > 1 ? "Invoices" : "Invoice",
            value: request.invoiceCodes.join(", "),
        },
        { label: "Status", value: refundStatusNote(request.status) },
    ].filter((row) => !!row.value);
}

function refundStatusNote(status: RefundRequest["status"]) {
    switch (status) {
        case "Completed":
            return "Sent — please allow time for it to reach your account.";

        case "Declined":
            return "Declined";

        default:
            return "Waiting for the branch to review it.";
    }
}

function buildRefundRequestsFromInvoices(invoiceList: any[]): RefundRequest[] {
    const byRefund = new Map<number, RefundRequest>();

    for (const invoice of invoiceList || []) {
        for (const payment of invoice.payments || []) {
            for (const refund of payment.refunds || []) {
                const id = Number(refund.refund_id);

                if (byRefund.has(id)) {
                    const existing = byRefund.get(id)!;

                    if (!existing.invoiceCodes.includes(invoice.invoice_code)) {
                        existing.invoiceCodes.push(invoice.invoice_code);
                    }

                    continue;
                }

                byRefund.set(id, {
                    id,
                    reference: refund.refund_code ?? null,
                    amount: Number(refund.refund_total ?? refund.amount ?? 0),
                    method: refund.refund_method ?? null,
                    reason: refund.declined_reason || "",
                    requestedAt: refund.created_at ?? "",
                    created_at: formatDateLabel(refund.created_at),
                    status: mapRefundRequestStatus(refund.status),
                    invoiceCodes: [invoice.invoice_code].filter(Boolean),
                });
            }
        }
    }

    // Sorted on the raw timestamp; the formatted label does not parse back.
    return [...byRefund.values()].sort(
        (a, b) =>
            new Date(b.requestedAt || 0).getTime() -
            new Date(a.requestedAt || 0).getTime(),
    );
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

    // Kept apart from the billing figures — a voided invoice asks for nothing —
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

    // A voided invoice is left out of the billing totals above, but the money
    // paid and refunded against it still belongs in the ledger.
    const ledgerInvoices = [...invoiceList, ...voidedList];

    transactions.value = mapTransactions(item.transactions);
    refundRequests.value = buildRefundRequestsFromInvoices(ledgerInvoices);
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

const requestsCollapsed = ref(false);

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
        formError.value = "Enter an amount of at least ₱1.";

        return;
    }

    if (requestedAmount > refundableAmount.value + 0.01) {
        formError.value = `Only ${peso(refundableAmount.value)} is available on this account.`;

        return;
    }

    if (!form.value.accountDetails.trim()) {
        formError.value =
            form.value.method === "GCash"
                ? "Enter the GCash number to receive the refund."
                : "Enter the card number to receive the refund.";

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
        const requested = Number(form.value.amount ?? 0);

        const res = await refundService.claim({
            patient_id: patientId,
            method: form.value.method,
            account_details: form.value.accountDetails,
            ...(requested > 0 ? { amount: requested } : {}),
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
        error("Enter an amount greater than ₱0.");
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

                success(`Payment recorded. Receipt ${receipt.receipt_no}.`);
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
        id: `payment-${receipt.lines[0]?.payment_id ?? receipt.receipt_no}-in`,
        invoiceId: receipt.lines[0]?.invoice_id ?? 0,
        invoiceCode: codes[0] ?? "",
        invoiceCodes: codes,
        type: "payment",
        label: `Payment · ${receipt.payment.method || "Unknown"}`,
        reference: receipt.lines[0]?.payment_reference ?? receipt.receipt_no,
        date: issuedAt,
        amount: round2(applied),
        method: receipt.payment.method ?? undefined,
        status: "completed",
        maskedCardNumber: receipt.payment.masked_account,
        receiptNo: receipt.receipt_no,
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
        const res = await paymentService.receipt({ receipt_no: receiptNo });

        activeReceipt.value = res?.data ?? res ?? null;
    } catch (err: any) {
        error(err?.message || "Unable to load that receipt.");
    } finally {
        loadingReceiptNo.value = null;
    }
}
</script>

<template>
    <div class="min-h-full bg-slate-50/60 p-5 dark:bg-surface">
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
            <section
                v-if="refundRequests.length"
                class="overflow-hidden rounded-2xl border border-emerald-200 shadow-sm dark:border-emerald-500/30"
            >
                <!-- && refundRequests[0]?.status.toLocaleLowerCase() === 'requested' -->
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 bg-emerald-600 px-5 py-3 text-left text-white transition hover:bg-emerald-700"
                    @click="requestsCollapsed = !requestsCollapsed"
                >
                    <span class="text-sm font-semibold">
                        Refund requests
                        <span class="ml-1 text-white/80">
                            ({{ refundRequests.length }})
                        </span>
                    </span>

                    <AppIcon
                        :name="
                            requestsCollapsed ? 'chevron-down' : 'chevron-up'
                        "
                        class="h-4 w-4 shrink-0"
                    />
                </button>

                <div
                    v-show="!requestsCollapsed"
                    class="grid gap-4 bg-white p-4 sm:grid-cols-2 dark:bg-secondary"
                >
                    <article
                        v-for="request in refundRequests"
                        :key="request.id"
                        class="overflow-hidden rounded-xl border border-gray-100 dark:border-white/10"
                    >
                        <div
                            class="flex items-center justify-between gap-3 px-4 py-2.5"
                            :class="refundStatusHeader(request.status)"
                        >
                            <span class="font-mono text-[11px] font-semibold">
                                {{ request.reference ?? `#${request.id}` }}
                            </span>

                            <span
                                class="text-[10px] font-bold uppercase tracking-wide"
                            >
                                {{ request.status }}
                            </span>
                        </div>

                        <dl
                            class="divide-y divide-gray-100 dark:divide-white/10"
                        >
                            <div
                                v-for="row in refundRows(request)"
                                :key="row.label"
                                class="flex items-start justify-between gap-3 px-4 py-2"
                            >
                                <dt
                                    class="shrink-0 text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    {{ row.label }}
                                </dt>

                                <dd
                                    class="min-w-0 truncate text-right text-[11px] font-medium text-gray-700 dark:text-gray-200"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>

                        <p
                            v-if="
                                request.status === 'Declined' && request.reason
                            "
                            class="border-t border-gray-100 bg-rose-50/60 px-4 py-2.5 text-[11px] leading-4 text-rose-700 dark:border-white/10 dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            Reason for refusal: {{ request.reason }}
                        </p>
                    </article>
                </div>
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
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
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
                                                }}<template v-if="lo.room_type">
                                                    ·
                                                    {{ lo.room_type }}</template
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
                                <AppIcon name="alert-circle" class="h-5 w-5" />
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
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-primary-500/10 dark:text-blue-300"
                                >
                                    <AppIcon name="receipt" class="h-5 w-5" />
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
                                    v-if="advanceBalance > 0"
                                    class="rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    Available
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
                                {{ peso(advanceBalance) }}
                            </p>

                            <!-- Matches the staff dashboard: the credit is what
                                 sits on the account now, with what has already
                                 gone back stated separately. -->
                            <p
                                v-if="totalRefundedAmount > 0"
                                class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                {{ peso(totalRefundedAmount) }} already refunded
                            </p>

                            <p
                                v-if="advanceBalance > 0 && openRefundRequest"
                                class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-[11px] leading-4 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                A refund request is already being reviewed. You
                                can ask for another once it is settled.
                            </p>

                            <button
                                v-else-if="advanceBalance > 0"
                                @click="openModal"
                                class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/15"
                            >
                                Request Refund
                                <AppIcon
                                    name="arrow-right"
                                    class="h-3.5 w-3.5"
                                />
                            </button>

                            <p
                                v-else
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
                                <div class="flex items-center justify-between">
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

                            <div>
                                <div class="flex items-center justify-between">
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
                                invoices.length === 1 ? "" : "s"
                            }}
                        </span>
                    </div>

                    <BalanceInvoiceList
                        :invoices="recentInvoices"
                        class="mt-4 flex-1"
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
                                    <AppIcon name="activity" class="h-4 w-4" />
                                </div>

                                <p
                                    class="text-sm font-bold text-gray-900 dark:text-white"
                                >
                                    Payments &amp; Refunds
                                </p>
                            </div>

                            <p
                                class="mt-1 pl-10 text-xs text-gray-400 dark:text-gray-500"
                            >
                                Money paid and returned on this account
                            </p>
                        </div>

                        <span
                            v-if="invoiceTransactions.length"
                            class="shrink-0 rounded-full bg-gray-50 px-3 py-1.5 text-[11px] font-semibold text-gray-500 dark:bg-white/5 dark:text-gray-400"
                        >
                            {{ invoiceTransactions.length }} transaction{{
                                invoiceTransactions.length === 1 ? "" : "s"
                            }}
                        </span>
                    </div>

                    <BalanceTransactionList
                        :transactions="recentTransactions"
                        :loading-receipt-no="loadingReceiptNo"
                        class="mt-4 flex-1"
                        @receipt="openReceipt"
                    />

                    <button
                        v-if="invoiceTransactions.length > RECENT_LIMIT"
                        type="button"
                        class="mt-4 w-full rounded-xl border border-gray-200 py-2.5 text-xs font-semibold text-gray-600 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                        @click="openAllTransactions"
                    >
                        View all {{ invoiceTransactions.length }} transactions
                    </button>
                </section>
            </div>
        </div>

        <Transition name="modal">
            <div
                v-if="showAllInvoices"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="showAllInvoices = false"
            >
                <div
                    class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div>
                            <p
                                class="text-sm font-bold text-gray-900 dark:text-white"
                            >
                                All invoices
                            </p>

                            <p
                                class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                            >
                                Every bill raised for
                                {{
                                    selectedLovedOne?.full_name ||
                                    "this resident"
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10"
                            @click="showAllInvoices = false"
                        >
                            <AppIcon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                        <div
                            v-if="isLoadingLedger"
                            class="flex items-center justify-center gap-2 py-14 text-xs text-gray-400 dark:text-gray-500"
                        >
                            <AppIcon
                                name="loader-circle"
                                class="h-4 w-4 animate-spin"
                            />
                            Loading invoices…
                        </div>

                        <BalanceInvoiceList v-else :invoices="listedInvoices" />
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div
                v-if="showAllTransactions"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="showAllTransactions = false"
            >
                <div
                    class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div>
                            <p
                                class="text-sm font-bold text-gray-900 dark:text-white"
                            >
                                All payments &amp; refunds
                            </p>

                            <p
                                class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                            >
                                Money paid and returned on this account
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10"
                            @click="showAllTransactions = false"
                        >
                            <AppIcon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                        <div
                            v-if="isLoadingLedger"
                            class="flex items-center justify-center gap-2 py-14 text-xs text-gray-400 dark:text-gray-500"
                        >
                            <AppIcon
                                name="loader-circle"
                                class="h-4 w-4 animate-spin"
                            />
                            Loading transactions…
                        </div>

                        <BalanceTransactionList
                            v-else
                            :transactions="invoiceTransactions"
                            :loading-receipt-no="loadingReceiptNo"
                            @receipt="openReceipt"
                        />
                    </div>
                </div>
            </div>
        </Transition>

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
                                class="flex h-8 w-8 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white dark:hover:bg-white/10"
                            >
                                <AppIcon name="x" class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="mt-4 text-lg font-bold">Request Refund</p>

                        <p class="mt-1 text-xs leading-5 text-white/75">
                            The credit on this account will be sent using your
                            selected method.
                        </p>
                    </div>

                    <div class="space-y-5 p-6">
                        <div
                            class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300"
                            >
                                Refund Amount
                            </p>

                            <p
                                class="mt-1 text-3xl font-bold text-emerald-700 dark:text-emerald-300"
                            >
                                {{ peso(advanceBalance) }}
                            </p>

                            <div
                                class="mt-3 flex items-start gap-2 border-t border-emerald-100 pt-3 dark:border-emerald-500/20"
                            >
                                <AppIcon
                                    name="info"
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500 dark:text-emerald-300"
                                />

                                <p
                                    class="text-[11px] leading-4 text-emerald-700/80 dark:text-emerald-300"
                                >
                                    {{ refundReason }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                            >
                                How much would you like back?
                            </label>

                            <input
                                v-model.number="form.amount"
                                type="number"
                                step="1"
                                min="1"
                                :max="refundableAmount"
                                class="mt-1.5 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-sm text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:bg-secondary dark:border-white/10 dark:text-white"
                            />

                            <div
                                class="mt-1.5 flex items-center justify-between gap-3"
                            >
                                <button
                                    type="button"
                                    class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-300"
                                    @click="form.amount = refundableAmount"
                                >
                                    Request all
                                </button>

                                <p
                                    v-if="Number(form.amount) > 0"
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        peso(
                                            refundableAmount -
                                                Number(form.amount),
                                        )
                                    }}
                                    stays on the account
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
                                    <option>Credit Card</option>
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
                                form.method === 'GCash'
                                    ? 'GCash Number'
                                    : 'Card Number'
                            "
                            :placeholder="
                                form.method === 'GCash'
                                    ? 'e.g. 0917 123 4567'
                                    : 'e.g. 4000 0000 0000 2503'
                            "
                        />

                        <div
                            v-if="formError"
                            class="flex items-start gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
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
                                class="flex-1 rounded-full border border-gray-200 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
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
                    class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Pay balance"
                >
                    <div
                        class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <AppIcon name="credit-card" class="h-5 w-5" />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    Pay balance
                                </h2>

                                <p
                                    class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        selectedLovedOne?.full_name ||
                                        "This resident"
                                    }}
                                    ·
                                    {{ peso(currentBalance) }} outstanding
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Close dialog"
                            :disabled="isPaying"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                            @click="closePaymentModal"
                        >
                            <AppIcon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        class="grid min-h-0 flex-1 gap-6 overflow-y-auto px-6 py-5 lg:grid-cols-2 lg:items-start"
                    >
                        <div class="space-y-5">
                            <div
                                class="rounded-2xl border border-rose-100 bg-rose-50 p-5 dark:border-rose-500/20 dark:bg-rose-500/10"
                            >
                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-xs font-bold uppercase tracking-wide text-rose-500 dark:text-rose-300"
                                    >
                                        Amount to charge
                                    </p>

                                    <AppIcon
                                        name="alert-circle"
                                        class="h-4 w-4 text-rose-400"
                                    />
                                </div>

                                <p
                                    class="mt-1 text-4xl font-bold text-rose-600 dark:text-rose-300"
                                >
                                    {{ peso(payAmount) }}
                                </p>

                                <p
                                    class="mt-3 border-t border-rose-100 pt-3 text-xs text-rose-500/80 dark:border-rose-500/20 dark:text-rose-300/70"
                                >
                                    {{ peso(currentBalance) }} outstanding in
                                    total across {{ unpaidInvoiceCount }} bill{{
                                        unpaidInvoiceCount === 1 ? "" : "s"
                                    }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >
                                    How much would you like to pay?
                                </label>

                                <input
                                    v-model.number="payAmount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="currentBalance"
                                    class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-base font-semibold text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                                />

                                <div
                                    class="mt-2 flex items-center justify-between gap-3"
                                >
                                    <button
                                        type="button"
                                        class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-300"
                                        @click="payAmount = currentBalance"
                                    >
                                        Pay the full balance
                                    </button>

                                    <span
                                        class="text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        Max {{ peso(currentBalance) }}
                                    </span>
                                </div>
                            </div>

                            <!-- <p
                                class="rounded-xl bg-gray-50 p-4 text-xs leading-5 text-gray-500 dark:bg-white/5 dark:text-gray-400"
                            >
                                Your payment is applied to the oldest bill first,
                                then to the next one, until the amount runs out.
                            </p> -->
                        </div>

                        <div class="space-y-5">
                            <PaymentForm
                                v-model:card="card"
                                :total-amount="payAmount"
                                :processing="isPaying"
                                :on-card-pay="payBalance"
                                gcash-label="GCash is not available yet"
                                gcash-description="GCash payments aren't available yet. Please use a card for now."
                                title="Card details"
                                description="Your card is charged securely through Xendit."
                                submit-label="Pay now"
                            />
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
