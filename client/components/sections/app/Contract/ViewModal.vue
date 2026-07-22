<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div
                class="w-full max-w-5xl h-[80vh] bg-white rounded-2xl shadow-xl border border-[#E4EFED] overflow-hidden flex flex-col"
            >
                <div
                    class="flex justify-between items-start gap-3 px-6 py-4 border-b border-[#E4EFED]"
                >
                    <div class="flex flex-col gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-[#16302E]">
                                Facility Plans
                            </h2>

                            <p class="text-sm text-[#9AB3AF]">
                                Manage all active and inactive branch contract
                                plans.
                            </p>
                        </div>

                        <BaseInput
                            v-model="search"
                            placeholder="Search plans..."
                            inputClass="w-[350px]"
                            :isSearch="true"
                        />
                    </div>

                    <button
                        type="button"
                        @click="close"
                        class="text-gray-400 hover:text-gray-700 text-lg shrink-0"
                    >
                        ✕
                    </button>
                </div>

                <div class="relative overflow-auto flex-1">
                    <div
                        v-if="isFetching"
                        class="absolute inset-0 bg-white/50 z-10"
                    />

                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#F7FAF9] sticky top-0">
                            <tr class="border-b border-[#E4EFED]">
                                <th
                                    class="py-3 px-3 text-xs uppercase text-muted"
                                >
                                    Category
                                </th>
                                <th
                                    class="py-3 px-6 text-xs uppercase text-muted"
                                >
                                    Type
                                </th>

                                <th
                                    class="py-3 px-3 text-xs uppercase text-muted"
                                >
                                    Price
                                </th>

                                <th
                                    class="py-3 px-3 text-xs uppercase text-muted"
                                >
                                    Billing
                                </th>

                                <th
                                    class="py-3 px-3 text-xs uppercase text-muted"
                                >
                                    Status
                                </th>

                                <th
                                    class="py-3 px-6 text-xs uppercase text-muted"
                                >
                                    Description
                                </th>

                                <th
                                    class="py-3 px-6 text-xs uppercase text-muted text-right"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[#E4EFED]">
                            <template v-if="isLoading">
                                <tr v-for="n in 5" :key="n">
                                    <td colspan="7" class="px-6 py-4">
                                        <div
                                            class="h-6 bg-slate-100 rounded animate-pulse"
                                        />
                                    </td>
                                </tr>
                            </template>

                            <tr v-else-if="filteredPlans.length === 0">
                                <td
                                    colspan="7"
                                    class="py-16 text-center text-sm text-gray-400"
                                >
                                    No plans found
                                </td>
                            </tr>

                            <tr
                                v-for="plan in filteredPlans"
                                :key="plan.branch_contract_id"
                                class="hover:bg-slate-50"
                            >
                                <td class="px-3 py-4 text-sm">
                                    {{ plan.category }}
                                </td>

                                <td
                                    class="px-6 py-4 text-sm font-medium text-[#16302E]"
                                >
                                    {{ plan.type }}
                                </td>

                                <td class="px-3 py-4 text-sm font-semibold">
                                    ₱{{ Number(plan.price).toLocaleString() }}
                                </td>

                                <td class="px-3 py-4 text-sm">
                                    {{ plan.billing_interval }}
                                </td>

                                <td class="px-3 py-4">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="
                                            plan.is_active
                                                ? 'bg-green-50 text-green-600'
                                                : 'bg-red-50 text-red-600'
                                        "
                                    >
                                        {{
                                            plan.is_active
                                                ? "Active"
                                                : "Inactive"
                                        }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ plan.description || "-" }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-primary hover:underline"
                                        @click="onUpdate(plan)"
                                    >
                                        Update
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="!isLoading && filteredPlans.length"
                    class="flex justify-between items-center px-6 py-4 border-t border-[#E4EFED] bg-white"
                >
                    <p class="text-xs text-muted">
                        Showing {{ filteredPlans.length }} plans
                    </p>

                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2 text-sm"
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
import { ref, computed, watch, onMounted } from "vue";
import { useRoute } from "vue-router";

import BaseInput from "~/components/ui/BaseInput.vue";
import { branchContractService } from "~/api/branch-contract/BranchContractService";

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
    update: [plan: any];
}>();

const route = useRoute();

const plans = ref<any[]>([]);
const isLoading = ref(true);
const isFetching = ref(false);
const search = ref("");

const branch_uuid = computed(() => route.params.uuid as string);

const filteredPlans = computed(() => {
    if (!search.value.trim()) {
        return plans.value;
    }

    const keyword = search.value.toLowerCase();

    return plans.value.filter((plan) => {
        return (
            plan.type?.toLowerCase().includes(keyword) ||
            plan.category?.toLowerCase().includes(keyword) ||
            plan.billing_interval?.toLowerCase().includes(keyword) ||
            plan.description?.toLowerCase().includes(keyword)
        );
    });
});

async function fetchPlans() {
    if (!branch_uuid.value) return;

    isFetching.value = true;

    try {
        const res = await branchContractService.list({
            branch_uuid: branch_uuid.value,
        });

        plans.value = res.data ?? res;
    } catch (err) {
        console.error(err);
    } finally {
        isFetching.value = false;
        isLoading.value = false;
    }
}

function close() {
    emit("close");
}

function onUpdate(plan: any) {
    emit("update", plan);
}

watch(
    () => props.open,
    (value) => {
        fetchPlans();
    },
);

onMounted(() => {
    if (props.open) {
        fetchPlans();
    }
});
</script>
