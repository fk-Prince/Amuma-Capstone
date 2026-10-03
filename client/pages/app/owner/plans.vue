<template>
    <div class="min-h-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div
            v-if="loading"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="n in 6"
                :key="n"
                class="h-[340px] animate-pulse rounded-2xl bg-white/50 dark:bg-white/5"
            />
        </div>

        <div
            v-else
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="plan in plans"
                :key="plan.plan_id"
                class="overflow-hidden rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-secondary shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-white/10 px-5 py-4"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/10 text-primary"
                        >
                            <Layers class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <h2
                                class="truncate text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ plan.name }}
                            </h2>
                            <p
                                class="text-[11px] text-muted dark:text-gray-400"
                            >
                                Plan {{ plan.plan_code }} ·
                                {{ planTypeLabel(plan.type) }}
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="editingId !== plan.plan_id"
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-secondary px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-gray-300 transition hover:bg-slate-50 dark:hover:bg-white/10"
                        @click="startEdit(plan)"
                    >
                        <Pencil class="h-3.5 w-3.5" />
                        Edit
                    </button>
                </div>

                <div v-if="editingId === plan.plan_id" class="space-y-4 p-5">
                    <BaseInput
                        v-model="draft.description"
                        mode="textarea"
                        :rows="3"
                        label="Description"
                        placeholder="Enter plan description"
                    />

                    <BaseInput
                        v-model="draft.price"
                        mode="number"
                        label="Yearly Price"
                        placeholder="0"
                    />

                    <BaseInput
                        v-model="draft.additional_branch_price"
                        mode="number"
                        label="Additional Branch Price (yearly)"
                        placeholder="0"
                    />

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <BaseButton
                            variant="secondary"
                            size="sm"
                            :disabled="saving"
                            @click="cancelEdit"
                        >
                            Cancel
                        </BaseButton>

                        <BaseButton
                            variant="primary"
                            size="sm"
                            :loading="saving"
                            @click="saveEdit(plan)"
                        >
                            Save Changes
                        </BaseButton>
                    </div>
                </div>

                <template v-else>
                    <div class="px-5 py-4">
                        <p
                            class="text-xs leading-relaxed text-muted dark:text-gray-400"
                        >
                            {{ plan.description || "No description provided." }}
                        </p>
                    </div>

                    <div
                        class="border-t border-slate-100 p-4 dark:border-white/10"
                    >
                        <p
                            class="text-lg font-bold tabular-nums text-secondary dark:text-white"
                        >
                            {{ formatCurrency(planPrice(plan)) }}
                            <span
                                class="text-xs font-medium text-muted dark:text-gray-400"
                            >
                                / year
                            </span>
                        </p>
                        <p class="text-[11px] text-muted dark:text-gray-400">
                            {{ branchLimitText(plan.type) }}
                        </p>
                        <p class="mt-1 text-[11px] text-muted dark:text-gray-400">
                            Additional branch
                            <span class="font-semibold text-secondary dark:text-white">
                                {{ formatCurrency(plan.additional_branch_price) }}
                            </span>
                            / year
                        </p>
                    </div>
                </template>
            </div>
        </div>

        <div
            v-if="!loading && plans.length === 0"
            class="flex min-h-80 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-white/5 px-6 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-500/10 text-primary-400 dark:text-primary-300"
            >
                <Layers class="h-7 w-7" />
            </div>

            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                No plans configured yet
            </h2>

            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-gray-400">
                Plans will appear here once they're seeded or created.
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Layers, Pencil } from "lucide-vue-next";
import { planService } from "~/api/plan/PlanService";
import { formatCurrency as formatCurrencyUtil } from "~/utils/currency";
import { useToast } from "~/composables/useToast";
import BaseInput from "~/components/ui/BaseInput.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import {
    branchLimitText,
    planPrice,
    planTypeLabel,
    type PlanType,
} from "~/utils/planType";

interface PlanRecord {
    plan_id: number;
    plan_code: string;
    name: string;
    description: string | null;
    type: PlanType;
    price: number | string;
    additional_branch_price: number | string;
}

definePageMeta({
    layout: "owner",
    middleware: ["auth-client", "owner-guard"],
});

useHead({
    title: "AMUMA Plans",
});

const { success, error } = useToast();

const plans = ref<PlanRecord[]>([]);
const loading = ref(true);
const saving = ref(false);
const editingId = ref<number | null>(null);

const draft = ref({
    description: "",
    price: "",
    additional_branch_price: "",
});

const formatCurrency = (value: number | string) => {
    const num = typeof value === "string" ? parseFloat(value) : value;

    return formatCurrencyUtil(num || 0, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
};

const fetchPlans = async () => {
    loading.value = true;

    try {
        const res = await planService.list();
        plans.value = res.data ?? res ?? [];
    } catch (err) {
        console.error("Failed to fetch plans:", err);
        plans.value = [];
    } finally {
        loading.value = false;
    }
};

function startEdit(plan: PlanRecord) {
    editingId.value = plan.plan_id;
    draft.value = {
        description: plan.description ?? "",
        price: String(plan.price),
        additional_branch_price: String(plan.additional_branch_price ?? plan.price),
    };
}

function cancelEdit() {
    editingId.value = null;
}

async function saveEdit(plan: PlanRecord) {
    saving.value = true;

    try {
        const res = await planService.update(plan.plan_id, {
            description: draft.value.description,
            price: Number(draft.value.price) || 0,
            additional_branch_price: Number(draft.value.additional_branch_price) || 0,
        });

        const updated = res.plan ?? res.data?.plan ?? res;

        const index = plans.value.findIndex((p) => p.plan_id === plan.plan_id);

        if (index !== -1) {
            plans.value[index] = { ...plans.value[index], ...updated };
        }

        editingId.value = null;
        success("Plan updated successfully.");
    } catch (err: any) {
        error(err?.message || "Failed to update plan.");
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    fetchPlans();
});
</script>
