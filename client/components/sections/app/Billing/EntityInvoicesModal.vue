<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="emit('close')"
            />

            <div
                class="relative z-10 flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-[13px] font-semibold text-gray-400 dark:text-gray-500"
                        >
                            {{ eyebrow }} invoices and payments
                        </p>

                        <h2
                            class="mt-0.5 truncate text-xl font-semibold text-gray-900 dark:text-white"
                        >
                            {{ title }}
                        </h2>

                        <p
                            v-if="subtitle"
                            class="mt-0.5 text-[13px] text-muted dark:text-gray-400"
                        >
                            {{ subtitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <div
                    v-if="!loading && invoices.length"
                    class="shrink-0 border-b border-gray-100 px-6 py-3 dark:border-white/10"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                        />

                        <input
                            v-model="query"
                            type="search"
                            placeholder="Search invoice code, description, receipt or method…"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-[13px] text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-white/10 dark:bg-secondary dark:text-white"
                        />
                    </div>
                </div>

                <SectionLoader v-if="loading" label="Loading invoices…" />

                <p
                    v-else-if="!invoices.length"
                    class="px-6 py-12 text-center text-[13px] text-muted dark:text-gray-400"
                >
                    No invoices for this record.
                </p>

                <div
                    v-else
                    class="grid min-h-0 flex-1 grid-cols-1 divide-y divide-gray-100 overflow-hidden md:grid-cols-[1.5fr_1fr] md:divide-x md:divide-y-0 dark:divide-white/10"
                >
                    <!-- INVOICES -->
                    <section class="flex min-h-0 flex-col">
                        <p
                            class="shrink-0 px-6 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                        >
                            Invoices
                        </p>

                        <p
                            v-if="!visibleInvoices.length"
                            class="px-6 py-8 text-center text-[13px] text-muted dark:text-gray-400"
                        >
                            No invoices match “{{ query }}”.
                        </p>

                        <ul
                            v-else
                            class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                        >
                            <li
                                v-for="invoice in visibleInvoices"
                                :key="invoice.invoice_id"
                                class="px-6 py-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <button
                                                type="button"
                                                class="font-mono text-[15px] font-semibold text-primary-700 hover:underline dark:text-primary-300"
                                                @click="
                                                    emit(
                                                        'view-invoice',
                                                        invoice.invoice_code,
                                                    )
                                                "
                                            >
                                                {{ invoice.invoice_code }}
                                            </button>

                                            <span
                                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
                                                :class="
                                                    statusClasses(
                                                        invoice.status,
                                                    )
                                                "
                                            >
                                                {{ invoice.status }}
                                            </span>
                                        </div>

                                        <p
                                            v-if="invoice.description"
                                            class="mt-1 text-[13px] text-muted dark:text-gray-400"
                                        >
                                            {{ invoice.description }}
                                            <template
                                                v-if="adlHoursBooked(invoice)"
                                            >
                                                ·
                                                {{
                                                    formatDuration(
                                                        adlHoursBooked(
                                                            invoice,
                                                        ) as number,
                                                    )
                                                }}
                                                booked
                                            </template>
                                        </p>

                                        <p
                                            class="mt-1 text-[12px] text-gray-400 dark:text-gray-500"
                                        >
                                            {{ formatDate(invoice.created_at) }}
                                        </p>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-[13px] font-semibold text-secondary dark:text-white"
                                        >
                                            ₱{{ formatMoney(invoice.total) }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-[12px]"
                                            :class="
                                                Number(invoice.balance_due) > 0
                                                    ? 'font-semibold text-danger'
                                                    : 'text-gray-400 dark:text-gray-500'
                                            "
                                        >
                                            {{
                                                Number(invoice.balance_due) > 0
                                                    ? `₱${formatMoney(
                                                          invoice.balance_due,
                                                      )} due`
                                                    : Number(
                                                            invoice.written_off_amount ??
                                                                0,
                                                        ) > 0
                                                      ? `₱${formatMoney(invoice.written_off_amount)} written off`
                                                      : "Settled"
                                            }}
                                        </p>

                                        <div
                                            class="mt-2 flex items-center justify-between gap-2"
                                        >
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <button
                                                    v-if="!isClosedStatus(invoice)"
                                                    type="button"
                                                    class="rounded-lg border border-primary/30 px-3 py-1.5 text-[12px] font-semibold text-primary transition hover:bg-primary/10"
                                                    @click="
                                                        emit(
                                                            'adjust-invoice',
                                                            invoice,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        isPaidStatus(invoice)
                                                            ? "Issue Refund/Credit"
                                                            : "Adjust"
                                                    }}
                                                </button>

                                                <button
                                                    v-if="
                                                        !isClosedStatus(
                                                            invoice,
                                                        ) &&
                                                        !isPaidStatus(invoice)
                                                    "
                                                    type="button"
                                                    class="rounded-lg border border-danger/30 px-3 py-1.5 text-[12px] font-semibold text-danger transition hover:bg-danger/10"
                                                    @click="
                                                        emit(
                                                            'void-invoice',
                                                            invoice,
                                                        )
                                                    "
                                                >
                                                    Void
                                                </button>

                                                <button
                                                    v-if="
                                                        !isClosedStatus(
                                                            invoice,
                                                        ) &&
                                                        Number(
                                                            invoice.balance_due,
                                                        ) > 0
                                                    "
                                                    type="button"
                                                    class="rounded-lg border border-amber-500/40 px-3 py-1.5 text-[12px] font-semibold text-amber-600 transition hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-500/10"
                                                    @click="
                                                        emit(
                                                            'write-off-invoice',
                                                            invoice,
                                                        )
                                                    "
                                                >
                                                    Write off
                                                </button>
                                            </div>

                                            <button
                                                v-if="
                                                    Number(
                                                        invoice.balance_due,
                                                    ) > 0
                                                "
                                                type="button"
                                                class="rounded-lg bg-primary px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-primary-600"
                                                @click="
                                                    emit('pay-invoice', invoice)
                                                "
                                            >
                                                Pay
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <!-- PAYMENTS -->
                    <section class="flex min-h-0 flex-col">
                        <p
                            class="shrink-0 px-6 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                        >
                            Payments
                        </p>

                        <p
                            v-if="!entries.length"
                            class="px-6 py-8 text-center text-[13px] text-muted dark:text-gray-400"
                        >
                            {{
                                term
                                    ? `No payments match “${query}”.`
                                    : "Nothing paid yet."
                            }}
                        </p>

                        <ul
                            v-else
                            class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                        >
                            <li v-for="entry in entries" :key="entry.key">
                                <component
                                    :is="entry.payment_code ? 'button' : 'div'"
                                    :type="
                                        entry.payment_code
                                            ? 'button'
                                            : undefined
                                    "
                                    class="flex w-full items-start justify-between gap-3 px-6 py-3.5 text-left transition"
                                    :class="
                                        entry.payment_code
                                            ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5'
                                            : ''
                                    "
                                    @click="
                                        entry.payment_code &&
                                        emit('view-receipt', entry.payment_code)
                                    "
                                >
                                    <span class="min-w-0">
                                        <span
                                            class="flex items-center gap-1.5"
                                            :class="
                                                entry.payment_code
                                                    ? 'font-mono text-[13px] font-semibold text-primary-700 dark:text-primary-300'
                                                    : 'text-[13px] text-muted dark:text-gray-400'
                                            "
                                        >
                                            <Loader2
                                                v-if="
                                                    loadingReceipt ===
                                                    entry.payment_code
                                                "
                                                class="h-3.5 w-3.5 animate-spin"
                                            />

                                            {{
                                                entry.payment_code ??
                                                entry.label
                                            }}
                                        </span>

                                        <span
                                            class="mt-0.5 block truncate text-[12px] text-gray-400 dark:text-gray-500"
                                        >
                                            {{ entry.detail }}
                                        </span>

                                        <span
                                            class="block text-[12px] text-gray-400 dark:text-gray-500"
                                        >
                                            {{ formatDate(entry.created_at) }}
                                        </span>

                                        <span
                                            v-if="entry.payment_code"
                                            class="mt-1 block text-[12px] font-medium text-primary hover:underline dark:text-primary-300"
                                        >
                                            View receipt →
                                        </span>
                                    </span>

                                    <span
                                        class="shrink-0 text-[13px] font-semibold"
                                        :class="entry.tone"
                                    >
                                        {{ entry.sign }}₱{{
                                            formatMoney(Math.abs(entry.amount))
                                        }}
                                    </span>
                                </component>
                            </li>
                        </ul>
                    </section>
                </div>

                <div
                    v-if="invoices.length"
                    class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p class="text-[13px] text-muted dark:text-gray-400">
                            {{
                                term
                                    ? "Outstanding on the matches"
                                    : "Outstanding on these invoices"
                            }}
                        </p>

                        <p
                            class="text-lg font-bold text-secondary dark:text-white"
                        >
                            ₱{{ formatMoney(outstanding) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 rounded-xl bg-primary px-5 py-2.5 text-[15px] font-semibold text-white transition hover:bg-primary-600"
                        @click="emit('close')"
                    >
                        Done
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Loader2, Search, X } from "lucide-vue-next";

import SectionLoader from "./SectionLoader.vue";
import { formatAmount } from "~/utils/currency";
import { statusClasses } from "~/utils/invoiceStatus";
import { formatDate, formatDuration } from "~/utils/time";
import type { PatientInvoiceItem } from "~/types/invoice";

const props = defineProps<{
    open: boolean;
    eyebrow: string;
    title: string;
    subtitle?: string | null;
    invoices: PatientInvoiceItem[];
    loading: boolean;
    // Receipt number currently being fetched, so its row can show a spinner.
    loadingReceipt?: string | null;
}>();

const emit = defineEmits<{
    (event: "view-invoice", invoiceCode: string): void;
    (event: "view-receipt", receiptNo: string | null | undefined): void;
    (event: "pay-invoice", invoice: PatientInvoiceItem): void;
    (event: "adjust-invoice", invoice: PatientInvoiceItem): void;
    (event: "void-invoice", invoice: PatientInvoiceItem): void;
    (event: "write-off-invoice", invoice: PatientInvoiceItem): void;
    (event: "close"): void;
}>();

function isClosedStatus(invoice: PatientInvoiceItem) {
    const status = (invoice.status ?? "").toLowerCase();

    return status === "void" || status === "written off" || status === "written_off";
}

function isPaidStatus(invoice: PatientInvoiceItem) {
    return (invoice.status ?? "").toLowerCase() === "paid";
}

function adlHoursBooked(invoice: PatientInvoiceItem) {
    const line = invoice.services?.find((s) => s.type === "ADL");

    return line?.hours_booked ?? null;
}

const query = ref("");

// A fresh record starts with a clean search rather than the last one's.
watch(
    () => props.open,
    (open) => {
        if (!open) query.value = "";
    },
);

const term = computed(() => query.value.trim().toLowerCase());

function matches(...fields: (string | null | undefined)[]) {
    if (!term.value) return true;

    return fields.some((field) =>
        String(field ?? "")
            .toLowerCase()
            .includes(term.value),
    );
}

const visibleInvoices = computed(() =>
    props.invoices.filter((invoice) =>
        matches(invoice.invoice_code, invoice.description, invoice.status),
    ),
);

const outstanding = computed(() =>
    visibleInvoices.value.reduce(
        (total, invoice) => total + Number(invoice.balance_due ?? 0),
        0,
    ),
);

// One column for every payment and refund across these invoices, newest first,
// rather than the same list repeated inside each invoice row.
const entries = computed(() =>
    allEntries.value.filter((entry) =>
        matches(
            entry.payment_code,
            entry.label,
            entry.invoice_code,
            entry.method,
        ),
    ),
);

const allEntries = computed(() =>
    props.invoices
        .flatMap((invoice) =>
            (invoice.payments ?? []).flatMap((payment) => {
                const amount = Number(payment.amount ?? 0);

                const paid = {
                    key: `p-${payment.allocation_id ?? payment.payment_id}-${invoice.invoice_id}`,
                    payment_code: payment.payment_code ?? null,
                    label: amount < 0 ? "Credit moved out" : "No receipt",
                    invoice_code: invoice.invoice_code,
                    method: payment.payment_method ?? "—",
                    detail: [invoice.invoice_code, payment.payment_method ?? "—"]
                        .filter(Boolean)
                        .join(" · "),
                    created_at: payment.created_at,
                    amount,
                    sign: amount < 0 ? "−" : "",
                    tone:
                        amount < 0
                            ? "text-slate-400 dark:text-gray-500"
                            : "text-primary-700 dark:text-primary-300",
                };

                // Until a withdrawal claims it the credit has no status of its
                // own; it simply sits on the account. Once one does, the row is
                // about the withdrawal, which draws on the account rather than
                // on this bill — so the invoice and the credit note behind it
                // stay with the credit.
                const refunds = (payment.refunds ?? []).map((refund) => {
                    const withdrawn = !!refund.status;

                    return {
                        key: `r-${refund.refund_id}`,
                        payment_code: null,
                        label: withdrawn
                            ? `Withdrawal · ${refund.status}`
                            : "Credit issued",
                        invoice_code: withdrawn ? null : invoice.invoice_code,
                        method: refund.refund_method ?? "—",
                        detail: withdrawn
                            ? (refund.refund_method ?? "—")
                            : [invoice.invoice_code, refund.reason]
                                  .filter(Boolean)
                                  .join(" · "),
                        created_at: refund.created_at,
                        amount: Number(refund.amount ?? 0),
                        sign: "−",
                        tone: "text-accent-700 dark:text-accent-300",
                    };
                });

                return [paid, ...refunds];
            }),
        )
        .sort(
            (a, b) =>
                new Date(b.created_at ?? 0).getTime() -
                new Date(a.created_at ?? 0).getTime(),
        ),
);

function formatMoney(value: number | string | null | undefined) {
    return formatAmount(value, { treatMissingAsZero: true });
}
</script>
