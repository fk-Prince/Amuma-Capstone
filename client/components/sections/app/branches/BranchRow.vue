<template>
    <div
        class="flex flex-wrap items-center gap-3 px-4 py-3 transition sm:flex-nowrap"
        :class="[
            active ? 'bg-primary-50/60 dark:bg-primary-500/10' : 'bg-white dark:bg-secondary',
            isRejected || deactivated ? 'opacity-80' : '',
        ]"
    >
        <img
            :src="branch.image"
            :alt="branch.name"
            class="h-10 w-10 shrink-0 rounded-lg object-cover"
        />

        <div class="min-w-0 flex-1">
            <div class="flex min-w-0 items-center gap-2">
                <p
                    class="truncate text-sm font-semibold text-slate-900 dark:text-white"
                    :title="branch.name"
                >
                    {{ branch.name }}
                </p>
                <span
                    v-if="activation?.slot_type"
                    class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                >
                    {{ activation.slot_type === "included" ? "Included" : "Additional" }}
                </span>
                <span
                    v-if="activation?.plan"
                    class="shrink-0 rounded bg-primary-50 px-1.5 py-0.5 text-[10px] font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                >
                    {{ activation.plan.name }}
                </span>
            </div>
            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-gray-400">
                {{ note ?? branch.address }}
            </p>
        </div>

        <div class="hidden shrink-0 text-xs text-slate-500 md:block dark:text-gray-400">
            {{ branch.patients }} patients · {{ branch.staffs }} staff
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-2">
            <span
                class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-medium"
                :class="statusPill.class"
            >
                {{ statusPill.label }}
            </span>

            <ActionButton
                v-if="!isRejected && activation?.can_change_plan"
                variant="outline"
                extra-class="!px-3 !py-1.5 !text-xs"
                :disabled="!canManage"
                tooltip="You need permission to update Manage Subscription to change a branch's plan."
                @click="emit('change-plan', branch)"
            >
                Change plan
            </ActionButton>

            <ActionButton
                v-if="!isRejected && !deactivated && activation?.upgrade_options?.length"
                variant="outline"
                extra-class="!px-3 !py-1.5 !text-xs"
                :disabled="!canManage"
                tooltip="You need permission to update Manage Subscription to upgrade a branch."
                @click="emit('upgrade', branch)"
            >
                <ArrowUpCircle class="h-3.5 w-3.5" />
                Upgrade
            </ActionButton>

            <ActionButton
                v-if="isRejected"
                variant="primary"
                extra-class="!px-3 !py-1.5 !text-xs"
                :disabled="!canResubmit"
                tooltip="You need permission to update Manage Subscription to resubmit a branch."
                @click="emit('resubmit', branch)"
            >
                <RotateCw class="h-3.5 w-3.5" />
                Resubmit
            </ActionButton>

            <ActionButton
                v-else-if="action"
                :variant="action === 'deactivate' ? 'outline' : 'primary'"
                :extra-class="
                    action === 'deactivate'
                        ? '!px-3 !py-1.5 !text-xs !text-rose-600 dark:!text-rose-300'
                        : '!px-3 !py-1.5 !text-xs'
                "
                :disabled="!canManage"
                tooltip="You need permission to update Manage Subscription to change a branch."
                @click="
                    action === 'deactivate'
                        ? emit('deactivate', branch)
                        : emit('reactivate', branch)
                "
            >
                <component
                    :is="action === 'deactivate' ? PowerOff : Power"
                    class="h-3.5 w-3.5"
                />
                {{
                    action === "deactivate"
                        ? "Deactivate"
                        : reactivationAmount > 0
                          ? `Reactivate · ₱${formatAmount(reactivationAmount)}`
                          : "Reactivate"
                }}
            </ActionButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { ArrowUpCircle, Power, PowerOff, RotateCw } from "lucide-vue-next";
import ActionButton from "~/components/ui/ActionButton.vue";
import { formatAmount } from "~/utils/currency";
import { useBranchActivation } from "~/composables/useBranchActivation";
import type { BranchCardData } from "./BranchCard.vue";

const props = withDefaults(
    defineProps<{
        branch: BranchCardData;
        active?: boolean;
        canResubmit?: boolean;
        canManage?: boolean;
    }>(),
    { canResubmit: true, canManage: false },
);

const emit = defineEmits<{
    resubmit: [branch: BranchCardData];
    deactivate: [branch: BranchCardData];
    reactivate: [branch: BranchCardData];
    "change-plan": [branch: BranchCardData];
    upgrade: [branch: BranchCardData];
}>();

const isRejected = computed(() => props.branch.review_status === "rejected");
const activation = computed(() => props.branch.activation ?? null);
const { deactivated, reactivationAmount, action, note } =
    useBranchActivation(activation);

const statusPill = computed(() => {
    if (isRejected.value) {
        return {
            label: "Rejected",
            class: "bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400",
        };
    }

    if (deactivated.value) {
        return {
            label: "Deactivated",
            class: "bg-slate-200/70 text-slate-600 dark:bg-white/10 dark:text-gray-300",
        };
    }

    return props.branch.status === "verified"
        ? {
              label: "Active",
              class: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
          }
        : {
              label: "Pending review",
              class: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
          };
});
</script>
