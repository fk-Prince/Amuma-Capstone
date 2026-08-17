<template>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-5 pb-8">
        <div class="flex flex-wrap items-center justify-between gap-3 no-print">
            <button
                type="button"
                @click="goBack"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-secondary transition"
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
                class="inline-flex items-center gap-2 rounded-full border border-primary-100 bg-white px-4 py-2 text-sm font-medium text-primary-600 shadow-sm transition hover:border-accent-500 hover:text-accent-600"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M6 9V3h12v6" />
                    <path
                        d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                    />
                    <path d="M8 14h8v6H8z" />
                </svg>
                Print
            </button>
        </div>

        <div
            class="hidden print:block rounded-2xl border border-primary-100 bg-white p-5"
        >
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-secondary"
            >
                <div>
                    <p
                        class="font-semibold uppercase tracking-[0.2em] text-muted text-[11px]"
                    >
                        Patient
                    </p>
                    <p class="mt-1 text-base font-semibold">
                        {{ summary?.patient?.full_name ?? "—" }}
                    </p>
                    <p class="text-muted">
                        {{ summary?.patient?.patient_uuid ?? "—" }}
                    </p>
                </div>

                <div class="text-right">
                    <p
                        class="font-semibold uppercase tracking-[0.2em] text-muted text-[11px]"
                    >
                        Invoices
                    </p>
                    <p class="mt-1 text-base font-semibold">
                        {{ summary?.invoice_count ?? 0 }}
                    </p>
                    <p class="text-muted">
                        Balance ₱{{ formatMoney(summary?.total_balance ?? 0) }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="loading"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-muted"
        >
            Loading patient invoices…
        </div>

        <div
            v-else-if="errors"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-danger"
        >
            {{ errors }}
        </div>

        <div
            v-else-if="summary"
            class="grid grid-cols-1 xl:grid-cols-[1fr_500px] gap-5 items-start"
        >
            <div
                class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 overflow-hidden"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 px-7 py-6 border-b border-primary-100 bg-gradient-to-b from-accent-500/[0.04] to-transparent"
                >
                    <div class="min-w-0">
                        <span
                            class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-accent-50 text-accent-600 inline-block mb-2"
                        >
                            {{ summary.patient?.patient_uuid }}
                        </span>

                        <h2
                            class="text-lg font-semibold text-secondary truncate"
                        >
                            {{ summary.patient?.full_name ?? "—" }}
                        </h2>

                        <p class="text-sm text-muted truncate">
                            {{ summary.invoice_count }} invoice(s) on record
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <button
                            v-if="canRefund"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border border-danger/30 bg-danger/10 px-3.5 py-1.5 text-xs font-medium text-danger hover:border-danger transition no-print"
                            @click="openRefundModal"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M3 12a9 9 0 1 0 3-6.7" />
                                <path d="M3 4v5h5" />
                            </svg>

                            Refund
                            <span class="opacity-70">
                                ₱{{ formatMoney(refundableAmount) }}
                            </span>
                        </button>

                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium capitalize shrink-0"
                            :class="statusClasses(summary.status)"
                        >
                            {{ summary.status }}
                        </span>
                    </div>
                </div>

                <div
                    class="grid border-b border-primary-100 bg-white divide-x divide-primary-100"
                    :class="gridColsClass"
                >
                    <div class="px-7 py-5 bg-slate-50/60">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-muted font-mono mb-2"
                        >
                            Total Amount
                        </p>
                        <p
                            class="text-2xl font-bold tracking-tight text-secondary"
                        >
                            ₱{{ formatMoney(summary.total_amount) }}
                        </p>
                    </div>

                    <div class="px-7 py-5 bg-primary-50/70">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-primary-700 font-mono mb-2"
                        >
                            Total Paid
                        </p>
                        <p
                            class="text-2xl font-bold tracking-tight text-primary-700"
                        >
                            ₱{{ formatMoney(summary.total_paid) }}
                        </p>
                    </div>

                    <div v-if="hasRefund" class="px-7 py-5 bg-accent-50/60">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-accent-700 font-mono mb-2"
                        >
                            Total Refunded
                        </p>
                        <p
                            class="text-2xl font-bold tracking-tight text-accent-700"
                        >
                            ₱{{ formatMoney(summary.total_refunded) }}
                        </p>
                    </div>

                    <div
                        v-if="hasProcessingRefund"
                        class="px-7 py-5 bg-primary-50"
                    >
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-primary-800 font-mono mb-2"
                        >
                            To Be Refunded
                        </p>
                        <p
                            class="text-2xl font-bold tracking-tight text-primary-800"
                        >
                            ₱{{ formatMoney(summary.total_refund_processing) }}
                        </p>
                    </div>

                    <div class="px-7 py-5 bg-danger/10">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-red-600 font-mono mb-2"
                        >
                            Total Balance
                        </p>
                        <p
                            class="text-2xl font-bold tracking-tight text-red-600"
                        >
                            ₱{{ formatMoney(summary.total_balance) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="summary.patient"
                    class="px-7 py-6 border-b border-primary-100"
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
                            :value="summary.patient.full_name"
                        />
                        <Field label="Gender" :value="summary.patient.gender" />
                        <Field
                            label="Birth Date"
                            :value="formatDate(summary.patient.date_of_birth)"
                        />
                        <Field
                            label="Age"
                            :value="
                                calculateAge(
                                    formatDate(summary.patient.date_of_birth),
                                    false,
                                )
                            "
                        />
                        <Field
                            label="Blood Type"
                            :value="summary.patient.blood_type"
                        />
                        <Field
                            label="Phone"
                            :value="summary.patient.phone_number"
                        />
                        <Field
                            label="Citizenship"
                            :value="summary.patient.citizenship"
                        />
                    </div>
                </div>

                <div class="px-7 py-6">
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
                                <path d="M3 21h18" />
                                <path d="M5 21V5l7-3 7 3v16" />
                                <path d="M9 21v-5h6v5" />
                                <path d="M9 8h.01" />
                                <path d="M15 8h.01" />
                                <path d="M9 11h.01" />
                                <path d="M15 11h.01" />
                            </svg>
                        </template>
                        Admission
                    </SectionHeader>

                    <div v-if="admissionInvoices.length" class="space-y-3">
                        <div
                            v-for="inv in admissionInvoices"
                            :key="`admission-${inv.invoice_id}`"
                            class="group rounded-2xl border border-primary-100 bg-white px-5 py-5 cursor-pointer transition-all duration-200 hover:border-primary-200 hover:bg-primary-50/20 hover:shadow-sm"
                            @click="viewInvoice(inv.invoice_code)"
                        >
                            <div class="flex flex-col gap-5">
                                <div
                                    class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex items-center gap-2 flex-wrap"
                                        >
                                            <span
                                                class="inline-flex items-center rounded-lg bg-primary-50 border border-primary-100 px-2.5 py-1 font-mono text-[11px] font-medium text-primary-700"
                                            >
                                                {{ inv.invoice_code }}
                                            </span>

                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium"
                                                :class="
                                                    statusClasses(inv.status)
                                                "
                                            >
                                                {{ inv.status }}
                                            </span>
                                        </div>

                                        <div
                                            class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4"
                                        >
                                            <div>
                                                <p
                                                    class="text-[10px] uppercase tracking-[0.14em] text-muted font-mono"
                                                >
                                                    Total
                                                </p>
                                                <p
                                                    class="mt-1 text-sm font-semibold text-secondary"
                                                >
                                                    ₱{{
                                                        formatMoney(inv.total)
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-[10px] uppercase tracking-[0.14em] text-muted font-mono"
                                                >
                                                    Paid
                                                </p>
                                                <p
                                                    class="mt-1 text-sm font-semibold text-primary-700"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            inv.amount_paid,
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-[10px] uppercase tracking-[0.14em] text-muted font-mono"
                                                >
                                                    Date
                                                </p>
                                                <p
                                                    class="mt-1 text-sm font-medium text-secondary"
                                                >
                                                    {{
                                                        formatDate(
                                                            inv.created_at,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="sm:min-w-[150px] sm:border-l sm:border-primary-100 sm:pl-5"
                                    >
                                        <p
                                            class="text-[10px] uppercase tracking-[0.14em] text-muted font-mono"
                                        >
                                            Balance Due
                                        </p>

                                        <p
                                            class="mt-1 text-lg font-bold tracking-tight"
                                            :class="
                                                Number(inv.balance_due ?? 0) > 0
                                                    ? 'text-danger'
                                                    : 'text-primary-700'
                                            "
                                        >
                                            ₱{{ formatMoney(inv.balance_due) }}
                                        </p>

                                        <p
                                            class="mt-1 text-[11px]"
                                            :class="
                                                Number(inv.balance_due ?? 0) > 0
                                                    ? 'text-danger/70'
                                                    : 'text-primary-700/70'
                                            "
                                        >
                                            {{
                                                Number(inv.balance_due ?? 0) > 0
                                                    ? "Outstanding"
                                                    : "Fully paid"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="invoiceRefundAmount(inv) > 0"
                                    class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl bg-primary-50/50 border border-primary-100 px-4 py-3"
                                >
                                    <span
                                        v-if="
                                            Number(inv.refunded_amount ?? 0) > 0
                                        "
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-accent-700"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-accent-500"
                                        ></span>
                                        Refunded ₱{{
                                            formatMoney(inv.refunded_amount)
                                        }}
                                    </span>

                                    <span
                                        v-if="
                                            Number(
                                                inv.refund_processing_amount ??
                                                    0,
                                            ) > 0
                                        "
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-700"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-primary-500"
                                        ></span>
                                        Refund on process ₱{{
                                            formatMoney(
                                                inv.refund_processing_amount,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between border-t border-primary-100 pt-3"
                                >
                                    <span class="text-[11px] text-muted">
                                        Admission invoice
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 group-hover:text-primary-700 transition"
                                    >
                                        View invoice
                                        <svg
                                            class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
                                            viewBox="0 0 20 20"
                                            fill="none"
                                        >
                                            <path
                                                d="M7.5 15L12.5 10L7.5 5"
                                                stroke="currentColor"
                                                stroke-width="1.75"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-primary-100 p-6 text-center text-sm text-muted"
                    >
                        No admission invoices found.
                    </div>
                </div>

                <div class="px-7 py-6 border-t border-primary-100">
                    <SectionHeader>
                        <template #icon>
                            <Stethoscope
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                        </template>
                        Services
                    </SectionHeader>

                    <div v-if="serviceInvoices.length" class="space-y-3">
                        <div
                            v-for="inv in serviceInvoices"
                            :key="`service-${inv.invoice_id}`"
                            class="rounded-xl border border-primary-100 px-5 py-4 cursor-pointer hover:border-accent-500/40 hover:bg-accent-50/30 transition"
                            @click="viewInvoice(inv.invoice_code)"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <span
                                            class="font-mono text-xs px-2 py-1 rounded-md bg-accent-50 text-accent-700"
                                        >
                                            {{ inv.invoice_code }}
                                        </span>

                                        <span
                                            class="px-2.5 py-1 rounded-full text-[11px] font-medium"
                                            :class="statusClasses(inv.status)"
                                        >
                                            {{ inv.status }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm"
                                    >
                                        <Field
                                            label="Total"
                                            :value="`₱${formatMoney(inv.total)}`"
                                        />
                                        <Field
                                            label="Paid"
                                            :value="`₱${formatMoney(inv.amount_paid)}`"
                                        />
                                        <Field
                                            label="Date"
                                            :value="formatDate(inv.created_at)"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-between sm:flex-col sm:items-end gap-2 shrink-0"
                                >
                                    <span class="text-xs text-muted">
                                        Balance
                                    </span>
                                    <span
                                        class="text-sm font-semibold text-danger"
                                    >
                                        ₱{{ formatMoney(inv.balance_due) }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="invoiceRefundAmount(inv) > 0"
                                class="mt-4 pt-3 border-t border-primary-100 flex flex-wrap gap-x-6 gap-y-2 text-xs"
                            >
                                <span
                                    v-if="Number(inv.refunded_amount ?? 0) > 0"
                                    class="text-primary-700"
                                >
                                    Refunded ₱{{
                                        formatMoney(inv.refunded_amount)
                                    }}
                                </span>

                                <span
                                    v-if="
                                        Number(
                                            inv.refund_processing_amount ?? 0,
                                        ) > 0
                                    "
                                    class="text-accent-700"
                                >
                                    Refund Processing ₱{{
                                        formatMoney(
                                            inv.refund_processing_amount,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-primary-100 p-6 text-center text-sm text-muted"
                    >
                        No service invoices found.
                    </div>
                </div>
            </div>

            <div v-if="showPayment" class="xl:sticky xl:top-6 print:hidden">
                <div class="p-6">
                    <PaymentForm
                        :processing="processingPayment"
                        :total-amount="summary.total_balance"
                        :enable-card="false"
                        :enable-g-cash="false"
                        :enable-cash="true"
                        title="Complete Payment"
                        :description="`Outstanding balance: ₱${formatMoney(
                            summary.total_balance,
                        )}`"
                        cash-label="Confirm Cash Payment"
                        cash-processing-label="Confirming payment..."
                        cash-description="Enter the cash amount received from the patient."
                        @cash-pay="handleCashPay"
                    />
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl shadow-sm ring-1 ring-black/5 bg-white p-6 text-center text-sm text-muted xl:sticky xl:top-6 print:hidden"
            >
                All invoices for this patient have been fully paid.
            </div>
        </div>

        <div
            v-else
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-muted"
        >
            No data found for this patient.
        </div>

        <Teleport to="body">
            <div
                v-if="refundModalOpen"
                class="fixed inset-0 bg-secondary/50 backdrop-blur-sm flex items-center justify-center z-50 p-4 no-print"
                @click.self="closeRefundModal"
            >
                <div
                    class="bg-white rounded-2xl shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-black/5 w-full max-w-sm p-6"
                >
                    <h3 class="text-base font-semibold text-secondary">
                        Refund Amount
                    </h3>

                    <p class="text-xs text-muted mt-1 mb-4">
                        Refundable amount: ₱{{ formatMoney(refundableAmount) }}
                    </p>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-xs font-medium text-muted">
                            Refund Amount
                        </span>

                        <input
                            v-model="refundAmount"
                            type="number"
                            min="0"
                            :max="refundableAmount"
                            step="0.01"
                            placeholder="0.00"
                            class="rounded-lg border border-primary-100 px-3 py-2 text-sm text-secondary focus:outline-none focus:ring-2 focus:ring-accent-500/30 focus:border-accent-500"
                        />
                    </label>

                    <p
                        v-if="hasProcessingRefund"
                        class="mt-2 text-xs text-accent-700"
                    >
                        ₱{{
                            formatMoney(summary?.total_refund_processing ?? 0)
                        }}
                        is already being processed for refund.
                    </p>

                    <label class="flex flex-col gap-1.5 mt-4">
                        <span class="text-xs font-medium text-muted">
                            Reason (optional)
                        </span>

                        <textarea
                            v-model="refundReason"
                            rows="2"
                            placeholder="e.g. overpayment, cancelled service"
                            class="rounded-lg border border-primary-100 px-3 py-2 text-sm text-secondary focus:outline-none focus:ring-2 focus:ring-accent-500/30 focus:border-accent-500 resize-none"
                        />
                    </label>

                    <p v-if="refundError" class="text-xs text-danger mt-3">
                        {{ refundError }}
                    </p>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-muted hover:text-secondary transition"
                            @click="closeRefundModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="!isRefundAmountValid || processingRefund"
                            class="rounded-lg bg-danger px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            @click="submitRefund"
                        >
                            {{
                                processingRefund
                                    ? "Refunding..."
                                    : "Confirm Refund"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted, h } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Stethoscope } from "lucide-vue-next";
import { invoiceService } from "~/api/invoice/InvoiceService";
import type {
    PatientInvoiceSummary,
    PatientInvoiceItem,
} from "~/types/invoice";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { useToast } from "~/composables/useToast";
import { calculateAge } from "~/utils/user";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Patient Invoices",
});

const route = useRoute();
const router = useRouter();

const uuid = computed(() => route.params.uuid as string);
const patientUuid = computed(() => route.params.p_uuid as string);

const summary = ref<PatientInvoiceSummary | null>(null);
const loading = ref(true);
const errors = ref("");
const { success, error } = useToast();
const processingPayment = ref(false);

const showPayment = computed(() => {
    return !!summary.value && Number(summary.value.total_balance ?? 0) > 0;
});

const hasRefund = computed(() => {
    return Number(summary.value?.total_refunded ?? 0) > 0;
});

const hasProcessingRefund = computed(() => {
    return Number(summary.value?.total_refund_processing ?? 0) > 0;
});

const refundableAmount = computed(() => {
    if (!summary.value) return 0;

    return Number(summary.value.total_refund_processing ?? 0);
});

const canRefund = computed(() => {
    return refundableAmount.value > 0;
});

const gridColsClass = computed(() => {
    const cols =
        3 + (hasRefund.value ? 1 : 0) + (hasProcessingRefund.value ? 1 : 0);

    return {
        "grid-cols-3": cols === 3,
        "grid-cols-4": cols === 4,
        "grid-cols-5": cols === 5,
    };
});

const admissionInvoices = computed(() => {
    return (summary.value?.invoices ?? []).filter((invoice) => {
        return (
            Array.isArray(invoice.facilities) && invoice.facilities.length > 0
        );
    });
});

const serviceInvoices = computed(() => {
    return (summary.value?.invoices ?? []).filter((invoice) => {
        return (
            (!Array.isArray(invoice.facilities) ||
                invoice.facilities.length === 0) &&
            Array.isArray(invoice.services) &&
            invoice.services.length > 0
        );
    });
});

function invoiceRefundAmount(inv: PatientInvoiceItem) {
    return (
        Number(inv.refunded_amount ?? 0) +
        Number(inv.refund_processing_amount ?? 0)
    );
}

const refundModalOpen = ref(false);
const refundAmount = ref("");
const refundReason = ref("");
const refundError = ref("");
const processingRefund = ref(false);

const isRefundAmountValid = computed(() => {
    const amount = Number(refundAmount.value);

    if (!refundAmount.value || Number.isNaN(amount) || amount <= 0) {
        return false;
    }

    return amount <= refundableAmount.value;
});

function openRefundModal() {
    if (!canRefund.value) return;

    refundAmount.value = "";
    refundReason.value = "";
    refundError.value = "";
    refundModalOpen.value = true;
}

function closeRefundModal() {
    if (processingRefund.value) return;

    refundModalOpen.value = false;
}

async function submitRefund() {
    if (!summary.value || !isRefundAmountValid.value) return;

    processingRefund.value = true;
    refundError.value = "";

    try {
        refundModalOpen.value = false;

        error("NOT YET SETUP");
        // await fetchSummary();
    } catch (err: any) {
        refundError.value =
            err?.data?.message ??
            err?.response?.data?.message ??
            err?.message ??
            "Refund failed. Please try again.";
    } finally {
        processingRefund.value = false;
    }
}

async function fetchSummary() {
    loading.value = true;
    errors.value = "";

    try {
        const response = await invoiceService.show(
            {
                branch_uuid: uuid.value,
                p_uuid: patientUuid.value,
                mode: route.query.mode,
            },
            patientUuid.value,
        );

        summary.value = response.data ?? response ?? null;
    } catch (err) {
        console.error(err);
        errors.value = "Unable to load invoices for this patient.";
    } finally {
        loading.value = false;
    }
}

async function handleCashPay(cash: number) {
    if (!summary.value) return;

    processingPayment.value = true;
    errors.value = "";

    try {
        const res = await invoiceService.create({
            cash,
            mode: "patient",
            payment_method: "CASH",
            p_uuid: patientUuid.value,
            branch_uuid: uuid.value,
        });

        success(res.message);

        await fetchSummary();
    } catch (err) {
        console.error(err);
        errors.value = "Payment failed. Please try again.";
    } finally {
        processingPayment.value = false;
    }
}

function handlePrint() {
    if (typeof window !== "undefined") {
        window.print();
    }
}

function goBack() {
    router.back();
}

function viewInvoice(invoiceCode: string) {
    router.push({
        path: `/app/branches/${uuid.value}/invoices/${invoiceCode}`,
        query: {
            mode: "invoice",
        },
    });
}

function statusClasses(status: string) {
    const normalized = (status ?? "").toLowerCase();

    if (normalized === "paid") {
        return "bg-primary-50 text-primary-700";
    }

    if (normalized === "partial") {
        return "bg-accent-50 text-accent-700";
    }

    if (normalized === "overdue") {
        return "bg-danger/10 text-danger";
    }

    return "bg-primary-50 text-primary-700";
}

function formatMoney(amount: number | string | null | undefined) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
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
    fetchSummary();
});

const Field = (fieldProps: { label: string; value: any }, { slots }: any) =>
    h("p", { class: "flex flex-col gap-0.5" }, [
        h("span", { class: "text-xs text-muted" }, fieldProps.label),
        h(
            "span",
            { class: "text-secondary font-medium" },
            slots.value ? slots.value() : (fieldProps.value ?? "—"),
        ),
    ]);

Field.props = ["label", "value"];

const SectionHeader = (_props: unknown, { slots }: any) =>
    h(
        "h3",
        {
            class: "flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-accent-600 mb-4",
        },
        [slots.icon?.(), slots.default?.()],
    );
</script>

<style scoped>
@media print {
    :global(html),
    :global(body) {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    .no-print,
    .print-hidden,
    .print\:hidden {
        display: none !important;
    }

    .rounded-2xl {
        border-radius: 0 !important;
    }

    .shadow-sm,
    .shadow,
    .shadow-md {
        box-shadow: none !important;
    }

    .ring-1,
    .ring-black\/5,
    .ring {
        box-shadow: none !important;
    }

    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>
