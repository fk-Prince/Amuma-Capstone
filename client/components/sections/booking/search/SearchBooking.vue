<template>
    <div class="w-full mx-auto px-6">
        <div v-if="props.loading" class="flex flex-col gap-4">
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
                v-for="branch in props.branches"
                :key="branch.uuid"
                :branch="branch"
                @select="handleSelect"
            />
        </div>

        <div
            v-if="!props.loading && props.branches.length === 0"
            class="text-center py-16 text-slate-400"
        >
            No branches found.
        </div>
    </div>
</template>

<script setup lang="ts">
import CardBooking from "./CardBooking.vue";
import type { BranchRetrieve } from "~/types/branch";

defineEmits(["select"]);

const props = defineProps<{
    branches: BranchRetrieve[];
    loading?: boolean;
}>();

const handleSelect = (branch: BranchRetrieve) => {
    navigateTo({
        path: `/booking/provider/${branch.uuid}`,
    });
};
</script>
