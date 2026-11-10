<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @click.self="requestClose"
        >
            <div
                class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                :aria-label="`Change ${branchName}'s plan`"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Building2 class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <h2
                                class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                            >
                                Change {{ branchName }}'s plan
                            </h2>
                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Included slot · now on {{ currentName }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        aria-label="Close dialog"
                        class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        :disabled="saving"
                        @click="requestClose"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        v-if="loading"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="h-56 animate-pulse rounded-xl bg-slate-100 dark:bg-white/5"
                        />
                    </div>

                    <BranchPlanPicker
                        v-else
                        v-model="selected"
                        :options="options"
                    />

                    <div
                        class="flex items-start gap-2.5 rounded-lg border border-primary/10 bg-primary/5 px-4 py-3 text-[13px] text-primary"
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                            {{ branchName }} uses an included slot on your Hybrid
                            subscription, so switching is free and takes effect
                            right away. Pages for modules the new plan doesn't
                            include become locked.
                        </span>
                    </div>

                    <p
                        v-if="errorMessage"
                        class="rounded-lg border border-danger/20 bg-danger/5 px-4 py-2 text-sm text-danger dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
                    >
                        {{ errorMessage }}
                    </p>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        :disabled="saving"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                        @click="requestClose"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="saving || !selected || selected === currentCode"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="save"
                    >
                        <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />
                        {{ saving ? "Saving..." : "Save plan" }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Building2, Info, LoaderCircle, X } from "lucide-vue-next";
import BranchPlanPicker, {
    type BranchPlanOption,
} from "~/components/sections/app/branches/BranchPlanPicker.vue";
import { planService } from "~/api/plan/PlanService";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { plansOfType, type PlanType } from "~/utils/planType";

const props = defineProps<{
    branchName: string;
    branchUuid: string;
    targetUuid: string;
    currentCode: string;
    currentName: string;
    planType: PlanType;
}>();

const emit = defineEmits<{
    close: [];
    changed: [result: any];
}>();

const plans = ref<any[]>([]);
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref<string | null>(null);
const selected = ref<string | null>(props.currentCode);

const options = computed<BranchPlanOption[]>(() =>
    plansOfType(plans.value, props.planType).map((plan: any) => ({
        plan_code: plan.plan_code,
        name: plan.name,
        description: plan.description,
        reason: plan.plan_code === props.currentCode ? "Current plan" : "",
        label: "Included slot",
        amount: "No charge",
    })),
);

const requestClose = () => {
    if (!saving.value) emit("close");
};

const save = async () => {
    if (!selected.value || saving.value) return;

    saving.value = true;
    errorMessage.value = null;

    try {
        const result = await subscriptionService.changeBranchPlan({
            branch_uuid: props.branchUuid,
            target_uuid: props.targetUuid,
            plan_code: selected.value,
        });

        emit("changed", result);
    } catch (err: any) {
        errorMessage.value = err?.message ?? "Couldn't change this branch's plan.";
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    try {
        plans.value = (await planService.list()) ?? [];
    } catch (err: any) {
        errorMessage.value = err?.message ?? "Failed to load plans.";
    } finally {
        loading.value = false;
    }
});
</script>
