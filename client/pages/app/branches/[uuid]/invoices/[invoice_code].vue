<template>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-5 pb-8">
        <div class="flex flex-wrap items-center justify-between gap-3 no-print">
            <button
                type="button"
                @click="goBack"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-[#6B8A87] hover:text-[#16302E] transition dark:hover:text-white dark:text-gray-400"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                    <path
                        d="M12.5 15L7.5 10L12.5 5"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                Back
            </button>

            <button
                type="button"
                @click="handlePrint"
                class="inline-flex items-center gap-2 rounded-full border border-[#DDECEC] bg-white px-4 py-2 text-sm font-medium text-[#0E7C7B] shadow-sm transition hover:border-[#0E7C7B] hover:text-[#0A5A58] dark:text-accent-300 dark:hover:text-accent-200 dark:border-white/10 dark:hover:border-accent-500/40 dark:bg-secondary"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <polyline points="6 9 6 3 18 3 18 9" />
                    <path
                        d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                    />
                    <rect x="6" y="14" width="12" height="7" rx="1" />
                </svg>
                Print invoice
            </button>
        </div>

        <div
            v-if="loading"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#6B8A87] dark:text-gray-400 dark:bg-secondary"
        >
            Loading invoice…
        </div>

        <div
            v-else-if="errorLabel"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#B3402F] dark:text-rose-300 dark:bg-secondary"
        >
            {{ errorLabel }}
        </div>

        <div
            v-else-if="invoice"
            class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-5 items-start"
        >
            <div
                class="invoice-sheet min-w-0 overflow-hidden rounded-[24px] border border-[#DDECEC] bg-white shadow-sm ring-1 ring-black/5 dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="print-only-header border-b border-[#DDECEC] px-6 py-5 dark:border-white/10"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-[11px] font-mono uppercase tracking-[0.2em] text-[#6B8A87] dark:text-gray-400"
                            >
                                Amuma Care
                            </p>
                            <h1
                                class="mt-1 text-xl font-semibold text-[#16302E] dark:text-white"
                            >
                                Invoice
                            </h1>
                            <p class="text-sm text-[#6B8A87] dark:text-gray-400">
                                {{ invoice.branch?.name ?? "Branch" }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p
                                class="text-[11px] font-mono uppercase tracking-[0.2em] text-[#6B8A87] dark:text-gray-400"
                            >
                                Invoice No.
                            </p>
                            <p class="text-sm font-semibold text-[#16302E] dark:text-white">
                                #{{ invoice.invoice_code }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 px-7 py-6 border-b border-[#EDF4F3] bg-gradient-to-b from-[#0E7C7B]/[0.04] to-transparent dark:border-white/10"
                >
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="min-w-0">
                            <span
                                class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] inline-block mb-2 dark:text-accent-300 dark:bg-accent-500/15"
                            >
                                #{{ invoice.invoice_code }}
                            </span>

                            <h2
                                class="text-lg font-semibold text-[#16302E] truncate dark:text-white"
                            >
                                {{ invoice.patient?.full_name ?? "—" }}
                            </h2>
                            <p class="text-sm text-[#6B8A87] truncate dark:text-gray-400">
                                {{ invoice.branch?.name ?? "—" }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-right">
                            <p
                                class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono dark:text-gray-400"
                            >
                                Created
                            </p>
                            <p class="text-sm font-medium text-[#16302E] dark:text-white">
                                {{ formatDate(invoice.created_at) }}
                            </p>
                        </div>

                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                            :class="statusClasses(invoice.status)"
                        >
                            {{ invoice.status }}
                        </span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 sm:divide-x divide-[#EDF4F3] border-b border-[#EDF4F3] bg-[#FAFDFC] dark:border-white/10 dark:bg-white/5"
                    :class="hasRefunds ? 'sm:grid-cols-4' : 'sm:grid-cols-3'"
                >
                    <div class="px-4 sm:px-7 py-4 sm:py-5">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono mb-1 dark:text-gray-400"
                        >
                            Total
                        </p>
                        <p class="text-2xl font-bold text-[#16302E] dark:text-white">
                            ₱{{ formatMoney(invoice.total) }}
                        </p>
                    </div>

                    <div class="px-4 sm:px-7 py-4 sm:py-5 bg-[#E4F4EE]/40">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#1F7A4D]/80 font-mono mb-1"
                        >
                            Amount Paid
                        </p>
                        <p class="text-2xl font-bold text-[#1F7A4D] dark:text-emerald-300">
                            ₱{{ formatMoney(invoice.amount_paid) }}
                        </p>
                    </div>

                    <div
                        v-if="hasRefunds"
                        class="px-4 sm:px-7 py-4 sm:py-5 bg-[#FDF3DE]/60"
                    >
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#966B1F]/80 font-mono mb-1"
                        >
                            Refunded
                        </p>
                        <p class="text-2xl font-bold text-[#966B1F] dark:text-amber-300">
                            ₱{{ formatMoney(invoice.refunded_amount) }}
                        </p>
                    </div>

                    <div class="px-4 sm:px-7 py-4 sm:py-5 bg-[#FBE8E6]/40">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#B3402F]/80 font-mono mb-1"
                        >
                            Balance Due
                        </p>
                        <p class="text-2xl font-bold text-[#B3402F] dark:text-rose-300">
                            ₱{{ formatMoney(invoice.balance_due) }}
                        </p>
                    </div>
                </div>

                <div class="px-7 py-6 space-y-8">
                    <section v-if="invoice.services?.length">
                        <SectionHeader>
                            <template #icon>
                                <Stethoscope
                                    class="h-3.5 w-3.5"
                                    :stroke-width="2"
                                />
                            </template>
                            Services
                        </SectionHeader>

                        <div class="space-y-3">
                            <div
                                v-for="item in invoice.services"
                                :key="item.schedule_services_id"
                                class="rounded-xl border border-[#EDF4F3] px-5 py-4 dark:border-white/10"
                            >
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-[1.4fr_1.4fr_1fr] gap-x-6 gap-y-4 text-sm"
                                >
                                    <Field
                                        label="Service"
                                        :value="item.service_name ?? 'Service'"
                                    />
                                    <Field label="Note" :value="item.note" />
                                    <Field
                                        label="Price"
                                        :value="`₱${formatMoney(item.price)}`"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="invoice.facilities?.length">
                        <SectionHeader>
                            <template #icon>
                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"
                                    />
                                    <path d="M15 3h6v6" />
                                    <path d="M10 14 21 3" />
                                </svg>
                            </template>
                            Facility Charges
                        </SectionHeader>

                        <div class="space-y-3">
                            <div
                                v-for="facility in invoice.facilities"
                                :key="facility.invoice_accommodation_id"
                                class="rounded-xl border border-[#EDF4F3] px-5 py-4 dark:border-white/10"
                            >
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                                >
                                    <Field
                                        label="Patient"
                                        :value="facility.patient_name"
                                    />
                                    <Field
                                        label="Price"
                                        :value="`₱${formatMoney(facility.price)}`"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="invoice.patient">
                        <SectionHeader>
                            <template #icon>
                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </template>
                            Patient Information
                        </SectionHeader>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                        >
                            <Field
                                label="Name"
                                :value="invoice.patient.full_name"
                            />
                            <Field
                                label="Gender"
                                :value="invoice.patient.gender"
                            />
                            <Field
                                label="Birth Date"
                                :value="
                                    formatDate(invoice.patient.date_of_birth)
                                "
                            />
                            <Field label="Age" :value="invoice.patient.age" />
                            <Field
                                label="Blood Type"
                                :value="invoice.patient.blood_type"
                            />
                            <Field
                                label="Phone"
                                :value="invoice.patient.phone_number"
                            />
                            <Field
                                label="Citizenship"
                                :value="invoice.patient.citizenship"
                            />
                        </div>
                    </section>

                    <section class="pt-5 border-t border-[#EDF4F3] dark:border-white/10">
                        <SectionHeader>
                            <template #icon>
                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect
                                        x="2"
                                        y="5"
                                        width="20"
                                        height="14"
                                        rx="2"
                                    />
                                    <path d="M2 10h20" />
                                </svg>
                            </template>
                            Payments
                        </SectionHeader>

                        <div v-if="invoice.payments?.length" class="space-y-3">
                            <div
                                v-for="payment in invoice.payments"
                                :key="payment.payment_id"
                                class="rounded-xl border border-[#EDF4F3] px-5 py-4 dark:border-white/10"
                            >
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm"
                                >
                                    <Field
                                        label="Method"
                                        :value="payment.payment_method"
                                    />
                                    <Field
                                        label="Reference"
                                        :value="payment.reference_id"
                                    />
                                    <Field
                                        label="Amount"
                                        :value="`₱${formatMoney(payment.amount)}`"
                                    />
                                </div>
                                <p class="text-xs text-[#6B8A87] mt-2 dark:text-gray-400">
                                    {{ formatDate(payment.created_at) }}
                                </p>

                                <div
                                    v-if="payment.refunds?.length"
                                    class="mt-3 space-y-2 border-t border-[#EDF4F3] pt-3 dark:border-white/10"
                                >
                                    <div
                                        v-for="refund in payment.refunds"
                                        :key="refund.refund_id"
                                        class="rounded-lg bg-[#FDF3DE]/50 px-4 py-3"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <span
                                                class="text-xs font-mono text-[#966B1F] dark:text-amber-300"
                                            >
                                                {{
                                                    refund.reference_id ??
                                                    "Refund"
                                                }}
                                            </span>

                                            <span
                                                class="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                                                :class="
                                                    refundStatusClasses(
                                                        refund.status,
                                                    )
                                                "
                                            >
                                                {{ refund.status }}
                                            </span>
                                        </div>

                                        <div
                                            class="mt-1.5 flex items-center justify-between gap-3"
                                        >
                                            <p
                                                class="text-xs text-[#6B8A87] dark:text-gray-400"
                                            >
                                                {{
                                                    refund.reason ??
                                                    "No reason provided."
                                                }}
                                            </p>

                                            <span
                                                class="shrink-0 text-sm font-semibold text-[#966B1F] dark:text-amber-300"
                                            >
                                                ₱{{
                                                    formatMoney(refund.amount)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p
                            v-else
                            class="rounded-xl border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            No payments recorded yet
                        </p>
                    </section>

                    <section
                        v-if="invoice.adjustments?.length"
                        class="pt-5 border-t border-[#EDF4F3] dark:border-white/10"
                    >
                        <SectionHeader>
                            <template #icon>
                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M3 6h18" />
                                    <path d="M6 12h12" />
                                    <path d="M9 18h6" />
                                </svg>
                            </template>
                            Adjustments
                        </SectionHeader>

                        <div class="space-y-3">
                            <div
                                v-for="adjustment in invoice.adjustments"
                                :key="adjustment.invoice_adjustment_id"
                                class="rounded-xl border border-[#EDF4F3] px-5 py-4 dark:border-white/10"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-medium text-[#16302E] capitalize dark:text-white"
                                        >
                                            {{
                                                formatAdjustmentType(
                                                    adjustment.type,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="text-xs text-[#6B8A87] mt-1 dark:text-gray-400"
                                        >
                                            {{
                                                adjustment.reason ??
                                                "No reason provided."
                                            }}
                                        </p>
                                        <p
                                            class="text-xs text-[#6B8A87] mt-1 dark:text-gray-400"
                                        >
                                            {{
                                                formatDate(
                                                    adjustment.created_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 text-sm font-semibold text-[#16302E] dark:text-white"
                                    >
                                        ₱{{
                                            formatMoney(adjustment.amount)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div
                v-if="showPayment"
                class="xl:sticky xl:top-6 no-print rounded-3xl border border-[#DDECEC] bg-white shadow-sm overflow-hidden dark:border-white/10 dark:bg-secondary"
            >
                <div class="p-6">
                    <PaymentForm
                        :processing="processingPayment"
                        :total-amount="invoice.balance_due"
                        :enable-card="false"
                        :enable-g-cash="false"
                        :enable-cash="true"
                        title="Complete Payment"
                        :description="`Balance due: ₱${formatMoney(invoice.balance_due)}`"
                        cash-label="Confirm Cash Payment"
                        cash-processing-label="Confirming payment..."
                        cash-description="Enter the cash amount received from the patient to settle this invoice."
                        @cash-pay="handleCashPay"
                    />
                </div>
            </div>

            <div
                v-else
                class="no-print rounded-2xl shadow-sm ring-1 ring-black/5 bg-white p-6 text-center text-sm text-[#6B8A87] xl:sticky xl:top-6 dark:text-gray-400 dark:bg-secondary"
            >
                This invoice is fully paid.
            </div>

            <div
                v-if="paymentChange > 0"
                class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center dark:border-emerald-500/20 dark:bg-emerald-500/10"
            >
                <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                    Change Returned
                </p>

                <p class="mt-1 text-3xl font-bold text-emerald-800 dark:text-emerald-300">
                    ₱{{ formatMoney(paymentChange) }}
                </p>
            </div>
        </div>

        <ConfirmDialog
            :open="showConfirmPayment"
            title="Confirm Cash Payment"
            :message="`Confirm payment of ₱${formatMoney(pendingCash)}?`"
            description="This will record the cash payment and update the invoice balance."
            confirm-label="Confirm Payment"
            cancel-label="Cancel"
            :loading="processingPayment"
            @confirm="confirmCashPayment"
            @cancel="cancelPayment"
            :allow-short-cash="true"
        />

        <PaymentReceipt
            v-if="activeReceipt"
            :receipt="activeReceipt"
            @close="activeReceipt = null"
        />
    </div>
</template>

<script lang="ts" setup>
import { ref, reactive, computed, onMounted, h } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Stethoscope } from "lucide-vue-next";
import { formatAmount } from "~/utils/currency";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { invoiceService } from "~/api/invoice/InvoiceService";
import type { InvoiceDetail } from "~/types/invoice";
import type { PaymentReceipt as PaymentReceiptData } from "~/types/receipt";
import { useToast } from "~/composables/useToast";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Invoice Details",
});

const { success, error } = useToast();
const route = useRoute();
const router = useRouter();
const showConfirmPayment = ref(false);
const pendingCash = ref<number | null>(null);
const paymentChange = ref(0);
const uuid = computed(() => route.params.uuid as string);
const invoiceCode = computed(() => route.params.invoice_code as string);

const invoice = ref<InvoiceDetail | null>(null);
const loading = ref(true);
const errorLabel = ref("");
const processingPayment = ref(false);

const activeReceipt = ref<PaymentReceiptData | null>(null);

const showPayment = computed(
    () => !!invoice.value && invoice.value.balance_due > 0,
);

const hasRefunds = computed(
    () => Number(invoice.value?.refunded_amount ?? 0) > 0,
);

async function fetchInvoice() {
    loading.value = true;
    errorLabel.value = "";

    try {
        const response = await invoiceService.show(
            {
                invoice_code: invoiceCode.value,
                branch_uuid: uuid.value,
                mode: route.query.mode,
            },
            invoiceCode.value,
        );

        invoice.value = response.data ?? response;
    } catch (err) {
        console.error(err);
        errorLabel.value = "Unable to load this invoice.";
    } finally {
        loading.value = false;
    }
}

async function handleCashPay(cash: any) {
    pendingCash.value = cash;
    showConfirmPayment.value = true;
}
async function confirmCashPayment() {
    if (!invoice.value || !pendingCash.value) return;

    processingPayment.value = true;

    try {
        const res = await invoiceService.create({
            cash: pendingCash.value,
            mode: route.query.mode as string,
            payment_method: "CASH",
            invoice_code: invoiceCode.value,
            branch_uuid: uuid.value,
        });

        success(res.message ?? "Payment completed successfully.");

        paymentChange.value = Number(res.change ?? 0);

        if (res.receipt) {
            activeReceipt.value = res.receipt;
        }

        showConfirmPayment.value = false;
        pendingCash.value = null;

        await fetchInvoice();
    } catch (err: any) {
        error(err?.message ?? "Payment failed. Please try again.");
    } finally {
        processingPayment.value = false;
    }
}
function cancelPayment() {
    if (processingPayment.value) return;
    showConfirmPayment.value = false;
    pendingCash.value = null;
}

function handlePrint() {
    if (typeof window !== "undefined") {
        window.print();
    }
}

function goBack() {
    router.back();
}

function statusClasses(status: string) {
    const normalized = (status ?? "").toLowerCase();

    if (normalized === "paid") {
        return "bg-[#E4F4EE] text-[#1F7A4D] dark:text-emerald-300 dark:bg-emerald-500/15";
    }
    if (normalized === "partial") {
        return "bg-[#E6F1FA] text-[#2563A6] dark:text-blue-300 dark:bg-blue-500/15";
    }
    if (normalized === "overdue") {
        return "bg-[#FBE8E6] text-[#B3402F] dark:text-rose-300 dark:bg-rose-500/15";
    }
    return "bg-[#FDF3DE] text-[#966B1F] dark:text-amber-300 dark:bg-amber-500/15";
}

function refundStatusClasses(status: string) {
    const normalized = (status ?? "").toLowerCase();

    if (normalized === "completed") {
        return "bg-[#E4F4EE] text-[#1F7A4D] dark:text-emerald-300 dark:bg-emerald-500/15";
    }
    if (normalized === "processing" || normalized === "pending") {
        return "bg-[#FDF3DE] text-[#966B1F] dark:text-amber-300 dark:bg-amber-500/15";
    }
    if (normalized === "failed" || normalized === "cancelled") {
        return "bg-[#FBE8E6] text-[#B3402F] dark:text-rose-300 dark:bg-rose-500/15";
    }
    return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
}

function formatAdjustmentType(type: string) {
    return (type ?? "")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

function formatDate(value: string | null | undefined) {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

onMounted(() => {
    fetchInvoice();
});

const Field = (fieldProps: { label: string; value: any }, { slots }: any) =>
    h("p", { class: "flex flex-col gap-0.5" }, [
        h("span", { class: "text-xs text-[#6B8A87] dark:text-gray-400" }, fieldProps.label),
        h(
            "span",
            { class: "text-[#16302E] font-medium dark:text-white" },
            slots.value ? slots.value() : (fieldProps.value ?? "—"),
        ),
    ]);
Field.props = ["label", "value"];

const SectionHeader = (_props: unknown, { slots }: any) =>
    h(
        "h3",
        {
            class: "flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4 dark:text-accent-300",
        },
        [slots.icon?.(), slots.default?.()],
    );
</script>

<style scoped>
.invoice-sheet {
    background: #ffffff;
}

.print-only-header {
    display: none;
}

@media print {
    :global(body) {
        background: #ffffff !important;
    }

    .no-print {
        display: none !important;
    }

    .invoice-sheet {
        border-radius: 0 !important;
        border-color: #dfe9e7 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .print-only-header {
        display: block;
    }

    @page {
        size: A4 landscape;
        margin: 10mm;
    }
}
</style>
