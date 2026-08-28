<template>
    <section>
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-secondary">
                    Patient Admissions
                </h2>

                <p class="mt-1 text-xs text-muted">
                    Current admissions are shown first.
                </p>
            </div>

            <span
                class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700"
            >
                {{ admissions.length }}
            </span>
        </div>

        <div v-if="admissions.length" class="space-y-4">
            <article
                v-for="admission in admissions"
                :key="admission.patient_admission_id"
                class="overflow-hidden rounded-2xl border transition"
                :class="
                    isCurrentAdmission(admission)
                        ? 'border-primary-300 bg-primary-50/20 shadow-sm'
                        : 'border-primary-100 bg-white'
                "
            >
                <div
                    v-if="isCurrentAdmission(admission)"
                    class="flex items-center justify-between gap-3 bg-primary-600 px-5 py-2.5 text-white"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="h-2 w-2 animate-pulse rounded-full bg-white"
                        />

                        <span
                            class="text-[10px] font-bold uppercase tracking-[0.15em]"
                        >
                            Current Admission
                        </span>
                    </div>

                    <span class="text-[10px] opacity-80"> Active </span>
                </div>

                <div class="p-5">
                    <div
                        class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-medium capitalize"
                                    :class="statusClasses(admission.status)"
                                >
                                    {{ admission.status }}
                                </span>
                            </div>

                            <div
                                class="mt-5 grid grid-cols-2 gap-x-5 gap-y-5 sm:grid-cols-4"
                            >
                                <Field
                                    label="Admission Date"
                                    :value="
                                        formatDate(admission.admission_date)
                                    "
                                />

                                <Field
                                    label="Discharge Date"
                                    :value="
                                        formatDate(admission.discharge_date)
                                    "
                                />

                                <Field
                                    label="Room"
                                    :value="admission.room?.room_no"
                                />

                                <Field
                                    label="Bed"
                                    :value="admission.bed?.bed_no"
                                />
                            </div>
                        </div>

                        <div
                            class="shrink-0 rounded-xl bg-primary-50 px-4 py-3 lg:min-w-[155px]"
                        >
                            <p
                                class="text-[10px] uppercase tracking-[0.14em] text-primary-600"
                            >
                                Admission Total
                            </p>

                            <p class="mt-1 text-lg font-bold text-primary-800">
                                ₱{{ formatMoney(admissionTotal(admission)) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="canViewDischarge(admission)"
                        class="mt-5 flex justify-end"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-danger/90"
                            @click="
                                emit(
                                    'view-discharge-termination',
                                    admission.patient_admission_id,
                                )
                            "
                        >
                            View Discharge
                        </button>
                    </div>

                    <div
                        v-if="admission.invoices?.length"
                        class="mt-6 border-t border-primary-100 pt-5"
                    >
                        <div
                            class="mb-3 flex items-center justify-between gap-3"
                        >
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted"
                            >
                                Admission Invoices
                            </p>

                            <span class="text-[11px] text-muted">
                                {{ admission.invoices.length }}
                                invoice(s)
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="invoice in admission.invoices"
                                :key="invoice.invoice_id"
                                class="overflow-hidden rounded-xl border border-primary-100 bg-white"
                            >
                                <button
                                    type="button"
                                    class="group w-full p-4 text-left transition hover:bg-primary-50/30"
                                    @click="
                                        emit(
                                            'view-invoice',
                                            invoice.invoice_code,
                                        )
                                    "
                                >
                                    <div
                                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <span
                                                    class="font-mono text-xs font-semibold text-primary-700"
                                                >
                                                    {{ invoice.invoice_code }}
                                                </span>

                                                <span
                                                    class="rounded-full px-2 py-1 text-[10px] font-medium"
                                                    :class="
                                                        statusClasses(
                                                            invoice.status,
                                                        )
                                                    "
                                                >
                                                    {{ invoice.status }}
                                                </span>

                                                <span
                                                    v-if="hasRefund(invoice)"
                                                    class="rounded-full bg-danger/10 px-2 py-1 text-[10px] font-medium text-danger"
                                                >
                                                    Refunded
                                                </span>
                                            </div>

                                            <p
                                                class="mt-1 text-[11px] text-muted"
                                            >
                                                {{
                                                    formatDate(
                                                        invoice.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="grid grid-cols-3 gap-5 sm:flex sm:items-center"
                                        >
                                            <div>
                                                <p
                                                    class="text-[10px] text-muted"
                                                >
                                                    Total
                                                </p>

                                                <p
                                                    class="mt-1 text-xs font-semibold text-secondary"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            invoice.total,
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-[10px] text-muted"
                                                >
                                                    Paid
                                                </p>

                                                <p
                                                    class="mt-1 text-xs font-semibold text-primary-700"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            invoice.amount_paid,
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-[10px] text-muted"
                                                >
                                                    Balance
                                                </p>

                                                <p
                                                    class="mt-1 text-xs font-semibold"
                                                    :class="
                                                        Number(
                                                            invoice.balance_due,
                                                        ) > 0
                                                            ? 'text-danger'
                                                            : 'text-primary-700'
                                                    "
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            invoice.balance_due,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </button>

                                <div
                                    v-if="hasRefund(invoice)"
                                    class="border-t border-danger/10 bg-danger/5 px-4 py-4"
                                >
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-danger/10 text-danger"
                                        >
                                            ↩
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-danger"
                                            >
                                                Refund Reason
                                            </p>

                                            <p
                                                class="mt-1 text-xs leading-5 text-secondary"
                                            >
                                                {{ refundReason(invoice) }}
                                            </p>

                                            <div
                                                v-if="refundAmount(invoice) > 0"
                                                class="mt-2 text-[11px] text-muted"
                                            >
                                                Refunded:
                                                <span
                                                    class="font-semibold text-danger"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            refundAmount(
                                                                invoice,
                                                            ),
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="mt-5 rounded-xl border border-dashed border-primary-100 px-4 py-5 text-center"
                    >
                        <p class="text-xs text-muted">
                            No invoices for this admission.
                        </p>
                    </div>
                </div>
            </article>
        </div>

        <EmptyState
            v-else
            title="No admissions found"
            description="This patient does not have any recorded admissions."
        />
    </section>
</template>

<script setup lang="ts">
import { formatAmount } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import type { DischargeCalculation, PatientAdmission } from "~/types/invoice";

const props = defineProps<{
    admissions: PatientAdmission[];
    dischargeCalculation?: DischargeCalculation | null;
}>();

const emit = defineEmits<{
    (event: "view-invoice", invoiceCode: string): void;
    (event: "view-discharge-termination", admissionId: number): void;
}>();

function isCurrentAdmission(admission: PatientAdmission) {
    return admission.status?.toLowerCase() === "admitted";
}

function canViewDischarge(admission: PatientAdmission) {
    const calculation = props.dischargeCalculation;

    if (!isCurrentAdmission(admission) || !calculation) {
        return false;
    }

    if (calculation.admission_id !== admission.patient_admission_id) {
        return false;
    }

    return (
        calculation.is_within_termination_fee_window === true ||
        calculation.is_within_yearly_half_refund_window === true
    );
}

function admissionTotal(admission: PatientAdmission) {
    return (admission.invoices ?? []).reduce(
        (total, invoice) => total + Number(invoice.total ?? 0),
        0,
    );
}

function getRefunds(invoice: any) {
    return (invoice.payments ?? []).flatMap(
        (payment: any) => payment.refunds ?? [],
    );
}

function hasRefund(invoice: any) {
    return getRefunds(invoice).some((refund: any) =>
        ["completed", "processing"].includes(refund.status?.toLowerCase()),
    );
}

function refundReason(invoice: any) {
    const refunds = getRefunds(invoice).filter((refund: any) =>
        ["completed", "processing"].includes(refund.status?.toLowerCase()),
    );

    return (
        refunds
            .map((refund: any) => refund.reason)
            .filter(Boolean)
            .join(" • ") || "No refund reason provided."
    );
}

function refundAmount(invoice: any) {
    return getRefunds(invoice)
        .filter((refund: any) =>
            ["completed", "processing"].includes(refund.status?.toLowerCase()),
        )
        .reduce(
            (total: number, refund: any) => total + Number(refund.amount ?? 0),
            0,
        );
}

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}


function statusClasses(status: string | null | undefined) {
    switch (status?.toLowerCase()) {
        case "paid":
            return "bg-primary-50 text-primary-700";

        case "partial":
            return "bg-accent-50 text-accent-700";

        case "pending":
            return "bg-slate-100 text-slate-600";

        case "admitted":
            return "bg-primary-50 text-primary-700";

        case "discharged":
            return "bg-slate-100 text-slate-600";

        case "overdue":
            return "bg-danger/10 text-danger";

        case "cancelled":
            return "bg-danger/10 text-danger";

        default:
            return "bg-slate-100 text-slate-600";
    }
}

const Field = (props: { label: string; value: unknown }) =>
    h(
        "div",
        {
            class: "flex min-w-0 flex-col gap-0.5",
        },
        [
            h(
                "span",
                {
                    class: "truncate text-[10px] font-semibold uppercase tracking-[0.11em] text-muted",
                },
                props.label,
            ),
            h(
                "span",
                {
                    class: "truncate text-sm font-medium text-secondary",
                },
                String(props.value ?? "—"),
            ),
        ],
    );

const EmptyState = (props: { title: string; description: string }) =>
    h(
        "div",
        {
            class: "rounded-xl border border-dashed border-primary-100 px-6 py-10 text-center",
        },
        [
            h(
                "p",
                {
                    class: "text-sm font-semibold text-secondary",
                },
                props.title,
            ),
            h(
                "p",
                {
                    class: "mt-1 text-xs text-muted",
                },
                props.description,
            ),
        ],
    );
</script>

<script lang="ts">
import { h } from "vue";

export default {};
</script>
