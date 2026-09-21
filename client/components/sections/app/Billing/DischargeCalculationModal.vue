<template>
    <Teleport to="body">
        <div
            v-if="calculation"
            class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
            @click.self="emit('close')"
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

                        <p class="mt-1 text-xs text-muted dark:text-gray-400">
                            Admission #{{ calculation.admission_id }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="emit('close')"
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

                                <p class="mt-1 text-2xl font-bold text-danger">
                                    ₱{{
                                        formatMoney(calculation.refund_amount)
                                    }}
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-primary-50 px-3 py-1.5 text-[10px] font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                {{
                                    calculation.eligible_for_refund
                                        ? "Refund Eligible"
                                        : "No Refund"
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <Field
                            label="Billing Cycle"
                            :value="calculation.billing_cycle"
                        />

                        <Field
                            label="Period Charge"
                            :value="`₱${formatMoney(calculation.period_price)}`"
                        />

                        <Field
                            label="Amount Paid"
                            :value="`₱${formatMoney(calculation.amount_paid)}`"
                        />

                        <Field
                            label="Retained Amount"
                            :value="`₱${formatMoney(calculation.retained_amount)}`"
                        />
                    </div>

                    <div
                        class="rounded-xl border border-primary-100 bg-slate-50/70 p-5 dark:border-primary-500/20 dark:bg-white/5"
                    >
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <Field
                                label="Admission Date"
                                :value="formatDate(calculation.admission_date)"
                            />

                            <Field
                                label="Discharge Date"
                                :value="formatDate(calculation.discharge_date)"
                            />

                            <Field
                                label="Days Since Admission"
                                :value="calculation.days_since_admission"
                            />

                            <Field
                                label="Refund"
                                :value="`₱${formatMoney(calculation.refund_amount)}`"
                            />
                        </div>
                    </div>

                    <!-- The refund above is worked out on this stay alone, but
                         the patient is leaving the branch, so everything still
                         owed anywhere on the account is settled with it. -->
                    <div
                        v-if="calculation.outstanding"
                        class="overflow-hidden rounded-xl border"
                        :class="
                            calculation.outstanding.balance_excluding_future > 0
                                ? 'border-danger/30'
                                : 'border-primary-100 dark:border-primary-500/20'
                        "
                    >
                        <div
                            class="flex items-center justify-between gap-3 px-5 py-3"
                            :class="
                                calculation.outstanding.balance_excluding_future > 0
                                    ? 'bg-danger/5'
                                    : 'bg-emerald-50/60 dark:bg-emerald-500/10'
                            "
                        >
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                                >
                                    Outstanding on this patient
                                </p>

                                <p
                                    class="mt-0.5 text-[11px] text-muted dark:text-gray-400"
                                >
                                    Admission and schedules combined, excluding periods not yet reached
                                </p>
                            </div>

                            <p
                                class="shrink-0 text-lg font-bold"
                                :class="
                                    calculation.outstanding.balance_excluding_future > 0
                                        ? 'text-danger'
                                        : 'text-emerald-600 dark:text-emerald-300'
                                "
                            >
                                ₱{{
                                    formatMoney(
                                        calculation.outstanding.balance_excluding_future,
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="grid grid-cols-2 gap-4 border-t border-primary-100 px-5 py-4 sm:grid-cols-3 dark:border-primary-500/20"
                        >
                            <Field
                                label="Admission"
                                :value="`₱${formatMoney(
                                    calculation.outstanding
                                        .accommodation_balance,
                                )}`"
                            />

                            <Field
                                label="Services"
                                :value="`₱${formatMoney(
                                    calculation.outstanding.service_balance,
                                )}`"
                            />

                            <Field
                                label="Other Invoices"
                                :value="`₱${formatMoney(
                                    calculation.outstanding.other_balance,
                                )}`"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end border-t border-primary-100 bg-slate-50/60 px-6 py-4 dark:border-primary-500/20 dark:bg-white/5"
                >
                    <button
                        type="button"
                        class="rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700"
                        @click="emit('close')"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { h } from "vue";

import { formatAmount } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import type { DischargeCalculation } from "~/types/invoice";

defineProps<{
    // Null both when the dialog is closed and when the open admission has no
    // calculation of its own, so one check covers both.
    calculation: DischargeCalculation | null;
}>();

const emit = defineEmits<{
    (event: "close"): void;
}>();

function formatMoney(value: number | string | null | undefined) {
    return formatAmount(value, { treatMissingAsZero: true });
}

const Field = (props: { label: string; value: unknown }) =>
    h("div", { class: "flex min-w-0 flex-col gap-0.5" }, [
        h(
            "span",
            {
                class: "truncate text-[10px] font-semibold uppercase tracking-[0.11em] text-muted dark:text-gray-400",
            },
            props.label,
        ),
        h(
            "span",
            {
                class: "truncate text-sm font-medium text-secondary dark:text-white",
            },
            String(props.value ?? "—"),
        ),
    ]);
</script>
