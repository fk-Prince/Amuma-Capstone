<template>
    <div
        class="bg-white dark:bg-secondary rounded-2xl border border-gray-100 dark:border-white/10 shadow-sm p-8 sm:p-10 text-center"
    >
        <div
            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-500/10 text-primary-500 dark:text-primary-300"
        >
            <component :is="icon ?? HeartHandshake" class="h-7 w-7" />
        </div>

        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ title }}
        </h3>

        <p
            v-if="description"
            class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto"
        >
            {{ description }}
        </p>

        <NuxtLink
            v-if="ctaTo"
            :to="ctaTo"
            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-600 transition-colors"
        >
            {{ ctaLabel }}
        </NuxtLink>
    </div>
</template>

<script setup lang="ts">
import { HeartHandshake } from "lucide-vue-next";

defineProps<{
    icon?: any;
    title: string;
    description?: string;
    ctaLabel?: string;
    ctaTo?: string;
}>();
</script>
