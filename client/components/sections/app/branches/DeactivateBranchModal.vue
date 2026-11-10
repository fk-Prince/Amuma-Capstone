<template>
    <ConfirmDialog
        :open="true"
        :title="`Deactivate ${branchName}?`"
        message="It becomes view-only right away. Only billing and Manage Subscription still work."
        confirm-label="Deactivate"
        variant="danger"
        :loading="loading"
        @confirm="emit('confirm')"
        @cancel="emit('close')"
    >
        <ul class="mt-3 space-y-1 text-xs leading-5 text-slate-500 dark:text-gray-400">
            <li>No refund.</li>
            <li v-if="activation.slot_type === 'included'">
                Keeps its included slot. Reactivate anytime for free.
            </li>
            <template v-else>
                <li>
                    Not billed at renewal<template v-if="activation.additional_branch_price > 0">
                        (−₱{{ formatAmount(activation.additional_branch_price) }})</template>.
                </li>
                <li>
                    Free to reactivate until {{ formatDate(activation.renews_on) }}.
                </li>
            </template>
        </ul>
    </ConfirmDialog>
</template>

<script setup lang="ts">
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import { formatAmount } from "~/utils/currency";
import type { BranchActivation } from "./BranchCard.vue";

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
