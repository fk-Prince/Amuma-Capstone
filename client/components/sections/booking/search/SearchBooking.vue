<template>
    <div class="w-full mx-auto px-6">
        <div
            v-if="!isMounted || props.loading"
            :class="
                compact
                    ? 'flex flex-col gap-4'
                    : 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4'
            "
        >
            <template v-if="compact">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="flex animate-pulse flex-col overflow-hidden rounded-2xl border border-muted-light bg-white shadow-sm md:flex-row dark:border-white/5 dark:bg-secondary dark:shadow-none"
                >
                    <div class="flex w-full shrink-0 flex-col md:w-64">
                        <div class="relative h-48 w-full flex-1 bg-muted-light dark:bg-white/10">
                            <div class="absolute left-3 top-3 h-6 w-20 rounded-full bg-white/60 dark:bg-white/10"></div>
                            <div class="absolute right-3 top-3 h-7 w-7 rounded-full bg-white/60 dark:bg-white/10"></div>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col p-6">
                        <div class="h-5 w-2/3 rounded bg-muted-light dark:bg-white/10"></div>

                        <div class="mt-2.5 flex items-start gap-1.5">
                            <div class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-muted-light dark:bg-white/10"></div>
                            <div class="flex-1 space-y-1.5">
                                <div class="h-3.5 w-full rounded bg-muted-light dark:bg-white/10"></div>
                                <div class="h-3.5 w-1/2 rounded bg-muted-light dark:bg-white/10"></div>
                            </div>
                        </div>

                        <div class="mt-2.5 flex items-center gap-1.5">
                            <div class="h-3.5 w-3.5 rounded-full bg-muted-light dark:bg-white/10"></div>
                            <div class="h-3.5 w-10 rounded bg-muted-light dark:bg-white/10"></div>
                        </div>

                        <div class="mt-4 h-3.5 w-full rounded bg-muted-light dark:bg-white/10"></div>
                        <div class="mt-2 h-3.5 w-4/5 rounded bg-muted-light dark:bg-white/10"></div>

                        <div class="mt-4 flex gap-1.5">
                            <div class="h-6 w-28 rounded-full bg-muted-light dark:bg-white/10"></div>
                            <div class="h-6 w-28 rounded-full bg-muted-light dark:bg-white/10"></div>
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-muted-light dark:bg-white/10"></div>
                            <div class="h-6 w-28 rounded-md bg-muted-light dark:bg-white/10"></div>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 flex-row items-center justify-between gap-4 border-t border-muted-light p-6 md:w-56 md:flex-col md:items-end md:justify-center md:border-l md:border-t-0 dark:border-white/10"
                    >
                        <div class="space-y-2 md:flex md:flex-col md:items-end">
                            <div class="h-3 w-16 rounded bg-muted-light dark:bg-white/10"></div>
                            <div class="h-7 w-28 rounded bg-muted-light dark:bg-white/10"></div>
                        </div>
                        <div class="h-10 w-32 rounded-lg bg-muted-light dark:bg-white/10 md:w-full"></div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-for="n in 6"
                    :key="n"
                    class="flex animate-pulse flex-col overflow-hidden rounded-2xl border border-muted-light bg-white dark:border-white/5 dark:bg-secondary"
                >
                    <div class="h-32 w-full bg-muted-light dark:bg-white/10"></div>

                    <div class="flex flex-1 flex-col p-4">
                        <div class="h-4 w-3/4 rounded bg-muted-light dark:bg-white/10"></div>
                        <div class="mt-2 h-3 w-1/2 rounded bg-muted-light dark:bg-white/10"></div>
                        <div class="mt-3 flex gap-1.5">
                            <div class="h-5 w-20 rounded-full bg-muted-light dark:bg-white/10"></div>
                            <div class="h-5 w-16 rounded-full bg-muted-light dark:bg-white/10"></div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="h-5 w-20 rounded bg-muted-light dark:bg-white/10"></div>
                            <div class="h-8 w-24 rounded-lg bg-muted-light dark:bg-white/10"></div>
                        </div>
                    </div>
                </div>
            </template>
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
                @hover="$emit('hover', $event)"
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

defineEmits(["select", "reset", "hover"]);

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