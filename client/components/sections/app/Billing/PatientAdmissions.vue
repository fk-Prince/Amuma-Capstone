<template>
    <section>
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-secondary dark:text-white">
                    Patient Admissions
                </h2>

                <p class="mt-1 text-xs text-muted dark:text-gray-400">
                    Current admissions are shown first.
                </p>
            </div>

            <span
                class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
            >
                {{ admissions.length }}
            </span>
        </div>

        <div v-if="admissions.length" class="space-y-4">
            <article
                v-for="admission in admissions"
                :key="admission.patient_admission_id"
                class="overflow-hidden rounded-2xl border transition dark:border-white/10"
                :class="
                    isCurrentAdmission(admission)
                        ? 'border-primary-300 bg-primary-50/20 shadow-sm dark:bg-primary-500/10'
                        : 'border-primary-100 bg-white dark:border-primary-500/20 dark:bg-secondary'
                "
            >
                <div
                    v-if="isCurrentAdmission(admission)"
                    class="flex items-center justify-between gap-3 bg-primary-600 px-5 py-2.5 text-white"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="h-2 w-2 animate-pulse rounded-full bg-white dark:bg-secondary"
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
                            class="shrink-0 rounded-xl bg-primary-50 px-4 py-3 lg:min-w-[155px] dark:bg-primary-500/10"
                        >
                            <p
                                class="text-[10px] uppercase tracking-[0.14em] text-primary-600 dark:text-primary-300"
                            >
                                Admission Total
                            </p>

                            <p class="mt-1 text-lg font-bold text-primary-800 dark:text-primary-300">
                                ₱{{ formatMoney(admission.total_amount) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="
                            canViewDischarge(admission) ||
                            isCurrentAdmission(admission)
                        "
                        class="mt-5 flex flex-wrap justify-end gap-2"
                    >
                        <button
                            v-if="isCurrentAdmission(admission)"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-primary-200 px-4 py-2.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-50 dark:border-primary-500/30 dark:text-primary-300 dark:hover:bg-primary-500/10"
                            @click="
                                emit(
                                    'extend-stay',
                                    admission.patient_admission_id,
                                )
                            "
                        >
                            Extend Stay
                        </button>

                        <button
                            v-if="canViewDischarge(admission)"
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
                        class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-primary-100 pt-5 dark:border-primary-500/20"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                            >
                                Admission Invoices
                            </p>

                            <p class="mt-1 text-xs text-muted dark:text-gray-400">
                                {{ admission.invoice_count ?? 0 }} invoice(s)

                                <span
                                    v-if="Number(admission.balance_due ?? 0) > 0"
                                    class="font-semibold text-danger"
                                >
                                    · ₱{{ formatMoney(admission.balance_due) }} due
                                </span>
                            </p>
                        </div>

                        <button
                            v-if="admission.invoice_count"
                            type="button"
                            class="shrink-0 rounded-xl border border-primary-200 px-4 py-2.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-50 dark:border-primary-500/30 dark:text-primary-300 dark:hover:bg-primary-500/10"
                            @click="
                                emit(
                                    'view-admission-invoices',
                                    admission.patient_admission_id,
                                )
                            "
                        >
                            View invoices
                        </button>
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
import { statusClasses } from "~/utils/invoiceStatus";
import { formatDate } from "~/utils/time";
import type { DischargeCalculation, PatientAdmission } from "~/types/invoice";

const props = defineProps<{
    admissions: PatientAdmission[];
    dischargeCalculation?: DischargeCalculation | null;
}>();

const emit = defineEmits<{
    (event: "view-admission-invoices", admissionId: number): void;
    (event: "view-discharge-termination", admissionId: number): void;
    (event: "extend-stay", admissionId: number): void;
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
        
        calculation.is_within_refund_window === true
    );
}

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
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
        ],
    );

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
</script>

<script lang="ts">
import { h } from "vue";

export default {};
</script>
