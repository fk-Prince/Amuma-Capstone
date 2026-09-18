<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-primary-950/60 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-4xl max-h-[90dvh] overflow-y-auto rounded-2xl bg-white shadow-[0_20px_60px_-15px_rgba(10,40,87,0.35)] ring-1 ring-black/5 dark:bg-secondary"
                >
                    <div
                        class="flex items-start gap-4 border-b border-slate-100 px-4 sm:px-8 py-5 sm:py-7 dark:border-white/10"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600 ring-1 ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-5.5 w-5.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14A2 2 0 003.84 21h16.32a2 2 0 001.73-3.14l-8.18-14a2 2 0 00-3.42 0z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3
                                class="text-xl font-semibold tracking-tight text-primary-950 dark:text-primary-300"
                            >
                                Discharge patient
                            </h3>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                This ends the patient's current admission and
                                future periods. This action cannot be undone.
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="loading"
                            class="ml-auto flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            @click="handleClose"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-4.5 w-4.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="px-4 sm:px-8 py-5 sm:py-7">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <section class="flex flex-col">
                                <h4
                                    class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    Current period
                                </h4>

                                <div
                                    v-if="showCurrentPeriodBlock"
                                    class="flex flex-1 flex-col rounded-xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-base font-semibold text-primary-950 dark:text-primary-300"
                                            >
                                                {{ currentBillingCycleLabel }}
                                                billing
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                                            >
                                                {{
                                                    currentInvoice?.invoice_code
                                                }}
                                            </p>

                                            <!-- <p
                                                v-if="invoiceCoversMorePeriods"
                                                class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                                            >
                                                This period
                                                {{
                                                    formatCurrency(periodPrice)
                                                }}
                                                — the invoice also covers
                                                another period of this stay
                                            </p> -->
                                        </div>

                                        <p
                                            class="shrink-0 text-base font-semibold text-primary-950 dark:text-primary-300"
                                        >
                                            {{
                                                formatCurrency(
                                                    invoiceTotal || periodPrice,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <!-- PAYMENT SUMMARY -->
                                    <div
                                        v-if="
                                            hasPaidAmount || hasRefundableAmount
                                        "
                                        class="mt-5 flex flex-col sm:flex-row gap-2.5"
                                    >
                                        <div
                                            v-if="hasPaidAmount"
                                            class="flex-1 rounded-lg bg-slate-50 p-3 dark:bg-white/5"
                                        >
                                            <p
                                                class="text-[11px] text-slate-500 dark:text-gray-400"
                                            >
                                                Paid
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold text-primary-950 dark:text-primary-300"
                                            >
                                                {{
                                                    formatCurrency(
                                                        currentNetPaidAmount,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            v-if="hasRefundableAmount"
                                            class="flex-1 rounded-lg p-3"
                                            :class="
                                                isEligibleForRefund
                                                    ? 'bg-emerald-50 dark:bg-emerald-500/10'
                                                    : 'bg-slate-100 dark:bg-white/10'
                                            "
                                        >
                                            <p
                                                class="text-[11px]"
                                                :class="
                                                    isEligibleForRefund
                                                        ? 'text-emerald-700 dark:text-emerald-300'
                                                        : 'text-slate-400 dark:text-gray-500'
                                                "
                                            >
                                                Refundable
                                            </p>

                                            <p
                                                class="mt-0.5 text-sm font-semibold"
                                                :class="
                                                    isEligibleForRefund
                                                        ? 'text-emerald-700 dark:text-emerald-300'
                                                        : 'text-slate-400 dark:text-gray-500'
                                                "
                                            >
                                                {{
                                                    formatCurrency(
                                                        currentRefundAmount,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- REFUND POLICY -->
                                    <div
                                        class="mt-5 rounded-lg border p-3.5 dark:border-white/10"
                                        :class="
                                            isEligibleForRefund
                                                ? 'border-rose-200 bg-rose-50 dark:border-rose-500/20 dark:bg-rose-500/10'
                                                : 'border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/5'
                                        "
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <p
                                                        class="text-sm font-semibold"
                                                        :class="
                                                            isEligibleForRefund
                                                                ? 'text-rose-800 dark:text-rose-300'
                                                                : 'text-slate-600 dark:text-gray-400'
                                                        "
                                                    >
                                                        {{ refundPolicyTitle }}
                                                    </p>

                                                    <span
                                                        class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                                                        :class="
                                                            isEligibleForRefund
                                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'
                                                                : 'bg-slate-200 text-slate-600 dark:bg-white/15 dark:text-gray-400'
                                                        "
                                                    >
                                                        {{ refundPolicyBadge }}
                                                    </span>
                                                </div>

                                                <p
                                                    class="mt-1.5 text-xs leading-5"
                                                    :class="
                                                        isEligibleForRefund
                                                            ? 'text-rose-700 dark:text-rose-300'
                                                            : 'text-slate-500 dark:text-gray-400'
                                                    "
                                                >
                                                    {{
                                                        refundPolicyDescription
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CALCULATION -->
                                    <div
                                        v-if="showWorking"
                                        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <p
                                                class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                            >
                                                Refund calculation
                                            </p>

                                            <span
                                                class="group relative inline-flex"
                                                tabindex="0"
                                                role="button"
                                                aria-label="How this was worked out"
                                            >
                                                <svg
                                                    class="h-3.5 w-3.5 cursor-help text-slate-400 transition hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="10"
                                                    />
                                                    <path d="M12 16v-4" />
                                                    <path d="M12 8h.01" />
                                                </svg>

                                                <span
                                                    class="pointer-events-none invisible absolute left-0 top-full z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-3 text-left opacity-0 shadow-lg transition duration-150 group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100 dark:border-white/10 dark:bg-secondary"
                                                >
                                                    <span
                                                        class="mb-2 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                                    >
                                                        How this was worked out
                                                    </span>

                                                    <span
                                                        class="block space-y-1 text-[11px] leading-5"
                                                    >
                                                        <span
                                                            class="flex justify-between gap-3"
                                                        >
                                                            <span
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                {{
                                                                    formatDate(
                                                                        periodStart,
                                                                    )
                                                                }}
                                                                →
                                                                {{
                                                                    formatDate(
                                                                        periodEnd,
                                                                    )
                                                                }}
                                                            </span>

                                                            <span
                                                                class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                            >
                                                                {{ periodDays }}
                                                                {{
                                                                    periodDays ===
                                                                    1
                                                                        ? "day"
                                                                        : "days"
                                                                }}
                                                            </span>
                                                        </span>

                                                        <span
                                                            class="flex justify-between gap-3"
                                                        >
                                                            <span
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                Stayed so far
                                                            </span>

                                                            <span
                                                                class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    consumedDays
                                                                }}
                                                                {{
                                                                    consumedDays ===
                                                                    1
                                                                        ? "day"
                                                                        : "days"
                                                                }}
                                                            </span>
                                                        </span>

                                                        <span
                                                            class="flex justify-between gap-3"
                                                        >
                                                            <span
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                Unused
                                                            </span>

                                                            <span
                                                                class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    remainingDays
                                                                }}
                                                                {{
                                                                    remainingDays ===
                                                                    1
                                                                        ? "day"
                                                                        : "days"
                                                                }}
                                                            </span>
                                                        </span>

                                                        <span
                                                            class="mt-1 flex justify-between gap-3 border-t border-slate-100 pt-1 dark:border-white/10"
                                                        >
                                                            <span
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                {{ periodDays }}
                                                                ×
                                                                {{
                                                                    formatCurrency(
                                                                        dailyRate,
                                                                    )
                                                                }}/day
                                                            </span>

                                                            <span
                                                                class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    formatCurrency(
                                                                        periodPrice,
                                                                    )
                                                                }}
                                                            </span>
                                                        </span>

                                                        <span
                                                            v-if="
                                                                hasRetainedHalf
                                                            "
                                                            class="flex justify-between gap-3"
                                                        >
                                                            <span
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                Half retained ÷
                                                                2
                                                            </span>

                                                            <span
                                                                class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    formatCurrency(
                                                                        retainedHalf,
                                                                    )
                                                                }}
                                                            </span>
                                                        </span>

                                                        <span
                                                            v-if="hasDaysStayed"
                                                            class="flex justify-between gap-3"
                                                        >
                                                            <span
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                Days stayed
                                                                {{
                                                                    consumedDays
                                                                }}
                                                                ×
                                                                {{
                                                                    formatCurrency(
                                                                        dailyRate,
                                                                    )
                                                                }}
                                                            </span>

                                                            <span
                                                                class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    formatCurrency(
                                                                        daysStayedAmount,
                                                                    )
                                                                }}
                                                            </span>
                                                        </span>

                                                        <span
                                                            class="mt-1 flex justify-between gap-3 border-t border-slate-100 pt-1 dark:border-white/10"
                                                        >
                                                            <span
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                Half + days
                                                                stayed
                                                            </span>

                                                            <span
                                                                class="shrink-0 font-semibold text-slate-700 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    formatCurrency(
                                                                        requiredPaymentAmount,
                                                                    )
                                                                }}
                                                            </span>
                                                        </span>
                                                    </span>
                                                </span>
                                            </span>
                                        </div>

                                        <div class="mt-3 space-y-2 text-sm">
                                            <div
                                                v-if="hasPaidAmount"
                                                class="flex justify-between gap-4"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    Amount paid
                                                </span>

                                                <span
                                                    class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            currentNetPaidAmount,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                v-if="hasRequiredPayment"
                                                class="flex justify-between gap-4"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    Charged for the stay
                                                </span>

                                                <span
                                                    class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            requiredPaymentAmount,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                v-if="currentRefundAmount > 0"
                                                class="border-t border-slate-200 pt-2 flex justify-between gap-4 dark:border-white/10"
                                            >
                                                <span
                                                    class="font-semibold text-slate-700 dark:text-gray-300"
                                                >
                                                    Refund
                                                </span>

                                                <span
                                                    class="shrink-0 font-bold text-emerald-600 dark:text-emerald-300"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            currentRefundAmount,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                v-else-if="
                                                    isUnderRequiredPayment
                                                "
                                                class="border-t border-slate-200 pt-2 flex justify-between gap-4 dark:border-white/10"
                                            >
                                                <span
                                                    class="font-semibold text-slate-700 dark:text-gray-300"
                                                >
                                                    Still to collect
                                                </span>

                                                <span
                                                    class="shrink-0 font-bold text-rose-600 dark:text-rose-300"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            requiredPaymentShortfall,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5"
                                >
                                    <p
                                        class="text-sm text-slate-500 dark:text-gray-400"
                                    >
                                        No active billing period.
                                    </p>
                                </div>
                            </section>

                            <!-- FUTURE -->
                            <section class="flex flex-col">
                                <h4
                                    class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    Future periods
                                </h4>

                                <div
                                    v-if="futureInvoiceCount > 0"
                                    class="flex flex-1 flex-col rounded-xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-base font-semibold text-primary-950 dark:text-primary-300"
                                        >
                                            {{ futureInvoiceCount }}
                                            {{
                                                futureInvoiceCount === 1
                                                    ? "period"
                                                    : "periods"
                                            }}
                                            not started
                                        </p>

                                        <span
                                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                        >
                                            Upcoming
                                        </span>
                                    </div>

                                    <div
                                        v-if="futureInvoices.length"
                                        class="mt-4 max-h-60 space-y-2.5 overflow-y-auto pr-1"
                                    >
                                        <div
                                            v-for="invoice in futureInvoices"
                                            :key="invoice.invoice_admission_id"
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-white/5"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <p
                                                            class="text-sm font-semibold text-primary-950 dark:text-primary-300"
                                                        >
                                                            {{
                                                                invoice.invoice_code
                                                            }}
                                                        </p>

                                                        <span
                                                            class="shrink-0 rounded-full border px-1.5 py-0.5 text-[10px] font-semibold uppercase dark:border-white/10"
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
                                                        class="mt-0.5 text-xs lowercase text-slate-500 dark:text-gray-400"
                                                    >
                                                        {{
                                                            invoice.contract
                                                                ?.billing_cycle ??
                                                            "—"
                                                        }}
                                                        billing
                                                    </p>

                                                    <div
                                                        v-if="
                                                            hasAnyAmount(
                                                                invoice,
                                                            )
                                                        "
                                                        class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs"
                                                    >
                                                        <span
                                                            v-if="
                                                                hasAmount(
                                                                    invoice.paid_amount,
                                                                )
                                                            "
                                                            class="text-slate-500 dark:text-gray-400"
                                                        >
                                                            Paid
                                                            {{
                                                                formatCurrency(
                                                                    invoice.paid_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            v-if="
                                                                hasAmount(
                                                                    invoice.refunded_amount,
                                                                )
                                                            "
                                                            class="text-rose-500 dark:text-rose-300"
                                                        >
                                                            Refunded
                                                            {{
                                                                formatCurrency(
                                                                    invoice.refunded_amount,
                                                                )
                                                            }}
                                                        </span>

                                                        <span
                                                            v-if="
                                                                hasAmount(
                                                                    invoice.net_paid_amount,
                                                                )
                                                            "
                                                            class="font-medium text-emerald-600 dark:text-emerald-300"
                                                        >
                                                            Refundable
                                                            {{
                                                                formatCurrency(
                                                                    invoice.net_paid_amount,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <p
                                                    class="shrink-0 text-sm font-semibold text-primary-950 dark:text-primary-300"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            invoice.price,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="hasRefundableFutureInvoices"
                                        class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50/60 p-3.5 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                                    >
                                        <p
                                            class="text-sm font-medium text-emerald-900 dark:text-emerald-300"
                                        >
                                            These periods will be refunded
                                        </p>

                                        <p
                                            class="mt-0.5 text-xs leading-5 text-emerald-800/80 dark:text-emerald-300/70"
                                        >
                                            The patient never stays them, so
                                            everything paid on the upcoming
                                            periods is returned automatically.
                                        </p>
                                    </div>

                                    <p
                                        v-else
                                        class="mt-5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                    >
                                        None of the upcoming periods have a
                                        refundable balance.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-1 items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5"
                                >
                                    <p
                                        class="text-sm text-slate-500 dark:text-gray-400"
                                    >
                                        No future billing periods to refund.
                                    </p>
                                </div>
                            </section>
                        </div>

                        <div
                            v-if="hasAccountCredit"
                            class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50/60 px-5 py-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <p
                                    class="text-sm font-semibold text-emerald-900 dark:text-emerald-300"
                                >
                                    Credit on the patient's account
                                </p>

                                <p
                                    class="text-lg font-bold text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ formatCurrency(accountCredit) }}
                                </p>
                            </div>

                            <p
                                class="mt-2 text-xs leading-5 text-emerald-800/80 dark:text-emerald-300/70"
                            >
                                Paid more than the invoices now ask for, usually
                                after a downgrade or a cancelled period. It
                                stays on the account.
                            </p>
                        </div>

                        <div
                            v-if="showCurrentPeriodBlock && hasRequiredPayment"
                            class="mt-6 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-slate-700 dark:text-gray-400"
                                >
                                    Required payment
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    {{ requiredPaymentDescription }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p
                                    class="text-lg font-bold"
                                    :class="
                                        isUnderRequiredPayment
                                            ? 'text-rose-600 dark:text-rose-300'
                                            : 'text-emerald-600 dark:text-emerald-300'
                                    "
                                >
                                    {{ formatCurrency(requiredPaymentAmount) }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs"
                                    :class="
                                        isUnderRequiredPayment
                                            ? 'text-rose-500 dark:text-rose-300'
                                            : 'text-emerald-600 dark:text-emerald-300'
                                    "
                                >
                                    {{
                                        isUnderRequiredPayment
                                            ? `Short by ${formatCurrency(
                                                  requiredPaymentShortfall,
                                              )}`
                                            : "Payment threshold met"
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="outstanding"
                            class="mt-6 flex items-center justify-between gap-3 rounded-xl border px-5 py-4"
                            :class="
                                overallBalance > 0
                                    ? 'border-rose-200 bg-rose-50 dark:border-rose-500/30 dark:bg-rose-500/10'
                                    : 'border-slate-200 bg-emerald-50 dark:border-white/10 dark:bg-emerald-500/10'
                            "
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Overall balance
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    Everything this patient owes, not counting
                                    periods that have not started.
                                </p>
                            </div>

                            <p
                                class="shrink-0 text-lg font-bold"
                                :class="
                                    overallBalance > 0
                                        ? 'text-rose-600 dark:text-rose-300'
                                        : 'text-emerald-600 dark:text-emerald-300'
                                "
                            >
                                {{ formatCurrency(overallBalance) }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <label
                                for="discharge-note"
                                class="block text-sm font-semibold text-slate-700 mb-2 dark:text-gray-400"
                            >
                                Discharge note
                                <span
                                    class="font-normal text-slate-400 dark:text-gray-500"
                                >
                                    (optional)
                                </span>
                            </label>

                            <textarea
                                id="discharge-note"
                                v-model="dischargeNote"
                                rows="3"
                                :disabled="loading"
                                placeholder="Why is this patient being discharged?"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 outline-none resize-none transition focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-slate-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500 dark:disabled:bg-white/5"
                            />
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 sm:px-8 py-5 dark:border-white/10 dark:bg-white/5"
                    >
                        <div
                            v-if="
                                showCurrentPeriodBlock && isUnderRequiredPayment
                            "
                            class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3.5 dark:border-amber-500/20 dark:bg-amber-500/10"
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-amber-900 dark:text-amber-300"
                                >
                                    Payment required before discharge
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-5 text-amber-800 dark:text-amber-300"
                                >
                                    The patient has paid
                                    {{ formatCurrency(currentNetPaidAmount) }}
                                    but must have at least
                                    {{ formatCurrency(requiredPaymentAmount) }}
                                    paid before discharge.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button
                                type="button"
                                :disabled="loading"
                                class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200/60 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/15"
                                @click="handleClose"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                :disabled="loading || isUnderRequiredPayment"
                                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="handleConfirm"
                            >
                                <svg
                                    v-if="loading"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>

                                {{
                                    loading
                                        ? "Discharging…"
                                        : "Discharge patient"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type {
    Admission,
    InvoiceAccommodation,
    PatientBilling,
} from "~/types/patient";
import { INVOICE_STATUS } from "~/types/invoice";
import { formatCurrency } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import { useDischargeRefund } from "~/composables/useDischargeRefund";

const props = withDefaults(
    defineProps<{
        open: boolean;
        admission?: Admission;
        futureInvoices?: InvoiceAccommodation[];
        billing?: PatientBilling | null;
        loading?: boolean;
    }>(),
    {
        futureInvoices: () => [],
        billing: null,
        loading: false,
    },
);

const emit = defineEmits<{
    confirm: [
        payload: {
            refund: boolean;
            currentRefundAmount: number | null;
            note: string;
        },
    ];
    cancel: [];
}>();

const dischargeNote = ref("");

const currentInvoice = computed(() => {
    return props.admission?.current_invoice ?? null;
});

const {
    currentNetPaidAmount,
    currentBillingCycleLabel,
    consumedDays,
    remainingDays,
    dailyRate,
    hasDaysStayed,
    periodPrice,
    invoiceTotal,
    invoiceCoversMorePeriods,
    retainedHalf,
    periodDays,
    periodStart,
    periodEnd,
    daysStayedAmount,
    daysSinceAdmissionStart,
    feeBaseAmount,
    isWithinRefundWindow,
    isEligibleForRefund,
    currentRefundAmount,
    requiredPaymentAmount,
    requiredPaymentDescription,
    isUnderRequiredPayment,
    requiredPaymentShortfall,
    refundPolicyTitle,
    refundPolicyBadge,
    refundPolicyDescription,
} = useDischargeRefund(computed(() => props.admission));

const outstanding = computed(
    () => props.admission?.discharge_calculation?.outstanding ?? null,
);

const overallBalance = computed(() =>
    getNumber(outstanding.value?.balance_excluding_future),
);

const futureInvoices = computed(() => {
    return props.futureInvoices ?? [];
});

const futureInvoiceCount = computed(() => {
    return futureInvoices.value.length;
});

function getNumber(value: unknown, fallback = 0): number {
    const number = Number(value);

    return Number.isFinite(number) ? number : fallback;
}

function getNetPaid(invoice?: InvoiceAccommodation | null): number {
    if (!invoice) {
        return 0;
    }

    return Math.max(0, getNumber(invoice.net_paid_amount));
}

function isRefundable(invoice?: InvoiceAccommodation | null): boolean {
    return getNetPaid(invoice) > 0;
}

const hasRefundableFutureInvoices = computed(() => {
    return futureInvoices.value.some((invoice) => isRefundable(invoice));
});

const showCurrentPeriodBlock = computed(() => {
    return !!currentInvoice.value;
});

// Money already paid that no invoice claims any more. It is not a debt and not
// automatically returned — it sits until someone refunds it.
const accountCredit = computed(() => getNumber(props.billing?.refundable));

const hasAccountCredit = computed(() => accountCredit.value > 0);

const hasRetainedHalf = computed(() => retainedHalf.value > 0);

// Worth showing whenever there is a figure to explain, not only when money is
// coming back — a discharge that owes money needs the working just as much.
// The working explains the half-retention split, so it only makes sense inside
// the refund window. A monthly plan is charged whole and its days are never
// worked out, so there is nothing to show.
const showWorking = computed(
    () =>
        isWithinRefundWindow.value &&
        periodPrice.value > 0 &&
        (isEligibleForRefund.value ||
            isUnderRequiredPayment.value ||
            hasRequiredPayment.value),
);

const hasPaidAmount = computed(() => currentNetPaidAmount.value > 0);

const hasRefundableAmount = computed(() => currentRefundAmount.value > 0);

const hasRequiredPayment = computed(
    () =>
        requiredPaymentAmount.value !== null && requiredPaymentAmount.value > 0,
);

function hasAmount(value: unknown): boolean {
    return getNumber(value) > 0;
}

function hasAnyAmount(invoice: InvoiceAccommodation): boolean {
    return (
        hasAmount(invoice.paid_amount) ||
        hasAmount(invoice.refunded_amount) ||
        hasAmount(invoice.net_paid_amount)
    );
}

const isCurrentInvoiceRefundable = computed(() => {
    return getNetPaid(currentInvoice.value) > 0;
});

const canDischarge = computed(() => {
    if (!showCurrentPeriodBlock.value) {
        return true;
    }

    if (requiredPaymentAmount.value === null) {
        return true;
    }

    return !isUnderRequiredPayment.value;
});

function statusClasses(status?: string | null) {
    const s = (status ?? "").toLowerCase();

    return (
        INVOICE_STATUS[s] ??
        "bg-slate-50 text-slate-500 border-slate-200 dark:bg-white/5 dark:text-gray-400 dark:border-white/10"
    );
}

function handleConfirm() {
    if (props.loading) {
        return;
    }

    if (!canDischarge.value) {
        return;
    }

    proceedWithDischarge();
}

function proceedWithDischarge() {
    emit("confirm", {
        // A period the patient will never stay is always refunded — there is
        // nothing to decide, so it is not asked.
        refund: hasRefundableFutureInvoices.value,

        currentRefundAmount: isCurrentInvoiceRefundable.value
            ? currentRefundAmount.value
            : null,

        note: dischargeNote.value.trim(),
    });
}

function handleClose() {
    if (props.loading) {
        return;
    }

    dischargeNote.value = "";

    emit("cancel");
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            dischargeNote.value = "";
        }
    },
);
</script>
