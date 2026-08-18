<template>
    <div
        class="rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] overflow-hidden"
    >
        <div class="border-b border-primary-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-primary-900">
                Admission Timeline
            </h3>

            <p class="text-xs text-muted mt-1">Complete admission history</p>
        </div>

        <div class="p-5">
            <div
                v-if="!allAdmissions.length"
                class="py-8 text-center text-sm text-slate-400"
            >
                No admission records.
            </div>

            <div v-else>
                <div class="relative">
                    <span
                        v-if="visibleAdmissions.length"
                        class="absolute left-[6px] top-2.5 bottom-2.5 w-0.5 -translate-x-1/2 bg-primary-100"
                    />

                    <div
                        v-for="(admission, idx) in visibleAdmissions"
                        :key="admission.patient_admission_id"
                        class="relative"
                        :class="
                            idx !== visibleAdmissions.length - 1 ? 'pb-6' : ''
                        "
                    >
                        <div class="flex items-start gap-3 mb-3">
                            <span
                                class="relative z-10 mt-1.5 h-3 w-3 shrink-0 rounded-full border-2 border-white"
                                :class="
                                    isCurrent(admission)
                                        ? 'bg-primary ring-4 ring-primary-100'
                                        : 'bg-slate-300'
                                "
                            />

                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-semibold text-primary-900"
                                        >
                                            Admission
                                        </p>

                                        <p
                                            class="text-[11px] text-muted mt-0.5"
                                        >
                                            {{
                                                formatDate(
                                                    admission.admitted_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 text-[12px] font-medium capitalize rounded-full px-2 py-1"
                                        :class="
                                            statusBadgeClass(admission.status)
                                        "
                                    >
                                        {{ admission.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="admission.invoices?.length"
                            class="ml-6 space-y-3"
                        >
                            <div
                                v-for="invoice in sortedInvoices(
                                    admission.invoices,
                                ).filter(isInvoiceVisible)"
                                :key="invoice.invoice_facility_id"
                                class="group relative"
                            >
                                <div
                                    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-200 hover:border-slate-300 hover:shadow-md"
                                >
                                    <div
                                        class="flex items-start justify-between gap-4 px-4 py-3.5"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex items-center gap-2 flex-wrap"
                                            >
                                                <span
                                                    class="text-[12px] font-semibold text-slate-800"
                                                >
                                                    {{
                                                        formatDate(
                                                            invoice.start_date,
                                                        )
                                                    }}
                                                </span>

                                                <span class="text-slate-300">
                                                    →
                                                </span>

                                                <span
                                                    class="text-[12px] font-semibold text-slate-800"
                                                >
                                                    {{
                                                        formatDate(
                                                            invoice.end_date,
                                                        )
                                                    }}
                                                </span>

                                                <span
                                                    v-if="
                                                        admission.status !==
                                                            'discharged' &&
                                                        admission
                                                            .current_invoice
                                                            ?.invoice_facility_id ===
                                                            invoice.invoice_facility_id
                                                    "
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-600/20"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                                    ></span>
                                                    Current
                                                </span>
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p
                                                class="text-sm font-bold tracking-tight text-slate-900"
                                            >
                                                {{
                                                    formatCurrency(
                                                        invoice.price,
                                                    )
                                                }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-[9px] uppercase tracking-wider text-slate-400"
                                            >
                                                Total
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="border-t border-slate-100 bg-slate-50/60 px-4 py-3"
                                    >
                                        <div
                                            class="grid grid-cols-3 gap-x-6 gap-y-3"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="text-[9px] font-medium uppercase tracking-wider text-slate-400"
                                                >
                                                    Accommodation
                                                </p>

                                                <p
                                                    class="mt-0.5 truncate text-[11px] font-semibold text-slate-700"
                                                >
                                                    {{
                                                        invoice.contract
                                                            ?.accommodation_type ??
                                                        admission
                                                            .current_contract
                                                            ?.accommodation_type ??
                                                        "—"
                                                    }}
                                                </p>
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="text-[9px] font-medium uppercase tracking-wider text-slate-400"
                                                >
                                                    Billing Cycle
                                                </p>

                                                <p
                                                    class="mt-0.5 truncate text-[11px] font-semibold text-slate-700"
                                                >
                                                    {{
                                                        invoice.contract
                                                            ?.billing_cycle ??
                                                        admission
                                                            .current_contract
                                                            ?.billing_cycle ??
                                                        "—"
                                                    }}
                                                </p>
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="text-[9px] font-medium uppercase tracking-wider text-slate-400"
                                                >
                                                    Payment Status
                                                </p>

                                                <span
                                                    class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide"
                                                    :class="
                                                        invoiceStatusClass(
                                                            invoice.status,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        invoice.status ===
                                                        "partial"
                                                            ? "Partially Paid"
                                                            : invoice.status
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="ml-6 rounded-xl bg-slate-50 border border-slate-100 px-4 py-4 text-center text-xs text-slate-400"
                        >
                            No invoices for this admission.
                        </div>
                    </div>
                </div>

                <div
                    v-if="canToggle"
                    class="mt-5 pt-4 border-t border-primary-100 flex justify-center"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-primary-100 bg-white px-4 py-2 text-xs font-medium text-primary hover:bg-primary-50 transition"
                        @click="toggleExpanded"
                    >
                        {{ expanded ? "Show less" : "Show more" }}

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="w-3.5 h-3.5 transition-transform"
                            :class="expanded ? 'rotate-180' : ''"
                        >
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Admission, InvoiceFacility } from "~/types/patient";
import { INVOICE_STATUS } from "~/types/invoice";

const props = defineProps<{
    admissions?: Admission[] | null;
}>();

const allAdmissions = computed(() => props.admissions ?? []);

const sortedAdmissions = computed(() =>
    [...allAdmissions.value].sort(
        (a, b) =>
            new Date(b.admitted_at).getTime() -
            new Date(a.admitted_at).getTime(),
    ),
);

const DEFAULT_VISIBLE_COUNT = 2;

const expanded = ref(false);

const visibleAdmissions = computed(() => sortedAdmissions.value);

const orderedInvoiceIds = computed(() => {
    const ids: number[] = [];

    for (const admission of sortedAdmissions.value) {
        for (const invoice of sortedInvoices(admission.invoices ?? [])) {
            ids.push(invoice.invoice_facility_id);
        }
    }

    return ids;
});

const visibleInvoiceIdSet = computed(() => {
    if (expanded.value) {
        return null;
    }

    return new Set(orderedInvoiceIds.value.slice(0, DEFAULT_VISIBLE_COUNT));
});

function isInvoiceVisible(invoice: InvoiceFacility) {
    return (
        visibleInvoiceIdSet.value === null ||
        visibleInvoiceIdSet.value.has(invoice.invoice_facility_id)
    );
}

const canToggle = computed(
    () => orderedInvoiceIds.value.length > DEFAULT_VISIBLE_COUNT,
);

function toggleExpanded() {
    expanded.value = !expanded.value;
}

function invoiceStatusClass(status?: string | null) {
    const normalized: Record<string, string> = {
        unpaid: "pending",
        voided: "void",
        canceled: "cancelled",
        partially_paid: "partial",
    };

    const s = (status ?? "").toLowerCase();
    const key = normalized[s] ?? s;

    return (
        INVOICE_STATUS[key] ?? // border-primary-100
        "bg-primary-50 text-primary-600"
    );
}

watch(
    () => props.admissions,
    () => {
        expanded.value = false;
    },
);

const currentAdmissionId = computed(() => {
    const active = sortedAdmissions.value.find(
        (a) => a.status === "admitted" || a.status === "waiting",
    );

    return active?.patient_admission_id ?? null;
});

function isCurrent(admission: Admission) {
    return admission.patient_admission_id === currentAdmissionId.value;
}

function sortedInvoices(invoices: InvoiceFacility[]) {
    return [...invoices].sort(
        (a, b) =>
            new Date(a.start_date ?? 0).getTime() -
            new Date(b.start_date ?? 0).getTime(),
    );
}

function formatDate(value?: string | null) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatCurrency(value?: string | number | null) {
    if (value === undefined || value === null || value === "") {
        return "—";
    }

    const num = Number(value);

    if (Number.isNaN(num)) {
        return String(value);
    }

    return num.toLocaleString(undefined, {
        style: "currency",
        currency: "PHP",
    });
}

function statusBadgeClass(status?: string) {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-emerald-100 text-emerald-700";

        case "waiting":
            return "bg-blue-100 text-blue-700";

        case "discharged":
        case "completed":
            return "bg-slate-100 text-slate-600";

        case "cancelled":
        case "rejected":
            return "bg-rose-100 text-rose-700";

        default:
            return "bg-primary-50 text-primary-600";
    }
}
</script>
