<template>
    <div class="min-h-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div
            v-if="loading"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="n in 3"
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
                            <p class="text-[11px] text-muted dark:text-gray-400">
                                Plan {{ plan.plan_code }}
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

                    <div class="grid grid-cols-2 gap-3">
                        <BaseInput
                            v-model="draft.monthly_price"
                            mode="number"
                            label="Monthly Price"
                            placeholder="0"
                        />

                        <BaseInput
                            v-model="draft.yearly_price"
                            mode="number"
                            label="Yearly Price"
                            placeholder="0"
                        />
                    </div>

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
                        class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-white/10 border-t border-slate-100 dark:border-white/10"
                    >
                        <div class="p-4">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500"
                            >
                                Monthly
                            </p>
                            <p
                                class="mt-1 text-lg font-bold tabular-nums text-secondary dark:text-white"
                            >
                                {{ formatCurrency(plan.monthly_price) }}
                            </p>
                        </div>

                        <div class="p-4">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500"
                            >
                                Yearly
                            </p>
                            <p
                                class="mt-1 text-lg font-bold tabular-nums text-secondary dark:text-white"
                            >
                                {{ formatCurrency(plan.yearly_price) }}
                            </p>
                        </div>
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

interface PlanRecord {
    plan_id: number;
    plan_code: string;
    name: string;
    description: string | null;
    monthly_price: number | string;
    yearly_price: number | string;
}

definePageMeta({
    layout: "owner",
    middleware: "auth-client",
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
    monthly_price: "",
    yearly_price: "",
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
        monthly_price: String(plan.monthly_price),
        yearly_price: String(plan.yearly_price),
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
            monthly_price: Number(draft.value.monthly_price) || 0,
            yearly_price: Number(draft.value.yearly_price) || 0,
        });

        const updated = res.plan ?? res.data?.plan ?? res;

        const index = plans.value.findIndex(
            (p) => p.plan_id === plan.plan_id,
        );

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
