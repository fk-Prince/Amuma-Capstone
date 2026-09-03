<template>
    <div class="py-6">
        <template v-if="loading">
            <div class="flex flex-col gap-3 animate-pulse">
                <div class="h-7 w-64 rounded-md bg-gray-200 dark:bg-white/10"></div>
                <div class="h-5 w-40 rounded-md bg-gray-200 dark:bg-white/10"></div>
                <div class="space-y-2 mt-2">
                    <div class="h-4 w-full rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-4 w-2/3 rounded bg-gray-200 dark:bg-white/10"></div>
                </div>
            </div>
        </template>

        <template v-else>
            <h1 class="text-[26px] font-semibold tracking-tight text-gray-900 dark:text-white">
                {{ branch?.name }}
            </h1>

            <div class="mt-2 flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                <Star class="h-4 w-4 fill-amber-500 text-amber-500 dark:text-amber-300" />
                <span class="font-semibold text-gray-900 dark:text-white">
                    {{ (branch?.averageRating ?? 0).toFixed(1) }}
                </span>
                <span class="text-gray-400 dark:text-gray-500">·</span>
                <span class="underline underline-offset-2">
                    {{ branch?.reviewCount || 0 }} review{{
                        branch?.reviewCount === 1 ? "" : "s"
                    }}
                </span>
            </div>

            <p
                v-if="branch?.description"
                class="mt-4 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
            >
                {{ branch.description }}
            </p>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Star } from "lucide-vue-next";
import type { BranchRetrieve } from "~/types/branch";

defineProps<{
    branch: BranchRetrieve | null;
    loading?: boolean;
}>();
</script>
