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
                            {{ patientName ?? "Patient" }}
                        </h2>
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
                    class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-6 py-3 dark:border-white/10"
                >
                    <p class="text-xs text-muted dark:text-gray-400">
                        {{ invoices.length }} unpaid invoice{{
                            invoices.length === 1 ? "" : "s"
                        }}
                    </p>

                    <button
                        type="button"
                        class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300"
                        @click="toggleAll"
                    >
                        {{ allSelected ? "Clear all" : "Select all" }}
                    </button>
                </div>

                <ul
                    class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10"
                >
                    <li
                        v-for="invoice in invoices"
                        :key="invoice.invoice_code"
                    >
                        <label
                            class="flex cursor-pointer items-start gap-3 px-6 py-4 transition hover:bg-gray-50 dark:hover:bg-white/5"
                        >
                            <input
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-primary focus:ring-primary/30 dark:border-white/20 dark:bg-transparent"
                                :checked="isSelected(invoice.invoice_code)"
                                @change="toggle(invoice.invoice_code)"
                            />

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="font-mono text-sm font-semibold text-secondary dark:text-white"
                                    >
                                        {{ invoice.invoice_code }}
                                    </span>

                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                        :class="statusClasses(invoice.status)"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </span>

                                <span
                                    v-if="invoice.description"
                                    class="mt-1 block text-xs text-muted dark:text-gray-400"
                                >
                                    {{ invoice.description }}
                                </span>

                                <span
                                    class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    <span>
                                        Total ₱{{ formatMoney(invoice.total) }}
                                    </span>

                                    <span v-if="Number(invoice.amount_paid) > 0">
                                        Paid ₱{{
                                            formatMoney(invoice.amount_paid)
                                        }}
                                    </span>

                                    <span
                                        class="font-semibold text-rose-500 dark:text-rose-300"
                                    >
                                        Due ₱{{
                                            formatMoney(invoice.balance_due)
                                        }}
                                    </span>
                                </span>
                            </span>

                            <span class="w-32 shrink-0" @click.prevent.stop>
                                <span
                                    class="block text-[10px] font-semibold uppercase tracking-wide"
                                    :class="
                                        isSelected(invoice.invoice_code)
                                            ? 'text-muted dark:text-gray-400'
                                            : 'text-gray-300 dark:text-gray-600'
                                    "
                                >
                                    Amount to pay
                                </span>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :max="invoice.balance_due"
                                    :disabled="!isSelected(invoice.invoice_code)"
                                    :value="amountFor(invoice.invoice_code)"
                                    class="mt-1 w-full rounded-lg border border-gray-200 px-2.5 py-1.5 text-right text-sm font-semibold text-secondary outline-none transition focus:border-primary disabled:bg-gray-50 disabled:text-gray-400 dark:border-white/10 dark:bg-transparent dark:text-white dark:disabled:bg-white/5"
                                    @input="
                                        setAmount(
                                            invoice.invoice_code,
                                            ($event.target as HTMLInputElement)
                                                .value,
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
                            {{ selectionLabel }}
                        </p>

                        <p
                            class="text-base font-bold text-secondary dark:text-white"
                        >
                            ₱{{ formatMoney(selectedTotal) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600"
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
import { computed } from "vue";
import { X } from "lucide-vue-next";

import { formatAmount } from "~/utils/currency";
import { statusClasses } from "~/utils/invoiceStatus";
import { amountFor as resolveAmount } from "~/utils/invoiceSelection";
import type { PatientInvoiceItem } from "~/types/invoice";

const props = defineProps<{
    open: boolean;
    invoices: PatientInvoiceItem[];
    patientName?: string | null;
    selected: string[];
    amounts: Record<string, number>;
    totalBalance: number;
}>();

const emit = defineEmits<{
    (event: "update:selected", codes: string[]): void;
    (event: "update:amounts", amounts: Record<string, number>): void;
    (event: "close"): void;
}>();

const allSelected = computed(
    () =>
        props.invoices.length > 0 &&
        props.selected.length === props.invoices.length,
);

const selectedTotal = computed(() => {
    if (!props.selected.length) {
        return Number(props.totalBalance ?? 0);
    }

    return props.selected.reduce(
        (total, code) => total + amountFor(code),
        0,
    );
});

const selectionLabel = computed(() => {
    const count = props.selected.length;

    if (!count) return "Paying the full outstanding balance";

    return `${count} invoice${count === 1 ? "" : "s"} selected`;
});

function isSelected(code: string) {
    return props.selected.includes(code);
}

function amountFor(code: string) {
    return resolveAmount(props.invoices, props.amounts, code);
}

function toggle(code: string) {
    emit(
        "update:selected",
        isSelected(code)
            ? props.selected.filter((item) => item !== code)
            : [...props.selected, code],
    );
}

function toggleAll() {
    emit(
        "update:selected",
        allSelected.value
            ? []
            : props.invoices.map((invoice) => invoice.invoice_code),
    );
}

function setAmount(code: string, value: string) {
    const next = { ...props.amounts };
    const parsed = Number(value);

    if (value === "" || Number.isNaN(parsed)) {
        delete next[code];
    } else {
        const balance = Number(
            props.invoices.find((invoice) => invoice.invoice_code === code)
                ?.balance_due ?? 0,
        );

        next[code] = Math.min(Math.max(parsed, 0), balance);
    }

    emit("update:amounts", next);
}

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}
</script>
