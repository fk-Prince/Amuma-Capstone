<template>
    <div
        class="min-h-screen-header w-full bg-slate-50 px-4 py-6 sm:px-6 lg:px-8 dark:bg-surface"
    >
        <div class="mx-auto space-y-5">
            <div
                class="flex flex-wrap items-center justify-between gap-3 no-print"
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-secondary dark:text-gray-400 dark:hover:text-white"
                    @click="goBack"
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
                    class="inline-flex items-center gap-2 rounded-xl border border-primary-100 bg-white px-4 py-2 text-sm font-medium text-primary-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 dark:border-primary-500/20 dark:bg-secondary dark:text-primary-300 dark:hover:bg-primary-500/10"
                    @click="handlePrint"
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
                v-if="loading"
                class="grid animate-pulse items-start gap-5 xl:grid-cols-[minmax(0,1fr)_500px]"
            >
                <div class="space-y-5">
                    <div
                        class="overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                    >
                        <div
                            class="space-y-3 border-b border-primary-100 px-6 py-7 dark:border-primary-500/20"
                        >
                            <div
                                class="h-4 w-40 rounded bg-primary-100/70 dark:bg-white/10"
                            />
                            <div
                                class="h-7 w-64 rounded bg-primary-100/70 dark:bg-white/10"
                            />
                            <div
                                class="h-3 w-48 rounded bg-primary-100/50 dark:bg-white/5"
                            />
                        </div>

                        <div
                            class="grid border-b border-primary-100 sm:grid-cols-2 lg:grid-cols-4 dark:border-primary-500/20"
                        >
                            <div
                                v-for="n in 4"
                                :key="n"
                                class="space-y-2 border-b border-primary-100 px-6 py-5 last:border-b-0 lg:border-b-0 lg:border-r lg:last:border-r-0 dark:border-primary-500/20"
                            >
                                <div
                                    class="h-2.5 w-20 rounded bg-primary-100/70 dark:bg-white/10"
                                />
                                <div
                                    class="h-6 w-28 rounded bg-primary-100/70 dark:bg-white/10"
                                />
                            </div>
                        </div>

                        <div class="space-y-3 px-6 py-6">
                            <div
                                v-for="n in 4"
                                :key="n"
                                class="h-12 rounded-xl bg-primary-50/70 dark:bg-white/5"
                            />
                        </div>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div
                            v-for="n in 2"
                            :key="n"
                            class="space-y-3 rounded-2xl border border-primary-100 bg-white p-6 shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                        >
                            <div
                                class="h-4 w-32 rounded bg-primary-100/70 dark:bg-white/10"
                            />

                            <div
                                v-for="row in 3"
                                :key="row"
                                class="h-10 rounded-lg bg-primary-50/70 dark:bg-white/5"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="space-y-4 rounded-2xl border border-primary-100 bg-white p-6 shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                >
                    <div
                        class="h-4 w-28 rounded bg-primary-100/70 dark:bg-white/10"
                    />
                    <div
                        class="h-8 w-40 rounded bg-primary-100/70 dark:bg-white/10"
                    />
                    <div
                        class="h-24 rounded-xl bg-primary-50/70 dark:bg-white/5"
                    />
                    <div
                        class="h-10 rounded-xl bg-primary-50/70 dark:bg-white/5"
                    />
                </div>
            </div>

            <div
                v-else-if="errors"
                class="rounded-2xl border border-danger/20 bg-white p-10 text-center shadow-sm dark:bg-secondary"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-danger/10 text-danger"
                >
                    <svg
                        class="h-6 w-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            d="M12 9v4M12 17h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75 3L13.7 3.8a2 2 0 00-3.4 0z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>

                <p
                    class="mt-4 text-sm font-semibold text-secondary dark:text-white"
                >
                    Unable to load patient account
                </p>

                <p class="mt-1 text-xs text-danger">
                    {{ errors }}
                </p>

                <button
                    type="button"
                    class="mt-5 rounded-xl bg-primary-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-primary-700"
                    @click="fetchSummary"
                >
                    Try Again
                </button>
            </div>

            <template v-else-if="summary">
                <div
                    class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_500px]"
                >
                    <main class="min-w-0 space-y-5">
                        <section
                            class="overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                        >
                            <div
                                class="border-b border-primary-100 bg-gradient-to-br from-primary-50 via-white to-accent-50/40 dark:from-primary-500/10 dark:via-secondary dark:to-accent-500/10 px-6 py-7 sm:px-7 dark:border-primary-500/20"
                            >
                                <div
                                    class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                class="rounded-lg bg-primary-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                                            >
                                                {{
                                                    summary.patient
                                                        ?.patient_uuid ?? "—"
                                                }}
                                            </span>
                                        </div>

                                        <h1
                                            class="mt-3 truncate text-2xl font-bold tracking-tight text-secondary sm:text-3xl dark:text-white"
                                        >
                                            {{
                                                summary.patient?.full_name ??
                                                "—"
                                            }}
                                        </h1>

                                        <div
                                            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted dark:text-gray-400"
                                        >
                                            <span>
                                                {{ summary.invoice_count }}
                                                invoice(s)
                                            </span>

                                            <span class="hidden sm:inline">
                                                •
                                            </span>

                                            <span>
                                                {{ admissions.length }}
                                                admission(s)
                                            </span>

                                            <span class="hidden sm:inline">
                                                •
                                            </span>

                                            <span>
                                                {{ services.length }}
                                                service(s)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="grid border-b border-primary-100 sm:grid-cols-2 lg:grid-cols-4 dark:border-primary-500/20"
                            >
                                <SummaryCard
                                    label="Total Amount"
                                    :value="summary.total_amount"
                                />

                                <SummaryCard
                                    label="Total Paid"
                                    :value="summary.total_paid"
                                    variant="paid"
                                    :action-label="
                                        receiptGroups.length
                                            ? 'View receipts'
                                            : undefined
                                    "
                                    :on-action="() => scrollTo(paymentsSection)"
                                />

                                <SummaryCard
                                    label="Refunded"
                                    :value="summary.total_refunded"
                                    variant="refunded"
                                    :action-label="
                                        refundHistory.length
                                            ? 'View refunds'
                                            : undefined
                                    "
                                    :on-action="() => scrollTo(refundsSection)"
                                />

                                <SummaryCard
                                    label="Balance Due"
                                    :value="summary.total_balance"
                                    variant="balance"
                                />
                            </div>

                            <div
                                v-if="hasProcessingRefund"
                                class="flex flex-wrap items-center justify-between gap-3 border-b border-primary-100 bg-accent-50/40 px-6 py-4 sm:px-7 dark:border-primary-500/20 dark:bg-accent-500/15"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-accent-700 dark:text-accent-300"
                                    >
                                        Refund Processing
                                    </p>

                                    <p
                                        class="mt-1 text-xs text-muted dark:text-gray-400"
                                    >
                                        A refund is currently being processed.
                                    </p>
                                </div>

                                <!-- <p class="text-sm font-bold text-accent-700 dark:text-accent-300">
                                    ₱{{
                                        formatMoney(
                                            summary.total_refund_processing,
                                        )
                                    }}
                                </p> -->

                                <div class="flex items-center gap-3">
                                    <p
                                        class="text-sm font-bold text-accent-700 dark:text-accent-300"
                                    >
                                        ₱{{
                                            formatMoney(
                                                summary.total_refund_processing,
                                            )
                                        }}
                                    </p>

                                    <button
                                        type="button"
                                        :disabled="processingRefund"
                                        class="inline-flex items-center gap-2 rounded-xl bg-danger px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-danger/90 disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="openRefundModal"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M3 12a9 9 0 1 0 3-6.7" />
                                            <path d="M3 4v6h6" />
                                        </svg>

                                        Refund
                                    </button>
                                </div>
                            </div>

                            <section
                                v-if="summary.patient"
                                class="border-b border-primary-100 px-6 py-6 sm:px-7 dark:border-primary-500/20"
                            >
                                <SectionHeader>
                                    <template #icon>
                                        <svg
                                            class="h-4 w-4"
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
                                    class="mt-5 grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3 lg:grid-cols-4"
                                >
                                    <Field
                                        label="Full Name"
                                        :value="summary.patient.full_name"
                                    />

                                    <Field
                                        label="Gender"
                                        :value="summary.patient.gender"
                                    />

                                    <Field
                                        label="Birth Date"
                                        :value="
                                            formatDate(
                                                summary.patient.date_of_birth,
                                            )
                                        "
                                    />

                                    <Field label="Age" :value="patientAge" />

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
                            </section>

                            <section>
                                <div
                                    class="border-b border-primary-100 px-4 sm:px-6 dark:border-primary-500/20"
                                >
                                    <div class="flex gap-1 overflow-x-auto">
                                        <button
                                            type="button"
                                            class="relative whitespace-nowrap px-4 py-4 text-sm font-medium transition"
                                            :class="
                                                activeTab === 'overview'
                                                    ? 'text-primary-700 dark:text-primary-300'
                                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                            "
                                            @click="activeTab = 'overview'"
                                        >
                                            Overview

                                            <span
                                                v-if="activeTab === 'overview'"
                                                class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary-600"
                                            />
                                        </button>

                                        <button
                                            type="button"
                                            class="relative inline-flex items-center gap-2 whitespace-nowrap px-4 py-4 text-sm font-medium transition"
                                            :class="
                                                activeTab === 'admissions'
                                                    ? 'text-primary-700 dark:text-primary-300'
                                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                            "
                                            @click="activeTab = 'admissions'"
                                        >
                                            Admissions

                                            <span
                                                class="rounded-full bg-primary-50 px-2 py-0.5 text-[10px] text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                                            >
                                                {{ admissions.length }}
                                            </span>

                                            <span
                                                v-if="
                                                    activeTab === 'admissions'
                                                "
                                                class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary-600"
                                            />
                                        </button>

                                        <button
                                            type="button"
                                            class="relative inline-flex items-center gap-2 whitespace-nowrap px-4 py-4 text-sm font-medium transition"
                                            :class="
                                                activeTab === 'services'
                                                    ? 'text-accent-700 dark:text-accent-300'
                                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                            "
                                            @click="activeTab = 'services'"
                                        >
                                            Services

                                            <span
                                                class="rounded-full bg-accent-50 px-2 py-0.5 text-[10px] text-accent-700 dark:bg-accent-500/15 dark:text-accent-300"
                                            >
                                                {{ services.length }}
                                            </span>

                                            <span
                                                v-if="activeTab === 'services'"
                                                class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-accent-600"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <div
                                    v-if="activeTab === 'overview'"
                                    class="space-y-5 px-6 py-6 sm:px-7"
                                >
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div
                                            class="rounded-xl border border-primary-100 bg-primary-50/40 p-5 dark:border-primary-500/20 dark:bg-primary-500/10"
                                        >
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div>
                                                    <p
                                                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary-600 dark:text-primary-300"
                                                    >
                                                        Account Status
                                                    </p>

                                                    <p
                                                        class="mt-2 text-lg font-bold capitalize text-secondary dark:text-white"
                                                    >
                                                        {{
                                                            summary.status ||
                                                            "Unknown"
                                                        }}
                                                    </p>
                                                </div>

                                                <span
                                                    class="rounded-full px-2.5 py-1 text-[10px] font-medium capitalize"
                                                    :class="
                                                        statusClasses(
                                                            summary.status,
                                                        )
                                                    "
                                                >
                                                    {{ summary.status }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4"
                                            >
                                                <div>
                                                    <p
                                                        class="text-[10px] text-muted dark:text-gray-400"
                                                    >
                                                        Invoices
                                                    </p>

                                                    <p
                                                        class="mt-1 text-sm font-semibold text-secondary dark:text-white"
                                                    >
                                                        {{
                                                            summary.invoice_count
                                                        }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <p
                                                        class="text-[10px] text-muted dark:text-gray-400"
                                                    >
                                                        Admissions
                                                    </p>

                                                    <p
                                                        class="mt-1 text-sm font-semibold text-secondary dark:text-white"
                                                    >
                                                        {{ admissions.length }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="rounded-xl border border-primary-100 bg-white p-5 dark:border-primary-500/20 dark:bg-secondary"
                                        >
                                            <p
                                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                                            >
                                                Latest Invoice
                                            </p>

                                            <template
                                                v-if="summary.latest_invoice"
                                            >
                                                <button
                                                    type="button"
                                                    class="mt-3 w-full text-left"
                                                    @click="
                                                        viewInvoice(
                                                            summary
                                                                .latest_invoice!
                                                                .invoice_code,
                                                        )
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center justify-between gap-3"
                                                    >
                                                        <span
                                                            class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-300"
                                                        >
                                                            {{
                                                                summary
                                                                    .latest_invoice
                                                                    .invoice_code
                                                            }}
                                                        </span>

                                                        <span
                                                            class="rounded-full px-2.5 py-1 text-[10px] font-medium"
                                                            :class="
                                                                statusClasses(
                                                                    summary
                                                                        .latest_invoice
                                                                        .status,
                                                                )
                                                            "
                                                        >
                                                            {{
                                                                summary
                                                                    .latest_invoice
                                                                    .status
                                                            }}
                                                        </span>
                                                    </div>

                                                    <div
                                                        class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3"
                                                    >
                                                        <div>
                                                            <p
                                                                class="text-[10px] text-muted dark:text-gray-400"
                                                            >
                                                                Total
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-secondary dark:text-white"
                                                            >
                                                                ₱{{
                                                                    formatMoney(
                                                                        summary
                                                                            .latest_invoice
                                                                            .total,
                                                                    )
                                                                }}
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <p
                                                                class="text-[10px] text-muted dark:text-gray-400"
                                                            >
                                                                Paid
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-primary-700 dark:text-primary-300"
                                                            >
                                                                ₱{{
                                                                    formatMoney(
                                                                        summary
                                                                            .latest_invoice
                                                                            .amount_paid,
                                                                    )
                                                                }}
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <p
                                                                class="text-[10px] text-muted dark:text-gray-400"
                                                            >
                                                                Balance
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold"
                                                                :class="
                                                                    Number(
                                                                        summary
                                                                            .latest_invoice
                                                                            .balance_due,
                                                                    ) > 0
                                                                        ? 'text-danger'
                                                                        : 'text-primary-700 dark:text-primary-300'
                                                                "
                                                            >
                                                                ₱{{
                                                                    formatMoney(
                                                                        summary
                                                                            .latest_invoice
                                                                            .balance_due,
                                                                    )
                                                                }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </button>
                                            </template>

                                            <p
                                                v-else
                                                class="mt-3 text-sm text-muted dark:text-gray-400"
                                            >
                                                No invoice available.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="activeTab === 'admissions'"
                                    class="px-6 py-6 sm:px-7"
                                >
                                    <PatientAdmissions
                                        :admissions="admissions"
                                        :discharge-calculation="
                                            summary.discharge_calculation
                                        "
                                        @view-invoice="viewInvoice"
                                        @view-discharge-termination="
                                            viewDischargeTermination
                                        "
                                    />
                                </div>

                                <div
                                    v-else-if="activeTab === 'services'"
                                    class="px-6 py-6 sm:px-7"
                                >
                                    <PatientServices :services="services" />
                                </div>
                            </section>
                        </section>

                        <section
                            v-if="transactions.length || refundHistory.length"
                            class="grid gap-5 lg:grid-cols-2"
                        >
                            <div
                                ref="paymentsSection"
                                class="overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                            >
                                <div
                                    class="flex items-center justify-between gap-3 border-b border-primary-100 px-6 py-4 dark:border-primary-500/20"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            Payment Receipts
                                        </p>

                                        <p
                                            class="text-xs text-muted dark:text-gray-400"
                                        >
                                            {{ receiptGroups.length }} recorded
                                        </p>
                                    </div>

                                    <button
                                        v-if="receiptGroups.length"
                                        type="button"
                                        class="shrink-0 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300"
                                        @click="transactionsOpen = true"
                                    >
                                        View all
                                    </button>
                                </div>

                                <ul
                                    v-if="receiptGroups.length"
                                    class="divide-y divide-primary-100 dark:divide-primary-500/20"
                                >
                                    <li
                                        v-for="receipt in receiptGroups.slice(
                                            0,
                                            5,
                                        )"
                                        :key="receipt.key"
                                        class="flex items-center gap-3 px-6 py-3.5 transition-colors"
                                        :class="
                                            receipt.receipt_no
                                                ? 'cursor-pointer hover:bg-primary-50/40 dark:hover:bg-white/5'
                                                : ''
                                        "
                                        @click="
                                            openReceiptByNo(receipt.receipt_no)
                                        "
                                    >
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300"
                                        >
                                            <Loader2
                                                v-if="
                                                    loadingReceipt ===
                                                    receipt.receipt_no
                                                "
                                                class="h-4 w-4 animate-spin"
                                            />

                                            <Receipt v-else class="h-4 w-4" />
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate font-mono text-xs font-semibold text-secondary dark:text-white"
                                            >
                                                {{
                                                    receipt.receipt_no ??
                                                    "No receipt"
                                                }}
                                            </p>

                                            <p
                                                class="truncate text-[11px] text-muted dark:text-gray-400"
                                            >
                                                {{
                                                    invoiceCodesLabel(
                                                        receipt.invoice_codes,
                                                    )
                                                }}
                                                · {{ receipt.payment_method }} ·
                                                {{
                                                    formatDateTime(
                                                        receipt.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <p
                                            class="shrink-0 text-sm font-bold text-primary-700 dark:text-primary-300"
                                        >
                                            ₱{{ formatMoney(receipt.amount) }}
                                        </p>
                                    </li>
                                </ul>

                                <p
                                    v-else
                                    class="px-6 py-8 text-center text-xs text-muted dark:text-gray-400"
                                >
                                    No payment receipts recorded yet.
                                </p>
                            </div>

                            <div
                                ref="refundsSection"
                                class="overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                            >
                                <div
                                    class="flex items-center justify-between gap-3 border-b border-primary-100 px-6 py-4 dark:border-primary-500/20"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            Refunds
                                        </p>

                                        <p
                                            class="text-xs text-muted dark:text-gray-400"
                                        >
                                            {{ refundHistory.length }} recorded
                                        </p>
                                    </div>

                                    <button
                                        v-if="refundHistory.length"
                                        type="button"
                                        class="shrink-0 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300"
                                        @click="refundsOpen = true"
                                    >
                                        View all
                                    </button>
                                </div>

                                <ul
                                    v-if="refundHistory.length"
                                    class="divide-y divide-primary-100 dark:divide-primary-500/20"
                                >
                                    <li
                                        v-for="refund in refundHistory.slice(
                                            0,
                                            5,
                                        )"
                                        :key="refund.refund_id"
                                        class="flex items-center gap-3 px-6 py-3.5 transition-colors hover:bg-accent-50/40 dark:hover:bg-white/5"
                                    >
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-600 dark:bg-accent-500/15 dark:text-accent-300"
                                        >
                                            <Undo2 class="h-4 w-4" />
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate font-mono text-xs font-semibold text-secondary dark:text-white"
                                            >
                                                {{ refund.invoice_code }}
                                            </p>

                                            <p
                                                class="truncate text-[11px] text-muted dark:text-gray-400"
                                            >
                                                {{
                                                    formatDateTime(
                                                        refund.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p
                                                class="text-sm font-bold text-accent-700 dark:text-accent-300"
                                            >
                                                ₱{{
                                                    formatMoney(refund.amount)
                                                }}
                                            </p>

                                            <span
                                                class="mt-0.5 inline-block rounded-full px-1.5 py-0.5 text-[10px] font-semibold capitalize"
                                                :class="
                                                    statusClasses(refund.status)
                                                "
                                            >
                                                {{ refund.status }}
                                            </span>
                                        </div>
                                    </li>
                                </ul>

                                <p
                                    v-else
                                    class="px-6 py-8 text-center text-xs text-muted dark:text-gray-400"
                                >
                                    No refunds recorded yet.
                                </p>
                            </div>
                        </section>
                    </main>

                    <aside class="xl:sticky xl:top-6 print:hidden">
                        <div
                            v-if="showPayment"
                            class="overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                        >
                            <div
                                class="border-b border-primary-100 bg-primary-50/60 px-6 py-5 dark:border-primary-500/20 dark:bg-primary-500/10"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-semibold uppercase tracking-[0.16em] text-primary-600 dark:text-primary-300"
                                        >
                                            Outstanding Balance
                                        </p>

                                        <p
                                            class="mt-1 text-3xl font-bold tracking-tight text-secondary dark:text-white"
                                        >
                                            ₱{{
                                                formatMoney(
                                                    summary.total_balance,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-primary-700 shadow-sm dark:bg-secondary dark:text-primary-300"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <rect
                                                x="3"
                                                y="5"
                                                width="18"
                                                height="14"
                                                rx="2"
                                            />
                                            <path d="M3 10h18" />
                                        </svg>
                                    </div>
                                </div>

                                <p
                                    class="mt-2 text-xs text-muted dark:text-gray-400"
                                >
                                    Amount still due from the patient.
                                </p>
                            </div>
                            <div
                                v-if="payableInvoices.length"
                                class="flex items-center justify-between gap-3 border-b border-primary-100 px-6 py-4 dark:border-primary-500/20"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-secondary dark:text-white"
                                    >
                                        {{ selectionSummaryLabel }}
                                    </p>

                                    <p
                                        class="text-xs text-muted dark:text-gray-400"
                                    >
                                        ₱{{ formatMoney(selectedBalance) }} will
                                        be settled
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="shrink-0 rounded-xl border border-primary-500 px-3 py-2 text-xs font-semibold text-primary-600 transition hover:bg-primary-500 hover:text-white dark:text-primary-300"
                                    @click="invoicePickerOpen = true"
                                >
                                    Choose invoices
                                </button>
                            </div>

                            <div class="space-y-4 p-6">
                                <div>
                                    <label
                                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                                    >
                                        Received from
                                    </label>

                                    <input
                                        v-model="payorName"
                                        type="text"
                                        :placeholder="
                                            summary.patient?.full_name ??
                                            'Name of payer'
                                        "
                                        class="mt-1.5 w-full rounded-xl border border-primary-100 bg-white px-3.5 py-2.5 text-sm text-secondary outline-none transition placeholder:text-muted focus:border-primary dark:border-primary-500/20 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500"
                                    />

                                    <p
                                        class="mt-1 text-[11px] text-muted dark:text-gray-400"
                                    >
                                        Leave blank if the patient paid in
                                        person.
                                    </p>
                                </div>

                                <PaymentForm
                                    :processing="processingPayment"
                                    :total-amount="selectedBalance"
                                    :enable-card="false"
                                    :enable-g-cash="false"
                                    :enable-cash="true"
                                    title="Complete Payment"
                                    :description="paymentDescription"
                                    cash-label="Confirm Cash Payment"
                                    cash-processing-label="Confirming payment..."
                                    cash-description="Enter the cash amount received at the counter."
                                    @cash-pay="handleCashPay"
                                />
                            </div>
                        </div>

                        <div
                            v-else
                            class="rounded-2xl border border-primary-100 bg-white p-7 text-center shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                        >
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M20 6L9 17l-5-5" />
                                </svg>
                            </div>

                            <p
                                class="mt-4 text-sm font-semibold text-secondary dark:text-white"
                            >
                                Account Settled
                            </p>

                            <p
                                class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                            >
                                All outstanding invoices for this patient have
                                been fully paid.
                            </p>
                        </div>

                        <div
                            class="mt-4 rounded-2xl border border-primary-100 bg-white p-5 shadow-sm dark:border-primary-500/20 dark:bg-secondary"
                        >
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                            >
                                Account Summary
                            </p>

                            <div class="mt-4 space-y-3">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span
                                        class="text-xs text-muted dark:text-gray-400"
                                    >
                                        Total invoices
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-secondary dark:text-white"
                                    >
                                        {{ summary.invoice_count }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span
                                        class="text-xs text-muted dark:text-gray-400"
                                    >
                                        Admissions
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-secondary dark:text-white"
                                    >
                                        {{ admissions.length }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span
                                        class="text-xs text-muted dark:text-gray-400"
                                    >
                                        Services
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-secondary dark:text-white"
                                    >
                                        {{ services.length }}
                                    </span>
                                </div>

                                <div
                                    class="border-t border-primary-100 pt-3 dark:border-primary-500/20"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <span
                                            class="text-xs font-medium text-secondary dark:text-white"
                                        >
                                            Current balance
                                        </span>

                                        <span
                                            class="text-sm font-bold"
                                            :class="
                                                showPayment
                                                    ? 'text-danger'
                                                    : 'text-primary-700 dark:text-primary-300'
                                            "
                                        >
                                            ₱{{
                                                formatMoney(
                                                    summary.total_balance,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </template>

            <div
                v-else
                class="rounded-2xl border border-primary-100 bg-white p-12 text-center shadow-sm dark:border-primary-500/20 dark:bg-secondary"
            >
                <p class="text-sm font-semibold text-secondary dark:text-white">
                    No patient data found
                </p>

                <p class="mt-1 text-xs text-muted dark:text-gray-400">
                    There is no invoice information available for this patient.
                </p>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="refundModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
                @click.self="closeRefundModal"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
                >
                    <div
                        class="border-b border-primary-100 px-6 py-5 dark:border-primary-500/20"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                                >
                                    Refund
                                </p>

                                <h3
                                    class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                                >
                                    Process Refund
                                </h3>

                                <p
                                    class="mt-1 text-xs text-muted dark:text-gray-400"
                                >
                                    Please confirm the refund amount below.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:hover:bg-white/10 dark:text-gray-400 dark:hover:text-white"
                                :disabled="processingRefund"
                                @click="closeRefundModal"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                >
                                    <path
                                        d="M6 6l12 12M18 6L6 18"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-5 p-6">
                        <div
                            class="rounded-xl border border-danger/20 bg-danger/5 p-5"
                        >
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                            >
                                Amount to Refund
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold tracking-tight text-danger"
                            >
                                ₱{{ formatMoney(refundableAmount) }}
                            </p>

                            <p
                                v-if="hasProcessingRefund"
                                class="mt-2 text-xs leading-5 text-muted dark:text-gray-400"
                            >
                                This amount is currently available for refund.
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-primary-100 bg-slate-50/70 px-4 py-3 dark:border-primary-500/20 dark:bg-white/5"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-xs text-muted dark:text-gray-400"
                                >
                                    Refundable Amount
                                </span>

                                <span
                                    class="text-sm font-bold text-secondary dark:text-white"
                                >
                                    ₱{{ formatMoney(refundableAmount) }}
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="refundError"
                            class="rounded-xl bg-danger/10 px-3 py-2.5 text-xs text-danger"
                        >
                            {{ refundError }}
                        </p>
                    </div>

                    <div
                        class="flex justify-end gap-2 border-t border-primary-100 bg-slate-50/60 px-6 py-4 dark:border-primary-500/20 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-white hover:text-secondary dark:hover:bg-secondary dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/10"
                            :disabled="processingRefund"
                            @click="closeRefundModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="processingRefund"
                            class="rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-danger/90 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="submitRefund"
                        >
                            {{
                                processingRefund
                                    ? "Processing..."
                                    : "Confirm Refund"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="dischargeModalOpen && selectedDischargeCalculation"
                class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
                @click.self="closeDischargeTermination"
            >
                <div
                    class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-primary-100 px-6 py-5 dark:border-primary-500/20"
                    >
                        <div>
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                            >
                                Discharge Termination
                            </p>

                            <h3
                                class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                            >
                                Discharge Calculation
                            </h3>

                            <p
                                class="mt-1 text-xs text-muted dark:text-gray-400"
                            >
                                Admission #{{
                                    selectedDischargeCalculation.admission_id
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:hover:bg-white/10 dark:text-gray-400 dark:hover:text-white"
                            @click="closeDischargeTermination"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                            >
                                <path
                                    d="M6 6l12 12M18 6L6 18"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-5 p-6">
                        <div
                            class="rounded-xl border border-danger/20 bg-danger/5 p-5"
                        >
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                                    >
                                        Refund Amount
                                    </p>

                                    <p
                                        class="mt-1 text-2xl font-bold text-danger"
                                    >
                                        ₱{{
                                            formatMoney(
                                                selectedDischargeCalculation.refund_amount,
                                            )
                                        }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-primary-50 px-3 py-1.5 text-[10px] font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                                >
                                    Refund Eligible
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <Field
                                label="Billing Cycle"
                                :value="
                                    selectedDischargeCalculation.billing_cycle
                                "
                            />

                            <Field
                                label="Contract Price"
                                :value="`₱${formatMoney(
                                    selectedDischargeCalculation.contract_price,
                                )}`"
                            />

                            <Field
                                label="Amount Paid"
                                :value="`₱${formatMoney(
                                    selectedDischargeCalculation.amount_paid,
                                )}`"
                            />

                            <Field
                                label="Required Payment"
                                :value="`₱${formatMoney(
                                    selectedDischargeCalculation.required_payment,
                                )}`"
                            />

                            <Field
                                label="Retention Amount"
                                :value="`₱${formatMoney(
                                    selectedDischargeCalculation.retention_amount,
                                )}`"
                            />

                            <Field
                                label="Termination Fee"
                                :value="`₱${formatMoney(
                                    selectedDischargeCalculation.termination_fee_amount,
                                )}`"
                            />
                        </div>

                        <div
                            class="rounded-xl border border-primary-100 bg-slate-50/70 p-5 dark:border-primary-500/20 dark:bg-white/5"
                        >
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                <Field
                                    label="Admission Date"
                                    :value="
                                        formatDate(
                                            selectedDischargeCalculation.admission_date,
                                        )
                                    "
                                />

                                <Field
                                    label="Discharge Date"
                                    :value="
                                        formatDate(
                                            selectedDischargeCalculation.discharge_date,
                                        )
                                    "
                                />

                                <Field
                                    label="Days Since Admission"
                                    :value="
                                        selectedDischargeCalculation.days_since_admission
                                    "
                                />

                                <Field
                                    label="Termination Fee %"
                                    :value="`${selectedDischargeCalculation.termination_fee_percent}%`"
                                />

                                <Field
                                    label="Payment Shortfall"
                                    :value="`₱${formatMoney(
                                        selectedDischargeCalculation.payment_shortfall,
                                    )}`"
                                />

                                <Field
                                    label="Refund"
                                    :value="`₱${formatMoney(
                                        selectedDischargeCalculation.refund_amount,
                                    )}`"
                                />
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-accent-100 bg-accent-50/40 p-4 dark:border-accent-500/20 dark:bg-accent-500/15"
                        >
                            <p
                                class="text-xs font-semibold text-accent-700 dark:text-accent-300"
                            >
                                Termination Fee Window
                            </p>

                            <p
                                class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                            >
                                This admission is currently within the
                                termination fee window. A
                                {{
                                    selectedDischargeCalculation.termination_fee_percent
                                }}% termination fee applies.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex justify-end border-t border-primary-100 bg-slate-50/60 px-6 py-4 dark:border-primary-500/20 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            class="rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700"
                            @click="closeDischargeTermination"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <PaymentReceipt
            v-if="activeReceipt"
            :receipt="activeReceipt"
            @close="activeReceipt = null"
        />

        <Teleport to="body">
            <div
                v-if="transactionsOpen || refundsOpen"
                class="fixed inset-0 z-[70] flex items-center justify-center p-4"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                    @click="closeHistory"
                />

                <div
                    class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-semibold text-gray-400 dark:text-gray-500"
                            >
                                {{
                                    transactionsOpen
                                        ? "Payment receipts"
                                        : "Refund history"
                                }}
                            </p>

                            <h2
                                class="mt-0.5 truncate text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                {{ summary?.patient?.full_name ?? "Patient" }}
                            </h2>
                        </div>

                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10"
                            @click="closeHistory"
                        >
                            <X class="h-4.5 w-4.5" />
                        </button>
                    </div>

                    <ul
                        v-if="transactionsOpen"
                        class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                    >
                        <li
                            v-for="receipt in receiptGroups"
                            :key="receipt.key"
                            class="flex items-start justify-between gap-4 px-6 py-4 transition-colors"
                            :class="
                                receipt.receipt_no
                                    ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5'
                                    : ''
                            "
                            @click="openReceiptByNo(receipt.receipt_no)"
                        >
                            <div class="min-w-0">
                                <p
                                    class="flex items-center gap-1.5 font-mono text-sm font-semibold text-secondary dark:text-white"
                                >
                                    <Loader2
                                        v-if="
                                            loadingReceipt ===
                                            receipt.receipt_no
                                        "
                                        class="h-3.5 w-3.5 animate-spin"
                                    />
                                    {{ receipt.receipt_no ?? "No receipt" }}
                                </p>

                                <p
                                    class="text-xs text-muted dark:text-gray-400"
                                >
                                    {{ receipt.invoice_codes.join(", ") }}

                                    <span
                                        v-if="receipt.invoice_codes.length > 1"
                                        class="text-gray-400 dark:text-gray-500"
                                    >
                                        ({{ receipt.invoice_codes.length }}
                                        invoices)
                                    </span>
                                </p>

                                <p
                                    class="text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    {{ receipt.payment_method }} ·
                                    {{ formatDateTime(receipt.created_at) }}
                                </p>
                            </div>

                            <p
                                class="shrink-0 text-sm font-semibold text-primary-700 dark:text-primary-300"
                            >
                                ₱{{ formatMoney(receipt.amount) }}
                            </p>
                        </li>
                    </ul>

                    <ul
                        v-else
                        class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                    >
                        <li
                            v-for="refund in refundHistory"
                            :key="refund.refund_id"
                            class="flex items-start justify-between gap-4 px-6 py-4"
                        >
                            <div class="min-w-0">
                                <p
                                    class="font-mono text-sm font-semibold text-secondary dark:text-white"
                                >
                                    {{ refund.invoice_code }}
                                </p>

                                <p
                                    class="text-xs text-muted dark:text-gray-400"
                                >
                                    {{
                                        refund.refund_method ||
                                        refund.payment_method
                                    }}
                                    <template v-if="refund.reason">
                                        · {{ refund.reason }}
                                    </template>
                                </p>

                                <p
                                    class="text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    {{ formatDateTime(refund.created_at) }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p
                                    class="text-sm font-semibold text-accent-700 dark:text-accent-300"
                                >
                                    ₱{{ formatMoney(refund.amount) }}
                                </p>

                                <span
                                    class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                    :class="statusClasses(refund.status)"
                                >
                                    {{ refund.status }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="invoicePickerOpen"
                class="fixed inset-0 z-[70] flex items-center justify-center p-4"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                    @click="invoicePickerOpen = false"
                />

                <div
                    class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-semibold text-gray-400 dark:text-gray-500"
                            >
                                Select invoices to pay
                            </p>

                            <h2
                                class="mt-0.5 truncate text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                {{ summary?.patient?.full_name ?? "Patient" }}
                            </h2>
                        </div>

                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10"
                            @click="invoicePickerOpen = false"
                        >
                            <X class="h-4.5 w-4.5" />
                        </button>
                    </div>

                    <div
                        class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-6 py-3 dark:border-white/10"
                    >
                        <p class="text-xs text-muted dark:text-gray-400">
                            {{ payableInvoices.length }} unpaid invoice{{
                                payableInvoices.length === 1 ? "" : "s"
                            }}
                        </p>

                        <button
                            type="button"
                            class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300"
                            @click="toggleSelectAllInvoices"
                        >
                            {{
                                allInvoicesSelected ? "Clear all" : "Select all"
                            }}
                        </button>
                    </div>

                    <ul
                        class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                    >
                        <li
                            v-for="invoice in payableInvoices"
                            :key="invoice.invoice_code"
                        >
                            <label
                                class="flex cursor-pointer items-start gap-3 px-6 py-4 transition hover:bg-gray-50 dark:hover:bg-white/5"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-primary focus:ring-primary/30 dark:border-white/20 dark:bg-transparent"
                                    :value="invoice.invoice_code"
                                    v-model="selectedInvoiceCodes"
                                />

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="font-mono text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            {{ invoice.invoice_code }}
                                        </span>

                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                            :class="
                                                statusClasses(invoice.status)
                                            "
                                        >
                                            {{ invoice.status }}
                                        </span>
                                    </span>

                                    <span
                                        class="mt-1 block text-xs text-muted dark:text-gray-400"
                                    >
                                        {{ invoiceDescription(invoice) }}
                                    </span>

                                    <span
                                        class="mt-1 block text-[11px] text-gray-400 dark:text-gray-500"
                                    >
                                        Total ₱{{
                                            formatMoney(invoice.total)
                                        }}
                                        · Paid ₱{{
                                            formatMoney(invoice.amount_paid)
                                        }}
                                        · Due ₱{{
                                            formatMoney(invoice.balance_due)
                                        }}
                                    </span>
                                </span>

                                <span class="w-32 shrink-0" @click.prevent.stop>
                                    <span
                                        class="block text-[10px] font-semibold uppercase tracking-wide text-muted dark:text-gray-500"
                                    >
                                        Amount to pay
                                    </span>

                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :max="invoice.balance_due"
                                        :disabled="
                                            !selectedInvoiceCodes.includes(
                                                invoice.invoice_code,
                                            )
                                        "
                                        :value="amountFor(invoice.invoice_code)"
                                        class="mt-1 w-full rounded-lg border border-gray-200 px-2.5 py-1.5 text-right text-sm font-semibold text-secondary outline-none transition focus:border-primary disabled:bg-gray-50 disabled:text-gray-400 dark:border-white/10 dark:bg-transparent dark:text-white dark:disabled:bg-white/5"
                                        @input="
                                            setInvoiceAmount(
                                                invoice.invoice_code,
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            )
                                        "
                                    />
                                </span>
                            </label>
                        </li>
                    </ul>

                    <div
                        class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p class="text-xs text-muted dark:text-gray-400">
                                {{ selectionSummaryLabel }}
                            </p>

                            <p
                                class="text-base font-bold text-secondary dark:text-white"
                            >
                                ₱{{ formatMoney(selectedBalance) }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600"
                            @click="invoicePickerOpen = false"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script lang="ts" setup>
import { computed, h, onMounted, ref, type Ref } from "vue";
import { Loader2, Receipt, Undo2, X } from "lucide-vue-next";
import { useRoute, useRouter } from "vue-router";

import { invoiceService } from "~/api/invoice/InvoiceService";
import { formatAmount } from "~/utils/currency";
import PatientAdmissions from "~/components/sections/app/Billing/PatientAdmissions.vue";
import PatientServices from "~/components/sections/app/Billing/PatientServices.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { useToast } from "~/composables/useToast";
import { calculateAge } from "~/utils/user";

import type {
    PatientAdmission,
    PatientInvoiceItem,
    PatientInvoiceSummary,
} from "~/types/invoice";
import type { PaymentReceipt as PaymentReceiptData } from "~/types/receipt";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Patient Invoices",
});

const route = useRoute();
const router = useRouter();

const { success, error } = useToast();

const uuid = computed(() => route.params.uuid as string);
const patientUuid = computed(() => route.params.p_uuid as string);

const summary = ref<PatientInvoiceSummary | null>(null);

const loading = ref(true);
const errors = ref("");

const processingPayment = ref(false);
const processingRefund = ref(false);

const activeReceipt = ref<PaymentReceiptData | null>(null);

const activeTab = ref<"overview" | "admissions" | "services">("overview");

const refundModalOpen = ref(false);

const refundError = ref("");

const services = computed(() => {
    return summary.value?.services ?? [];
});

const admissions = computed(() => {
    const items = [...(summary.value?.admissions ?? [])];

    return items.sort((a, b) => {
        const aCurrent = isCurrentAdmission(a);
        const bCurrent = isCurrentAdmission(b);

        if (aCurrent !== bCurrent) {
            return aCurrent ? -1 : 1;
        }

        const aDate = a.admission_date
            ? new Date(a.admission_date).getTime()
            : 0;

        const bDate = b.admission_date
            ? new Date(b.admission_date).getTime()
            : 0;

        return bDate - aDate;
    });
});

const currentAdmission = computed(() => {
    return admissions.value.find((admission) => isCurrentAdmission(admission));
});

const showPayment = computed(() => {
    return Number(summary.value?.total_balance ?? 0) > 0;
});

const payorName = ref("");
const invoicePickerOpen = ref(false);
const transactionsOpen = ref(false);
const refundsOpen = ref(false);

const transactions = computed(() =>
    (summary.value?.invoices ?? [])
        .flatMap((invoice) =>
            (invoice.payments ?? []).map((payment) => ({
                ...payment,
                invoice_code: invoice.invoice_code,
            })),
        )
        .sort(
            (a, b) =>
                new Date(b.created_at ?? 0).getTime() -
                new Date(a.created_at ?? 0).getTime(),
        ),
);

// One counter transaction produces one receipt but a payment row per invoice,
// so the list groups by receipt and keeps the per-invoice split for the modal.
const receiptGroups = computed(() => {
    const groups = new Map<
        string,
        {
            key: string;
            receipt_no: string | null;
            amount: number;
            invoice_codes: string[];
            payment_method: string;
            created_at: string | null;
        }
    >();

    for (const payment of transactions.value) {
        const key = payment.receipt_no ?? `payment-${payment.payment_id}`;
        const existing = groups.get(key);

        if (existing) {
            existing.amount += Number(payment.amount ?? 0);

            if (!existing.invoice_codes.includes(payment.invoice_code)) {
                existing.invoice_codes.push(payment.invoice_code);
            }

            continue;
        }

        groups.set(key, {
            key,
            receipt_no: payment.receipt_no ?? null,
            amount: Number(payment.amount ?? 0),
            invoice_codes: [payment.invoice_code],
            payment_method: payment.payment_method,
            created_at: payment.created_at,
        });
    }

    return [...groups.values()];
});

const refundHistory = computed(() =>
    transactions.value
        .flatMap((payment) =>
            (payment.refunds ?? []).map((refund) => ({
                ...refund,
                invoice_code: payment.invoice_code,
                payment_method: payment.payment_method,
            })),
        )
        .sort(
            (a, b) =>
                new Date(b.created_at ?? 0).getTime() -
                new Date(a.created_at ?? 0).getTime(),
        ),
);
const selectedInvoiceCodes = ref<string[]>([]);
const invoiceAmounts = ref<Record<string, number>>({});

const payableInvoices = computed(() =>
    (summary.value?.invoices ?? []).filter(
        (invoice) => Number(invoice.balance_due ?? 0) > 0,
    ),
);

const allInvoicesSelected = computed(
    () =>
        payableInvoices.value.length > 0 &&
        selectedInvoiceCodes.value.length === payableInvoices.value.length,
);

function invoiceBalance(code: string) {
    const invoice = payableInvoices.value.find(
        (item) => item.invoice_code === code,
    );

    return Number(invoice?.balance_due ?? 0);
}

// An amount is only ever as large as what that invoice still owes, so a typo
// cannot overpay one invoice at the expense of another.
function amountFor(code: string) {
    const entered = invoiceAmounts.value[code];
    const balance = invoiceBalance(code);

    if (entered === undefined || entered === null || Number.isNaN(entered)) {
        return balance;
    }

    return Math.min(Math.max(Number(entered), 0), balance);
}

const allocations = computed(() =>
    Object.fromEntries(
        selectedInvoiceCodes.value
            .map((code) => [code, amountFor(code)] as const)
            .filter(([, amount]) => amount > 0),
    ),
);

// Nothing ticked means "settle the whole account", which is what this panel
// did before selection existed.
const selectedBalance = computed(() => {
    if (!selectedInvoiceCodes.value.length) {
        return Number(summary.value?.total_balance ?? 0);
    }

    return Object.values(allocations.value).reduce(
        (total, amount) => total + amount,
        0,
    );
});

const selectionSummaryLabel = computed(() => {
    const count = selectedInvoiceCodes.value.length;

    if (!count) return "Paying the full outstanding balance";

    return `${count} invoice${count === 1 ? "" : "s"} selected`;
});

const paymentDescription = computed(() => {
    const amount = `₱${formatMoney(selectedBalance.value)}`;

    if (!selectedInvoiceCodes.value.length) {
        return `Outstanding balance: ${amount}`;
    }

    return `${selectionSummaryLabel.value}: ${amount}`;
});

function invoiceDescription(invoice: PatientInvoiceItem) {
    const services = (invoice.services ?? [])
        .map((line) => line.service_name)
        .filter(Boolean);

    if (services.length) return services.join(", ");

    const stays = (invoice.accommodations ?? []).map((stay) =>
        [
            stay.accommodation_type,
            stay.billing_cycle,
            stay.room_no ? `Room ${stay.room_no}` : null,
            stay.bed_no ? `Bed ${stay.bed_no}` : null,
        ]
            .filter(Boolean)
            .join(" · "),
    );

    if (stays.length) return stays.join(" | ");

    return "No line items recorded";
}

function invoiceCodesLabel(codes: string[]) {
    if (codes.length <= 2) return codes.join(", ");

    return `${codes.slice(0, 2).join(", ")} +${codes.length - 2} more`;
}

function closeHistory() {
    transactionsOpen.value = false;
    refundsOpen.value = false;
}

const paymentsSection = ref<HTMLElement | null>(null);
const refundsSection = ref<HTMLElement | null>(null);

function scrollTo(section: Ref<HTMLElement | null>) {
    section.value?.scrollIntoView({ behavior: "smooth", block: "center" });
}

const loadingReceipt = ref<string | null>(null);

// Receipts are addressed by number rather than kept in the summary payload, so
// opening one is a lookup against the branch's receipt list.
async function openReceiptByNo(receiptNo?: string | null) {
    if (!receiptNo || loadingReceipt.value) return;

    loadingReceipt.value = receiptNo;

    try {
        const response = await invoiceService.receipts({
            branch_uuid: uuid.value,
            search: receiptNo,
            per_page: 1,
        });

        const receipt = (response?.data ?? response ?? []).find(
            (row: PaymentReceiptData) => row.receipt_no === receiptNo,
        );

        if (!receipt) {
            error("That receipt could not be found.");
            return;
        }

        closeHistory();
        activeReceipt.value = receipt;
    } catch (err: any) {
        console.error(err);
        error(err?.message ?? "Unable to open that receipt.");
    } finally {
        loadingReceipt.value = null;
    }
}

function formatDateTime(value: string | null | undefined) {
    if (!value) return "—";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

function setInvoiceAmount(code: string, value: string) {
    const parsed = Number(value);

    if (value === "" || Number.isNaN(parsed)) {
        delete invoiceAmounts.value[code];
        return;
    }

    invoiceAmounts.value[code] = Math.min(
        Math.max(parsed, 0),
        invoiceBalance(code),
    );
}

function toggleSelectAllInvoices() {
    selectedInvoiceCodes.value = allInvoicesSelected.value
        ? []
        : payableInvoices.value.map((invoice) => invoice.invoice_code);
}

const hasProcessingRefund = computed(() => {
    return Number(summary.value?.total_refund_processing ?? 0) > 0;
});

const refundableAmount = computed(() => {
    return Number(summary.value?.total_refund_processing ?? 0);
});

const canRefund = computed(() => {
    return refundableAmount.value > 0;
});

const patientAge = computed(() => {
    const date = summary.value?.patient?.date_of_birth;

    if (!date) {
        return "—";
    }

    return calculateAge(formatDate(date), false);
});

function isCurrentAdmission(admission: PatientAdmission) {
    return admission.status?.toLowerCase() === "admitted";
}

function openRefundModal() {
    if (!canRefund.value) {
        return;
    }

    refundError.value = "";
    refundModalOpen.value = true;
}

function closeRefundModal() {
    if (processingRefund.value) {
        return;
    }

    refundModalOpen.value = false;
}

async function submitRefund() {
    if (!summary.value || !canRefund.value) {
        return;
    }

    processingRefund.value = true;
    refundError.value = "";

    try {
        const res = await invoiceService.action({
            p_uuid: patientUuid.value,
            branch_uuid: uuid.value,
            patient_admission_id:
                currentAdmission.value?.patient_admission_id ?? "",
            type: "refund",
        });

        success(res.message ?? "Refund processed successfully.");
        refundModalOpen.value = false;
        summary.value = res.data.data ?? res.data ?? res;
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
    if (!summary.value) {
        return;
    }

    processingPayment.value = true;
    errors.value = "";

    try {
        const response = await invoiceService.create({
            cash,
            mode: "patient",
            payment_method: "CASH",
            p_uuid: patientUuid.value,
            branch_uuid: uuid.value,
            payor_name: payorName.value.trim(),
            invoice_codes: selectedInvoiceCodes.value,
            allocations: allocations.value,
        });

        success(response.message);

        selectedInvoiceCodes.value = [];
        invoiceAmounts.value = {};
        payorName.value = "";

        if (response.receipt) {
            activeReceipt.value = response.receipt;
        }

        await fetchSummary();
    } catch (err: any) {
        console.error(err);

        // Toast rather than `errors`, which swaps the whole page into its
        // load-failure state over a payment that merely got rejected.
        error(err?.message ?? "Payment failed. Please try again.");
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
const dischargeModalOpen = ref(false);
const selectedDischargeAdmissionId = ref<number | null>(null);

const selectedDischargeCalculation = computed(() => {
    if (!summary.value?.discharge_calculation) {
        return null;
    }

    if (
        selectedDischargeAdmissionId.value !==
        summary.value.discharge_calculation.admission_id
    ) {
        return null;
    }

    return summary.value.discharge_calculation;
});

function viewDischargeTermination(admissionId: number) {
    selectedDischargeAdmissionId.value = admissionId;
    dischargeModalOpen.value = true;
}

function closeDischargeTermination() {
    dischargeModalOpen.value = false;
    selectedDischargeAdmissionId.value = null;
}

function statusClasses(status: string | null | undefined) {
    switch (status?.toLowerCase()) {
        case "paid":
            return "bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300";

        case "partial":
            return "bg-accent-50 text-accent-700 dark:bg-accent-500/15 dark:text-accent-300";

        case "pending":
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";

        case "admitted":
            return "bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300";

        case "discharged":
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";

        case "overdue":
            return "bg-danger/10 text-danger";

        case "cancelled":
            return "bg-danger/10 text-danger";

        default:
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
    }
}

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

function formatDate(value: string | null | undefined) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "—";
    }

    return date.toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

onMounted(fetchSummary);

const Field = (
    fieldProps: {
        label: string;
        value: unknown;
    },
    { slots }: any,
) =>
    h(
        "div",
        {
            class: "flex min-w-0 flex-col gap-0.5",
        },
        [
            h(
                "span",
                {
                    class: "truncate text-[10px] font-semibold uppercase tracking-[0.11em] text-muted dark:text-gray-400",
                },
                fieldProps.label,
            ),
            h(
                "span",
                {
                    class: "truncate text-sm font-medium text-secondary dark:text-white",
                },
                slots.value ? slots.value() : String(fieldProps.value ?? "—"),
            ),
        ],
    );

Field.props = ["label", "value"];

const SectionHeader = (_props: unknown, { slots }: any) =>
    h(
        "h2",
        {
            class: "flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.15em] text-accent-700 dark:text-accent-300",
        },
        [
            h(
                "span",
                {
                    class: "flex h-7 w-7 items-center justify-center rounded-lg bg-accent-50 dark:bg-accent-500/15",
                },
                slots.icon?.(),
            ),
            slots.default?.(),
        ],
    );

const SummaryCard = (props: {
    label: string;
    value: number;
    variant?: string;
    actionLabel?: string;
    onAction?: () => void;
}) => {
    const variants: Record<
        string,
        {
            container: string;
            label: string;
            value: string;
        }
    > = {
        default: {
            container: "bg-white dark:bg-secondary",
            label: "text-muted dark:text-gray-400",
            value: "text-secondary dark:text-white",
        },
        paid: {
            container: "bg-primary-50/60 dark:bg-primary-500/10",
            label: "text-primary-700 dark:text-primary-300",
            value: "text-primary-700 dark:text-primary-300",
        },
        refunded: {
            container: "bg-accent-50/50 dark:bg-accent-500/15",
            label: "text-accent-700 dark:text-accent-300",
            value: "text-accent-700 dark:text-accent-300",
        },
        balance: {
            container: "bg-danger/5",
            label: "text-danger",
            value: "text-danger",
        },
    };

    const variant = variants[props.variant ?? "default"] ?? variants.default;

    if (!variant) return;
    return h(
        "div",
        {
            class: `border-b border-primary-100 px-5 py-5 last:border-b-0 sm:px-6 lg:border-b-0 lg:border-r lg:last:border-r-0 ${variant.container}`,
        },
        [
            h(
                "p",
                {
                    class: `text-[10px] font-semibold uppercase tracking-[0.14em] ${variant.label}`,
                },
                props.label,
            ),
            h(
                "p",
                {
                    class: `mt-2 text-xl font-bold tracking-tight sm:text-2xl ${variant.value}`,
                },
                `₱${formatMoney(props.value)}`,
            ),
            props.actionLabel && props.onAction
                ? h(
                      "button",
                      {
                          type: "button",
                          class: `mt-2 text-[11px] font-semibold underline-offset-2 hover:underline ${variant.label}`,
                          onClick: props.onAction,
                      },
                      props.actionLabel,
                  )
                : null,
        ],
    );
};

const EmptyState = (props: { title: string; description: string }) =>
    h(
        "div",
        {
            class: "rounded-xl border border-dashed border-primary-100 px-6 py-10 text-center dark:border-primary-500/20",
        },
        [
            h(
                "p",
                {
                    class: "text-sm font-semibold text-secondary dark:text-white",
                },
                props.title,
            ),
            h(
                "p",
                {
                    class: "mt-1 text-xs text-muted dark:text-gray-400",
                },
                props.description,
            ),
        ],
    );

EmptyState.props = ["title", "description"];
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

    .rounded-2xl,
    .rounded-xl {
        border-radius: 0 !important;
    }

    .shadow,
    .shadow-sm,
    .shadow-md,
    .shadow-lg,
    .shadow-xl,
    .shadow-2xl {
        box-shadow: none !important;
    }

    .ring-1,
    .ring {
        box-shadow: none !important;
    }

    @page {
        size: A4 landscape;
        margin: 10mm;
    }
}
</style>
