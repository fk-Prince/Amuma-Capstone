<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/40 backdrop-blur-sm p-4 font-sans dark:bg-white/10"
        >
            <div
                class="w-full max-w-3xl h-[80vh] bg-white rounded-3xl shadow-xl border border-muted-light overflow-hidden flex flex-col dark:bg-secondary dark:border-white/10"
            >
                <div class="border-b border-muted-light dark:border-white/10">
                    <div
                        class="flex items-start justify-between gap-3 px-6 pt-5 pb-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-secondary dark:text-white">
                                Facility Plans
                            </h2>

                            <p class="text-sm text-muted mt-0.5 dark:text-gray-400">
                                Manage all active and inactive branch contract
                                plans.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="close"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-muted hover:text-secondary hover:bg-light shrink-0 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="px-6 pb-4">
                        <BaseInput
                            v-model="search"
                            placeholder="Search plans..."
                            inputClass="w-full"
                            :isSearch="true"
                        />
                    </div>
                </div>

                <div class="relative overflow-auto flex-1">
                    <div v-if="loading" class="divide-y divide-muted-light">
                        <div v-for="n in 5" :key="n" class="px-6 py-4">
                            <div
                                class="h-14 bg-light/60 rounded-2xl animate-pulse dark:bg-white/5"
                            />
                        </div>
                    </div>

                    <div
                        v-else-if="filteredPlans.length === 0"
                        class="py-20 text-center text-sm text-muted dark:text-gray-400"
                    >
                        No plans found
                    </div>

                    <div v-else class="divide-y divide-muted-light">
                        <div
                            v-for="group in groupedPlans"
                            :key="group.category"
                        >
                            <p
                                class="sticky top-0 z-[5] px-6 py-2 text-[11px] font-semibold uppercase tracking-wide text-primary-600 bg-primary-50 dark:text-primary-300 dark:bg-primary-500/10"
                            >
                                {{ group.category }}
                            </p>

                            <div class="divide-y divide-muted-light">
                                <div
                                    v-for="plan in group.items"
                                    :key="plan.branch_contract_id"
                                    class="flex items-start gap-4 px-6 py-4 transition-colors hover:bg-light/40 dark:hover:bg-white/5"
                                >
                                    <div
                                        class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center shrink-0 mt-0.5 dark:bg-primary-500/10"
                                    >
                                        <Building2
                                            class="w-4.5 h-4.5 text-primary"
                                        />
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <p
                                                class="text-sm font-semibold text-secondary dark:text-white"
                                            >
                                                {{ plan.accommodation_type }}
                                            </p>

                                            <span
                                                class="px-2 py-0.5 rounded-full text-[11px] font-medium"
                                                :class="
                                                    plan.is_active
                                                        ? 'bg-accent-50 text-accent-600 dark:bg-accent-500/15 dark:text-accent-300'
                                                        : 'bg-danger/10 text-danger'
                                                "
                                            >
                                                {{
                                                    plan.is_active
                                                        ? "Active"
                                                        : "Inactive"
                                                }}
                                            </span>
                                        </div>

                                        <p class="text-xs text-muted mt-0.5 dark:text-gray-400">
                                            {{ plan.billing_cycle }}
                                        </p>

                                        <p
                                            v-if="plan.description"
                                            class="text-xs text-muted leading-relaxed mt-2 line-clamp-2 dark:text-gray-400"
                                        >
                                            {{ plan.description }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex flex-col items-end gap-2 shrink-0"
                                    >
                                        <p
                                            class="text-sm font-bold text-secondary dark:text-white"
                                        >
                                            {{ formatCurrency(plan.price) }}
                                        </p>

                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-primary hover:text-primary-700 dark:hover:text-primary-300"
                                            @click="onUpdate(plan)"
                                        >
                                            Update
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!loading && filteredPlans.length"
                    class="flex justify-between items-center px-6 py-4 border-t border-muted-light bg-white dark:bg-secondary dark:border-white/10"
                >
                    <p class="text-xs text-muted dark:text-gray-400">
                        Showing {{ filteredPlans.length }} plans
                    </p>

                    <button
                        type="button"
                        class="rounded-xl border border-muted-light px-4 py-2 text-sm text-secondary hover:bg-light dark:border-white/10 dark:text-white dark:hover:bg-white/5"
                        @click="close"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { X, Building2 } from "lucide-vue-next";

import BaseInput from "~/components/ui/BaseInput.vue";
import { formatCurrency } from "~/utils/currency";

const props = defineProps<{
    open: boolean;
    plans: any[];
    loading: boolean;
}>();

const emit = defineEmits<{
    close: [];
    update: [plan: any];
}>();

const search = ref("");

const filteredPlans = computed(() => {
    if (!search.value.trim()) {
        return props.plans;
    }

    const keyword = search.value.toLowerCase();

    return props.plans.filter((plan) => {
        return (
            plan.accommodation_type?.toLowerCase().includes(keyword) ||
            plan.category?.toLowerCase().includes(keyword) ||
            plan.billing_cycle?.toLowerCase().includes(keyword) ||
            plan.description?.toLowerCase().includes(keyword)
        );
    });
});

const groupedPlans = computed(() => {
    const groups: Record<string, any[]> = {};

    for (const plan of filteredPlans.value) {
        const key = plan.category || "Uncategorized";

        if (!groups[key]) {
            groups[key] = [];
        }

        groups[key].push(plan);
    }

    return Object.keys(groups)
        .sort((a, b) => a.localeCompare(b))
        .map((category) => ({
            category,
            items: groups[category],
        }));
});

function close() {
    emit("close");
}

function onUpdate(plan: any) {
    emit("update", plan);
}
</script>
