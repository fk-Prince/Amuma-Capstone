<template>
    <div
        class="rounded-xl border border-primary-100 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/5"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary"
                >
                    Invoice
                </p>

                <p
                    v-if="invoiceCode"
                    class="mt-0.5 truncate text-sm font-semibold text-secondary dark:text-white"
                >
                    {{ invoiceCode }}
                </p>
            </div>

            <template v-if="canEdit">
                <button
                    v-if="!editing"
                    type="button"
                    :disabled="disabled"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-primary/30 bg-white px-2.5 py-1 text-[11px] font-semibold text-primary transition hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-transparent dark:hover:bg-white/5"
                    @click="startEdit"
                >
                    <Pencil class="h-3 w-3" />
                    Edit amount
                </button>

                <button
                    v-else
                    type="button"
                    :disabled="disabled"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-transparent dark:text-gray-400 dark:hover:bg-white/5"
                    @click="reset"
                >
                    <RotateCcw class="h-3 w-3" />
                    Reset
                </button>
            </template>
        </div>

        <dl class="mt-3 text-xs">
            <div class="flex items-center justify-between gap-3 py-1">
                <dt class="text-muted dark:text-gray-400">Invoice total</dt>
                <dd class="font-medium text-secondary dark:text-white">
                    {{ formatCurrency(billed) }}
                </dd>
            </div>

            <div
                v-if="deposit"
                class="flex items-center justify-between gap-3 py-1"
            >
                <dt class="text-muted dark:text-gray-400">
                    {{ deposit.label }}
                </dt>
                <dd class="font-medium text-secondary dark:text-white">
                    {{ deposit.percent }}%
                </dd>
            </div>

            <div class="flex items-center justify-between gap-3 py-1">
                <dt class="text-muted dark:text-gray-400">Amount paid</dt>
                <dd class="font-medium text-secondary dark:text-white">
                    {{ formatCurrency(paid) }}
                </dd>
            </div>

            <div
                class="mt-1 flex items-center justify-between gap-3 border-t border-primary-100 pt-2.5 dark:border-white/10"
            >
                <dt class="font-semibold text-secondary dark:text-white">
                    <label for="cancel-invoice-amount">{{ amountLabel }}</label>
                </dt>

                <dd v-if="!editing" class="text-sm font-semibold text-primary">
                    {{ formatCurrency(amount) }}
                </dd>

                <dd v-else class="relative w-40">
                    <span
                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted dark:text-gray-500"
                    >
                        ₱
                    </span>

                    <input
                        id="cancel-invoice-amount"
                        v-model="amount"
                        type="number"
                        inputmode="decimal"
                        min="0"
                        :max="max"
                        step="0.01"
                        :disabled="disabled"
                        class="w-full rounded-lg border border-primary-100 bg-white py-1.5 pl-7 pr-3 text-right text-sm font-semibold text-secondary focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        @blur="clamp"
                    />
                </dd>
            </div>
        </dl>

        <p
            v-if="editing"
            class="mt-1 text-right text-[11px] text-muted dark:text-gray-500"
        >
            Cannot exceed {{ maxHint }}.
        </p>

        <p
            v-if="amountError"
            class="mt-1 text-right text-xs text-rose-600 dark:text-rose-300"
        >
            {{ amountError }}
        </p>

        <div v-if="editing" class="mt-3">
            <label
                for="cancel-invoice-reason"
                class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
            >
                Reason for changing the amount
                <span class="text-danger">*</span>
            </label>

            <textarea
                id="cancel-invoice-reason"
                v-model="reason"
                rows="2"
                maxlength="200"
                :disabled="disabled"
                placeholder="Why is the amount being changed? This is recorded on the invoice."
                class="mt-1.5 w-full resize-none rounded-xl border border-primary-100 bg-white px-3 py-2 text-sm text-secondary placeholder:text-slate-400 focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
            />

            <p
                v-if="reasonError"
                class="mt-1 text-xs text-rose-600 dark:text-rose-300"
            >
                {{ reasonError }}
            </p>
        </div>

        <p
            class="mt-3 rounded-lg bg-white px-3 py-2 text-[11px] leading-5 text-muted dark:bg-white/5 dark:text-gray-400"
        >
            {{ outcome }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Pencil, RotateCcw } from "lucide-vue-next";
import { formatCurrency } from "~/utils/currency";

const props = defineProps<{
    invoiceCode?: string | null;
    billed: number;
    paid: number;
    max: number;
    defaultAmount: number;
    amountLabel: string;
    maxHint: string;
    // What the family paid when they booked, as a share of the price.
    deposit?: { label: string; percent: number } | null;
    disabled?: boolean;
    amountError?: string;
    reasonError?: string;
}>();

const amount = defineModel<number | string>("amount", { required: true });
const reason = defineModel<string>("reason", { required: true });
const editing = defineModel<boolean>("editing", { required: true });

const canEdit = computed(() => props.max > 0);

const outcome = computed(() => {
    const final = Number(amount.value) || 0;
    const credit = Math.max(0, Math.round((props.paid - final) * 100) / 100);

    if (final <= 0) {
        return props.paid > 0
            ? `The invoice will be voided and ${formatCurrency(props.paid)} goes back to the patient as credit.`
            : "The invoice will be voided.";
    }

    if (credit > 0) {
        return `The invoice will be adjusted to ${formatCurrency(final)} and ${formatCurrency(credit)} goes back to the patient as credit.`;
    }

    if (final >= props.billed) {
        return "The invoice stays as billed.";
    }

    return `The invoice will be adjusted down to ${formatCurrency(final)}.`;
});

function startEdit() {
    editing.value = true;
}

function reset() {
    editing.value = false;
    amount.value = props.defaultAmount;
    reason.value = "";
}

function clamp() {
    const value = Number(amount.value);

    if (!Number.isFinite(value) || value < 0) {
        amount.value = 0;
    } else if (value > props.max) {
        amount.value = props.max;
    }
}
</script>
