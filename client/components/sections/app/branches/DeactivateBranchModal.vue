<template>
    <ConfirmDialog
        :open="true"
        :title="`Deactivate ${branchName}?`"
        message="It becomes view-only right away. Only billing and Manage Subscription still work."
        confirm-label="Deactivate"
        loading-label="Deactivating..."
        variant="danger"
        :loading="loading"
        @confirm="emit('confirm')"
        @cancel="emit('close')"
    >
        <ul class="mt-3 list-disc space-y-1 pl-4 text-xs leading-5 text-slate-500 marker:text-slate-300 dark:text-gray-400 dark:marker:text-gray-600">
            <li>You won't get a refund for the time already paid.</li>
            <li v-if="activation.slot_type === 'included'">
                It keeps its included slot, so you can reactivate it anytime
                for free.
            </li>
            <template v-else>
                <li>
                    It won't be billed at renewal<template v-if="activation.additional_branch_price > 0">,
                        saving ₱{{ formatAmount(activation.additional_branch_price) }}</template>.
                </li>
                <li>
                    You can reactivate it for free until
                    {{ formatDate(activation.renews_on) }}.
                </li>
            </template>
        </ul>
    </ConfirmDialog>
</template>

<script setup lang="ts">
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import { formatAmount } from "~/utils/currency";
import type { BranchActivation } from "~/types/branch";

defineProps<{
    branchName: string;
    activation: BranchActivation;
    loading?: boolean;
}>();

const emit = defineEmits<{
    confirm: [];
    close: [];
}>();

const formatDate = (date?: string | null) =>
    date
        ? new Date(date).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
          })
        : "the end of the paid period";
</script>
