<template>
    <div class="w-full mx-auto px-6">
        <div v-if="!isMounted || props.loading" class="flex flex-col gap-4">
            <div
                v-for="n in 2"
                :key="n"
                class="border rounded-2xl overflow-hidden animate-pulse dark:border-white/10"
            >
                <div class="h-32 bg-gray-200 dark:bg-white/10"></div>
                <div class="p-4 space-y-3">
                    <div class="h-4 bg-gray-200 rounded w-3/4 dark:bg-white/10"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/2 dark:bg-white/10"></div>
                    <div class="flex justify-between mt-4">
                        <div class="h-6 w-16 bg-gray-200 rounded-full dark:bg-white/10"></div>
                        <div class="h-3 w-20 bg-gray-200 rounded dark:bg-white/10"></div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            :class="
                compact
                    ? 'flex flex-col gap-4'
                    : 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4'
            "
        >
            <CardBooking
                :variant="compact ? 2 : 3"
                v-for="branch in props.branches"
                :key="branch.uuid"
                :branch="branch"
                @select="handleSelect"
            />
        </div>

        <div
            v-if="isMounted && !props.loading && props.branches.length === 0"
            class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-16 text-center dark:border-white/10 dark:bg-white/[0.02]"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-500 dark:bg-primary-500/10 dark:text-primary-300"
            >
                <SearchX class="h-6 w-6" />
            </div>

            <div>
                <p class="text-base font-semibold text-secondary dark:text-white">
                    No providers found
                </p>
                <p class="mt-1 max-w-sm text-sm text-muted dark:text-gray-400">
                    We couldn't find any providers matching your current
                    location and care type. Try a different location, or
                    reset your filters to see everyone nearby.
                </p>
            </div>

            <button
                type="button"
                class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-600"
                @click="$emit('reset')"
            >
                <RotateCcw class="h-3.5 w-3.5" />
                Reset filters
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import CardBooking from "./CardBooking.vue";
import type { BranchRetrieve } from "~/types/branch";
import { ref, onMounted } from "vue";
import { SearchX, RotateCcw } from "lucide-vue-next";

defineEmits(["select", "reset"]);

const props = defineProps<{
    branches: BranchRetrieve[];
    loading?: boolean;
    compact?: boolean;
}>();

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const handleSelect = (branch: BranchRetrieve) => {
    navigateTo({
        path: `/booking/provider/${branch.uuid}`,
    });
};
</script>