<template>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-5 pb-8">
        <div class="flex flex-wrap items-center justify-between gap-3 no-print">
            <button
                type="button"
                @click="goBack"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-[#6B8A87] hover:text-[#16302E] transition"
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
                class="inline-flex items-center gap-2 rounded-full border border-[#DDECEC] bg-white px-4 py-2 text-sm font-medium text-[#0E7C7B] shadow-sm transition hover:border-[#0E7C7B] hover:text-[#0A5A58]"
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
            class="hidden print:block rounded-2xl border border-[#EDF4F3] bg-white p-5"
        >
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-[#16302E]"
            >
                <div>
                    <p
                        class="font-semibold uppercase tracking-[0.2em] text-[#6B8A87] text-[11px]"
                    >
                        Patient
                    </p>
                    <p class="mt-1 text-base font-semibold">
                        {{ summary?.patient?.full_name ?? "—" }}
                    </p>
                    <p class="text-[#6B8A87]">
                        {{ summary?.patient?.patient_uuid ?? "—" }}
                    </p>
                </div>
                <div class="text-right">
                    <p
                        class="font-semibold uppercase tracking-[0.2em] text-[#6B8A87] text-[11px]"
                    >
                        Invoices
                    </p>
                    <p class="mt-1 text-base font-semibold">
                        {{ summary?.invoice_count ?? 0 }}
                    </p>
                    <p class="text-[#6B8A87]">
                        Balance ₱{{ formatMoney(summary?.total_balance ?? 0) }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="loading"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#6B8A87]"
        >
            Loading patient invoices…
        </div>

        <div
            v-else-if="error"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#B3402F]"
        >
            {{ error }}
        </div>

        <div
            v-else-if="summary"
            class="grid grid-cols-1 xl:grid-cols-[1fr_500px] gap-5 items-start"
        >
            <div
                class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 overflow-hidden"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 px-7 py-6 border-b border-[#EDF4F3] bg-gradient-to-b from-[#0E7C7B]/[0.04] to-transparent"
                >
                    <div class="min-w-0">
                        <span
                            class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] inline-block mb-2"
                        >
                            {{ summary.patient?.patient_uuid }}
                        </span>

                        <h2
                            class="text-lg font-semibold text-[#16302E] truncate"
                        >
                            {{ summary.patient?.full_name ?? "—" }}
                        </h2>
                        <p class="text-sm text-[#6B8A87] truncate">
                            {{ summary.invoice_count }} invoice(s) on record
                        </p>
                    </div>

                    <span
                        class="px-3 py-1 rounded-full text-xs font-medium capitalize shrink-0"
                        :class="statusClasses(summary.status)"
                    >
                        {{ summary.status }}
                    </span>
                </div>

                <!-- Billing summary highlight -->
                <div
                    class="grid grid-cols-3 divide-x divide-[#EDF4F3] border-b border-[#EDF4F3] bg-[#FAFDFC]"
                >
                    <div class="px-7 py-5">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono mb-1"
                        >
                            Total Amount
                        </p>
                        <p class="text-2xl font-bold text-[#16302E]">
                            ₱{{ formatMoney(summary.total_amount) }}
                        </p>
                    </div>

                    <div class="px-7 py-5 bg-[#E4F4EE]/40">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#1F7A4D]/80 font-mono mb-1"
                        >
                            Total Paid
                        </p>
                        <p class="text-2xl font-bold text-[#1F7A4D]">
                            ₱{{ formatMoney(summary.total_paid) }}
                        </p>
                    </div>

                    <div class="px-7 py-5 bg-[#FBE8E6]/40">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#B3402F]/80 font-mono mb-1"
                        >
                            Total Balance
                        </p>
                        <p class="text-2xl font-bold text-[#B3402F]">
                            ₱{{ formatMoney(summary.total_balance) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="summary.patient"
                    class="px-7 py-6 border-b border-[#EDF4F3]"
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

                <div
                    v-if="summary.latest_invoice"
                    class="px-7 py-6 border-b border-[#EDF4F3]"
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
                        Latest Invoice
                    </SectionHeader>

                    <div
                        class="rounded-xl border border-[#EDF4F3] px-5 py-4 cursor-pointer hover:border-[#0E7C7B]/40 transition"
                        @click="
                            viewInvoice(summary.latest_invoice.invoice_code)
                        "
                    >
                        <div
                            class="grid grid-cols-1 sm:grid-cols-4 gap-x-6 gap-y-4 text-sm"
                        >
                            <Field
                                label="Invoice"
                                :value="summary.latest_invoice.invoice_code"
                            />
                            <Field
                                label="Status"
                                :value="summary.latest_invoice.status"
                            />
                            <Field
                                label="Total"
                                :value="`₱${formatMoney(summary.latest_invoice.total)}`"
                            />
                            <Field
                                label="Date"
                                :value="
                                    formatDate(
                                        summary.latest_invoice.created_at,
                                    )
                                "
                            />
                        </div>
                    </div>
                </div>

                <div class="px-7 py-6">
                    <SectionHeader>
                        <template #icon>
                            <Stethoscope
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                        </template>
                        All Invoices
                    </SectionHeader>

                    <div v-if="summary.invoices?.length" class="space-y-3">
                        <div
                            v-for="inv in summary.invoices"
                            :key="inv.invoice_id"
                            class="rounded-xl border border-[#EDF4F3] px-5 py-4 cursor-pointer hover:border-[#0E7C7B]/40 transition"
                            @click="viewInvoice(inv.invoice_code)"
                        >
                            <div
                                class="grid grid-cols-1 sm:grid-cols-4 gap-x-6 gap-y-4 text-sm"
                            >
                                <Field
                                    label="Invoice"
                                    :value="inv.invoice_code"
                                />
                                <Field label="Status" :value="inv.status" />
                                <Field
                                    label="Total"
                                    :value="`₱${formatMoney(inv.total)}`"
                                />
                                <Field
                                    label="Date"
                                    :value="formatDate(inv.created_at)"
                                />
                            </div>
                        </div>
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
                        :description="`Outstanding balance: ₱${formatMoney(summary.total_balance)}`"
                        cash-label="Confirm Cash Payment"
                        cash-processing-label="Confirming payment..."
                        cash-description="Enter the cash amount received from the patient."
                        @cash-pay="handleCashPay"
                    />
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl shadow-sm ring-1 ring-black/5 bg-white p-6 text-center text-sm text-[#6B8A87] xl:sticky xl:top-6 print:hidden"
            >
                All invoices for this patient have been fully paid.
            </div>
        </div>

        <div
            v-else
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#6B8A87]"
        >
            No data found for this patient.
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted, h } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Stethoscope } from "lucide-vue-next";
import { invoiceService } from "~/api/invoice/InvoiceService";
import type { PatientInvoiceSummary } from "~/types/invoice";
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
const error = ref("");
const { success } = useToast();
const processingPayment = ref(false);
const showPayment = computed(() => {
    return !!summary.value && summary.value.total_balance > 0;
});

async function fetchSummary() {
    loading.value = true;
    error.value = "";

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
        error.value = "Unable to load invoices for this patient.";
    } finally {
        loading.value = false;
    }
}
async function handleCashPay(cash: number) {
    if (!summary.value) return;

    processingPayment.value = true;

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
        error.value = "Payment failed. Please try again.";
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

    if (normalized === "paid") return "bg-[#E4F4EE] text-[#1F7A4D]";
    if (normalized === "partial") return "bg-[#E6F1FA] text-[#2563A6]";
    if (normalized === "overdue") return "bg-[#FBE8E6] text-[#B3402F]";
    return "bg-[#FDF3DE] text-[#966B1F]";
}

function formatMoney(amount: number | string | null | undefined) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
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
        h("span", { class: "text-xs text-[#6B8A87]" }, fieldProps.label),
        h(
            "span",
            { class: "text-[#16302E] font-medium" },
            slots.value ? slots.value() : (fieldProps.value ?? "—"),
        ),
    ]);
Field.props = ["label", "value"];

const SectionHeader = (_props: unknown, { slots }: any) =>
    h(
        "h3",
        {
            class: "flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4",
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
