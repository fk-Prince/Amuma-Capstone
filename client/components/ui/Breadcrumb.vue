<template>
    <nav aria-label="Breadcrumb" class="flex min-w-0 items-center">
        <ol class="flex min-w-0 items-center gap-1.5 text-sm">
            <li class="flex shrink-0 items-center gap-1.5">
                <NuxtLink
                    to="/"
                    class="flex items-center text-gray-400 transition-colors hover:text-primary dark:text-gray-500"
                    aria-label="Home"
                >
                    <Home class="h-4 w-4" />
                </NuxtLink>
            </li>

            <!-- Small screens collapse everything before the last two crumbs,
                 since the full trail doesn't fit and was clipping instead
                 of scrolling. -->
            <li
                v-if="items.length > 2"
                class="flex shrink-0 items-center gap-1.5 sm:hidden"
            >
                <ChevronRight class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                <span class="text-gray-400 dark:text-gray-500">…</span>
            </li>

            <li
                v-for="(item, index) in items"
                :key="index"
                class="min-w-0 items-center gap-1.5"
                :class="index >= items.length - 2 ? 'flex' : 'hidden sm:flex'"
            >
                <ChevronRight class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />

                <span
                    v-if="!item.to || index === items.length - 1"
                    class="max-w-[7rem] min-w-0 truncate font-medium text-gray-700 dark:text-gray-300 sm:max-w-none"
                    :aria-current="index === items.length - 1 ? 'page' : undefined"
                >
                    {{ item.label }}
                </span>

                <NuxtLink
                    v-else
                    :to="item.to"
                    class="max-w-[7rem] min-w-0 truncate text-gray-500 transition-colors hover:text-primary dark:text-gray-400 sm:max-w-[16rem]"
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
