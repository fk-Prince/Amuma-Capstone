<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4"
            @click.self="handleClose"
        >
            <div
                role="alertdialog"
                aria-modal="true"
                aria-label="Cancel schedule"
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
            >
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    Cancel this schedule?
                </h3>

                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-medium">{{ schedule?.schedule_code }}</span>
                    <template v-if="schedule?.patient?.full_name">
                        for {{ schedule.patient.full_name }}
                    </template>
                    will be cancelled and can no longer be edited.
                </p>

                <div
                    v-if="fetching"
                    class="mt-4 h-40 animate-pulse rounded-xl bg-gray-100 dark:bg-white/10"
                />

                <template v-else>
                    <CancelInvoiceAmount
                        v-if="preview?.invoice && preview.invoice.billed > 0"
                        v-model:amount="amount"
                        v-model:reason="reason"
                        v-model:editing="editing"
                        class="mt-4"
                        :invoice-code="preview.invoice.invoice_code"
                        :billed="preview.invoice.billed"
                        :paid="preview.invoice.paid"
                        :max="preview.invoice.billed"
                        :default-amount="preview.invoice.billed"
                        amount-label="Amount to bill"
                        :max-hint="`the ${formatCurrency(preview.invoice.billed)} invoice total`"
                        :disabled="loading"
                        :amount-error="amountError"
                        :reason-error="reasonError"
                    />

                    <p
                        v-if="preview?.shared_invoice_codes?.length"
                        class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-5 text-muted dark:bg-white/5 dark:text-gray-400"
                    >
                        {{ preview.shared_invoice_codes.join(", ") }} also bills
                        other schedules, so it won't be changed.
                    </p>

                    <p
                        v-if="fetchFailed"
                        class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[11px] leading-5 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        Couldn't load this schedule's invoice. It will be left
                        as it is.
                    </p>
                </template>

                <div class="mt-5 flex justify-end gap-2.5">
                    <button
                        type="button"
                        :disabled="loading"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                        @click="handleClose"
                    >
                        Keep Schedule
                    </button>

                    <button
                        type="button"
                        :disabled="loading || fetching"
                        class="flex min-w-[90px] items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-600 disabled:cursor-not-allowed disabled:opacity-70"
                        @click="handleConfirm"
                    >
                        <LoaderCircle
                            v-if="loading"
                            class="h-4 w-4 shrink-0 animate-spin"
                        />
                        {{ loading ? "Saving..." : "Cancel Schedule" }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { LoaderCircle } from "lucide-vue-next";
import CancelInvoiceAmount from "~/components/sections/app/Billing/CancelInvoiceAmount.vue";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { formatCurrency } from "~/utils/currency";
import type { ScheduleItem } from "~/types/schedule";

interface CancelPreview {
    invoice: {
        invoice_code: string;
        billed: number;
        paid: number;
    } | null;
    shared_invoice_codes: string[];
}

const props = defineProps<{
    open: boolean;
    schedule: ScheduleItem | null;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (
        e: "confirm",
        settlement: { final_amount?: number; adjustment_reason?: string },
    ): void;
    (e: "cancel"): void;
}>();

const route = useRoute();

const preview = ref<CancelPreview | null>(null);
const fetching = ref(false);
const fetchFailed = ref(false);

const amount = ref<number | string>(0);
const reason = ref("");
const editing = ref(false);
const amountError = ref("");
const reasonError = ref("");

watch(
    () => props.open,
    async (isOpen) => {
        if (!isOpen) return;

        preview.value = null;
        fetchFailed.value = false;
        amount.value = 0;
        reason.value = "";
        editing.value = false;
        amountError.value = "";
        reasonError.value = "";

        if (!props.schedule) return;

        fetching.value = true;

        try {
            const res = await scheduleService.action({
                type: "cancel_preview",
                branch_uuid: route.params.uuid,
                schedule_id: props.schedule.schedule_id,
            });

            preview.value = res as CancelPreview;
            amount.value = preview.value.invoice?.billed ?? 0;
        } catch {
            fetchFailed.value = true;
        } finally {
            fetching.value = false;
        }
    },
    { immediate: true },
);

function handleClose() {
    if (props.loading) return;
    emit("cancel");
}

function handleConfirm() {
    amountError.value = "";
    reasonError.value = "";

    const invoice = preview.value?.invoice;

    if (!invoice || !editing.value) {
        emit("confirm", {});
        return;
    }

    const final = Number(amount.value);

    if (!Number.isFinite(final) || final < 0 || final > invoice.billed) {
        amountError.value = `Enter an amount from 0 to ${formatCurrency(invoice.billed)}.`;
        return;
    }

    const rounded = Math.round(final * 100) / 100;

    if (rounded >= invoice.billed) {
        emit("confirm", {});
        return;
    }

    const why = reason.value.trim();

    if (why.length < 3) {
        reasonError.value = "Give a reason for changing the amount.";
        return;
    }

    emit("confirm", { final_amount: rounded, adjustment_reason: why });
}
</script>
