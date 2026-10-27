<template>
    <ConfirmDialog
        :open="open"
        title="Complete this schedule?"
        message="This action cannot be undone."
        description="This schedule will be marked as completed and can no longer be edited afterward."
        confirm-label="Complete Schedule"
        cancel-label="Keep Editing"
        :loading="loading"
        @confirm="emit('confirm')"
        @cancel="emit('cancel')"
    >
        <div
            v-if="fetching"
            class="mt-4 h-16 animate-pulse rounded-xl bg-gray-100 dark:bg-white/10"
        />

        <div
            v-else-if="invoice"
            class="mt-4 rounded-xl px-4 py-3"
            :class="
                isFullyPaid
                    ? 'bg-emerald-50 dark:bg-emerald-500/10'
                    : 'bg-amber-50 dark:bg-amber-500/10'
            "
        >
            <div class="flex items-center justify-between gap-3">
                <span
                    class="inline-flex items-center gap-1.5 text-sm font-semibold"
                    :class="
                        isFullyPaid
                            ? 'text-emerald-700 dark:text-emerald-300'
                            : 'text-amber-700 dark:text-amber-300'
                    "
                >
                    <CircleCheck v-if="isFullyPaid" class="h-4 w-4" />
                    <CircleAlert v-else class="h-4 w-4" />
                    {{ isFullyPaid ? "Fully paid" : "Not fully paid" }}
                </span>
                <span class="text-[11px] text-muted dark:text-gray-400">
                    {{ invoice.invoice_code }}
                </span>
            </div>

            <dl
                v-if="!isFullyPaid"
                class="mt-2 space-y-1 text-xs text-slate-600 dark:text-gray-300"
            >
                <div class="flex justify-between">
                    <dt>Billed</dt>
                    <dd class="tabular-nums">{{ formatCurrency(invoice.billed) }}</dd>
                </div>
                <div v-if="invoice.paid > 0" class="flex justify-between">
                    <dt>Paid</dt>
                    <dd class="tabular-nums">{{ formatCurrency(invoice.paid) }}</dd>
                </div>
                <div class="flex justify-between font-semibold text-amber-700 dark:text-amber-300">
                    <dt>Balance</dt>
                    <dd class="tabular-nums">{{ formatCurrency(invoice.balance) }}</dd>
                </div>
            </dl>
        </div>

        <p
            v-else-if="fetchFailed"
            class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[11px] leading-5 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
        >
            Couldn't load this schedule's payment status.
        </p>
    </ConfirmDialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { CircleAlert, CircleCheck } from "lucide-vue-next";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { formatCurrency } from "~/utils/currency";
import type { ScheduleItem } from "~/types/schedule";

interface CompletePreview {
    invoice: {
        invoice_code: string;
        billed: number;
        paid: number;
        balance: number;
    } | null;
}

const props = defineProps<{
    open: boolean;
    schedule: ScheduleItem | null;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "confirm"): void;
    (e: "cancel"): void;
}>();

const route = useRoute();

const invoice = ref<CompletePreview["invoice"]>(null);
const fetching = ref(false);
const fetchFailed = ref(false);

const isFullyPaid = computed(() => (invoice.value?.balance ?? 0) <= 0);

watch(
    () => props.open,
    async (isOpen) => {
        if (!isOpen) return;

        invoice.value = null;
        fetchFailed.value = false;

        if (!props.schedule) return;

        fetching.value = true;

        try {
            const res = (await scheduleService.action({
                type: "complete_preview",
                branch_uuid: route.params.uuid,
                schedule_id: props.schedule.schedule_id,
            })) as CompletePreview;

            invoice.value = res.invoice;
        } catch {
            fetchFailed.value = true;
        } finally {
            fetching.value = false;
        }
    },
    { immediate: true },
);
</script>
