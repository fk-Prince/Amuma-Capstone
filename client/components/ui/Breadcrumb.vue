<template>
    <nav aria-label="Breadcrumb" class="flex items-center overflow-x-auto">
        <ol class="flex items-center gap-1.5 whitespace-nowrap text-sm">
            <li class="flex items-center gap-1.5">
                <NuxtLink
                    to="/"
                    class="flex items-center text-gray-400 transition-colors hover:text-primary dark:text-gray-500"
                    aria-label="Home"
                >
                    <Home class="h-4 w-4" />
                </NuxtLink>
            </li>

            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-1.5"
            >
                <ChevronRight class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />

                <span
                    v-if="!item.to || index === items.length - 1"
                    class="max-w-[16rem] truncate font-medium text-gray-700 dark:text-gray-300"
                    :aria-current="index === items.length - 1 ? 'page' : undefined"
                >
                    {{ item.label }}
                </span>

                <NuxtLink
                    v-else
                    :to="item.to"
                    class="max-w-[16rem] truncate text-gray-500 transition-colors hover:text-primary dark:text-gray-400"
                >
                    {{ item.label }}
                </NuxtLink>
            </li>
        </ol>
    </nav>
</template>

<script setup lang="ts">
import { Home, ChevronRight } from "lucide-vue-next";

export interface BreadcrumbItem {
    label: string;
    to?: string;
}

defineProps<{
    items: BreadcrumbItem[];
}>();
</script>
