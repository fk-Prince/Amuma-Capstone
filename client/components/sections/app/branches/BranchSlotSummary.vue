<template>
    <div
        class="mb-5 grid grid-cols-1 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/60 sm:grid-cols-3 dark:border-white/10 dark:bg-white/5"
    >
        <div class="px-5 py-3">
            <p class="text-[11px] text-slate-500 dark:text-gray-400">Included slots</p>
            <p class="text-[15px] font-semibold text-slate-900 dark:text-white">
                {{ used }} of {{ limit }} used
            </p>
            <div v-if="limit <= 20" class="mt-2 flex gap-1">
                <span
                    v-for="n in limit"
                    :key="n"
                    class="h-1.5 flex-1 rounded-sm"
                    :class="
                        n <= used - deactivated
                            ? 'bg-primary'
                            : n <= used
                              ? 'bg-primary/20 outline outline-1 -outline-offset-1 outline-dashed outline-primary'
                              : 'bg-slate-200 dark:bg-white/10'
                    "
                />
            </div>
        </div>

        <div
            class="border-t border-slate-200 px-5 py-3 sm:border-l sm:border-t-0 dark:border-white/10"
        >
            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                Additional branches billed
            </p>
            <p class="text-[15px] font-semibold text-slate-900 dark:text-white">
                {{ billed ? `${billed} at next renewal` : "None" }}
            </p>
        </div>

        <div
            class="border-t border-slate-200 px-5 py-3 sm:border-l sm:border-t-0 dark:border-white/10"
        >
            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                Next renewal<template v-if="renewsOn"> · {{ formatActivationDate(renewsOn) }}</template>
            </p>
            <p class="text-[15px] font-semibold text-slate-900 dark:text-white">
                ₱{{ formatAmount(Number(plan?.yearly_total) || 0) }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatAmount } from "~/utils/currency";
import { formatActivationDate } from "~/composables/useBranchActivation";

const props = defineProps<{
    plan: {
        branch_limit?: number;
        included_used?: number;
        included_deactivated?: number;
        additional_branches?: number;
        yearly_total?: number;
    } | null;
    renewsOn?: string | null;
}>();

const limit = computed(() => Number(props.plan?.branch_limit) || 0);
const used = computed(() => Number(props.plan?.included_used) || 0);
const deactivated = computed(() => Number(props.plan?.included_deactivated) || 0);
const billed = computed(() => Number(props.plan?.additional_branches) || 0);
</script>
