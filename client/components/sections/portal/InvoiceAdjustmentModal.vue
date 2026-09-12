<script setup lang="ts">
import { computed } from "vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import { formatCurrency } from "~/utils/currency";
import { formatBillingDateTime } from "~/utils/portal-billing";
import type { PortalInvoice } from "~/types/portal-billing";

const props = defineProps<{
    invoice: PortalInvoice | null;
}>();

const emit = defineEmits<{
    (event: "close"): void;
}>();

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
}

// Newest first, and each line says which way the bill moved.
const entries = computed(() =>
    [...(props.invoice?.adjustments ?? [])]
        .sort(
            (a, b) =>
                new Date(b.created_at ?? 0).getTime() -
                new Date(a.created_at ?? 0).getTime(),
        )
        .map((adjustment) => ({
            ...adjustment,
            lowered: Number(adjustment.amount ?? 0) <= 0,
        })),
);

const runningTotal = computed(() =>
    entries.value.reduce(
        (total, adjustment) => total + Number(adjustment.amount ?? 0),
        0,
    ),
);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="invoice"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-600 dark:text-amber-300"
                            >
                                Adjustment history
                            </p>

                            <p
                                class="mt-1 truncate text-sm font-bold text-gray-900 dark:text-white"
                            >
                                {{ invoice.invoice_code }}
                            </p>

                            <p
                                class="truncate text-xs text-gray-400 dark:text-gray-500"
                            >
                                {{ invoice.description }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            <AppIcon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-px border-b border-gray-100 bg-gray-100 text-center dark:border-white/10 dark:bg-white/10"
                    >
                        <div class="bg-white px-4 py-3 dark:bg-secondary">
                            <p
                                class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500"
                            >
                                Originally billed
                            </p>

                            <p
                                class="mt-0.5 text-sm font-bold text-gray-500 line-through dark:text-gray-400"
                            >
                                {{ peso(invoice.total) }}
                            </p>
                        </div>

                        <div class="bg-white px-4 py-3 dark:bg-secondary">
                            <p
                                class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500"
                            >
                                Now
                            </p>

                            <p
                                class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white"
                            >
                                {{ peso(invoice.adjusted_total) }}
                            </p>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                        <ol class="space-y-3">
                            <li
                                v-for="(entry, index) in entries"
                                :key="entry.invoice_adjustment_id ?? index"
                                class="rounded-2xl border border-gray-100 p-3 dark:border-white/10"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <p
                                        class="text-xs leading-5 text-gray-700 dark:text-gray-200"
                                    >
                                        {{ entry.reason }}
                                    </p>

                                    <p
                                        class="shrink-0 text-sm font-bold"
                                        :class="
                                            entry.lowered
                                                ? 'text-emerald-600 dark:text-emerald-300'
                                                : 'text-rose-600 dark:text-rose-300'
                                        "
                                    >
                                        {{ entry.lowered ? "−" : "+"
                                        }}{{ peso(Math.abs(entry.amount)) }}
                                    </p>
                                </div>

                                <p
                                    class="mt-1 text-[10px] text-gray-400 dark:text-gray-500"
                                >
                                    {{ formatBillingDateTime(entry.created_at)
                                    }}<template v-if="entry.type">
                                        · {{ entry.type }}</template
                                    >
                                </p>
                            </li>
                        </ol>

                        <p
                            v-if="!entries.length"
                            class="py-8 text-center text-xs text-gray-400 dark:text-gray-500"
                        >
                            This invoice has not been adjusted.
                        </p>
                    </div>

                    <div
                        v-if="entries.length"
                        class="flex items-center justify-between gap-3 border-t border-gray-100 px-6 py-3 dark:border-white/10"
                    >
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            {{
                                runningTotal <= 0
                                    ? "Total reduced by"
                                    : "Total added"
                            }}
                        </span>

                        <span
                            class="text-sm font-bold"
                            :class="
                                runningTotal <= 0
                                    ? 'text-emerald-600 dark:text-emerald-300'
                                    : 'text-rose-600 dark:text-rose-300'
                            "
                        >
                            {{ peso(Math.abs(runningTotal)) }}
                        </span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
