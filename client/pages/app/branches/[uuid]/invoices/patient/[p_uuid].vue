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
                    @click="fetchSummary()"
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
                                                        ?.patient_code ?? "—"
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
                                v-if="pendingRefundRequests.length"
                                class="flex justify-end border-b border-primary-100 px-6 py-3 sm:px-7 no-print dark:border-primary-500/20"
                            >
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 shadow-sm transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
                                    @click="openRefundReview"
                                >
                                    <AppIcon
                                        name="arrow-down-circle"
                                        class="h-4 w-4"
                                    />

                                    View requested withdrawal

                                    <span
                                        v-if="pendingRefundRequests.length > 1"
                                        class="rounded-full bg-amber-200/70 px-1.5 text-[11px] font-bold dark:bg-amber-500/30"
                                    >
                                        {{ pendingRefundRequests.length }}
                                    </span>
                                </button>
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
                                    action-label="View receipts"
                                    :on-action="openReceiptHistory"
                                />

                                <SummaryCard
                                    label="Credit"
                                    :value="creditOnAccount"
                                    variant="refunded"
                                    :hint="creditHint"
                                    :hint-action-label="
                                        hasRefundable
                                            ? issuingRefund
                                                ? 'Withdrawing…'
                                                : 'Withdraw'
                                            : undefined
                                    "
                                    :hint-action="openCreditRefund"
                                />

                                <SummaryCard
                                    label="Balance Due"
                                    :value="summary.total_balance"
                                    variant="balance"
                                />
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
                                        :value="
                                            formatPhone(
                                                summary.patient.phone_number,
                                            )
                                        "
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
                                            @click="selectTab('overview')"
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
                                            @click="selectTab('admissions')"
                                        >
                                            Admissions

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
                                            @click="selectTab('services')"
                                        >
                                            Services

                                            <span
                                                v-if="activeTab === 'services'"
                                                class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-accent-600"
                                            />
                                        </button>

                                        <button
                                            type="button"
                                            class="relative whitespace-nowrap px-4 py-4 text-sm font-medium transition"
                                            :class="
                                                activeTab === 'invoices'
                                                    ? 'text-primary-700 dark:text-primary-300'
                                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                            "
                                            @click="selectTab('invoices')"
                                        >
                                            Invoices

                                            <span
                                                v-if="activeTab === 'invoices'"
                                                class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary-600"
                                            />
                                        </button>

                                        <button
                                            type="button"
                                            class="relative whitespace-nowrap px-4 py-4 text-sm font-medium transition"
                                            :class="
                                                activeTab === 'transactions'
                                                    ? 'text-primary-700 dark:text-primary-300'
                                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                                            "
                                            @click="selectTab('transactions')"
                                        >
                                            Transactions

                                            <span
                                                v-if="
                                                    activeTab === 'transactions'
                                                "
                                                class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary-600"
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
                                                        Patient Balance Status
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

                                            <button
                                                v-if="summary.invoice_count"
                                                type="button"
                                                class="mt-5 w-full rounded-xl border border-primary-200 px-4 py-2.5 text-xs font-semibold text-primary-700 transition hover:bg-white dark:border-primary-500/30 dark:text-primary-300 dark:hover:bg-white/10"
                                                @click="openAllInvoices"
                                            >
                                                View all invoices and payments
                                            </button>
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

                                                    <p
                                                        v-if="
                                                            summary
                                                                .latest_invoice
                                                                .description
                                                        "
                                                        class="mt-1.5 text-xs text-muted dark:text-gray-400"
                                                    >
                                                        {{
                                                            summary
                                                                .latest_invoice
                                                                .description
                                                        }}
                                                    </p>

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
                                    <SectionLoader
                                        v-if="loadingSection === 'admissions'"
                                        label="Loading admissions…"
                                    />

                                    <PatientAdmissions
                                        v-else
                                        :admissions="admissions"
                                        :discharge-calculation="
                                            summary.discharge_calculation ??
                                            null
                                        "
                                        @view-admission-invoices="
                                            openAdmissionInvoices
                                        "
                                        @view-discharge-termination="
                                            viewDischargeTermination
                                        "
                                        @extend-stay="openExtendModal"
                                    />
                                </div>

                                <div
                                    v-else-if="activeTab === 'services'"
                                    class="px-6 py-6 sm:px-7"
                                >
                                    <SectionLoader
                                        v-if="loadingSection === 'services'"
                                        label="Loading services…"
                                    />

                                    <PatientServices
                                        v-else
                                        :services="services"
                                        @view-service-invoices="
                                            openServiceInvoices
                                        "
                                    />
                                </div>

                                <div
                                    v-else-if="activeTab === 'invoices'"
                                    class="px-6 py-6 sm:px-7"
                                >
                                    <SectionLoader
                                        v-if="loadingSection === 'invoices'"
                                        label="Loading invoices…"
                                    />

                                    <p
                                        v-else-if="!tabInvoices.length"
                                        class="py-10 text-center text-[13px] text-muted dark:text-gray-400"
                                    >
                                        No invoices for this patient yet.
                                    </p>

                                    <ul
                                        v-else
                                        class="divide-y divide-gray-100 dark:divide-white/10"
                                    >
                                        <li
                                            v-for="invoice in tabInvoices"
                                            :key="invoice.invoice_id"
                                            class="flex items-start justify-between gap-3 py-4 first:pt-0 last:pb-0"
                                        >
                                            <div class="min-w-0">
                                                <div
                                                    class="flex flex-wrap items-center gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        class="font-mono text-[15px] font-semibold text-primary-700 hover:underline dark:text-primary-300"
                                                        @click="
                                                            viewInvoice(
                                                                invoice.invoice_code,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            invoice.invoice_code
                                                        }}
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

                                                    <span
                                                        v-if="
                                                            isAdjusted(invoice)
                                                        "
                                                        class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                                                    >
                                                        Adjusted
                                                    </span>
                                                </div>

                                                <p
                                                    v-if="invoice.description"
                                                    class="mt-1 text-[13px] text-muted dark:text-gray-400"
                                                >
                                                    {{ invoice.description }}
                                                </p>

                                                <button
                                                    v-if="
                                                        !invoice.void_reason &&
                                                        latestAdjustment(
                                                            invoice,
                                                        )
                                                    "
                                                    type="button"
                                                    class="mt-1 inline-flex max-w-full items-center gap-1 text-[12px] font-medium text-amber-600 underline underline-offset-2 transition hover:no-underline dark:text-amber-300"
                                                    @click="
                                                        adjustmentInvoice =
                                                            invoice
                                                    "
                                                >
                                                    <span class="truncate">
                                                        {{
                                                            latestAdjustment(
                                                                invoice,
                                                            )?.reason
                                                        }}
                                                    </span>

                                                    <span
                                                        v-if="
                                                            invoice.adjustments &&
                                                            invoice.adjustments
                                                                ?.length > 1
                                                        "
                                                        class="shrink-0 text-amber-500/80"
                                                    >
                                                        +{{
                                                            invoice.adjustments
                                                                .length - 1
                                                        }}
                                                        more
                                                    </span>

                                                    <AppIcon
                                                        name="chevron-right"
                                                        class="h-3 w-3 shrink-0"
                                                    />
                                                </button>

                                                <p
                                                    v-if="invoice.void_reason"
                                                    class="mt-1 text-[12px] text-danger"
                                                >
                                                    Voided:
                                                    {{ invoice.void_reason }}
                                                    <template
                                                        v-if="invoice.voided_by"
                                                    >
                                                        · by
                                                        {{ invoice.voided_by }}
                                                    </template>
                                                </p>

                                                <p
                                                    v-if="invoice.write_off_reason"
                                                    class="mt-1 text-[12px] text-amber-600 dark:text-amber-300"
                                                >
                                                    Written off:
                                                    {{ invoice.write_off_reason }}
                                                    <template
                                                        v-if="invoice.written_off_by"
                                                    >
                                                        · by
                                                        {{ invoice.written_off_by }}
                                                    </template>
                                                </p>

                                                <p
                                                    class="mt-1 text-[12px] text-gray-400 dark:text-gray-500"
                                                >
                                                    {{
                                                        formatDate(
                                                            invoice.created_at,
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div class="shrink-0 text-right">
                                                <p
                                                    v-if="isAdjusted(invoice)"
                                                    class="text-[12px] text-gray-400 line-through dark:text-gray-500"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            invoice.original_total,
                                                        )
                                                    }}
                                                </p>

                                                <p
                                                    class="text-[13px] font-semibold text-secondary dark:text-white"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            invoice.total,
                                                        )
                                                    }}
                                                </p>

                                                <p
                                                    class="mt-0.5 text-[12px]"
                                                    :class="
                                                        Number(
                                                            invoice.balance_due,
                                                        ) > 0
                                                            ? 'font-semibold text-danger'
                                                            : 'text-gray-400 dark:text-gray-500'
                                                    "
                                                >
                                                    {{
                                                        Number(
                                                            invoice.balance_due,
                                                        ) > 0
                                                            ? `₱${formatMoney(invoice.balance_due)} due`
                                                            : "Settled"
                                                    }}
                                                </p>

                                                <div
                                                    class="mt-2 flex items-center justify-end gap-2"
                                                >
                                                    <button
                                                        v-if="
                                                            !isClosedStatus(
                                                                invoice.status,
                                                            )
                                                        "
                                                        type="button"
                                                        class="rounded-lg border border-danger/30 px-3 py-1.5 text-[12px] font-semibold text-danger transition hover:bg-danger/10"
                                                        @click="
                                                            openVoidModal(
                                                                invoice,
                                                            )
                                                        "
                                                    >
                                                        Void
                                                    </button>

                                                    <button
                                                        v-if="
                                                            !isClosedStatus(
                                                                invoice.status,
                                                            ) &&
                                                            Number(
                                                                invoice.balance_due,
                                                            ) > 0
                                                        "
                                                        type="button"
                                                        class="rounded-lg border border-amber-500/40 px-3 py-1.5 text-[12px] font-semibold text-amber-600 transition hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-500/10"
                                                        @click="
                                                            openWriteOffModal(
                                                                invoice,
                                                            )
                                                        "
                                                    >
                                                        Write off
                                                    </button>

                                                    <button
                                                        v-if="
                                                            Number(
                                                                invoice.balance_due,
                                                            ) > 0
                                                        "
                                                        type="button"
                                                        class="rounded-lg bg-primary px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-primary-600"
                                                        @click="
                                                            payFromEntity(
                                                                invoice,
                                                            )
                                                        "
                                                    >
                                                        Pay
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    v-else-if="activeTab === 'transactions'"
                                    class="px-6 py-6 sm:px-7"
                                >
                                    <SectionLoader
                                        v-if="loadingSection === 'invoices'"
                                        label="Loading transactions…"
                                    />

                                    <div
                                        v-else
                                        class="grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8"
                                    >
                                        <section>
                                            <p
                                                class="pb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                                            >
                                                Payments
                                            </p>

                                            <p
                                                v-if="!tabPayments.length"
                                                class="py-8 text-center text-[13px] text-muted dark:text-gray-400"
                                            >
                                                No payment transactions.
                                            </p>

                                            <ul
                                                v-else
                                                class="divide-y divide-gray-100 dark:divide-white/10"
                                            >
                                                <li
                                                    v-for="entry in tabPayments"
                                                    :key="entry.key"
                                                    class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
                                                >
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[13px] font-semibold text-secondary dark:text-white"
                                                        >
                                                            {{ entry.label }}
                                                        </p>

                                                        <div
                                                            class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-gray-400 dark:text-gray-500"
                                                        >
                                                            <span
                                                                v-if="
                                                                    entry.invoiceCode
                                                                "
                                                            >
                                                                {{
                                                                    entry.invoiceCode
                                                                }}
                                                            </span>

                                                            <span>
                                                                {{
                                                                    formatDate(
                                                                        entry.createdAt,
                                                                    )
                                                                }}
                                                            </span>

                                                            <button
                                                                v-if="
                                                                    entry.receiptNo
                                                                "
                                                                type="button"
                                                                :disabled="
                                                                    loadingReceipt ===
                                                                    entry.receiptNo
                                                                "
                                                                class="font-semibold text-primary-700 hover:underline disabled:cursor-wait disabled:opacity-70 dark:text-primary-300"
                                                                @click="
                                                                    openReceiptByNo(
                                                                        entry.receiptNo,
                                                                    )
                                                                "
                                                            >
                                                                {{
                                                                    loadingReceipt ===
                                                                    entry.receiptNo
                                                                        ? "Loading…"
                                                                        : "View receipt"
                                                                }}
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <p
                                                        class="shrink-0 text-[13px] font-semibold text-emerald-600 dark:text-emerald-300"
                                                    >
                                                        +₱{{
                                                            formatMoney(
                                                                Math.abs(
                                                                    entry.amount,
                                                                ),
                                                            )
                                                        }}
                                                    </p>
                                                </li>
                                            </ul>
                                        </section>

                                        <section
                                            class="md:border-l md:border-gray-100 md:pl-8 md:dark:border-white/10"
                                        >
                                            <p
                                                class="pb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted dark:text-gray-400"
                                            >
                                                Withdrawals
                                            </p>

                                            <p
                                                v-if="!tabRefunds.length"
                                                class="py-8 text-center text-[13px] text-muted dark:text-gray-400"
                                            >
                                                No withdrawals.
                                            </p>

                                            <ul
                                                v-else
                                                class="divide-y divide-gray-100 dark:divide-white/10"
                                            >
                                                <li
                                                    v-for="entry in tabRefunds"
                                                    :key="entry.key"
                                                    class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
                                                >
                                                    <div class="min-w-0">
                                                        <div
                                                            class="flex flex-wrap items-center gap-2"
                                                        >
                                                            <p
                                                                class="text-[13px] font-semibold text-secondary dark:text-white"
                                                            >
                                                                {{
                                                                    entry.label
                                                                }}
                                                            </p>

                                                            <span
                                                                v-if="
                                                                    entry.status
                                                                "
                                                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
                                                                :class="
                                                                    statusClasses(
                                                                        entry.status,
                                                                    )
                                                                "
                                                            >
                                                                {{
                                                                    entry.status
                                                                }}
                                                            </span>
                                                        </div>

                                                        <div
                                                            class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-gray-400 dark:text-gray-500"
                                                        >
                                                            <span>
                                                                {{
                                                                    formatDate(
                                                                        entry.createdAt,
                                                                    )
                                                                }}
                                                            </span>
                                                        </div>

                                                        <p
                                                            v-if="entry.reason"
                                                            class="mt-1 text-[12px] text-muted dark:text-gray-400"
                                                        >
                                                            {{ entry.reason }}
                                                        </p>
                                                    </div>

                                                    <p
                                                        class="shrink-0 text-[13px] font-semibold"
                                                        :class="
                                                            entry.amount
                                                                ? 'text-danger'
                                                                : 'text-gray-400 dark:text-gray-500'
                                                        "
                                                    >
                                                        {{
                                                            entry.amount
                                                                ? "−"
                                                                : ""
                                                        }}₱{{
                                                            formatMoney(
                                                                Math.abs(
                                                                    entry.amount,
                                                                ),
                                                            )
                                                        }}
                                                    </p>
                                                </li>
                                            </ul>
                                        </section>
                                    </div>
                                </div>
                            </section>
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
                                    @click="openInvoicePicker"
                                >
                                    Choose invoices
                                </button>
                            </div>

                            <div class="space-y-4 p-6">
                                <div>
                                    <label
                                        class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                                    >
                                        Received from

                                        <span
                                            class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[9px] font-medium normal-case tracking-normal text-muted dark:bg-white/10 dark:text-gray-400"
                                        >
                                            Optional
                                        </span>
                                    </label>

                                    <input
                                        v-model="payorName"
                                        type="text"
                                        class="mt-1.5 w-full rounded-xl border border-primary-100 bg-white px-3.5 py-2.5 text-sm text-secondary outline-none transition focus:border-primary dark:border-primary-500/20 dark:bg-secondary dark:text-white"
                                    />
                                </div>

                                <div
                                    v-if="hasCredit"
                                    class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                                >
                                    <label
                                        class="flex cursor-pointer items-start gap-3"
                                    >
                                        <input
                                            v-model="useCredit"
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500/30 dark:border-white/20 dark:bg-transparent"
                                        />

                                        <span class="min-w-0 flex-1">
                                            <span
                                                class="flex flex-wrap items-center justify-between gap-2"
                                            >
                                                <span
                                                    class="text-sm font-semibold text-emerald-900 dark:text-emerald-300"
                                                >
                                                    Use credit on account
                                                </span>

                                                <span
                                                    class="text-sm font-bold text-emerald-700 dark:text-emerald-300"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            availableCredit,
                                                        )
                                                    }}
                                                </span>
                                            </span>

                                            <span
                                                class="mt-0.5 block text-xs text-emerald-800/80 dark:text-emerald-300/70"
                                            >
                                                Money already paid that no
                                                invoice claims any more.
                                            </span>
                                        </span>
                                    </label>

                                    <div
                                        v-if="useCredit"
                                        class="mt-3 space-y-1 border-t border-emerald-200/70 pt-3 text-xs dark:border-emerald-500/20"
                                    >
                                        <div
                                            class="flex justify-between gap-3 text-emerald-800/80 dark:text-emerald-300/70"
                                        >
                                            <span>Credit applied</span>
                                            <span class="font-semibold">
                                                − ₱{{
                                                    formatMoney(creditToApply)
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="flex justify-between gap-3 text-emerald-900 dark:text-emerald-300"
                                        >
                                            <span class="font-semibold">
                                                {{
                                                    creditCoversEverything
                                                        ? "Nothing left to collect"
                                                        : "Still to collect in cash"
                                                }}
                                            </span>

                                            <span class="font-bold">
                                                ₱{{
                                                    formatMoney(
                                                        balanceAfterCredit,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <p
                                            v-if="
                                                availableCredit > creditToApply
                                            "
                                            class="pt-1 text-emerald-800/70 dark:text-emerald-300/60"
                                        >
                                            ₱{{
                                                formatMoney(
                                                    availableCredit -
                                                        creditToApply,
                                                )
                                            }}
                                            stays on the account.
                                        </p>
                                    </div>
                                </div>

                                <button
                                    v-if="creditCoversEverything"
                                    type="button"
                                    :disabled="processingPayment"
                                    class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="applyCreditOnly"
                                >
                                    {{
                                        processingPayment
                                            ? "Applying credit…"
                                            : `Settle ₱${formatMoney(creditToApply)} with credit`
                                    }}
                                </button>

                                <PaymentForm
                                    v-else
                                    :processing="processingPayment"
                                    :total-amount="balanceAfterCredit"
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

                        <!-- <div
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
                        </div> -->
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
                v-if="decliningRefund"
                class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
                @click.self="closeDeclineRefund"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
                >
                    <p
                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                    >
                        Decline refund
                    </p>

                    <h3
                        class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                    >
                        Decline ₱{{ formatMoney(decliningRefund.amount) }}?
                    </h3>

                    <p
                        class="mt-2 text-xs leading-5 text-muted dark:text-gray-400"
                    >
                        The family will see this reason on their request, and
                        the credit stays on the account.
                    </p>

                    <label
                        class="mt-4 block text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                    >
                        Reason
                        <span class="text-danger">*</span>
                    </label>

                    <textarea
                        v-model="declineReason"
                        rows="2"
                        class="mt-1.5 w-full rounded-xl border border-primary-100 px-3 py-2 text-sm text-secondary focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            :disabled="decliningInProgress"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-slate-100 hover:text-secondary disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                            @click="closeDeclineRefund"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="
                                decliningInProgress || !declineReason.trim()
                            "
                            class="rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-danger/90 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="confirmDeclineRefund"
                        >
                            {{
                                decliningInProgress
                                    ? "Declining..."
                                    : "Decline request"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <VoidInvoiceModal
            :invoice="voidTarget"
            :reason="voidReason"
            :processing="voidingInvoice"
            @update:reason="voidReason = $event"
            @confirm="confirmVoid"
            @close="closeVoidModal"
        />

        <WriteOffInvoiceModal
            :invoice="writeOffTarget"
            :reason="writeOffReason"
            :processing="writingOffInvoice"
            @update:reason="writeOffReason = $event"
            @confirm="confirmWriteOff"
            @close="closeWriteOffModal"
        />

        <RefundReviewModal
            :open="refundReviewOpen"
            :requests="pendingRefundRequests"
            :credit="creditOnAccount"
            :processing="processingRefund"
            :error-message="refundError"
            @approve="approveRefundRequest"
            @decline="declineRefundRequest"
            @close="closeRefundReview"
        />

        <InvoiceAdjustmentModal
            :invoice="adjustmentInvoiceView"
            @close="adjustmentInvoice = null"
        />

        <BillingCycleModal
            :open="extendModalOpen"
            :admission="extendAdmission as any"
            @select="handleExtendSelect"
            @close="closeExtendModal"
        />

        <EntityInvoicesModal
            :open="entityScope !== null"
            :eyebrow="entityScope?.eyebrow ?? ''"
            :title="entityScope?.title ?? ''"
            :subtitle="entityScope?.subtitle"
            :invoices="entityInvoices"
            :loading="entityLoading"
            :loading-receipt="loadingReceipt"
            @view-invoice="viewInvoice"
            @view-receipt="openReceiptByNo"
            @pay-invoice="payFromEntity"
            @void-invoice="openVoidModal"
            @write-off-invoice="openWriteOffModal"
            @close="closeEntityInvoices"
        />

        <ExtendPaymentModal
            :open="extendPaymentOpen"
            :amount-due="extendAmountDue"
            :plan-label="extendPlanLabel"
            :coverage-label="extendCoverageLabel"
            :cash="extendCash"
            :payor-name="extendPayor"
            :processing="extending"
            @update:cash="extendCash = $event"
            @update:payor-name="extendPayor = $event"
            @confirm="confirmExtendPayment"
            @close="cancelExtendPayment"
        />

        <DischargeCalculationModal
            :calculation="
                dischargeModalOpen ? selectedDischargeCalculation : null
            "
            @close="closeDischargeTermination"
        />

        <PaymentReceipt
            v-if="activeReceipt"
            :receipt="activeReceipt"
            @close="activeReceipt = null"
        />

        <BillingHistoryModal
            :mode="historyMode"
            :patient-name="summary?.patient?.full_name"
            :receipts="receiptGroups"
            :refunds="refundHistory"
            :loading-receipt="loadingReceipt"
            @open-receipt="openReceiptByNo"
            @close="closeHistory"
        />

        <InvoicePickerModal
            :open="invoicePickerOpen"
            :invoices="payableInvoices"
            :patient-name="summary?.patient?.full_name"
            :selected="selectedInvoiceCodes"
            :amounts="invoiceAmounts"
            :total-balance="Number(summary?.total_balance ?? 0)"
            @update:selected="selectedInvoiceCodes = $event"
            @update:amounts="invoiceAmounts = $event"
            @close="invoicePickerOpen = false"
        />
    </div>
    <WithdrawCreditsModal
        :open="creditRefundOpen"
        :available="totalRefundable"
        :amount="refundAmount"
        :processing="issuingRefund"
        :error-message="creditRefundError"
        @update:amount="setCreditRefundAmount"
        @confirm="confirmCreditRefund"
        @close="creditRefundOpen = false"
    />
</template>

<script lang="ts" setup>
import { computed, h, onMounted, ref } from "vue";
import { Loader2, Receipt, Undo2 } from "lucide-vue-next";
import { useRoute, useRouter } from "vue-router";
import { admissionService } from "~/api/admission/AdmissionService";
import { invoiceService } from "~/api/invoice/InvoiceService";
import { refundService } from "~/api/refund/RefundService";
import { formatAmount } from "~/utils/currency";
import { statusClasses } from "~/utils/invoiceStatus";
import { amountFor as resolveInvoiceAmount } from "~/utils/invoiceSelection";
import BillingCycleModal from "~/components/sections/app/Patient/BillingCycleModal.vue";
import BillingHistoryModal from "~/components/sections/app/Billing/BillingHistoryModal.vue";
import WithdrawCreditsModal from "~/components/sections/app/Billing/WithdrawCreditsModal.vue";
import DischargeCalculationModal from "~/components/sections/app/Billing/DischargeCalculationModal.vue";
import EntityInvoicesModal from "~/components/sections/app/Billing/EntityInvoicesModal.vue";
import ExtendPaymentModal from "~/components/sections/app/Billing/ExtendPaymentModal.vue";
import InvoicePickerModal from "~/components/sections/app/Billing/InvoicePickerModal.vue";
import RefundReviewModal from "~/components/sections/app/Billing/RefundReviewModal.vue";
import InvoiceAdjustmentModal from "~/components/sections/portal/InvoiceAdjustmentModal.vue";
import SectionLoader from "~/components/sections/app/Billing/SectionLoader.vue";
import VoidInvoiceModal from "~/components/sections/app/Billing/VoidInvoiceModal.vue";
import WriteOffInvoiceModal from "~/components/sections/app/Billing/WriteOffInvoiceModal.vue";
import PatientAdmissions from "~/components/sections/app/Billing/PatientAdmissions.vue";
import PatientServices from "~/components/sections/app/Billing/PatientServices.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import AppIcon from "~/components/ui/AppIcon.vue";
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

const activeTab = ref<
    "overview" | "admissions" | "services" | "invoices" | "transactions"
>("overview");

const refundReviewOpen = ref(false);

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

// The picker is the only thing on the page that needs the full invoice list,
// so that section is fetched when it is opened.
async function openInvoicePicker() {
    await loadSection("invoices");
    invoicePickerOpen.value = true;
}
const transactionsOpen = ref(false);

const historyMode = computed<"receipts" | "refunds" | null>(() =>
    transactionsOpen.value ? "receipts" : null,
);

const transactions = computed(() =>
    [
        ...(summary.value?.invoices ?? []),
        ...(summary.value?.voided_invoices ?? []),
    ]
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

const receiptGroups = computed(() => {
    const groups = new Map<
        string,
        {
            key: string;
            payment_code: string | null;
            amount: number;
            invoice_codes: string[];
            payment_method: string;
            created_at: string | null;
        }
    >();

    for (const payment of transactions.value) {
        const amount = Number(payment.amount ?? 0);

        if (amount <= 0) {
            continue;
        }

        const key = payment.payment_code ?? `payment-${payment.payment_id}`;
        const existing = groups.get(key);

        if (existing) {
            existing.amount += amount;

            if (!existing.invoice_codes.includes(payment.invoice_code)) {
                existing.invoice_codes.push(payment.invoice_code);
            }

            continue;
        }

        groups.set(key, {
            key,
            payment_code: payment.payment_code ?? null,
            amount,
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

const allInvoices = computed(() =>
    [
        ...(summary.value?.invoices ?? []),
        ...(summary.value?.voided_invoices ?? []),
    ].sort(
        (a, b) =>
            new Date(b.created_at ?? 0).getTime() -
            new Date(a.created_at ?? 0).getTime(),
    ),
);

// The Invoices tab lists every invoice on the account, voided ones included,
// newest first.
const tabInvoices = computed(() => allInvoices.value);

const adjustmentInvoice = ref<any | null>(null);

// The history dialog is shared with the portal, which names the pair the other
// way round: total is what was billed, adjusted_total what is asked for now.
const adjustmentInvoiceView = computed(() =>
    adjustmentInvoice.value
        ? {
              ...adjustmentInvoice.value,
              total: Number(adjustmentInvoice.value.original_total ?? 0),
              adjusted_total: Number(adjustmentInvoice.value.total ?? 0),
          }
        : null,
);

function isAdjusted(invoice: any) {
    return (
        Number(invoice.original_total ?? invoice.total ?? 0) !==
        Number(invoice.total ?? 0)
    );
}

// However many an invoice has collected, the row carries the most recent one
// and the rest are read in the history dialog.
function latestAdjustment(invoice: any) {
    return [...(invoice.adjustments ?? [])].sort(
        (a: any, b: any) =>
            new Date(b.created_at ?? 0).getTime() -
            new Date(a.created_at ?? 0).getTime(),
    )[0];
}

// Each side of the ledger is its own column, read from its own table: a
// payment is one record with one amount, and so is a refund.
const byNewest = (a: { createdAt: string | null }, b: typeof a) =>
    new Date(b.createdAt ?? 0).getTime() - new Date(a.createdAt ?? 0).getTime();

const entryLabel = (
    code: string | null,
    type: string,
    method?: string | null,
) => [code, type, methodLabel(method)].filter(Boolean).join(" · ");

const tabPayments = computed(() =>
    (summary.value?.payments ?? [])
        .map((payment: any) => ({
            key: `payment-${payment.payment_id}`,
            label: entryLabel(
                payment.transaction_code,
                "Payment",
                payment.payment_method,
            ),
            invoiceCode: (payment.invoice_codes ?? []).join(", "),
            reference: payment.reference_id ?? null,
            receiptNo: payment.payment_code ?? null,
            amount: Number(payment.amount ?? 0),
            createdAt: payment.created_at ?? null,
        }))
        .sort(byNewest),
);

const tabRefunds = computed(() =>
    (summary.value?.refunds ?? [])
        .map((refund: any) => {
            const status = (refund.status ?? "").toLowerCase();

            return {
                key: `refund-${refund.refund_id}`,
                label: entryLabel(
                    refund.refund_code,
                    "Withdrawal",
                    refund.refund_method,
                ),
                reference: refund.refund_code ?? null,
                amount: ["completed", "approved"].includes(status)
                    ? Number(refund.amount ?? 0)
                    : 0,
                status: refund.status,
                reason: status === "rejected" ? refund.declined_reason : null,
                createdAt: refund.created_at ?? null,
            };
        })
        .sort(byNewest),
);

const isVoided = (status?: string) => (status ?? "").toLowerCase() === "void";

const isClosedStatus = (status?: string) => {
    const value = (status ?? "").toLowerCase();

    return value === "void" || value === "written off" || value === "written_off";
};

const issuingRefund = ref(false);

const totalRefundable = computed(() =>
    Number(summary.value?.total_refundable ?? 0),
);

const hasRefundable = computed(() => totalRefundable.value > 0);

const pendingWithdrawal = computed(() =>
    Number(summary.value?.total_refund_requested ?? 0),
);

// Credit claimed by a withdrawal awaiting a decision is still the family's
// money, so it stays in the figure even though it cannot be spent yet.
const creditOnAccount = computed(
    () => totalRefundable.value + pendingWithdrawal.value,
);

const creditHint = computed(() => {
    if (pendingWithdrawal.value > 0) {
        return `₱${formatMoney(pendingWithdrawal.value)} being withdrawn`;
    }

    return `₱${formatMoney(summary.value?.total_withdrawn ?? 0)} already withdrawn`;
});

const refundAmount = ref<number | null>(null);

function setCreditRefundAmount(value: number | string | null) {
    refundAmount.value =
        value === "" || value === null || Number.isNaN(Number(value))
            ? null
            : Number(value);
}

const creditRefundOpen = ref(false);

const creditRefundError = computed(() => {
    const amount = Number(refundAmount.value ?? 0);

    if (refundAmount.value === null || refundAmount.value === ("" as any)) {
        return "";
    }

    if (!Number.isFinite(amount) || amount <= 0) {
        return "Enter an amount greater than 0.";
    }

    if (amount > totalRefundable.value + 0.01) {
        return `Only ₱${formatMoney(totalRefundable.value)} is available.`;
    }

    return "";
});

function openCreditRefund() {
    refundAmount.value = totalRefundable.value;
    creditRefundOpen.value = true;
}

async function confirmCreditRefund() {
    if (creditRefundError.value) return;

    await issueRefunds();

    creditRefundOpen.value = false;
}

async function issueRefunds() {
    if (issuingRefund.value) return;

    if (totalRefundable.value <= 0) return;

    const requested = Number(refundAmount.value ?? 0);

    if (requested > totalRefundable.value + 0.01) {
        error("That is more than the credit on this patient's account.");

        return;
    }

    issuingRefund.value = true;

    try {
        const res = await refundService.issue({
            p_uuid: patientUuid.value,
            branch_uuid: uuid.value,
            ...(requested > 0 ? { amount: requested } : {}),
        });

        success(
            res?.message ??
                (requested > 0
                    ? `Withdrawal of ₱${formatMoney(requested)} recorded.`
                    : "Withdrawal recorded."),
        );

        refundAmount.value = null;

        await fetchSummary();
    } catch (err: any) {
        error(
            err?.data?.message ??
                err?.response?.data?.message ??
                err?.message ??
                "Unable to withdraw the credit.",
        );

        await fetchSummary();
    } finally {
        issuingRefund.value = false;
    }
}

const voidTarget = ref<PatientInvoiceItem | null>(null);
const voidReason = ref("");
const voidingInvoice = ref(false);

function openVoidModal(invoice: PatientInvoiceItem) {
    voidTarget.value = invoice;
    voidReason.value = "";
}

function closeVoidModal() {
    if (voidingInvoice.value) return;

    voidTarget.value = null;
    voidReason.value = "";
}

async function confirmVoid() {
    if (!voidTarget.value || voidingInvoice.value || !voidReason.value.trim())
        return;

    voidingInvoice.value = true;

    try {
        const res = await invoiceService.action({
            type: "void",
            branch_uuid: uuid.value,
            p_uuid: patientUuid.value,
            invoice_code: voidTarget.value.invoice_code,
            reason: voidReason.value.trim() || undefined,
        });

        success(res.message ?? "Invoice voided successfully.");

        summary.value = res?.data?.data ?? res?.data ?? null;

        voidTarget.value = null;
        voidReason.value = "";
    } catch (err: any) {
        error(
            err?.data?.message ??
                err?.response?.data?.message ??
                err?.message ??
                "Failed to void invoice. Please try again.",
        );
    } finally {
        voidingInvoice.value = false;
    }
}

const writeOffTarget = ref<PatientInvoiceItem | null>(null);
const writeOffReason = ref("");
const writingOffInvoice = ref(false);

function openWriteOffModal(invoice: PatientInvoiceItem) {
    writeOffTarget.value = invoice;
    writeOffReason.value = "";
}

function closeWriteOffModal() {
    if (writingOffInvoice.value) return;

    writeOffTarget.value = null;
    writeOffReason.value = "";
}

async function confirmWriteOff() {
    if (
        !writeOffTarget.value ||
        writingOffInvoice.value ||
        !writeOffReason.value.trim()
    )
        return;

    writingOffInvoice.value = true;

    try {
        const res = await invoiceService.action({
            type: "write-off",
            branch_uuid: uuid.value,
            p_uuid: patientUuid.value,
            invoice_code: writeOffTarget.value.invoice_code,
            reason: writeOffReason.value.trim() || undefined,
        });

        success(res.message ?? "Invoice written off successfully.");

        summary.value = res?.data?.data ?? res?.data ?? null;

        writeOffTarget.value = null;
        writeOffReason.value = "";
    } catch (err: any) {
        error(
            err?.data?.message ??
                err?.response?.data?.message ??
                err?.message ??
                "Failed to write off invoice. Please try again.",
        );
    } finally {
        writingOffInvoice.value = false;
    }
}

function amountFor(code: string) {
    return resolveInvoiceAmount(
        payableInvoices.value,
        invoiceAmounts.value,
        code,
    );
}

const allocations = computed(() =>
    Object.fromEntries(
        selectedInvoiceCodes.value
            .map((code) => [code, amountFor(code)] as const)
            .filter(([, amount]) => amount > 0),
    ),
);

const useCredit = ref(false);

const availableCredit = computed(() =>
    Number(summary.value?.total_refundable ?? 0),
);

const hasCredit = computed(() => availableCredit.value > 0);

const creditToApply = computed(() =>
    useCredit.value
        ? Math.round(
              Math.min(availableCredit.value, selectedBalance.value) * 100,
          ) / 100
        : 0,
);

const balanceAfterCredit = computed(
    () =>
        Math.round(
            Math.max(0, selectedBalance.value - creditToApply.value) * 100,
        ) / 100,
);

const creditCoversEverything = computed(
    () => useCredit.value && balanceAfterCredit.value <= 0,
);

async function applyCreditOnly() {
    await handleCashPay(0);
}

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

function invoiceCodesLabel(codes: string[]) {
    if (codes.length <= 2) return codes.join(", ");

    return `${codes.slice(0, 2).join(", ")} +${codes.length - 2} more`;
}

function closeHistory() {
    transactionsOpen.value = false;
}

// Receipts are read from the invoices, so that section has to be on hand
// before the history can list anything.
async function openReceiptHistory() {
    await loadSection("invoices");
    transactionsOpen.value = true;
}

const loadingReceipt = ref<string | null>(null);

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
            (row: PaymentReceiptData) => row.payment_code === receiptNo,
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

// What the family has asked for and accounting has not answered yet.
const pendingRefundRequests = computed(() =>
    (summary.value?.refunds ?? []).filter(
        (refund: any) => (refund.status ?? "").toLowerCase() === "requested",
    ),
);

const decliningRefund = ref<any | null>(null);
const declineReason = ref("");
const decliningInProgress = ref(false);

function openDeclineRefund(request: any) {
    decliningRefund.value = request;
    declineReason.value = "";
}

function closeDeclineRefund() {
    if (decliningInProgress.value) return;

    decliningRefund.value = null;
    declineReason.value = "";
}

async function confirmDeclineRefund() {
    if (!decliningRefund.value || !declineReason.value.trim()) return;

    decliningInProgress.value = true;

    try {
        const res = await invoiceService.action({
            type: "decline-refund",
            branch_uuid: uuid.value,
            p_uuid: patientUuid.value,
            refund_id: decliningRefund.value.refund_id,
            reason: declineReason.value.trim(),
        });

        success(res.message ?? "Refund request declined.");

        summary.value = res?.data?.data ?? res?.data ?? summary.value;

        decliningRefund.value = null;
        declineReason.value = "";
    } catch (err: any) {
        error(
            err?.data?.message ??
                err?.response?.data?.message ??
                err?.message ??
                "Failed to decline the refund request.",
        );
    } finally {
        decliningInProgress.value = false;
    }
}

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

function openRefundReview() {
    refundError.value = "";
    refundReviewOpen.value = true;
}

function closeRefundReview() {
    if (processingRefund.value) {
        return;
    }

    refundReviewOpen.value = false;
}

// Declining asks for a reason, so it hands over to that dialog rather than
// stacking it on top of the review.
function declineRefundRequest(request: any) {
    refundReviewOpen.value = false;
    openDeclineRefund(request);
}

async function approveRefundRequest(request: any) {
    const refundId = request?.refund_id;

    if (!summary.value || !refundId) {
        refundError.value = "There is no refund request to approve.";

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
            refund_id: refundId,
            type: "refund",
        });

        success(res.message ?? "Refund processed successfully.");
        refundReviewOpen.value = false;
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

// Opening the page fetches the summary cards and the admissions, and nothing
// else. Services load with their tab; the invoice list loads only when the
// payment panel or a history dialog actually needs it.
const INITIAL_SECTIONS = ["admissions", "refunds"];
const BACKGROUND_SECTIONS = ["services"];

// Sections the tabs pull in on demand.
const LAZY_SECTIONS = ["services", "invoices"];

// The transactions tab reads the payments carried on the invoices section.
const SECTION_FOR_TAB: Record<string, string> = { transactions: "invoices" };

const loadedSections = ref(new Set<string>());
const pendingSections = ref(new Set<string>());

// Keyed by the section a tab reads, not the tab itself: Transactions is fed by
// the invoices section, so it has to watch that one to know it is still loading.
const loadingSection = computed(() => {
    const section = SECTION_FOR_TAB[activeTab.value] ?? activeTab.value;

    return pendingSections.value.has(section) ? section : null;
});

async function fetchSummary(sections: string[] = INITIAL_SECTIONS) {
    loading.value = true;
    errors.value = "";

    try {
        const response = await invoiceService.show(
            {
                branch_uuid: uuid.value,
                p_uuid: patientUuid.value,
                mode: route.query.mode,
                sections: sections.join(","),
            },
            patientUuid.value,
        );

        const data = response.data ?? response ?? null;

        summary.value =
            summary.value && data ? { ...summary.value, ...data } : data;

        sections.forEach((section) => loadedSections.value.add(section));

        // This also runs after money has moved, so every other section already
        // held is out of date. They are dropped rather than re-fetched, and
        // pulled again only when something asks for them.
        const stale = ["invoices", ...BACKGROUND_SECTIONS].filter(
            (section) => !sections.includes(section),
        );

        stale.forEach((section) => loadedSections.value.delete(section));

        if (stale.includes(activeTab.value)) {
            loadSection(activeTab.value);
        }
    } catch (err) {
        console.error(err);
        errors.value = "Unable to load invoices for this patient.";
    } finally {
        loading.value = false;
    }
}

// The invoices behind one admission or one service, fetched when that record
// is opened rather than carried in the lists.
type EntityScope = {
    kind: "admission" | "service" | "patient";
    id: number;
    eyebrow: string;
    title: string;
    subtitle?: string | null;
};

const entityScope = ref<EntityScope | null>(null);
const entityInvoices = ref<PatientInvoiceItem[]>([]);
const entityLoading = ref(false);

async function openEntityInvoices(scope: EntityScope) {
    entityScope.value = scope;
    entityInvoices.value = [];
    entityLoading.value = true;

    try {
        const response = await invoiceService.show(
            {
                branch_uuid: uuid.value,
                p_uuid: patientUuid.value,
                mode: route.query.mode,
                sections:
                    scope.kind === "admission"
                        ? "admission_invoices"
                        : "service_invoices",
                ...(scope.kind === "admission"
                    ? { admission_id: scope.id }
                    : { schedule_services_id: scope.id }),
            },
            patientUuid.value,
        );

        const data = response.data ?? response ?? null;

        entityInvoices.value =
            data?.admission_invoices ?? data?.service_invoices ?? [];
    } catch (err) {
        console.error(err);
        error("Unable to load the invoices for this record.");
    } finally {
        entityLoading.value = false;
    }
}

function openAdmissionInvoices(admissionId: number) {
    const admission = admissions.value.find(
        (row) => row.patient_admission_id === admissionId,
    );

    openEntityInvoices({
        kind: "admission",
        id: admissionId,
        eyebrow: "Admission",
        title: admission?.room?.room_no
            ? `Room ${admission.room.room_no}`
            : `Admission #${admissionId}`,
        subtitle: admission
            ? `${formatDate(admission.admission_date)} — ${formatDate(
                  admission.discharge_date,
              )}`
            : null,
    });
}

function openServiceInvoices(scheduleServiceId: number) {
    const service = services.value.find(
        (row: any) => row.schedule_services_id === scheduleServiceId,
    );

    openEntityInvoices({
        kind: "service",
        id: scheduleServiceId,
        eyebrow: "Service",
        title: service?.service_name ?? `Service #${scheduleServiceId}`,
        subtitle: null,
    });
}

async function openAllInvoices() {
    entityScope.value = {
        kind: "patient",
        id: 0,
        eyebrow: "All",
        title: summary.value?.patient?.full_name ?? "Patient",
        subtitle: `${summary.value?.invoice_count ?? 0} invoice(s) on this patient `,
    };

    entityInvoices.value = [];
    entityLoading.value = true;

    try {
        await loadSection("invoices");

        entityInvoices.value = [
            ...(summary.value?.invoices ?? []),
            ...(summary.value?.voided_invoices ?? []),
        ];
    } finally {
        entityLoading.value = false;
    }
}

function closeEntityInvoices() {
    entityScope.value = null;
    entityInvoices.value = [];
}

async function payFromEntity(invoice: PatientInvoiceItem) {
    await loadSection("invoices");

    selectedInvoiceCodes.value = [invoice.invoice_code];
    invoiceAmounts.value = {
        [invoice.invoice_code]: Number(invoice.balance_due ?? 0),
    };

    closeEntityInvoices();
}

async function loadSection(section: string) {
    if (
        loadedSections.value.has(section) ||
        pendingSections.value.has(section)
    ) {
        return;
    }

    pendingSections.value.add(section);

    try {
        const response = await invoiceService.show(
            {
                branch_uuid: uuid.value,
                p_uuid: patientUuid.value,
                mode: route.query.mode,
                sections: section,
            },
            patientUuid.value,
        );

        const data = response.data ?? response ?? null;

        if (data) {
            summary.value = { ...(summary.value ?? {}), ...data };
            loadedSections.value.add(section);
        }
    } catch (err) {
        console.error(err);
        if (activeTab.value === section) {
            error(`Unable to load ${section}.`);
        }
    } finally {
        pendingSections.value.delete(section);
    }
}

function selectTab(
    tab: "overview" | "admissions" | "services" | "invoices" | "transactions",
) {
    activeTab.value = tab;

    const section = SECTION_FOR_TAB[tab] ?? tab;

    if (LAZY_SECTIONS.includes(section)) {
        loadSection(section);
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
            ...(useCredit.value
                ? { use_credit: true, credit_amount: creditToApply.value }
                : {}),
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
        error(err?.message ?? "Payment failed. Please try again.");
    } finally {
        processingPayment.value = false;
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

const extendModalOpen = ref(false);
const extendAdmissionId = ref<number | null>(null);
const extending = ref(false);

const extendAdmission = computed(
    () =>
        admissions.value.find(
            (admission) =>
                admission.patient_admission_id === extendAdmissionId.value,
        ) ?? null,
);

function openExtendModal(admissionId: number) {
    extendAdmissionId.value = admissionId;
    extendModalOpen.value = true;
}

function closeExtendModal() {
    extendModalOpen.value = false;
    extendAdmissionId.value = null;
}

// The plan chosen in the first step, held until the cash for it is taken.
type ExtendSelection = {
    contract: {
        contract_id: number;
        accommodation_type?: string | null;
        billing_cycle?: string | null;
        price?: number | string;
    };
    end_date: string;
    room?: { room_id: number };
    bed?: { bed_id: number };
};

const pendingExtend = ref<ExtendSelection | null>(null);
const extendCash = ref<number | string | null>(null);
const extendPayor = ref("");

const extendPaymentOpen = computed(() => pendingExtend.value !== null);

const extendAmountDue = computed(() =>
    Number(pendingExtend.value?.contract?.price ?? 0),
);

const extendPlanLabel = computed(() => {
    const contract = pendingExtend.value?.contract;

    if (!contract) return "Extension";

    return [contract.accommodation_type, contract.billing_cycle]
        .filter(Boolean)
        .join(" · ");
});

const extendCoverageLabel = computed(() =>
    pendingExtend.value?.end_date
        ? `Covered until ${formatDate(pendingExtend.value.end_date)}`
        : null,
);

function handleExtendSelect(payload: ExtendSelection) {
    if (!extendAdmissionId.value) {
        return;
    }

    pendingExtend.value = payload;
    extendCash.value = Number(payload.contract?.price ?? 0) || null;
    extendPayor.value = "";
    extendModalOpen.value = false;
}

function cancelExtendPayment() {
    pendingExtend.value = null;
    extendCash.value = null;
    extendAdmissionId.value = null;
}

async function confirmExtendPayment() {
    const selection = pendingExtend.value;

    if (!selection || !extendAdmissionId.value) {
        return;
    }

    extending.value = true;

    try {
        const response = await admissionService.action({
            branch_uuid: uuid.value,
            p_uuid: patientUuid.value,
            admission_id: extendAdmissionId.value,
            action: "extend",
            end_date: selection.end_date,
            contract_id: selection.contract.contract_id,
            ...(selection.room ? { room_id: selection.room.room_id } : {}),
            ...(selection.bed ? { bed_id: selection.bed.bed_id } : {}),
            require_payment: true,
            cash: Number(extendCash.value ?? 0),
            payment_method: "CASH",
            payor_name: extendPayor.value.trim(),
            include_patient: false,
        });

        success(response?.message ?? "Stay extended and paid.");

        cancelExtendPayment();

        if (response?.receipt) {
            activeReceipt.value = response.receipt;
        }
        await fetchSummary();
    } catch (err: any) {
        console.error(err);
        error(
            err?.data?.message ??
                err?.response?.data?.message ??
                "Unable to extend this stay.",
        );
    } finally {
        extending.value = false;
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

onMounted(() => fetchSummary());

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
    hint?: string;
    hintActionLabel?: string;
    hintAction?: () => void;
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
            props.hint
                ? h(
                      "div",
                      { class: "mt-1.5 flex flex-wrap items-center gap-2" },
                      [
                          h(
                              "span",
                              {
                                  class: "text-[11px] text-muted dark:text-gray-400",
                              },
                              props.hint,
                          ),
                          props.hintActionLabel && props.hintAction
                              ? h(
                                    "button",
                                    {
                                        type: "button",
                                        class: "rounded-md bg-accent-600 px-2 py-0.5 text-[10px] font-semibold text-white transition hover:bg-accent-700",
                                        onClick: props.hintAction,
                                    },
                                    props.hintActionLabel,
                                )
                              : null,
                      ],
                  )
                : null,
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

// Without this the kebab-case attributes never reach their camelCase props,
// so the action buttons silently never render.
SummaryCard.props = [
    "label",
    "value",
    "variant",
    "hint",
    "hintActionLabel",
    "hintAction",
    "actionLabel",
    "onAction",
];

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
