<script setup lang="ts">
import { ref, watch } from "vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { formatCurrency } from "~/utils/currency";
import {
    formatBillingDateTime,
    formatBillingStatus,
    invoiceStatusClasses,
    toPortalInvoiceDetail,
} from "~/utils/portal-billing";
import type { PortalInvoiceDetail } from "~/types/portal-billing";

const props = defineProps<{
    patientId: number | null;
    invoiceCode: string | null;
}>();

const emit = defineEmits<{ (event: "close"): void }>();

const invoice = ref<PortalInvoiceDetail | null>(null);
const loading = ref(false);
const loadError = ref("");

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
}

async function load() {
    if (!props.patientId || !props.invoiceCode) return;

    loading.value = true;
    loadError.value = "";
    invoice.value = null;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "invoice",
            patient_id: props.patientId,
            invoice_code: props.invoiceCode,
        });

        invoice.value = toPortalInvoiceDetail(res?.data ?? {});
    } catch (err: any) {
        loadError.value = err?.message || "Unable to load this invoice.";
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.invoiceCode,
    (code) => {
        if (code) load();
    },
    { immediate: true },
);
</script>

<template>
    <Transition name="modal">
        <div
            v-if="invoiceCode"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p
                                class="text-sm font-bold text-gray-900 dark:text-white"
                            >
                                {{ invoiceCode }}
                            </p>

                            <span
                                v-if="invoice"
                                class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                                :class="invoiceStatusClasses(invoice.status)"
                            >
                                {{ formatBillingStatus(invoice.status) }}
                            </span>
                        </div>

                        <p
                            v-if="invoice"
                            class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                        >
                            {{ formatBillingDateTime(invoice.created_at) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        <AppIcon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-[400px] flex-1 overflow-y-auto px-6 py-5">
                    <div v-if="loading" class="animate-pulse space-y-4">
                        <div
                            v-for="i in 3"
                            :key="i"
                            class="flex items-start justify-between gap-4"
                        >
                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-2.5 w-20 rounded bg-gray-100 dark:bg-white/5"
                                />
                                <div
                                    class="h-3.5 w-44 rounded bg-gray-200 dark:bg-white/10"
                                />
                            </div>
                            <div
                                class="h-3.5 w-16 rounded bg-gray-200 dark:bg-white/10"
                            />
                        </div>

                        <div
                            class="mt-6 h-24 rounded-2xl bg-gray-50 dark:bg-white/5"
                        />
                    </div>

                    <p
                        v-else-if="loadError"
                        class="py-14 text-center text-xs text-rose-500 dark:text-rose-300"
                    >
                        {{ loadError }}
                    </p>

                    <template v-else-if="invoice">
                        <p
                            v-if="invoice.void_reason"
                            class="mb-4 rounded-xl bg-rose-50 px-3 py-2 text-[11px] text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            Voided: {{ invoice.void_reason }}
                        </p>

                        <p
                            class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                        >
                            Items
                        </p>

                        <div
                            class="mt-2 divide-y divide-gray-100 dark:divide-white/10"
                        >
                            <div
                                v-for="(line, index) in invoice.lines"
                                :key="index"
                                class="flex items-start justify-between gap-4 py-3 first:pt-0"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-wide text-primary"
                                    >
                                        {{ line.category }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-gray-800 break-words dark:text-gray-200"
                                    >
                                        {{ line.description || "—" }}
                                    </p>
                                    <p
                                        v-if="line.detail"
                                        class="mt-0.5 text-[10px] text-gray-400 dark:text-gray-500"
                                    >
                                        {{ line.detail }}
                                    </p>
                                </div>

                                <p
                                    class="shrink-0 text-xs font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ peso(line.amount) }}
                                </p>
                            </div>

                            <p
                                v-if="!invoice.lines.length"
                                class="py-3 text-xs text-gray-400 dark:text-gray-500"
                            >
                                No line items recorded.
                            </p>
                        </div>

                        <div
                            class="mt-5 space-y-2 rounded-2xl bg-gray-50 p-4 text-xs dark:bg-white/5"
                        >
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">
                                    Subtotal
                                </span>
                                <span class="text-gray-900 dark:text-white">
                                    {{ peso(invoice.total) }}
                                </span>
                            </div>

                            <div
                                v-for="adjustment in invoice.adjustments"
                                :key="adjustment.invoice_adjustment_id"
                                class="flex justify-between gap-3"
                            >
                                <span
                                    class="truncate text-amber-600 dark:text-amber-300"
                                >
                                    {{ adjustment.reason }}
                                </span>
                                <span
                                    class="shrink-0 text-amber-600 dark:text-amber-300"
                                >
                                    {{ peso(adjustment.amount) }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">
                                    Paid
                                </span>
                                <span
                                    class="text-emerald-600 dark:text-emerald-300"
                                >
                                    {{ peso(invoice.net_paid) }}
                                </span>
                            </div>

                            <div
                                class="flex justify-between border-t border-gray-200 pt-2 text-sm font-bold dark:border-white/10"
                            >
                                <span class="text-gray-900 dark:text-white">
                                    Balance due
                                </span>
                                <span
                                    :class="
                                        invoice.balance_due > 0
                                            ? 'text-rose-500 dark:text-rose-300'
                                            : 'text-gray-900 dark:text-white'
                                    "
                                >
                                    {{ peso(invoice.balance_due) }}
                                </span>
                            </div>
                        </div>

                        <template v-if="invoice.payments.length">
                            <p
                                class="mt-5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                            >
                                Payments
                            </p>

                            <div
                                class="mt-2 divide-y divide-gray-100 dark:divide-white/10"
                            >
                                <div
                                    v-for="payment in invoice.payments"
                                    :key="payment.payment_id"
                                    class="flex items-center justify-between gap-4 py-2.5"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="font-mono text-[11px] font-semibold text-gray-800 dark:text-gray-200"
                                        >
                                            {{
                                                payment.payment_code ?? "Payment"
                                            }}
                                        </p>
                                        <p
                                            class="text-[10px] text-gray-400 dark:text-gray-500"
                                        >
                                            {{ payment.payment_method }} ·
                                            {{
                                                formatBillingDateTime(
                                                    payment.created_at ?? undefined,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <p
                                        class="shrink-0 text-xs font-semibold text-emerald-600 dark:text-emerald-300"
                                    >
                                        {{ peso(payment.amount) }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(12px) scale(0.98);
}
</style>
