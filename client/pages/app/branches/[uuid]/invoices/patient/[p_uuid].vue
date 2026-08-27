<template>
    <div
        class="min-h-screen-header w-full bg-slate-50 px-4 py-6 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-[1600px] space-y-5">
            <div
                class="flex flex-wrap items-center justify-between gap-3 no-print"
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-secondary"
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
                    class="inline-flex items-center gap-2 rounded-xl border border-primary-100 bg-white px-4 py-2 text-sm font-medium text-primary-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50"
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
                class="rounded-2xl border border-primary-100 bg-white p-12 text-center shadow-sm"
            >
                <div
                    class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-primary-100 border-t-primary-600"
                />

                <p class="mt-4 text-sm font-medium text-secondary">
                    Loading patient account...
                </p>

                <p class="mt-1 text-xs text-muted">
                    Please wait while the invoice information is loaded.
                </p>
            </div>

            <div
                v-else-if="errors"
                class="rounded-2xl border border-danger/20 bg-white p-10 text-center shadow-sm"
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

                <p class="mt-4 text-sm font-semibold text-secondary">
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
                            class="overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-sm"
                        >
                            <div
                                class="border-b border-primary-100 bg-gradient-to-br from-primary-50 via-white to-accent-50/40 px-6 py-7 sm:px-7"
                            >
                                <div
                                    class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                class="rounded-lg bg-primary-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-primary-700"
                                            >
                                                {{
                                                    summary.patient
                                                        ?.patient_uuid ?? "—"
                                                }}
                                            </span>
                                        </div>

                                        <h1
                                            class="mt-3 truncate text-2xl font-bold tracking-tight text-secondary sm:text-3xl"
                                        >
                                            {{
                                                summary.patient?.full_name ??
                                                "—"
                                            }}
                                        </h1>

                                        <div
                                            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted"
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
                                class="grid border-b border-primary-100 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                <SummaryCard
                                    label="Total Amount"
                                    :value="summary.total_amount"
                                />

                                <SummaryCard
                                    label="Total Paid"
                                    :value="summary.total_paid"
                                    variant="paid"
                                />

                                <SummaryCard
                                    label="Refunded"
                                    :value="summary.total_refunded"
                                    variant="refunded"
                                />

                                <SummaryCard
                                    label="Balance Due"
                                    :value="summary.total_balance"
                                    variant="balance"
                                />
                            </div>

                            <div
                                v-if="hasProcessingRefund"
                                class="flex flex-wrap items-center justify-between gap-3 border-b border-primary-100 bg-accent-50/40 px-6 py-4 sm:px-7"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-accent-700"
                                    >
                                        Refund Processing
                                    </p>

                                    <p class="mt-1 text-xs text-muted">
                                        A refund is currently being processed.
                                    </p>
                                </div>

                                <!-- <p class="text-sm font-bold text-accent-700">
                                    ₱{{
                                        formatMoney(
                                            summary.total_refund_processing,
                                        )
                                    }}
                                </p> -->

                                <div class="flex items-center gap-3">
                                    <p
                                        class="text-sm font-bold text-accent-700"
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
                                class="border-b border-primary-100 px-6 py-6 sm:px-7"
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
                                    class="border-b border-primary-100 px-4 sm:px-6"
                                >
                                    <div class="flex gap-1 overflow-x-auto">
                                        <button
                                            type="button"
                                            class="relative whitespace-nowrap px-4 py-4 text-sm font-medium transition"
                                            :class="
                                                activeTab === 'overview'
                                                    ? 'text-primary-700'
                                                    : 'text-muted hover:text-secondary'
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
                                                    ? 'text-primary-700'
                                                    : 'text-muted hover:text-secondary'
                                            "
                                            @click="activeTab = 'admissions'"
                                        >
                                            Admissions

                                            <span
                                                class="rounded-full bg-primary-50 px-2 py-0.5 text-[10px] text-primary-700"
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
                                                    ? 'text-accent-700'
                                                    : 'text-muted hover:text-secondary'
                                            "
                                            @click="activeTab = 'services'"
                                        >
                                            Services

                                            <span
                                                class="rounded-full bg-accent-50 px-2 py-0.5 text-[10px] text-accent-700"
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
                                            class="rounded-xl border border-primary-100 bg-primary-50/40 p-5"
                                        >
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div>
                                                    <p
                                                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary-600"
                                                    >
                                                        Account Status
                                                    </p>

                                                    <p
                                                        class="mt-2 text-lg font-bold capitalize text-secondary"
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
                                                class="mt-5 grid grid-cols-2 gap-4"
                                            >
                                                <div>
                                                    <p
                                                        class="text-[10px] text-muted"
                                                    >
                                                        Invoices
                                                    </p>

                                                    <p
                                                        class="mt-1 text-sm font-semibold text-secondary"
                                                    >
                                                        {{
                                                            summary.invoice_count
                                                        }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <p
                                                        class="text-[10px] text-muted"
                                                    >
                                                        Admissions
                                                    </p>

                                                    <p
                                                        class="mt-1 text-sm font-semibold text-secondary"
                                                    >
                                                        {{ admissions.length }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="rounded-xl border border-primary-100 bg-white p-5"
                                        >
                                            <p
                                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted"
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
                                                            class="font-mono text-sm font-semibold text-primary-700"
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
                                                                class="text-[10px] text-muted"
                                                            >
                                                                Total
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-secondary"
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
                                                                class="text-[10px] text-muted"
                                                            >
                                                                Paid
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-primary-700"
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
                                                                class="text-[10px] text-muted"
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
                                                                        : 'text-primary-700'
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
                                                class="mt-3 text-sm text-muted"
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
                    </main>

                    <aside class="xl:sticky xl:top-6 print:hidden">
                        <div
                            v-if="showPayment"
                            class="overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-sm"
                        >
                            <div
                                class="border-b border-primary-100 bg-primary-50/60 px-6 py-5"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-semibold uppercase tracking-[0.16em] text-primary-600"
                                        >
                                            Outstanding Balance
                                        </p>

                                        <p
                                            class="mt-1 text-3xl font-bold tracking-tight text-secondary"
                                        >
                                            ₱{{
                                                formatMoney(
                                                    summary.total_balance,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-primary-700 shadow-sm"
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

                                <p class="mt-2 text-xs text-muted">
                                    Amount still due from the patient.
                                </p>
                            </div>

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
                            class="rounded-2xl border border-primary-100 bg-white p-7 text-center shadow-sm"
                        >
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-700"
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
                                class="mt-4 text-sm font-semibold text-secondary"
                            >
                                Account Settled
                            </p>

                            <p class="mt-1 text-xs leading-5 text-muted">
                                All outstanding invoices for this patient have
                                been fully paid.
                            </p>
                        </div>

                        <div
                            class="mt-4 rounded-2xl border border-primary-100 bg-white p-5 shadow-sm"
                        >
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted"
                            >
                                Account Summary
                            </p>

                            <div class="mt-4 space-y-3">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="text-xs text-muted">
                                        Total invoices
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-secondary"
                                    >
                                        {{ summary.invoice_count }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="text-xs text-muted">
                                        Admissions
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-secondary"
                                    >
                                        {{ admissions.length }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="text-xs text-muted">
                                        Services
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-secondary"
                                    >
                                        {{ services.length }}
                                    </span>
                                </div>

                                <div class="border-t border-primary-100 pt-3">
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <span
                                            class="text-xs font-medium text-secondary"
                                        >
                                            Current balance
                                        </span>

                                        <span
                                            class="text-sm font-bold"
                                            :class="
                                                showPayment
                                                    ? 'text-danger'
                                                    : 'text-primary-700'
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
                class="rounded-2xl border border-primary-100 bg-white p-12 text-center shadow-sm"
            >
                <p class="text-sm font-semibold text-secondary">
                    No patient data found
                </p>

                <p class="mt-1 text-xs text-muted">
                    There is no invoice information available for this patient.
                </p>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="refundModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print"
                @click.self="closeRefundModal"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10"
                >
                    <div class="border-b border-primary-100 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                                >
                                    Refund
                                </p>

                                <h3
                                    class="mt-1 text-lg font-semibold text-secondary"
                                >
                                    Process Refund
                                </h3>

                                <p class="mt-1 text-xs text-muted">
                                    Please confirm the refund amount below.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary"
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
                                class="mt-2 text-xs leading-5 text-muted"
                            >
                                This amount is currently available for refund.
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-primary-100 bg-slate-50/70 px-4 py-3"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-xs text-muted">
                                    Refundable Amount
                                </span>

                                <span class="text-sm font-bold text-secondary">
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
                        class="flex justify-end gap-2 border-t border-primary-100 bg-slate-50/60 px-6 py-4"
                    >
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-white hover:text-secondary"
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print"
                @click.self="closeDischargeTermination"
            >
                <div
                    class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-primary-100 px-6 py-5"
                    >
                        <div>
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                            >
                                Discharge Termination
                            </p>

                            <h3
                                class="mt-1 text-lg font-semibold text-secondary"
                            >
                                Discharge Calculation
                            </h3>

                            <p class="mt-1 text-xs text-muted">
                                Admission #{{
                                    selectedDischargeCalculation.admission_id
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary"
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
                                    class="rounded-full bg-primary-50 px-3 py-1.5 text-[10px] font-semibold text-primary-700"
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
                            class="rounded-xl border border-primary-100 bg-slate-50/70 p-5"
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
                            class="rounded-xl border border-accent-100 bg-accent-50/40 p-4"
                        >
                            <p class="text-xs font-semibold text-accent-700">
                                Termination Fee Window
                            </p>

                            <p class="mt-1 text-xs leading-5 text-muted">
                                This admission is currently within the
                                termination fee window. A
                                {{
                                    selectedDischargeCalculation.termination_fee_percent
                                }}% termination fee applies.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex justify-end border-t border-primary-100 bg-slate-50/60 px-6 py-4"
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
    </div>
</template>

<script lang="ts" setup>
import { computed, h, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import { invoiceService } from "~/api/invoice/InvoiceService";
import PatientAdmissions from "~/components/sections/app/Billing/PatientAdmissions.vue";
import PatientServices from "~/components/sections/app/Billing/PatientServices.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { useToast } from "~/composables/useToast";
import { calculateAge } from "~/utils/user";

import type { PatientAdmission, PatientInvoiceSummary } from "~/types/invoice";
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
        });

        success(response.message);

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

function formatMoney(amount: number | string | null | undefined) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
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
                    class: "truncate text-[10px] font-semibold uppercase tracking-[0.11em] text-muted",
                },
                fieldProps.label,
            ),
            h(
                "span",
                {
                    class: "truncate text-sm font-medium text-secondary",
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
            class: "flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.15em] text-accent-700",
        },
        [
            h(
                "span",
                {
                    class: "flex h-7 w-7 items-center justify-center rounded-lg bg-accent-50",
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
            container: "bg-white",
            label: "text-muted",
            value: "text-secondary",
        },
        paid: {
            container: "bg-primary-50/60",
            label: "text-primary-700",
            value: "text-primary-700",
        },
        refunded: {
            container: "bg-accent-50/50",
            label: "text-accent-700",
            value: "text-accent-700",
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
        ],
    );
};

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
