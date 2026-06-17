<template>
    <div class="w-full mx-auto px-6">
        <div v-if="loading" class="flex flex-col gap-4">
            <div
                v-for="n in 3"
                :key="n"
                class="border rounded-2xl overflow-hidden animate-pulse"
            >
                <div class="h-32 bg-slate-100"></div>
                <div class="p-4 space-y-3">
                    <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                    <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                    <div class="flex justify-between mt-4">
                        <div class="h-6 w-16 bg-slate-100 rounded-full"></div>
                        <div class="h-3 w-20 bg-slate-100 rounded"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="flex flex-col gap-4">
            <CardBooking
                :variant="2"
                v-for="branch in branches"
                :key="branch.uuid"
                :branch="branch"
                @select="$emit('select', $event)"
            />
        </div>

        <div
            v-if="!loading && branches.length === 0"
            class="text-center py-16 text-slate-400"
        >
            No branches found.
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { branchService } from "~/api/branch/BranchService";
import CardBooking from "./CardBooking.vue";
import type { BranchRetrieve } from "~/types/branch";
import { useRoute } from "vue-router";

defineEmits(["select"]);

const route = useRoute();

const branches = ref<BranchRetrieve[]>([]);
const loading = ref(true);

const fetchBranches = async () => {
    loading.value = true;
    try {
        const res = await branchService.filtered({
            per_page: Number(route.query.per_page ?? 9),
            city: String(route.query.city ?? ""),
            lat: String(route.query.lat ?? ""),
            long: String(route.query.long ?? ""),
            name: String(route.query.name ?? ""),
            plan_name: String(route.query.plan_name ?? ""),
            sort: String(route.query.sort ?? "recommended"),
        });
        branches.value = res?.data ?? [];
    } catch (err) {
        console.error(err);
        branches.value = [];
    } finally {
        loading.value = false;
    }
};

watch(
    () => route.query,
    () => fetchBranches(),
    { immediate: true, deep: true },
);
</script>
